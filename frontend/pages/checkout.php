<?php
$page_title = "Book Shop - Checkout";
ob_start();
session_start();

$api_base_url = $_ENV['API_BASE_URL'];
$access_token = $_SESSION['access_token'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

// Redirect to login if not authenticated
if (empty($access_token) || empty($user_id)) {
    header("Location: /login");
    exit();
}

function makeApiRequest($url, $access_token, $method = 'GET', $body = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . ($access_token ?? ""),
        "Content-Type: application/json"
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        return ["success" => false, "message" => "Request failed: " . $error, "data" => null, "http_code" => $http_code];
    }
    
    $decoded_response = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ["success" => false, "message" => "Invalid JSON response", "data" => null, "http_code" => $http_code];
    }
    
    return $http_code === 200 && isset($decoded_response['success']) && $decoded_response['success']
        ? $decoded_response
        : ["success" => false, "message" => $decoded_response['message'] ?? "Unable to fetch data (HTTP $http_code)", "data" => null, "http_code" => $http_code];
}

// Fetch cart data
function fetchCartData($api_base_url, $access_token) {
    return makeApiRequest($api_base_url . "/cart?action=get-cart", $access_token);
}

// Fetch book details by book_id
function fetchBookDetails($api_base_url, $access_token, $book_id) {
    return makeApiRequest($api_base_url . "/book?action=get-book&id=" . urlencode($book_id), $access_token);
}

// Fetch user data
function fetchUserData($api_base_url, $access_token, $user_id) {
    return makeApiRequest($api_base_url . "/user?action=get-user&id=" . urlencode($user_id), $access_token);
}

// Function to save order to API
function saveOrder($api_base_url, $access_token, $order_data) {
    $url = $api_base_url . "/order?action=create";
    return makeApiRequest($url, $access_token, 'POST', $order_data);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = strtolower($_POST['payment_method']);
    $amount = floatval($_POST['amount']);
    
    $order_info = [
        "user_id" => $user_id,
        "full_name" => $_POST['full_name'],
        "phone" => $_POST['phone'],
        "total_price" => $amount,
        "shipping_address" => $_POST['shipping_address'],
        "payment_method" => strtoupper($payment_method)
    ];
    
    $carts = $_SESSION['cart_items'] ?? [];
    $cart_ids = array_column($carts, 'id');

    // Validate cart IDs
    if (empty($cart_ids)) {
        $error_message = "No cart items found. Please add items to your cart.";
    } else {
        $order_data = [
            "order_info" => $order_info,
            "carts" => $cart_ids
        ];

        // Debug: Log order data to verify
        // error_log("Order Data: " . json_encode($order_data));

        $order_response = saveOrder($api_base_url, $access_token, $order_data);

        if ($order_response['success'] && isset($order_response['data']['payment_url']) && $payment_method !== 'cod') {
            // Redirect to payment URL for online payment methods
            $payment_url = $order_response['data']['payment_url'];
            header("Location: $payment_url");
            exit();
        } elseif ($order_response['success'] && $payment_method === 'cod') {
            // For COD, redirect to a confirmation page or clear cart
            $_SESSION['cart_items'] = []; // Clear cart
            header("Location: /order-confirmation?status=success&message=Order placed successfully with Cash on Delivery");
            exit();
        } else {
            // Handle error
            $error_message = $order_response['message'] ?? "Failed to create order";
        }
    }
}

// Fetch cart and book details
$cart_response = fetchCartData($api_base_url, $access_token);
$cart_with_book_details = [];
$subtotal = 0;
$shipping = 30000; // Flat rate shipping in VND
$errors = [];

if ($cart_response['success'] && !empty($cart_response['data'])) {
    foreach ($cart_response['data'] as $item) {
        $book_id = $item['book_id'];
        $cart_item_id = $item['id'];
        $book_response = fetchBookDetails($api_base_url, $access_token, $book_id);
        
        if ($book_response['success'] && $book_response['data']) {
            $book = $book_response['data'];
            $price = floatval($book['price']);
            $quantity = $item['quantity'];
            $total = $price * $quantity;
            $subtotal += $total;
            
            $cart_with_book_details[] = [
                'id' => $cart_item_id, // Fixed key from 'id дальше: '
                'book_id' => $book_id,
                'title' => $book['title'],
                'price' => $price,
                'quantity' => $quantity,
                'total' => $total
            ];
        } else {
            $errors[] = "Failed to fetch book details for ID $book_id: " . ($book_response['message'] ?? "Unknown error");
        }
    }
    // Store cart items in session
    $_SESSION['cart_items'] = $cart_with_book_details;
} else {
    $errors[] = "Failed to fetch cart data: " . ($cart_response['message'] ?? "Unknown error");
}

$total = $subtotal + $shipping;

