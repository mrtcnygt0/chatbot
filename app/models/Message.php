<?php
/**
 * Message Model
 */

require_once __DIR__ . '/../../config/database.php';

class Message {
    private $db;
    private $table = 'messages';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create new message
     */
    public function create($conversationId, $role, $content, $tokens = 0) {
        $sql = "INSERT INTO {$this->table} (conversation_id, role, content, tokens) 
                VALUES (:conversation_id, :role, :content, :tokens)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':conversation_id' => $conversationId,
            ':role' => $role,
            ':content' => $content,
            ':tokens' => $tokens
        ]);
        
        return $this->db->lastInsertId();
    }

    /**
     * Get conversation messages
     */
    public function getConversationMessages($conversationId, $limit = 100) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE conversation_id = :conversation_id 
                ORDER BY created_at ASC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':conversation_id', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get last N messages for context
     */
    public function getLastMessages($conversationId, $count = 10) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE conversation_id = :conversation_id 
                ORDER BY created_at DESC 
                LIMIT :count";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':conversation_id', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue(':count', $count, PDO::PARAM_INT);
        $stmt->execute();
        
        // Reverse to get chronological order
        return array_reverse($stmt->fetchAll());
    }

    /**
     * Count conversation messages
     */
    public function countConversationMessages($conversationId) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE conversation_id = :conversation_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':conversation_id' => $conversationId]);
        return $stmt->fetch()['total'];
    }

    /**
     * Delete message
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Delete all conversation messages
     */
    public function deleteConversationMessages($conversationId) {
        $sql = "DELETE FROM {$this->table} WHERE conversation_id = :conversation_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':conversation_id' => $conversationId]);
    }

    /**
     * Get total tokens used in conversation
     */
    public function getConversationTokens($conversationId) {
        $sql = "SELECT SUM(tokens) as total_tokens FROM {$this->table} 
                WHERE conversation_id = :conversation_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':conversation_id' => $conversationId]);
        $result = $stmt->fetch();
        return $result['total_tokens'] ?? 0;
    }
}
