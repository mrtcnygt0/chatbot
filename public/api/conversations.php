<?php
/**
 * Conversations API Endpoint
 */

require_once __DIR__ . '/../../app/controllers/ChatController.php';

header('Content-Type: application/json');

// Get JSON input for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $_POST = array_merge($_POST, $input);
    }
}

$controller = new ChatController();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $controller->getConversations();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $controller->createConversation();
            break;
        case 'rename':
            $controller->renameConversation();
            break;
        case 'delete':
            $controller->deleteConversation();
            break;
        default:
            Response::error('Invalid action');
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
