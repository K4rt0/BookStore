<?php
require_once __DIR__ . '/../controllers/BookController.php';

$controller = new BookController();
$flag = false;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($_GET['action'] == 'get-all-books')
            $controller->get_all_books();
        elseif ($_GET['action'] == 'get-all-books-pagination') {
            AuthMiddleware::requireAuth(true);
            $controller->get_all_books_pagination($_GET);
        }
        else $flag = true;
        break;

    case 'POST':
        if ($_GET['action'] == 'create')
            $controller->create();
        else $flag = true;
        break;

    case 'PUT':
        if ($_GET['action'] == 'update') {
            AuthMiddleware::requireAuth(true);
            $controller->update();
        }
        else $flag = true;
        break;

    case 'PATCH':
        if ($_GET['action'] == 'update-active') {
            AuthMiddleware::requireAuth(true);
            $controller->category_active($_GET);
        }
        else $flag = true;
        break;
        
    default:
        $flag = true;
}

if($flag) ApiResponse::error("Phương thức không hỗ trợ !", 405);
