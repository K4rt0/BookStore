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
    $api_base_url = $_ENV['API_BASE_URL'] ?? 'https://your-api.com';
    $access_token = $_SESSION['access_token'] ?? null;
    $api_url = $api_base_url . "/cart?action=get-cart";
    
    // Set up cURL request
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . ($access_token ?? ""),
        "Content-Type: " . "application/json"
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Process response
    if ($status_code == 200) {
        $data = json_decode($response, true);
        if ($data['success'] && isset($data['data'])) {
            // Count the number of items in the cart (array length of data)
            $count = count($data['data']);
            return $count;
        }
    } else {
        error_log("Failed to fetch cart count: HTTP $status_code, cURL Error: " . ($error ?: "None"));
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
                                                <a href="<?php echo isset($_SESSION['is_admin']) && $_SESSION['is_admin'] ? '/admin/dashboard' : '/profile'; ?>">
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

<!-- JavaScript for updating cart count -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Variables for API call
    const userId = "<?php echo htmlspecialchars($_SESSION['user_id'] ?? ''); ?>";
    const apiBaseUrl = "<?php echo htmlspecialchars($_ENV['API_BASE_URL'] ?? 'https://your-api.com'); ?>";
    const accessToken = "<?php echo htmlspecialchars($_SESSION['access_token'] ?? ''); ?>";

    // Function to update cart count
    window.updateCartCount = function() {
        // If user is not logged in, set count to 0
        if (!userId || !accessToken) {
            const cartCountElement = document.querySelector('.cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = '0';
            }
            return;
        }

        // Fetch updated cart count from API
        fetch(`${apiBaseUrl}/cart?action=get-cart`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const cartCountElement = document.querySelector('.cart-count');
            if (cartCountElement) {
                if (data.success && data.data) {
                    // Update cart count with the number of items in the cart
                    cartCountElement.textContent = data.data.length;
                } else {
                    cartCountElement.textContent = '0';
                }
            }
        })
        .catch(error => {
            console.error('Error fetching cart count:', error);
            const cartCountElement = document.querySelector('.cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = '0';
            }
        });
    };

    // Initial update of cart count on page load
    window.updateCartCount();
});
</script>