<?php
$page_title = "Admin Dashboard - Order Details";
$layout = 'admin';
ob_start();

// API base URL and session variables
$api_base_url = $_ENV['API_BASE_URL'];
$access_token = $_SESSION['access_token'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
$is_admin = $_SESSION['is_admin'] ?? false;

// Redirect to login if not authenticated
if (empty($access_token) || empty($user_id)) {
    header("Location: /login?redirect=/admin/order-details");
    exit();
}

if (!$is_admin) {
    header("Location: /?error=" . urlencode("Access denied: Admin privileges required"));
    exit();
}

$order_id = $_GET['id'] ?? '';
if (empty($order_id)) {
    header("Location: /admin/orders?error=" . urlencode("Order ID is required"));
    exit();
}

function call_api($url, $access_token, $method = 'GET') {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token,
        ],
    ]);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);

    if ($curl_error) {
        error_log("cURL Error: $curl_error");
    }

    $data = json_decode($response, true);

    if ($http_code === 401 && isset($data['message']) && stripos($data['message'], 'expired') !== false) {
        session_destroy();
        header("Location: /login?error=expired_token");
        exit;
    }

    return [
        'success' => $http_code === 200 && (isset($data['success']) && $data['success'] || $response === 'Success'),
        'data' => $data['data'] ?? [],
        'message' => $data['message'] ?? "Failed to fetch data. HTTP Code: $http_code" . ($curl_error ? " - cURL Error: $curl_error" : ""),
        'http_code' => $http_code
    ];
}

// Fetch order details from API
$api_url = $api_base_url . "/order?action=get-order&id=" . urlencode($order_id);
$response = call_api($api_url, $access_token);
$order = $response['success'] ? $response['data']['order'] : [];
$order_details = $response['success'] ? ($response['data']['order_details'] ?? []) : [];
$error_message = !$response['success'] ? $response['message'] : ($_GET['error'] ?? '');

// Fetch payment details from API
$payment = [];
$payment_response = call_api($api_base_url . "/payment?action=get-payment-by-order&order_id=" . urlencode($order_id), $access_token);
if ($payment_response['success'] && !empty($payment_response['data'])) {
    $payment = $payment_response['data'];
} else {
    error_log("Failed to fetch payment for order $order_id: " . $payment_response['message']);
}

// Fetch book details for each order detail
$books = [];
foreach ($order_details as &$detail) {
    $book_id = $detail['book_id'];
    if (!isset($books[$book_id])) {
        $book_response = call_api($api_base_url . "/book?action=get-book&id=" . urlencode($book_id), $access_token);
        if ($book_response['success'] && !empty($book_response['data']['book'])) {
            $books[$book_id] = $book_response['data']['book'];
        } else {
            $books[$book_id] = [
                'title' => 'Unknown Book (' . $book_id . ')',
                'image_url' => null
            ];
            error_log("Failed to fetch book $book_id: " . $book_response['message']);
        }
    }
    $detail['book'] = $books[$book_id];
}
unset($detail); 

?>

