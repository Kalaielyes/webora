<?php
/**
 * API Handler for Cheque Module - webora Integration
 * RESTful endpoints for all cheque operations
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../models/config.php';
require_once __DIR__ . '/../models/Session.php';
require_once __DIR__ . '/../controller/ChequierRouter.php';
require_once __DIR__ . '/../controller/ChequierController.php';
require_once __DIR__ . '/../controller/ChequeController.php';
require_once __DIR__ . '/../controller/BiometricFaceAuthController.php';

// Verify admin session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];
$path = str_replace('/webora-main/api/chequier-api.php', '', $request_uri);
$path = ltrim($path, '/');

// Route the request
$result = ChequierRouter::route($path);

if (!$result) {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found', 'path' => $path]);
}

