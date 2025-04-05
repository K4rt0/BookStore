<?php
require_once __DIR__ . '/../controllers/UserController.php';

$controller = new UserController();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        if ($_GET['action'] == 'register')
            $controller->register();
        elseif ($_GET['action'] == 'login')
            $controller->login();
        elseif ($_GET['action'] == 'logout')
            $controller->logout();
        elseif ($_GET['action'] == 'refresh-token')
            $controller->refresh_token();
        else
            ApiResponse::error("Phương thức không hỗ trợ", 405);
        break;

    default:
        ApiResponse::error("Phương thức không hỗ trợ", 405);
}
