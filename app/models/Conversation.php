<?php
/**
 * Conversation Model
 */

require_once __DIR__ . '/../../config/database.php';

class Conversation {
    private $db;
    private $table = 'conversations';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create new conversation
     */
    public function create($userId, $title = 'New Chat') {
        $sql = "INSERT INTO {$this->table} (user_id, title) VALUES (:user_id, :title)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':title' => $title
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Find conversation by ID
     */
    public function findById($id, $userId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        
        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
        }
        
        $sql .= " LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $params = [':id' => $id];
        
        if ($userId !== null) {
            $params[':user_id'] = $userId;
        }
        
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Get user conversations
     */
    public function getUserConversations($userId, $limit = 50, $offset = 0) {
        $sql = "SELECT c.*, 
                COUNT(m.id) as message_count,
                MAX(m.created_at) as last_message_at
                FROM {$this->table} c
                LEFT JOIN messages m ON c.id = m.conversation_id
                WHERE c.user_id = :user_id
                GROUP BY c.id
                ORDER BY c.updated_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Update conversation title
     */
    public function updateTitle($id, $userId, $title) {
        $sql = "UPDATE {$this->table} 
                SET title = :title, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
            ':title' => $title
        ]);
    }

    /**
     * Delete conversation
     */
    public function delete($id, $userId) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId
        ]);
    }

    /**
     * Update conversation timestamp
     */
    public function touch($id) {
        $sql = "UPDATE {$this->table} SET updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Count user conversations
     */
    public function countUserConversations($userId) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch()['total'];
    }

    /**
     * Get all conversations (admin)
     */
    public function getAll($limit = 100, $offset = 0) {
        $sql = "SELECT c.*, u.email as user_email,
                COUNT(m.id) as message_count
                FROM {$this->table} c
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN messages m ON c.id = m.conversation_id
                GROUP BY c.id
                ORDER BY c.updated_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
