<?php
require_once __DIR__ . '/../controllers/AdminController.php';

$controller = new AdminController();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        if ($_GET['action'] === 'login')
            $controller->login();
        else
            ApiResponse::error("Phương thức không hỗ trợ", 405);
        break;
    default:
        ApiResponse::error("Phương thức không hỗ trợ", 405);
}