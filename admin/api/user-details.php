<?php
/**
 * Admin User Details API
 */

require_once __DIR__ . '/../../app/controllers/AdminController.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$controller = new AdminController();
$controller->getUserDetails();
