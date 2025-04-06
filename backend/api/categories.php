<?php
require_once __DIR__ . '/../controllers/CategoryController.php';

$controller = new CategoryController();
$flag = false;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($_GET['action'] == 'profile') $controller->profile();
        elseif ($_GET['action'] == 'get-all-users') {
            AuthMiddleware::requireAuth(true);
            $controller->get_all_users();
        }
        elseif ($_GET['action'] == 'get-all-users-pagination') {
            AuthMiddleware::requireAuth(true);
            $controller->get_all_users_pagination($_GET);
        }
        else $flag = true;
        break;

    case 'POST':
        if ($_GET['action'] == 'create')
            $controller->create();
        else $flag = true;
        break;

    case 'PUT':
        if ($_GET['action'] == 'update') {
            AuthMiddleware::requireAuth(true);
            $controller->update();
        }
        else $flag = true;
        break;
        
    default:
        $flag = true;
}

if($flag) ApiResponse::error("Phương thức không hỗ trợ !", 405);
