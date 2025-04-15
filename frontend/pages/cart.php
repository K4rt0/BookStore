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

// Fetch cart and book details
$cart_response = fetchCartData($api_base_url, $access_token);
$cart_with_book_details = [];
$subtotal = 0;
$errors = [];

if ($cart_response['success'] && !empty($cart_response['data'])) {
    foreach ($cart_response['data'] as $item) {
        $book_id = $item['book_id'];
        $cart_item_id = $item['id'];
        $book_response = fetchBookDetails($api_base_url, $access_token, $book_id);
        
        if ($book_response['success'] && $book_response['data']) {
            $book = $book_response['data']['book'];
            $price = floatval($book['price']);
            $quantity = $item['quantity'];
            $total = $price * $quantity;
            $subtotal += $total;
            
            $cart_with_book_details[] = [
                'id' => $cart_item_id,
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
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cart_with_book_details)): ?>
                            <?php foreach ($cart_with_book_details as $item): ?>
                                <tr data-cart-id="<?php echo htmlspecialchars($item['id']); ?>" data-book-id="<?php echo htmlspecialchars($item['book_id']); ?>">
                                    <td>
                                        <div class="media">
                                            <div class="d-flex image-container" style="width: 200px;height: 120px;">
                                                <img class="cart-item-image h-100 w-100" style="object-fit: cover;" src="<?php echo htmlspecialchars($item['image_url'] ?? '/assets/img/gallery/default.jpg'); ?>" alt="<?php echo htmlspecialchars($item['title'] ?? 'Unnamed Product'); ?>" />
                                            </div>
                                            <div class="media-body">
                                                <p>
                                                    <a class="text-dark" href="/book-details?id=<?php echo htmlspecialchars($item['book_id']); ?>">
                                                        <?php echo htmlspecialchars($item['title'] ?? 'Unnamed Product'); ?>
                                                    </a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <h5 class="price" data-price="<?php echo $item['price']; ?>"><?php echo number_format($item['price'], 0, ',', '.'); ?>₫</h5>
                                    </td>
                                    <td>
                                        <div class="product_count">
                                            <span class="input-number-decrement"> <i class="ti-minus"></i></span>
                                            <input
                                                id="quantity-<?php echo htmlspecialchars($item['id']); ?>"
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
                                            id="total-<?php echo htmlspecialchars($item['id']); ?>"
                                            class="total">₫<?php echo number_format($item['total'], 2); ?></h5>
                                    </td>
                                    <td>
                                        <button class="delete-item btn p-2 m-0">
                                            <i class="bx bx-x-circle"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td></td>
                                <td></td>
                                <td>
                                    <h5>Subtotal</h5>
                                </td>
                                <td>
                                    <h5 id="subtotal">₫<?php echo number_format($subtotal, 2); ?></h5>
                                </td>
                                <td></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">
                                    <p>Your cart is empty!</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if (!empty($cart_with_book_details)): ?>
                    <div class="checkout_btn_inner float-right d-flex justify-content-between">
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

    // Debounce function to prevent multiple rapid clicks
    const debounce = (func, wait) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    };

    const updateCartItem = (cartId, newQuantity, row) => {
        if (pendingRequests[cartId]) {
            console.log(`Request already in progress for cartId: ${cartId}`);
            return;
        }
        pendingRequests[cartId] = true;

        console.log(`Updating item with cartId: ${cartId}, newQuantity: ${newQuantity}`);

        const input = row.querySelector(`#quantity-${cartId}`);
        const totalElement = row.querySelector(`#total-${cartId}`);

        if (!input || !totalElement) {
            console.error(`Input or total element not found for cartId: ${cartId}`);
            delete pendingRequests[cartId];
            return;
        }

        const price = parseFloat(row.querySelector('.price').getAttribute('data-price'));
        const originalQuantity = parseInt(input.value) || 0;

        input.value = newQuantity;
        totalElement.textContent = formatCurrency(price * newQuantity);

        // Recalculate subtotal after updating this item
        recalculateSubtotal();

        // Make PATCH request to update cart
        fetch(`${apiBaseUrl}/cart?action=update-cart`, {
            method: 'PATCH',
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: cartId,
                quantity: newQuantity
            })
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            delete pendingRequests[cartId];
            if (!data.success) {
                throw new Error(data.message || 'Failed to update cart.');
            }
        })
        .catch(error => {
            delete pendingRequests[cartId];
            console.error('Error updating cart:', error.message);
            alert(`Error: ${error.message}`);
            // Revert UI on error
            input.value = originalQuantity;
            totalElement.textContent = formatCurrency(price * originalQuantity);
            recalculateSubtotal();
        });
    };

    const deleteItem = (cartId, row) => {
        if (pendingRequests[cartId]) return;
        pendingRequests[cartId] = true;

        console.log(`Deleting item with cartId: ${cartId}`);

        fetch(`${apiBaseUrl}/cart?action=delete&id=${cartId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            delete pendingRequests[cartId];
            if (data.success) {
                row.remove();
                recalculateSubtotal();
                if (!document.querySelectorAll('tr[data-cart-id]').length) {
                    window.location.reload();
                }
            } else {
                throw new Error(data.message || 'Failed to delete item from cart.');
            }
        })
        .catch(error => {
            delete pendingRequests[cartId];
            console.error('Error deleting item:', error.message);
            alert(`Error: ${error.message}`);
        });
    };

    const addToCartSingleUnit = (cartId, row) => {
        const input = row.querySelector(`#quantity-${cartId}`);
        const currentQuantity = parseInt(input.value) || 0;
        const newQuantity = currentQuantity; // Increment the quantity

        updateCartItem(cartId, newQuantity, row);
    };

    const updateQuantity = (cartId, newQuantity, row) => {
        updateCartItem(cartId, newQuantity, row);
    };

    const recalculateSubtotal = () => {
        let subtotal = 0;
        const rows = document.querySelectorAll('tr[data-cart-id]');

        rows.forEach(row => {
            const cartId = row.getAttribute('data-cart-id');
            const price = parseFloat(row.querySelector('.price').getAttribute('data-price'));
            const quantityInput = row.querySelector(`#quantity-${cartId}`);
            const quantity = parseInt(quantityInput.value) || 0;

            const itemTotal = price * quantity;

            subtotal += itemTotal;
        });


        const subtotalElement = document.getElementById('subtotal');
        if (subtotalElement) {
            subtotalElement.textContent = formatCurrency(subtotal);
        }

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

    // Increment quantity with debouncing
    document.querySelectorAll('.input-number-increment').forEach(button => {
        const debouncedIncrement = debounce(() => {
            const row = button.closest('tr');
            const cartId = row.getAttribute('data-cart-id');
            const input = row.querySelector(`#quantity-${cartId}`);
            const currentQuantity = parseInt(input.value) || 0;
            const maxQuantity = parseInt(input.getAttribute('max')) || 10;


            if (currentQuantity <= maxQuantity) {
                addToCartSingleUnit(cartId, row);
            } else {
                alert(`Maximum quantity (${maxQuantity}) reached.`);
            }
        }, 300); 

        button.addEventListener('click', debouncedIncrement);
    });

    // Decrement quantity
    document.querySelectorAll('.input-number-decrement').forEach(button => {
        button.addEventListener('click', () => {
            const row = button.closest('tr');
            const cartId = row.getAttribute('data-cart-id');
            const input = row.querySelector(`#quantity-${cartId}`);
            const totalElement = row.querySelector(`#total-${cartId}`);

            if (!input || !totalElement) {
                console.error(`Input or total element not found for cartId: ${cartId}`);
                return;
            }

            const currentQuantity = parseInt(input.value) || 0;

            if (currentQuantity >= 1) {
                const newQuantity = currentQuantity;
                updateQuantity(cartId, newQuantity, row);
            } else if (currentQuantity === 1) {
                if (confirm('Remove this item from your cart?')) {
                    deleteItem(cartId, row);
                }
            } else {
                console.log(`Quantity is already 0 for cartId: ${cartId}`);
            }
        });
    });

    // Delete item
    document.querySelectorAll('.delete-item').forEach(button => {
        button.addEventListener('click', () => {
            const row = button.closest('tr');
            const cartId = row.getAttribute('data-cart-id');
            deleteItem(cartId, row);
        });
    });

    // Initial subtotal calculation
    recalculateSubtotal();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main-layout.php';
?>