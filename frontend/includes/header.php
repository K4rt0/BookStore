<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart count
$cart_count = 0;

// Function to get cart count from API
function getCartCountFromApi() {
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
        return 0;
    }
    
    $user_id = $_SESSION['user_id'];
    $api_url = "/api/cart/count?user_id=" . $user_id;
    
    // Get API token from session if available
    $headers = [];
    if (isset($_SESSION['api_token'])) {
        $headers[] = "Authorization: Bearer " . $_SESSION['api_token'];
    }
    
    // Set up cURL request
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    // Execute request
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Process response
    if ($status_code == 200) {
        $data = json_decode($response, true);
        return isset($data['count']) ? $data['count'] : 0;
    }
    
    return 0;
}

// Get cart count from API if user is logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $cart_count = getCartCountFromApi();
    // Cache the count in session for better performance
    $_SESSION['cart_count'] = $cart_count;
} else if (isset($_SESSION['cart_count'])) {
    // Use cached count if available
    $cart_count = $_SESSION['cart_count'];
}
?>

<header>
    <div class="header-area">
        <div class="main-header">
            <div class="header-top">
                <div class="container">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="header-info-left d-flex align-items-center">
                                    <!-- logo -->
                                    <div class="logo">
                                        <a href="/">
                                            <div class="d-flex align-items-center">
                                                <img src="/assets/img/logo/logo.png" alt="ABC Book">
                                            </div>
                                        </a>
                                    </div>
                                    <!-- Search Box -->
                                    <div class="search-box-wrapper">
                                        <form action="#" class="form-box">
                                            <input type="text" name="Search" placeholder="Search book by author or publisher">
                                            <button type="submit" class="search-icon">
                                                <i class="ti-search"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="header-info-right d-flex align-items-center">
                                    <ul class="header-menu">
                                        
                                        <li class="">
                                            <a href="/cart">
                                                <div class="cart-icon-container">
                                                    <img src="/assets/img/icon/cart.svg" alt="Shopping Cart">
                                                    <span class="cart-count"><?php echo $cart_count; ?></span>
                                                </div>
                                            </a>
                                        </li>
                                        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                                            <!-- Display email for logged in user -->
                                            <li class="user-email">
                                                <a href="/profile">
                                                    <?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : (isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Profile'); ?>
                                                </a>
                                            </li>
                                            <li><a href="/logout" class="btn logout-btn">Logout</a></li>
                                        <?php else: ?>
                                            <!-- Display login button if not logged in -->
                                            <li><a href="/login" class="btn header-btn signin-btn">Sign in</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="header-bottom header-sticky">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-xl-12">
                            <!-- Main-menu -->
                            <div class="main-menu text-center">
                                <nav>
                                    <ul id="navigation">
                                        <li><a href="/">Home</a></li>
                                        <li><a href="/category">Categories</a></li>
                                        <li><a href="/about">About</a></li>
                                        <li><a href="#">Pages</a>
                                            <ul class="submenu">
                                                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                                                    <!-- Show these options if logged in -->
                                                    <li><a href="/profile">Profile</a></li>
                                                    <li><a href="/logout">Logout</a></li>
                                                <?php else: ?>
                                                    <!-- Show this option if not logged in -->
                                                    <li><a href="/login">Login</a></li>
                                                <?php endif; ?>
                                                <li><a href="/cart">Cart</a></li>
                                                <li><a href="/checkout">Checkout</a></li>
                                                <li><a href="/book-details">Book Details</a></li>
                                                <li><a href="/blog-details">Blog Details</a></li>
                                                <li><a href="/elements">Element</a></li>
                                            </ul>
                                        </li>
                                        <li><a href="/blog">Blog</a></li>
                                        <li><a href="/contact">Contact</a></li>
                                    </ul>
                                </nav>
                            </div>
                            <!-- Mobile Menu -->
                            <div class="mobile_menu d-block d-lg-none"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

