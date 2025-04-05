<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../helpers/ExceptionHandler.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

ExceptionHandler::handle();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$uri = $_GET['route'] ?? '';
$resource = explode('?', $uri)[0];

switch (true) {
    case str_starts_with($resource, 'users'):
        require_once __DIR__ . '/users.php';
        break;
    case str_starts_with($resource, 'admin'):
        require_once __DIR__ . '/admin.php';
        break;
    default:
        http_response_code(404);
        echo json_encode(['message' => 'Không tìm thấy route']);
        break;
}
