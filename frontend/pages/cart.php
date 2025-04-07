<?php
$page_title = "Book Shop - Cart";
ob_start(); // Bắt đầu bộ đệm để lưu nội dung trang

// API base URL and access token from environment/session
$api_base_url = $_ENV['API_BASE_URL'] ?? 'https://your-api.com';
$access_token = $_SESSION['access_token'] ?? null;

// Log the access token for debugging
error_log("Access Token: " . ($access_token ?? "Not set"));

// Function to make API requests
function makeApiRequest($url, $access_token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . ($access_token ?? ""),
        "Content-Type: application/json"
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Log the API request details for debugging
    error_log("API Request URL: " . $url);
    error_log("API Response: " . ($response ?: "No response"));
    if ($error) {
        error_log("cURL Error: " . $error);
    }
    
    // Check if the request failed at the network level
    if ($response === false) {
        return [
            "success" => false,
            "message" => "Request failed: " . ($error ?: "Unknown cURL error"),
            "data" => null,
            "http_code" => $http_code
        ];
    }
    
    // Try to decode the response as JSON
    $decoded_response = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            "success" => false,
            "message" => "Invalid JSON response: " . json_last_error_msg(),
            "data" => null,
            "http_code" => $http_code,
            "raw_response" => $response
        ];
    }
    
    // Check if the HTTP status code is 200 and the response is successful
    if ($http_code === 200 && isset($decoded_response['success']) && $decoded_response['success']) {
        return $decoded_response;
    } else {
        return [
            "success" => false,
            "message" => $decoded_response['message'] ?? "Unable to fetch data (HTTP $http_code)",
            "data" => null,
            "http_code" => $http_code,
            "raw_response" => $response
        ];
    }
}

// Function to fetch cart data
function fetchCartData($api_base_url, $access_token) {
    $url = $api_base_url . "/cart?action=get-cart";
    return makeApiRequest($url, $access_token);
}

// Function to fetch book details by book_id
function fetchBookDetails($api_base_url, $access_token, $book_id) {
    $url = $api_base_url . "/book?action=get-book&id=" . urlencode($book_id);
    return makeApiRequest($url, $access_token);
}

// Check if the access token is set
if (empty($access_token)) {
    $errors[] = "Access token is not set. Please log in.";
    $cart_response = ["success" => false, "message" => "Access token missing", "data" => null];
} else {
    // Fetch cart data
    $cart_response = fetchCartData($api_base_url, $access_token);
}

// Fetch book details for each cart item
$cart_with_book_details = [];
$subtotal = 0;
$errors = [];

if ($cart_response['success'] && !empty($cart_response['data'])) {
    foreach ($cart_response['data'] as $item) {
        $book_id = $item['book_id'];
        $book_response = fetchBookDetails($api_base_url, $access_token, $book_id);
        
        if ($book_response['success'] && $book_response['data']) {
            $book = $book_response['data'];
            $price = floatval($book['price']);
            $quantity = $item['quantity'];
            $total = $price * $quantity;
            $subtotal += $total;
            
            $cart_with_book_details[] = [
                'title' => $book['title'],
                'image_url' => $book['image_url'],
                'price' => $price,
                'quantity' => $quantity,
                'total' => $total
            ];
        } else {
            $errors[] = "Failed to fetch details for book ID $book_id: " . ($book_response['message'] ?? "Unknown error");
        }
    }
} else {
    $errors[] = "Failed to fetch cart data: " . ($cart_response['message'] ?? "Unknown error");
    if (isset($cart_response['raw_response'])) {
        $errors[] = "Raw API Response: " . htmlspecialchars(substr($cart_response['raw_response'], 0, 200));
    }
}

?>

<div class="container">
    <div class="row">
        <div class="col-xl-12">
            <div class="slider-area">
                <div class="slider-height2 slider-bg5 d-flex align-items-center justify-content-center">
                    <div class="hero-caption hero-caption2">
                        <h2>Cart</h2>
                    </div>
                </div>
            </div>
        </div>
    </div> 
