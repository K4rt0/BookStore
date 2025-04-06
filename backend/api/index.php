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
    case str_starts_with($resource, 'admin'):
        require_once __DIR__ . '/admin.php';
        break;
    case str_starts_with($resource, 'imgur'):
        require_once __DIR__ . '/imgur.php';
        break;
    case str_starts_with($resource, 'user'):
        require_once __DIR__ . '/users.php';
        break;
    case str_starts_with($resource, 'category'):
        require_once __DIR__ . '/categories.php';
        break;
    case str_starts_with($resource, 'book'):
        require_once __DIR__ . '/books.php';
        break;
    default:
        ApiResponse::error("Không tìm thấy route !", 404);
        break;
}