// Fetch user data
$user_response = fetchUserData($api_base_url, $access_token, $user_id);
$user_data = $user_response['success'] && $user_response['data'] ? $user_response['data'] : null;

// Check for error messages
$error_message = isset($error_message) ? $error_message : ($_GET['error'] ?? null);

?>

<div class="container">
    <div class="row">
        <div class="col-xl-12">
            <div class="slider-area">
                <div class="slider-height2 slider-bg5 d-flex align-items-center justify-content-center">
                    <div class="hero-caption hero-caption2">
                        <h2>Checkout</h2>
                    </div>
                </div>
            </div>
        </div>
    </div> 
</div>

<section class="checkout_area section-padding">
    <div class="container">
        <?php if (!empty($errors) || $error_message): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
                <?php if ($error_message): ?>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="billing_details">
            <div class="row">
                <div class="col-lg-8">
                    <h3>Billing Information</h3>
                    <form class="row contact_form" action="" method="post" novalidate="novalidate" id="checkout_form">
                        <div class="col-md-12 form-group p_star">
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" placeholder="**Full Name" required />
                        </div>
                        <div class="col-md-6 form-group p_star">
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" placeholder="**Phone Number" required />
                        </div>
                        <div class="col-md-6 form-group p_star">
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" placeholder="**Email Address" required />
                        </div>
                        <div class="col-md-12 form-group p_star">
                            <input type="text" class="form-control" id="add1" name="address1" value="<?php echo htmlspecialchars($user_data['address'] ?? ''); ?>" placeholder="**Address (House number, street, ward/commune)" required />
                        </div>
                        <div class="col-md-12 form-group">
                            <textarea class="form-control" name="message" id="message" rows="1" placeholder="**Order Notes (optional)"><?php echo htmlspecialchars($user_data['notes'] ?? ''); ?></textarea>
                        </div>
                        <!-- Hidden fields for order -->
                        <input type="hidden" name="amount" value="<?php echo $total; ?>">
                        <input type="hidden" name="payment_method" id="payment_method" value="">
                        <button type="submit" class="btn" id="checkout_btn">Place Order</button>
                        <!-- PayPal Button Container -->
                        <div id="paypal-button-container" style="display: none; margin-top: 10px;"></div>
                    </form>
                </div>
                <div class="col-lg-4">
                    <div class="order_box">
                        <h2>Your Order</h2>
                        <ul class="list">
                            <li><a href="#">Product<span>Total</span></a></li>
                            <?php foreach ($cart_with_book_details as $item): ?>
                                <li>
                                    <a href="#">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                        <span class="middle">x <?php echo $item['quantity']; ?></span>
                                        <span class="last"><?php echo number_format($item['total'], 0, ',', '.'); ?> VND</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <ul class="list list_2">
                            <li><a href="#">Subtotal <span><?php echo number_format($subtotal, 0, ',', '.'); ?> VND</span></a></li>
                            <li><a href="#">Shipping <span><?php echo number_format($shipping, 0, ',', '.'); ?> VND</span></a></li>
                            <li><a href="#">Total <span><strong><?php echo number_format($total, 0, ',', '.'); ?> VND</strong></span></a></li>
                        </ul>
                        <h4 class="mt-4">Payment Method</h4>
                        <!-- Online Payment -->
                        <div class="payment_item">
                            <div class="radion_btn">
                                <input type="radio" id="f-option-online" name="payment_method_radio" value="online" onchange="updatePaymentMethod('online')" checked />
                                <label for="f-option-online">Online Payment</label>
                                <div class="check"></div>
                            </div>
                            <p>Pay securely online using one of the following methods:</p>
                            <div class="online-payment-options" style="margin-left: 20px;">
                                <div class="payment_option">
                                    <input type="radio" id="f-option-vnpay" name="online_payment_method" value="vnpay" checked onchange="updatePaymentMethod('vnpay')" />
                                    <label for="f-option-vnpay">VNPay</label>
                                    <img style="width: 5rem; margin-left: 10px;" src="./assets/svg/vnpay.svg" alt="VNPay" />
                                </div>
                                <div class="payment_option">
                                    <input type="radio" id="f-option-paypal" name="online_payment_method" value="paypal" onchange="updatePaymentMethod('paypal')" />
                                    <label for="f-option-paypal">PayPal</label>
                                    <img style="width: 5rem; margin-left: 10px;" src="./assets/svg/64px-PayPal.svg.png" alt="PayPal" />
                                </div>
                                <div class="payment_option">
                                    <input type="radio" id="f-option-momo" name="online_payment_method" value="momo" onchange="updatePaymentMethod('momo')" />
                                    <label for="f-option-momo">MoMo</label>
                                    <img style="width: 5rem; margin-left: 10px;" src="./assets/svg/momo.svg" alt="MoMo" />
                                </div>
                            </div>
                        </div>
                        <!-- Cash on Delivery -->
                        <div class="payment_item">
                            <div class="radion_btn">
                                <input type="radio" id="f-option-cod" name="payment_method_radio" value="cod" onchange="updatePaymentMethod('cod')" />
                                <label for="f-option-cod">Cash on Delivery (COD)</label>
                                <div class="check"></div>
                            </div>
                            <p>Pay with cash upon delivery at the shipping address.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Update payment method and toggle PayPal button visibility
