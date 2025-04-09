<?php
// index.php
session_start([
    'cookie_path' => '/',
    'cookie_domain' => '',
]);
require_once __DIR__ . '/includes/env-loader.php';

// Debug: Log session info
error_log("Session ID: " . session_id());
error_log("Session save path: " . session_save_path());

// Get the requested URL path (with fallback if .htaccess isn't working)
if (isset($_GET['url'])) {
    $url = rtrim($_GET['url'], '/');
} else {
    // Fallback: Parse the URL directly from REQUEST_URI
    $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $url = ltrim($url, '/');
    $url = rtrim($url, '/');
}
$url = filter_var($url, FILTER_SANITIZE_URL);
$url_parts = explode('/', $url);

// Debug: Log the URL and route
error_log("Requested URL: " . $url);
error_log("URL Parts: " . print_r($url_parts, true));

$routes = [
    '' => 'pages/home.php',
    'index' => 'pages/home.php',
    'login' => 'pages/login.php',
    'logout' => 'pages/logout.php',
    'register' => 'pages/register.php',
    'cart' => 'pages/cart.php',
    'checkout' => 'pages/checkout.php',
    'book-details' => 'pages/book-details.php',
    'blog' => 'pages/blog.php',
    'blog-details' => 'pages/blog-details.php',
    'category' => 'pages/category.php',
    'about' => 'pages/about.php',
    'contact' => 'pages/contact.php',
    'error' => 'pages/error.php',
    'profile' => 'pages/profile.php',

    // ✅ Admin-only routes
    'admin' => 'pages/admin/dashboard.php',
    'admin/dashboard' => 'pages/admin/dashboard.php',
    'admin/users' => 'pages/admin/users.php',
    'admin/books' => 'pages/admin/books.php',
    'admin/categories' => 'pages/admin/categories.php',
    'admin/category-create' => 'pages/admin/category-create.php',
    'admin/category-edit' => 'pages/admin/category-edit.php',
    'admin/book-create' => 'pages/admin/book-create.php',
    'admin/book-edit' => 'pages/admin/book-edit.php',
    'admin/orders' => 'pages/admin/orders.php',
    'admin/order-details' => 'pages/admin/order-details.php', // Đổi từ 'orders-details' thành 'order-details'
];

$admin_only_routes = [
    'admin',
    'admin/dashboard',
    'admin/users',
    'admin/books',
    'admin/categories',
    'admin/category-create',
    'admin/category-edit',
    'admin/book-create',
    'admin/book-edit',
    'admin/orders',
    'admin/order-details' // Thêm route này vào danh sách admin-only
];

$protected_routes = ['logout', 'profile'];

$route = implode('/', array_slice($url_parts, 0, 2));

// Xử lý dynamic routes (các route có tham số như id)
if (isset($url_parts[2])) {
    $_GET['id'] = $url_parts[2];
}

$controller_file = $routes[$route] ?? null;

// Check if the route is admin-only
if (in_array($route, $admin_only_routes)) {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['is_admin'])) {
        error_log("Access denied: Admin route without admin rights");
        http_response_code(403);
        require_once 'pages/error.php';
        exit;
    }
}

// Handle dynamic routes (e.g., /book-details/123)
if ($route === 'book-details' && isset($url_parts[1])) {
    $book_id = filter_var($url_parts[1], FILTER_VALIDATE_INT);
    if ($book_id !== false) {
        $_GET['id'] = $book_id;
        $controller_file = 'pages/book-details.php';
    }
}

// Handle dynamic routes for admin/order-details (e.g., /admin/order-details/123e4567-e89b-12d3-a456-426614174000)
if ($route === 'admin/order-details' && isset($url_parts[2])) {
    $order_id = filter_var($url_parts[2], FILTER_SANITIZE_STRING);
    if (!empty($order_id)) {
        $_GET['id'] = $order_id;
        $controller_file = 'pages/admin/order-details.php';
    }
}

// Load the controller file or show 404
if ($controller_file && file_exists($controller_file)) {
    require_once $controller_file;
} else {
    http_response_code(404);
    require_once 'pages/error.php';
}