<?php
/**
 * API Log Model
 */

require_once __DIR__ . '/../../config/database.php';

class ApiLog {
    private $db;
    private $table = 'api_logs';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create new API log
     */
    public function create($userId, $conversationId, $inputTokens, $outputTokens, $cost, $status = 'success', $errorMessage = null) {
        $sql = "INSERT INTO {$this->table} 
                (user_id, conversation_id, model, input_tokens, output_tokens, total_tokens, cost, status, error_message) 
                VALUES (:user_id, :conversation_id, :model, :input_tokens, :output_tokens, :total_tokens, :cost, :status, :error_message)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':conversation_id' => $conversationId,
            ':model' => 'gpt-4o-mini',
            ':input_tokens' => $inputTokens,
            ':output_tokens' => $outputTokens,
            ':total_tokens' => $inputTokens + $outputTokens,
            ':cost' => $cost,
            ':status' => $status,
            ':error_message' => $errorMessage
        ]);
        
        return $this->db->lastInsertId();
    }

    /**
     * Get user logs
     */
    public function getUserLogs($userId, $limit = 100, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get all logs (admin)
     */
    public function getAll($limit = 100, $offset = 0) {
        $sql = "SELECT al.*, u.email as user_email 
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get total cost
     */
    public function getTotalCost($userId = null) {
        $sql = "SELECT SUM(cost) as total_cost FROM {$this->table}";
        
        if ($userId !== null) {
            $sql .= " WHERE user_id = :user_id";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if ($userId !== null) {
            $stmt->execute([':user_id' => $userId]);
        } else {
            $stmt->execute();
        }
        
        $result = $stmt->fetch();
        return $result['total_cost'] ?? 0;
    }

    /**
     * Get usage statistics
     */
    public function getStatistics($userId = null, $days = 30) {
        $sql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as requests,
                SUM(input_tokens) as input_tokens,
                SUM(output_tokens) as output_tokens,
                SUM(total_tokens) as total_tokens,
                SUM(cost) as cost
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
        }
        
        $sql .= " GROUP BY DATE(created_at) ORDER BY date DESC";
        
        $stmt = $this->db->prepare($sql);
        $params = [':days' => $days];
        
        if ($userId !== null) {
            $params[':user_id'] = $userId;
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get top users by cost
     */
    public function getTopUsers($limit = 10) {
        $sql = "SELECT 
                u.id,
                u.email,
                COUNT(al.id) as total_requests,
                SUM(al.total_tokens) as total_tokens,
                SUM(al.cost) as total_cost
                FROM users u
                LEFT JOIN {$this->table} al ON u.id = al.user_id
                GROUP BY u.id
                ORDER BY total_cost DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get daily usage for chart
     */
    public function getDailyUsage($days = 30) {
        $sql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as requests,
                SUM(cost) as cost
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':days' => $days]);
        return $stmt->fetchAll();
    }
}
