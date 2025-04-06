<?php
require_once __DIR__ . '/../controllers/AdminController.php';

$controller = new AdminController();
$flag = false;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        if ($_GET['action'] === 'login')
            $controller->login();
        else $flag = true;
        break;

    case 'GET':
        if ($_GET['action'] === 'get-all-users') {
            AuthMiddleware::requireAuth(true);
            $controller->getAllUsers();
        }
        else $flag = true;
        break;
    default:
        $flag = true;
}

if ($flag) ApiResponse::error("Phương thức không hỗ trợ !", 405);