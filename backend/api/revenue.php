<?php
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../helpers/AuthMiddleware.php';

$controller = new PaymentController();
$flag = false;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($_GET['action'] == 'result')
            $controller->result_payment($_GET);
        else $flag = true;
        break;
    default:
        $flag = true;
}

if($flag) ApiResponse::error("Phương thức không hỗ trợ !", 405);