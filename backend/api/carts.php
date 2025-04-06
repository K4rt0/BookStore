<?php
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../helpers/AuthMiddleware.php';

$controller = new CartController();
$flag = false;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($_GET['action'] == 'get-cart') {
            $data = AuthMiddleware::requireAuth();
            $controller->get_cart($data->sub);
        }
        break;

    case 'POST':
        if ($_GET['action'] == 'add-to-cart') {
            AuthMiddleware::requireAuth();
            $controller->add_to_cart();
        }
        else $flag = true;
        break;

    case 'PATCH':
        if ($_GET['action'] == 'update-cart') {
            $data = AuthMiddleware::requireAuth();
            $controller->update_cart($data->sub);
        }
        else $flag = true;
        break;

    case 'DELETE':
        if ($_GET['action'] == 'delete') {
            $data = AuthMiddleware::requireAuth();
            $controller->delete($_GET, $data->sub);
        }
        else $flag = true;
        break;
        
    default:
        $flag = true;
}

if($flag) ApiResponse::error("Phương thức không hỗ trợ !", 405);
