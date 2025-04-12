<?php
require_once __DIR__ . '/../controllers/ReviewController.php';
require_once __DIR__ . '/../helpers/AuthMiddleware.php';

$controller = new ReviewController();
$flag = false;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        if ($_GET['action'] == 'create') {
            $data = AuthMiddleware::requireAuth();
            $controller->create_review($data->sub);
        }
        break;

    default:
        $flag = true;
        break;
}

if($flag) ApiResponse::error("Phương thức không hỗ trợ !", 405);