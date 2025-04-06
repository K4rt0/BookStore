<?php
require_once __DIR__ . '/../controllers/BookController.php';
require_once __DIR__ . '/../helpers/AuthMiddleware.php';

$controller = new BookController();
$flag = false;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($_GET['action'] == 'get-book')
            $controller->get_book($_GET);
        elseif ($_GET['action'] == 'get-all-books')
            $controller->get_all_books();
        elseif ($_GET['action'] == 'get-all-books-pagination')
            $controller->get_all_books_pagination($_GET);
        else $flag = true;
        break;

    case 'POST':
        if ($_GET['action'] == 'create')
            $controller->create();
        elseif ($_GET['action'] == 'update') {
            AuthMiddleware::requireAuth(true);
            $controller->update();
        }
        else $flag = true;
        break;

    case 'PATCH':
        if ($_GET['action'] == 'undo-delete') {
            AuthMiddleware::requireAuth(true);
            $controller->update();
        }
        else $flag = true;
        break;

    case 'DELETE':
        if ($_GET['action'] == 'delete') {
            AuthMiddleware::requireAuth(true);
            $controller->delete($_GET);
        }
        else $flag = true;
        break;
    

        
    default:
        $flag = true;
}

if($flag) ApiResponse::error("Phương thức không hỗ trợ !", 405);
