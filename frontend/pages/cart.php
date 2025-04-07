<?php
$page_title = "Book Shop - Cart";
ob_start();

// Start session
session_start();

// API base URL and session variables
$api_base_url = $_ENV['API_BASE_URL'];
$access_token = $_SESSION['access_token'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

// Redirect to login if not authenticated
if (empty($access_token) || empty($user_id)) {
    header("Location: /login");
    exit();
}

// Function to make API requests
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

// Fetch cart and book details
$cart_response = fetchCartData($api_base_url, $access_token);
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
                'book_id' => $book_id,
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
                            <?php foreach ($cart_with_book_details as $item): ?>
                                <tr data-book-id="<?php echo htmlspecialchars($item['book_id']); ?>">
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
                                        <h5 class="price" data-price="<?php echo $item['price']; ?>">₫<?php echo number_format($item['price'], 2); ?></h5>
                                    </td>
                                    <td>
                                        <div class="product_count">
                                            <span class="input-number-decrement"> <i class="ti-minus"></i></span>
                                           <input
                                                id="quantity-<?php echo $item['book_id']; ?>"
                                                class="input-number"
                                                type="text"
                                                value="<?php echo $item['quantity']; ?>"
                                                min="0"
                                                max="10">
                                            <span class="input-number-increment"> <i class="ti-plus"></i></span>
                                        </div>
                                    </td>
                                    <td>
                                        <h5
                                            id="total-<?php echo $item['book_id']; ?>"
                                            class="total">₫<?php echo number_format($item['total'], 2); ?></h5>
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
                    </tbody>
                </table>
                <?php if (!empty($cart_with_book_details)): ?>
                    <div class="checkout_btn_inner float-right">
                        <a class="btn" href="/category">Continue Shopping</a>
                        <a class="btn checkout_btn" href="/checkout">Proceed to Checkout</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const userId = "<?php echo htmlspecialchars($user_id ?? ''); ?>";
    const apiBaseUrl = "<?php echo htmlspecialchars($api_base_url); ?>";
    const accessToken = "<?php echo htmlspecialchars($access_token ?? ''); ?>";
    let pendingRequests = {};

    const formatCurrency = amount => `₫${amount.toLocaleString('vi-VN', { minimumFractionDigits: 2 })}`;

    const addToCartSingleUnit = (bookId, row) => {
        if (pendingRequests[bookId]) return;
        pendingRequests[bookId] = true;

        const input = row.querySelector(`#quantity-${bookId}`);
        const totalElement = row.querySelector(`#total-${bookId}`);
        const price = parseFloat(row.querySelector('.price').getAttribute('data-price'));
        const currentQuantity = parseInt(input.value) || 0;
        const newQuantity = currentQuantity + 1;

        fetch(`${apiBaseUrl}/cart?action=add-to-cart`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_id: userId, book_id: bookId, quantity: 1 }) // luôn cộng thêm 1
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            delete pendingRequests[bookId];
            if (data.success) {
                // Tăng UI chỉ cho item tương ứng
                input.value = newQuantity;
                totalElement.textContent = formatCurrency(price * newQuantity);
                recalculateSubtotal();
            } else {
                throw new Error(data.message || 'Failed to add to cart.');
            }
        })
        .catch(error => {
            delete pendingRequests[bookId];
            console.error('Error adding item:', error.message);
            alert(`Error: ${error.message}`);
        });
    };



    const updateQuantity = (bookId, newQuantity, row) => {
        if (pendingRequests[bookId]) return;

        const input = row.querySelector('.input-number');
        const price = parseFloat(row.querySelector('.price').getAttribute('data-price'));
        const totalElement = row.querySelector('.total');
        const originalQuantity = parseInt(input.value);

        input.value = newQuantity;
        totalElement.textContent = formatCurrency(price * newQuantity);
        recalculateSubtotal();

        pendingRequests[bookId] = true;
        const url = `${apiBaseUrl}/cart?action=add-to-cart`;

        fetch(url, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_id: userId, book_id: bookId, quantity: newQuantity })
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            delete pendingRequests[bookId];
            if (!data.success) {
                throw new Error(`Failed to update quantity: ${data.message}`);
            }
            window.location.reload();
        })
        .catch(error => {
            delete pendingRequests[bookId];
            console.error('Error updating quantity:', error.message);
            alert(`Error: ${error.message}`);
            input.value = originalQuantity;
            totalElement.textContent = formatCurrency(price * originalQuantity);
            recalculateSubtotal();
        });
    };

    
    const recalculateSubtotal = () => {
        let subtotal = 0;
        document.querySelectorAll('tr[data-book-id]').forEach(row => {
            const price = parseFloat(row.querySelector('.price').getAttribute('data-price'));
            const quantity = parseInt(row.querySelector('.input-number').value) || 0;
            subtotal += price * quantity;
        });
        document.getElementById('subtotal').textContent = formatCurrency(subtotal);
        const checkoutBtn = document.querySelector('.checkout_btn');
        if (checkoutBtn) {
            if (subtotal <= 0) {
                checkoutBtn.classList.add('disabled');
                checkoutBtn.setAttribute('disabled', 'disabled');
            } else {
                checkoutBtn.classList.remove('disabled');
                checkoutBtn.removeAttribute('disabled');
            }
        }
    };

    // Increment quantity
    document.querySelectorAll('.input-number-increment').forEach(button => {
        button.addEventListener('click', () => {
            const row = button.closest('tr');
            const bookId = row.getAttribute('data-book-id');
            const input = row.querySelector('.input-number');
            const currentQuantity = parseInt(input.value) || 0;
            const maxQuantity = parseInt(input.getAttribute('max')) || 10;

            if (currentQuantity < maxQuantity) {
                addToCartSingleUnit(bookId, row);
            } else {
                alert(`Maximum quantity (${maxQuantity}) reached.`);
            }
        });
    });

    // Decrement quantity
    document.querySelectorAll('.input-number-decrement').forEach(button => {
        button.addEventListener('click', () => {
            const row = button.closest('tr');
            const bookId = row.getAttribute('data-book-id');
            const input = row.querySelector('.input-number');
            const currentQuantity = parseInt(input.value) || 0;

            if (currentQuantity > 1) {
                const newQuantity = currentQuantity - 1;
                updateQuantity(bookId, newQuantity, row);
            } else if (currentQuantity === 1 && confirm('Remove this item from your cart?')) {
                removeItem(bookId, row);
            }
        });
    });
});
</script>


<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main-layout.php';
?>