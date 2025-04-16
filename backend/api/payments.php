<?php
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../helpers/AuthMiddleware.php';

$controller = new PaymentController();
$flag = false;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($_GET['action'] == 'get-payment')
            $controller->get_payment($_GET);
        elseif ($_GET['action'] == 'result')
            $controller->result_payment($_GET);
        else $flag = true;
        break;

    /* case 'POST':
        if ($_GET['action'] == 'create') {
            $data = AuthMiddleware::requireAuth();
            $controller->create_payment();
        }
        else $flag = true;
        break; */

    /* case 'PUT':
        if ($_GET['action'] == 'update') {
            AuthMiddleware::requireAuth(true);
            $controller->update();
        }
        else $flag = true;
        break; */

    case 'PATCH':
        if ($_GET['action'] == 'update') {
            AuthMiddleware::requireAuth(true);
            $controller->update_payment();
        }
        else $flag = true;
        break;
        
    default:
        $flag = true;
}

if($flag) ApiResponse::error("Phương thức không hỗ trợ !", 405);
