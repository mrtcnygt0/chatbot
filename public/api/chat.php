<?php
/**
 * Chat API Endpoint - Send Message
 */

require_once __DIR__ . '/../../app/controllers/ChatController.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $_POST = array_merge($_POST, $input);
}

$controller = new ChatController();
$controller->sendMessage();