</div>
<!-- Hero area End -->
<!--================Cart Area =================-->
<section class="cart_area section-padding">
    <div class="container">
        <div class="cart_inner">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Product</th>
                            <th scope="col">Price</th>
                            <th scope="col">Quantity</th>
                            <th scope="col">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cart_with_book_details)): ?>
                            <?php foreach ($cart_with_book_details as $index => $item): ?>
                                <tr>
                                    <td>
                                        <div class="media">
                                            <div class="d-flex">
                                                <img src="<?php echo htmlspecialchars($item['image_url'] ?? '/assets/img/gallery/default.jpg'); ?>" alt="" />
                                            </div>
                                            <div class="media-body">
                                                <p><?php echo htmlspecialchars($item['title'] ?? 'Unnamed Product'); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <h5>₫<?php echo number_format($item['price'], 2); ?></h5>
                                    </td>
                                    <td>
                                        <div class="product_count">
                                            <span class="input-number-decrement"> <i class="ti-minus"></i></span>
                                            <input class="input-number" type="text" value="<?php echo $item['quantity']; ?>" min="0" max="10" data-index="<?php echo $index; ?>">
                                            <span class="input-number-increment"> <i class="ti-plus"></i></span>
                                        </div>
                                    </td>
                                    <td>
                                        <h5>₫<?php echo number_format($item['total'], 2); ?></h5>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">
                                    <p>Your cart is empty!</p>
                                    <?php if (!empty($errors)): ?>
                                        <?php foreach ($errors as $error): ?>
                                            <p style="color: red;">Error: <?php echo htmlspecialchars($error); ?></p>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php if (!empty($cart_with_book_details)): ?>
                            <tr class="bottom_button">
                                <td>
                                    <a class="btn" href="#">Update Cart</a>
                                </td>
                                <td></td>
                                <td></td>
                                <td>
                                    <div class="cupon_text float-right">
                                        <a class="btn" href="#">Close Coupon</a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td>
                                    <h5>Subtotal</h5>
                                </td>
                                <td>
                                    <h5>₫<?php echo number_format($subtotal, 2); ?></h5>
                                </td>
                            </tr>
                            <tr class="shipping_area">
                                <td></td>
                                <td></td>
                                <td>
                                    <h5>Shipping</h5>
                                </td>
                                <td>
                                    <div class="shipping_box">
                                        <ul class="list">
                                            <li>
                                                Flat Rate: ₫5,000
                                                <input type="radio" aria-label="Radio button for following text input">
                                            </li>
                                            <li>
                                                Free Shipping
                                                <input type="radio" aria-label="Radio button for following text input">
                                            </li>
                                            <li>
                                                Flat Rate: ₫10,000
                                                <input type="radio" aria-label="Radio button for following text input">
                                            </li>
                                            <li class="active">
                                                Local Delivery: ₫2,000
                                                <input type="radio" aria-label="Radio button for following text input">
                                            </li>
                                        </ul>
                                        <h6>
                                            Calculate Shipping
                                            <i class="fa fa-caret-down" aria-hidden="true"></i>
                                        </h6>
                                        <select class="shipping_select">
                                            <option value="1">Bangladesh</option>
                                            <option value="2">India</option>
                                            <option value="4">Pakistan</option>
                                        </select>
                                        <select class="shipping_select section_bg">
                                            <option value="1">Select a State</option>
                                            <option value="2">Select a State</option>
                                            <option value="4">Select a State</option>
                                        </select>
                                        <input class="post_code" type="text" placeholder="Postcode/Zipcode" />
                                        <a class="btn" href="#">Update Details</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="checkout_btn_inner float-right">
                    <a class="btn" href="#">Continue Shopping</a>
                    <?php if (!empty($cart_with_book_details)): ?>
                        <a class="btn checkout_btn" href="#">Proceed to checkout</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean(); // Lấy nội dung từ bộ đệm và gán vào biến $content
include __DIR__ . '/../layouts/main-layout.php'; // Bao gồm layout chính
?>