<?php
require_once __DIR__ . '/../controllers/AdminController.php';

$controller = new AdminController();
$flag = false;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        if ($_GET['action'] === 'login') $controller->login();
        /* elseif ($_GET['action'] === 'get-all-users') {
            AuthMiddleware::requireAuth(true);
            $controller->getAllUsers();
        } elseif ($_GET['action'] === 'block-user') {
            AuthMiddleware::requireAuth(true);
            $controller->blockUser();
        } elseif ($_GET['action'] === 'unblock-user') {
            AuthMiddleware::requireAuth(true);
            $controller->unblockUser();
        } elseif ($_GET['action'] === 'delete-user') {
            AuthMiddleware::requireAuth(true);
            $controller->deleteUser();
        } */
        else $flag = true;
        break;

    default:
        $flag = true;
}

if ($flag) ApiResponse::error("Phương thức không hỗ trợ !", 405);