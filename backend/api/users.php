<?php
require_once __DIR__ . '/../controllers/UserController.php';

$controller = new UserController();
$flag = false;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($_GET['action'] == 'profile')
            $controller->profile();
        else $flag = true;
        break;

    case 'POST':
        if ($_GET['action'] == 'register')
            $controller->register();
        elseif ($_GET['action'] == 'login')
            $controller->login();
        elseif ($_GET['action'] == 'logout')
            $controller->logout();
        elseif ($_GET['action'] == 'refresh-token')
            $controller->refresh_token();
        else $flag = true;
        break;

    case 'PUT':
        if ($_GET['action'] == 'update-profile')
            $controller->update_profile();
        else $flag = true;
        break;

    case 'PATCH':
        if ($_GET['action'] == 'update-password')
            $controller->update_password();
        else $flag = true;
        break;
        
    default:
        $flag = true;
}

if($flag) ApiResponse::error("Phương thức không hỗ trợ !", 405);
