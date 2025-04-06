<?php
require_once __DIR__ . '/../controllers/ImgurController.php';

$controller = new ImgurController();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        if ($_GET['action'] === 'upload') {
            $controller->upload();
        } elseif ($_GET['action'] === 'delete') {
            $controller->delete();
        } else {
            ApiResponse::error("Hành động không hợp lệ", 400);
        }
        break;
    default:
        ApiResponse::error("Phương thức không hỗ trợ", 405);
}
