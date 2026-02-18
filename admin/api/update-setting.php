<?php
/**
 * Admin Update Setting API
 */

require_once __DIR__ . '/../../app/controllers/AdminController.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $_POST = array_merge($_POST, $input);
}

$controller = new AdminController();
$controller->updateSetting();
