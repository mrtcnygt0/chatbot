<?php
/**
 * Rate Limiting Middleware
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';

class RateLimitMiddleware {
    
    /**
     * Check rate limit
     */
    public static function check($identifier, $type = 'api', $maxAttempts = 10, $windowMinutes = 1) {
        self::cleanup();
        
        $db = Database::getInstance()->getConnection();
        $resetAt = date('Y-m-d H:i:s', strtotime("+{$windowMinutes} minutes"));
        
        // Get or create rate limit record
        $sql = "SELECT * FROM rate_limits 
                WHERE identifier = :identifier AND type = :type 
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':identifier' => $identifier,
            ':type' => $type
        ]);
        
        $record = $stmt->fetch();
        
        if (!$record) {
            // Create new record
            $sql = "INSERT INTO rate_limits (identifier, type, attempts, reset_at) 
                    VALUES (:identifier, :type, 1, :reset_at)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':identifier' => $identifier,
                ':type' => $type,
                ':reset_at' => $resetAt
            ]);
            
            return true;
        }
        
        // Check if reset time has passed
        if (strtotime($record['reset_at']) <= time()) {
            // Reset counter
            $sql = "UPDATE rate_limits 
                    SET attempts = 1, reset_at = :reset_at 
                    WHERE identifier = :identifier AND type = :type";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':identifier' => $identifier,
                ':type' => $type,
                ':reset_at' => $resetAt
            ]);
            
            return true;
        }
        
        // Check if limit exceeded
        if ($record['attempts'] >= $maxAttempts) {
            $secondsRemaining = strtotime($record['reset_at']) - time();
            Response::error(
                'Rate limit exceeded. Please try again later.',
                ['retry_after' => $secondsRemaining],
                429
            );
        }
        
        // Increment attempts
        $sql = "UPDATE rate_limits 
                SET attempts = attempts + 1 
                WHERE identifier = :identifier AND type = :type";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':identifier' => $identifier,
            ':type' => $type
        ]);
        
        return true;
    }

    /**
     * Check login rate limit
     */
    public static function checkLogin($email) {
        $config = Database::getInstance()->getConfig();
        $maxAttempts = $config['security']['max_login_attempts'] ?? 5;
        $lockoutDuration = ($config['security']['lockout_duration'] ?? 900) / 60; // Convert to minutes
        
        return self::check($email, 'login', $maxAttempts, $lockoutDuration);
    }

    /**
     * Check chat rate limit
     */
    public static function checkChat($userId) {
        $config = Database::getInstance()->getConfig();
        $maxRequests = $config['rate_limit']['chat_requests_per_minute'] ?? 10;
        
        return self::check("user_{$userId}", 'chat', $maxRequests, 1);
    }

    /**
     * Check API rate limit
     */
    public static function checkApi($userId) {
        $config = Database::getInstance()->getConfig();
        $maxRequests = $config['rate_limit']['api_requests_per_hour'] ?? 100;
        
        return self::check("user_{$userId}", 'api', $maxRequests, 60);
    }

    /**
     * Reset rate limit
     */
    public static function reset($identifier, $type) {
        $db = Database::getInstance()->getConnection();
        
        $sql = "DELETE FROM rate_limits WHERE identifier = :identifier AND type = :type";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':identifier' => $identifier,
            ':type' => $type
        ]);
    }

    /**
     * Cleanup old records
     */
    private static function cleanup() {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "DELETE FROM rate_limits WHERE reset_at < NOW()";
            $db->exec($sql);
        } catch (Exception $e) {
            error_log('Rate limit cleanup failed: ' . $e->getMessage());
        }
    }
}
