<?php
/**
 * Admin Clear Logs API
 */

require_once __DIR__ . '/../../app/middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../../app/middlewares/CsrfMiddleware.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/helpers/Response.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    AuthMiddleware::requireAdmin();
    CsrfMiddleware::verify();
    
    $db = Database::getInstance()->getConnection();
    
    // Clear old API logs (keep last 30 days)
    $sql = "DELETE FROM api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $db->exec($sql);
    
    Response::success('Logs cleared successfully');
    
} catch (Exception $e) {
    error_log('Clear logs error: ' . $e->getMessage());
    Response::error('Failed to clear logs');
}
