<?php
/**
 * Messages API Endpoint
 */

require_once __DIR__ . '/../../app/controllers/ChatController.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$controller = new ChatController();
$controller->getMessages();