<div class="container mt-4">
    <h2>Order Details</h2>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if (!empty($order)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h4>Order #<?= htmlspecialchars($order['id']) ?></h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> <?= htmlspecialchars($order['full_name']) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                        <p><strong>Total Price:</strong> <?= number_format((float)$order['total_price'], 0, ',', '.') ?>₫</p>
                        <p><strong>Status:</strong> 
                            <span class="badge <?= $order['status'] === 'Delivered' ? 'bg-success' : ($order['status'] === 'Cancelled' ? 'bg-danger' : 'bg-warning') ?>">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Shipping Address:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                        <p><strong>Payment Method:</strong> <?= htmlspecialchars($payment['payment_method'] ?? 'N/A') ?></p>
                        <p><strong>Payment Status:</strong> 
                            <span class="badge <?= ($payment['status'] ?? '') === 'Paid' ? 'bg-success' : 'bg-warning' ?>">
                                <?= htmlspecialchars($payment['status'] ?? 'N/A') ?>
                            </span>
                        </p>
                        <p><strong>Created At:</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($order['created_at']))) ?></p>
                        <p><strong>Updated At:</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($order['updated_at']))) ?></p>
                    </div>
                </div>

                <!-- Order Details Table -->
                <div class="mt-4">
                    <h5>Order Items</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th style="vertical-align: middle;">#</th>
                                    <th style="vertical-align: middle;">Book</th>
                                    <th style="vertical-align: middle;">Quantity</th>
                                    <th style="vertical-align: middle;">Price</th>
                                    <th style="vertical-align: middle;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($order_details)): ?>
                                    <?php foreach ($order_details as $index => $detail): ?>
                                        <tr>
                                            <td style="vertical-align: middle;"><?= $index + 1 ?></td>
                                            <td style="vertical-align: middle;">
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($detail['book']['image_url'])): ?>
                                                        <img src="<?= htmlspecialchars($detail['book']['image_url']) ?>" 
                                                             alt="<?= htmlspecialchars($detail['book']['title']) ?>" 
                                                             style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">
                                                    <?php else: ?>
                                                        <div style="width: 50px; height: 50px; background: #f0f0f0; margin-right: 10px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #666;">
                                                            No Image
                                                        </div>
                                                    <?php endif; ?>
                                                    <span><?= htmlspecialchars($detail['book']['title'] ?? $detail['book_id']) ?></span>
                                                </div>
                                            </td>
                                            <td style="vertical-align: middle;"><?= htmlspecialchars($detail['quantity']) ?></td>
                                            <td style="vertical-align: middle;"><?= number_format((float)$detail['price'], 0, ',', '.') ?>₫</td>
                                            <td style="vertical-align: middle;"><?= number_format((float)$detail['price'] * (int)$detail['quantity'], 0, ',', '.') ?>₫</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center" style="vertical-align: middle;">No items found in this order.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Actions to update status -->
                <div class="mt-3">
                    <h5>Update Status</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if (strtolower($order['status']) === 'pending'): ?>
                            <button type="button" class="btn btn-sm btn-primary toggle-order-status" 
                                    data-order-id="<?= htmlspecialchars($order['id']) ?>" data-status="Processing">
                                Process
                            </button>
                        <?php elseif (strtolower($order['status']) === 'processing'): ?>
                            <button type="button" class="btn btn-sm btn-info toggle-order-status" 
                                    data-order-id="<?= htmlspecialchars($order['id']) ?>" data-status="Shipped">
                                Ship
                            </button>
                        <?php elseif (strtolower($order['status']) === 'shipped'): ?>
                            <button type="button" class="btn btn-sm btn-success toggle-order-status" 
                                    data-order-id="<?= htmlspecialchars($order['id']) ?>" data-status="Delivered">
                                Deliver
                            </button>
                        <?php endif; ?>
                        <?php if (strtolower($order['status']) !== 'delivered' && strtolower($order['status']) !== 'cancelled'): ?>
                            <button type="button" class="btn btn-sm btn-danger toggle-order-status" 
                                    data-order-id="<?= htmlspecialchars($order['id']) ?>" data-status="Cancelled">
                                Cancel
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-warning">Order not found.</div>
    <?php endif; ?>

    <div class="mt-3">
        <a href="/admin/orders" class="btn btn-secondary">Back to Order List</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.toggle-order-status').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const orderId = this.getAttribute('data-order-id');
            const newStatus = this.getAttribute('data-status');

            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = 'Processing...';

            const confirmMsg = `Are you sure you want to set this order to ${newStatus}?`;
            if (!confirm(confirmMsg)) {
                btn.disabled = false;
                btn.textContent = originalText;
                return;
            }

            const apiUrl = `<?= $api_base_url ?>/order?action=update-status&order_id=${encodeURIComponent(orderId)}&status=${newStatus}`;
            console.log('PATCH URL:', apiUrl);

            try {
                const response = await fetch(apiUrl, {
                    method: 'PATCH',
                    headers: {
                        'Authorization': 'Bearer <?= $_SESSION['access_token'] ?? '' ?>',
                        'Content-Type': 'application/json'
                    },
                });

                const rawResponse = await response.text();
                console.log('Raw Response:', rawResponse);

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status} - ${response.statusText}`);
                }

                let data;
                try {
                    data = JSON.parse(rawResponse);
                } catch (e) {
                    // Handle non-JSON response (e.g., plain text "Success")
                    if (rawResponse.trim().toLowerCase() === 'success') {
                        data = { success: true, message: 'Status updated successfully' };
                    } else {
                        throw new Error('Invalid JSON response: ' + rawResponse);
                    }
                }

                console.log('Parsed Response data:', data);
                if (data.success) {
                    alert(`Order has been updated to ${newStatus} successfully.`);
                    location.reload();
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            } catch (err) {
                console.error('Fetch error:', err);
                alert('Failed to update order status: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin-layout.php';
?>