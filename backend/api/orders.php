<?php
require_once __DIR__ . '/../controllers/OrderController.php';

$controller = new OrderController();
$flag = false;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($_GET['action'] == 'get-order')
            $controller->get_order($_GET);
        elseif ($_GET['action'] == 'get-all-orders') {
            $data = AuthMiddleware::requireAuth(true);
            $controller->get_all_orders();
        }
        elseif ($_GET['action'] == 'get-all-orders-pagination') {
            $data = AuthMiddleware::requireAuth(true);
            $controller->get_all_orders_pagination($_GET);
        }
        elseif ($_GET['action'] == 'get-all-my-orders') {
            $data = AuthMiddleware::requireAuth();
            $controller->get_all_my_orders($data->sub);
        }
        elseif ($_GET['action'] == 'get-all-my-orders-pagination') {
            $data = AuthMiddleware::requireAuth();
            $controller->get_all_my_orders_pagination($_GET, $data->sub);
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

    case 'PATCH':
        if ($_GET['action'] == 'update-status') {
            AuthMiddleware::requireAuth(true);
            $controller->update_status($_GET);
        }
        elseif ($_GET['action'] == 'cancel-order') {
            $data = AuthMiddleware::requireAuth();
            $controller->cancel_order($data->sub, $_GET);
        }
        else $flag = true;
        break;
        
    default:
        $flag = true;
}

if($flag) ApiResponse::error("Phương thức không hỗ trợ !", 405);