function updatePaymentMethod(method) {
    const paymentMethodInput = document.getElementById('payment_method');
    const checkoutBtn = document.getElementById('checkout_btn');
    const paypalButtonContainer = document.getElementById('paypal-button-container');

    if (method === 'online') {
        const onlineMethod = document.querySelector('input[name="online_payment_method"]:checked').value;
        paymentMethodInput.value = onlineMethod;
        if (onlineMethod === 'paypal') {
            checkoutBtn.style.display = 'none';
            paypalButtonContainer.style.display = 'block';
        } else {
            checkoutBtn.style.display = 'block';
            paypalButtonContainer.style.display = 'none';
            checkoutBtn.textContent = `Pay with ${onlineMethod.charAt(0).toUpperCase() + onlineMethod.slice(1)}`;
        }
    } else if (method === 'vnpay' || method === 'paypal' || method === 'momo') {
        paymentMethodInput.value = method;
        if (method === 'paypal') {
            checkoutBtn.style.display = 'none';
            paypalButtonContainer.style.display = 'block';
        } else {
            checkoutBtn.style.display = 'block';
            paypalButtonContainer.style.display = 'none';
            checkoutBtn.textContent = `Pay with ${method.charAt(0).toUpperCase() + method.slice(1)}`;
        }
    } else if (method === 'cod') {
        paymentMethodInput.value = method;
        checkoutBtn.style.display = 'block';
        paypalButtonContainer.style.display = 'none';
        checkoutBtn.textContent = 'Place Order';
    }
}

// Update payment method when online payment sub-options change
document.querySelectorAll('input[name="online_payment_method"]').forEach(input => {
    input.addEventListener('change', () => {
        if (document.getElementById('f-option-online').checked) {
            updatePaymentMethod(input.value);
        }
    });
});

updatePaymentMethod('vnpay');

const paypalClientId = '<?php echo htmlspecialchars($_ENV['PAYPAL_CLIENT_ID'] ?? ''); ?>';
// Load PayPal SDK dynamically
const script = document.createElement('script');
script.src = `https://www.paypal.com/sdk/js?client-id=${paypalClientId}&currency=USD`;
script.async = true;
script.onload = () => {
    paypal.Buttons({
        style: {
            shape: "rect",
            layout: "vertical",
            color: "gold",
            label: "paypal",
        },
        onClick: () => {
            // Validate form before proceeding
            const form = document.getElementById('checkout_form');
            if (!form.checkValidity()) {
                form.reportValidity();
                return false;
            }
        },
        createOrder: async () => {
            try {
                // Collect form data
                const formData = new FormData(document.getElementById('checkout_form'));
                const data = Object.fromEntries(formData.entries());

                // Prepare order data
                const orderInfo = {
                    user_id: '<?php echo $user_id; ?>',
                    full_name: data.first_name,
                    phone: data.phone,
                    total_price: parseFloat(data.amount),
                    shipping_address: data.address1,
                    payment_method: 'PAYPAL'
                };

                    const cartItems = <?php echo json_encode($_SESSION['cart_items'] ?? []); ?>;
                    const cartIds = cartItems.map(item => item.id);

                    if (!cartIds.length) {
                        throw new Error('No cart items found');
                    }

                    const orderData = {
                        order_info: orderInfo,
                        carts: cartIds
                    };

                    // Debug: Log order data
                    console.log('PayPal Order Data:', orderData);

                    const response = await fetch('<?php echo $api_base_url; ?>/order?action=create', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': 'Bearer <?php echo $access_token; ?>'
                        },
                        body: JSON.stringify(orderData)
                    });

                    const orderResult = await response.json();

                    if (!orderResult.success || !orderResult.data.payment_url) {
                        throw new Error(orderResult.message || 'Failed to create order');
                    }

                    window.location.href = orderResult.data.payment_url;
                } catch (error) {
                    console.error('PayPal Error:', error);
                    alert(`Could not initiate PayPal Checkout: ${error.message}`);
                }
            }
        }).render('#paypal-button-container');
    };
    document.head.appendChild(script);
}
</script>

<style>
.payment_item {
    margin-bottom: 20px;
}

.payment_option {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.payment_option input[type="radio"] {
    margin-right: 10px;
}

.payment_option label {
    margin-right: 10px;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main-layout.php';
?>