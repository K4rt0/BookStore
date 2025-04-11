<?php
require_once __DIR__ . '/../controllers/OrderController.php';

$controller = new OrderController();
$flag = false;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($_GET['action'] == 'get-order')
        {
            $data = AuthMiddleware::requireAuth();
            $controller->get_order($_GET);
        }
        else $flag = true;
        break;

    case 'POST':
        if ($_GET['action'] == 'create') {
            $data = AuthMiddleware::requireAuth();
            $controller->create();
        }
        else $flag = true;
        break;

    /* case 'PUT':
        if ($_GET['action'] == 'update') {
            AuthMiddleware::requireAuth(true);
            $controller->update();
        }
        else $flag = true;
        break; */

    /* case 'PATCH':
        if ($_GET['action'] == 'update-active') {
            AuthMiddleware::requireAuth(true);
            $controller->category_active($_GET);
        }
        else $flag = true;
        break; */
        
    default:
        $flag = true;
}

if($flag) ApiResponse::error("Phương thức không hỗ trợ !", 405);
