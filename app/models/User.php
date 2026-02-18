<?php
/**
 * User Model
 */

require_once __DIR__ . '/../../config/database.php';

class User {
    private $db;
    private $table = 'users';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create new user
     */
    public function create($email, $passwordHash, $role = 'user') {
        $config = Database::getInstance()->getConfig();
        
        $sql = "INSERT INTO {$this->table} 
                (email, password_hash, role, quota_tokens, daily_limit, monthly_limit) 
                VALUES (:email, :password_hash, :role, :quota_tokens, :daily_limit, :monthly_limit)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':role' => $role,
            ':quota_tokens' => $config['budget']['default_user_quota'],
            ':daily_limit' => $config['budget']['default_daily_limit'],
            ':monthly_limit' => $config['budget']['default_monthly_limit']
        ]);
        
        return $this->db->lastInsertId();
    }

    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Find user by ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Get all users (for admin)
     */
    public function getAll($limit = 100, $offset = 0) {
        $sql = "SELECT id, email, role, quota_tokens, daily_limit, monthly_limit, 
                tokens_used_today, tokens_used_month, is_active, created_at 
                FROM {$this->table} 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Count total users
     */
    public function count() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmt = $this->db->query($sql);
        return $stmt->fetch()['total'];
    }

    /**
     * Update user quota
     */
    public function updateQuota($userId, $quotaTokens, $dailyLimit, $monthlyLimit) {
        $sql = "UPDATE {$this->table} 
                SET quota_tokens = :quota_tokens, 
                    daily_limit = :daily_limit, 
                    monthly_limit = :monthly_limit 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $userId,
            ':quota_tokens' => $quotaTokens,
            ':daily_limit' => $dailyLimit,
            ':monthly_limit' => $monthlyLimit
        ]);
    }

    /**
     * Deduct tokens from user
     */
    public function deductTokens($userId, $tokens) {
        // Check if we need to reset daily/monthly counters
        $this->resetCountersIfNeeded($userId);
        
        $sql = "UPDATE {$this->table} 
                SET quota_tokens = quota_tokens - :tokens,
                    tokens_used_today = tokens_used_today + :tokens,
                    tokens_used_month = tokens_used_month + :tokens
                WHERE id = :id AND quota_tokens >= :tokens";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $userId,
            ':tokens' => $tokens
        ]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if user has enough tokens
     */
    public function hasEnoughTokens($userId, $tokens) {
        $this->resetCountersIfNeeded($userId);
        
        $sql = "SELECT quota_tokens, daily_limit, monthly_limit, 
                tokens_used_today, tokens_used_month 
                FROM {$this->table} 
                WHERE id = :id AND is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // Check all limits
        if ($user['quota_tokens'] < $tokens) {
            return false;
        }
        
        if ($user['tokens_used_today'] + $tokens > $user['daily_limit']) {
            return false;
        }
        
        if ($user['tokens_used_month'] + $tokens > $user['monthly_limit']) {
            return false;
        }
        
        return true;
    }

    /**
     * Reset daily/monthly counters if needed
     */
    private function resetCountersIfNeeded($userId) {
        $user = $this->findById($userId);
        if (!$user) return;
        
        $today = date('Y-m-d');
        $lastReset = $user['last_reset_date'];
        
        // Reset daily counter
        if ($lastReset !== $today) {
            $sql = "UPDATE {$this->table} 
                    SET tokens_used_today = 0, 
                        last_reset_date = :today";
            
            // Reset monthly counter if it's a new month
            if ($lastReset && date('Y-m', strtotime($lastReset)) !== date('Y-m')) {
                $sql .= ", tokens_used_month = 0";
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $userId,
                ':today' => $today
            ]);
        }
    }

    /**
     * Update user status
     */
    public function updateStatus($userId, $isActive) {
        $sql = "UPDATE {$this->table} SET is_active = :is_active WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $userId,
            ':is_active' => $isActive ? 1 : 0
        ]);
    }

    /**
     * Delete user
     */
    public function delete($userId) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $userId]);
    }

    /**
     * Get user statistics
     */
    public function getStatistics($userId) {
        $sql = "SELECT 
                u.quota_tokens,
                u.daily_limit,
                u.monthly_limit,
                u.tokens_used_today,
                u.tokens_used_month,
                COUNT(DISTINCT c.id) as total_conversations,
                COUNT(DISTINCT m.id) as total_messages,
                COALESCE(SUM(al.total_tokens), 0) as total_tokens_used,
                COALESCE(SUM(al.cost), 0) as total_cost
                FROM {$this->table} u
                LEFT JOIN conversations c ON u.id = c.user_id
                LEFT JOIN messages m ON c.id = m.conversation_id
                LEFT JOIN api_logs al ON u.id = al.user_id
                WHERE u.id = :id
                GROUP BY u.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch();
    }
}
