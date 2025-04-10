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
    $api_base_url = $_ENV['API_BASE_URL'];
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
                                        <form action="/search" method="GET" class="form-box">
                                            <input type="text" id="search-input" name="keyword" placeholder="Search book by author or publisher" autocomplete="off">
                                            <button type="submit" class="search-icon">
                                                <i class="ti-search"></i>
                                            </button>
                                            <!-- Autocomplete Dropdown -->
                                            <div id="autocomplete-dropdown" class="autocomplete-dropdown"></div>
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

<!-- CSS for Autocomplete Dropdown -->
<style>
/* CSS for Autocomplete Dropdown - Improved Version */
.search-box-wrapper {
    position: relative;
    width: 100%;
}

.autocomplete-dropdown {
    position: absolute;
    top: calc(100% + 5px);
    left: 0;
    right: 0;
    background-color: #fff;
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    max-height: 350px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    padding: 8px 0;
    transition: all 0.3s ease;
}

.autocomplete-dropdown.show {
    display: block;
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.autocomplete-item {
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    transition: background-color 0.2s ease;
}

.autocomplete-item:last-child {
    border-bottom: none;
}

.autocomplete-item:hover {
    background-color: #f7f9fc;
}

.autocomplete-item img {
    width: 50px;
    height: 65px;
    object-fit: cover;
    margin-right: 15px;
    border-radius: 6px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.autocomplete-item .book-info {
    flex: 1;
}

.autocomplete-item .book-title {
    font-weight: 600;
    font-size: 14px;
    color: #333;
    margin-bottom: 4px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.autocomplete-item .book-author {
    font-size: 13px;
    color: #6b7280;
    font-style: italic;
}

/* Custom scrollbar for the dropdown */
.autocomplete-dropdown::-webkit-scrollbar {
    width: 6px;
}

.autocomplete-dropdown::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.autocomplete-dropdown::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

.autocomplete-dropdown::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

/* Empty state style */
.autocomplete-empty {
    padding: 20px;
    text-align: center;
    color: #6b7280;
    font-style: italic;
}

/* Highlight matching text */
.highlight {
    background-color: rgba(255, 204, 0, 0.2);
    padding: 0 2px;
    border-radius: 2px;
}
</style>

<!-- JavaScript for Autocomplete and Cart Count -->
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

    // Autocomplete Search Functionality
    const searchInput = document.getElementById('search-input');
    const autocompleteDropdown = document.getElementById('autocomplete-dropdown');

    // Debounce function to limit API calls
    const debounce = (func, wait) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    };

    // Function to fetch books from API
    const fetchBooks = async (keyword) => {
        if (!keyword.trim()) {
            autocompleteDropdown.innerHTML = '';
            autocompleteDropdown.classList.remove('show');
            return;
        }

        try {
            const response = await fetch(`${apiBaseUrl}/book?action=get-all-books-pagination&page=1&limit=6&is_deleted=0&search=${encodeURIComponent(keyword)}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${accessToken}`,
                    'Content-Type': 'application/json'
                }
            });
            const data = await response.json();

            if (data.success && data.data && data.data.books && data.data.books.length > 0) {
                displayAutocompleteResults(data.data.books);
            } else {
                autocompleteDropdown.innerHTML = '<div class="autocomplete-item">No results found</div>';
                autocompleteDropdown.classList.add('show');
            }
        } catch (error) {
            console.error('Error fetching books:', error);
            autocompleteDropdown.innerHTML = '<div class="autocomplete-item">Error fetching results</div>';
            autocompleteDropdown.classList.add('show');
        }
    };

    // Function to display autocomplete results
    const displayAutocompleteResults = (books) => {
        autocompleteDropdown.innerHTML = '';
        books.forEach(book => {
            const item = document.createElement('div');
            item.classList.add('autocomplete-item');
            item.innerHTML = `
                <img src="${book.image_url || '/assets/img/placeholder.jpg'}" alt="${book.title}">
                <div class="book-info">
                    <div class="book-title">${book.title}</div>
                    <div class="book-author">${book.author}</div>
                </div>
            `;
            item.addEventListener('click', () => {
                window.location.href = `/book-details?id=${book.id}`;
            });
            autocompleteDropdown.appendChild(item);
        });
        autocompleteDropdown.classList.add('show');
    };

    // Debounced search function
    const debouncedSearch = debounce(fetchBooks, 300);

    // Event listener for input
    searchInput.addEventListener('input', (e) => {
        const keyword = e.target.value;
        debouncedSearch(keyword);
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !autocompleteDropdown.contains(e.target)) {
            autocompleteDropdown.classList.remove('show');
        }
    });

    // Show dropdown when focusing on input
    searchInput.addEventListener('focus', () => {
        if (searchInput.value.trim()) {
            debouncedSearch(searchInput.value);
        }
    });
});
</script>