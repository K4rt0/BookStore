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

// Define routes
$routes = [
    '' => 'pages/home.php',                // Homepage (/)
    'index' => 'pages/home.php',           // /index
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
];

// Protected routes that require login
$protected_routes = ['logout', 'profile'];

// Determine the route
$route = $url_parts[0] ?? '';
$controller_file = $routes[$route] ?? null;

// Debug: Log the route and controller file
error_log("Route: " . $route);
error_log("Controller File: " . ($controller_file ?: 'Not found'));

// Debug: Log session state before middleware check
error_log("Session logged_in before middleware: " . (isset($_SESSION['logged_in']) ? 'true' : 'false'));
error_log("Session data: " . print_r($_SESSION, true));

// Check if the route is protected
if (in_array($route, $protected_routes) && (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true)) {
    error_log("Redirecting to /login because user is not logged in");
    header("Location: /login");
    exit;
}

// Handle dynamic routes (e.g., /book-details/123)
if ($route === 'book-details' && isset($url_parts[1])) {
    $book_id = filter_var($url_parts[1], FILTER_VALIDATE_INT);
    if ($book_id !== false) {
        $_GET['id'] = $book_id;
        $controller_file = 'pages/book-details.php';
    }
}

// Load the controller file or show 404
if ($controller_file && file_exists($controller_file)) {
    require_once $controller_file;
} else {
    http_response_code(404);
    require_once 'pages/error.php';
}