<?php
/**
 * Authentication Middleware
 */

require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Url.php';
require_once __DIR__ . '/../models/User.php';

class AuthMiddleware {
    
    /**
     * Initialize secure session
     */
    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', 1);
            
            session_name('CHATBOT_SESSION');
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['created_at'])) {
                $_SESSION['created_at'] = time();
            } elseif (time() - $_SESSION['created_at'] > 1800) { // 30 minutes
                session_regenerate_id(true);
                $_SESSION['created_at'] = time();
            }
        }
    }

    /**
     * Check if user is authenticated
     */
    public static function check() {
        self::initSession();
        return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
    }

    /**
     * Require authentication
     */
    public static function require() {
        if (!self::check()) {
            if (self::isAjaxRequest()) {
                Response::error('Unauthorized', null, 401);
            } else {
                Response::redirect('/login.php');
            }
        }
    }

    /**
     * Require admin role
     */
    public static function requireAdmin() {
        self::require();
        
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            if (self::isAjaxRequest()) {
                Response::error('Forbidden - Admin access required', null, 403);
            } else {
                Response::forbidden('Admin access required');
            }
        }
    }

    /**
     * Login user
     */
    public static function login($userId, $email, $role) {
        self::initSession();
        
        // Regenerate session ID on login
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Store session in database
        self::storeSession($userId);
    }

    /**
     * Logout user
     */
    public static function logout() {
        self::initSession();
        
        // Remove session from database
        if (isset($_SESSION['user_id'])) {
            self::removeSession($_SESSION['user_id']);
        }
        
        // Clear session
        $_SESSION = [];
        
        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }

    /**
     * Get current user ID
     */
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user
     */
    public static function getUser() {
        if (!self::check()) {
            return null;
        }
        
        $userModel = new User();
        return $userModel->findById(self::getUserId());
    }

    /**
     * Check if admin
     */
    public static function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    /**
     * Check if AJAX request
     */
    private static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Store session in database
     */
    private static function storeSession($userId) {
        try {
            $db = Database::getInstance()->getConnection();
            $sessionId = session_id();
            $hashedSessionId = Security::hashSessionId($sessionId);
            $ip = Security::getClientIp();
            $userAgent = Security::getUserAgent();
            
            $sql = "INSERT INTO sessions (id, user_id, ip_address, user_agent) 
                    VALUES (:id, :user_id, :ip_address, :user_agent)
                    ON DUPLICATE KEY UPDATE 
                    ip_address = :ip_address, 
                    user_agent = :user_agent, 
                    last_activity = CURRENT_TIMESTAMP";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':id' => $hashedSessionId,
                ':user_id' => $userId,
                ':ip_address' => $ip,
                ':user_agent' => substr($userAgent, 0, 255)
            ]);
        } catch (Exception $e) {
            error_log('Session store failed: ' . $e->getMessage());
        }
    }

    /**
     * Remove session from database
     */
    private static function removeSession($userId) {
        try {
            $db = Database::getInstance()->getConnection();
            $sessionId = session_id();
            $hashedSessionId = Security::hashSessionId($sessionId);
            
            $sql = "DELETE FROM sessions WHERE id = :id AND user_id = :user_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':id' => $hashedSessionId,
                ':user_id' => $userId
            ]);
        } catch (Exception $e) {
            error_log('Session removal failed: ' . $e->getMessage());
        }
    }

    /**
     * Clean old sessions
     */
    public static function cleanOldSessions() {
        try {
            $db = Database::getInstance()->getConnection();
            $config = Database::getInstance()->getConfig();
            $lifetime = $config['security']['session_lifetime'] ?? 7200;
            
            $sql = "DELETE FROM sessions 
                    WHERE last_activity < DATE_SUB(NOW(), INTERVAL :lifetime SECOND)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([':lifetime' => $lifetime]);
        } catch (Exception $e) {
            error_log('Session cleanup failed: ' . $e->getMessage());
        }
    }
}
