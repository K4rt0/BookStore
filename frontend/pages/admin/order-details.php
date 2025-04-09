<?php
$page_title = "Admin Dashboard - Order Details";
$layout = 'admin';
ob_start();

// Start session
session_start();

// API base URL and session variables
$api_base_url = $_ENV['API_BASE_URL'];
$access_token = $_SESSION['access_token'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
$is_admin = $_SESSION['is_admin'] ?? false; // Lấy giá trị is_admin từ session

// Redirect to login if not authenticated
if (empty($access_token) || empty($user_id)) {
    header("Location: /login?redirect=order-details");
    exit();
}

// Check admin role
if (!$is_admin) {
    // Redirect to homepage with error if user is not admin
    header("Location: /?error=" . urlencode("Access denied: Admin privileges required"));
    exit();
}

// Get order_id from URL
$order_id = $_GET['id'] ?? '';
if (empty($order_id)) {
    header("Location: /admin/orders?error=" . urlencode("Order ID is required"));
    exit();
}

// Tạo dữ liệu mẫu (mock data) cho đơn hàng và danh sách sản phẩm từ bảng order_details
$order = [
    'id' => $order_id,
    'full_name' => 'Nguyen Van A',
    'phone' => '0901234567',
    'total_price' => 230000.00,
    'status' => 'Pending',
    'shipping_address' => '123 Main Street, District 1, Ho Chi Minh City',
    'payment_method' => 'VNPay',
    'created_at' => '2025-04-07 10:00:00',
    'updated_at' => '2025-04-07 10:00:00',
    'order_details' => [ // Đổi tên key từ 'items' thành 'order_details' để phản ánh tên bảng
        [
            'id' => 'detail1-uuid',
            'order_id' => $order_id,
            'book_id' => 'book1-uuid',
            'book_title' => 'Book Title 1',
            'quantity' => 2,
            'price' => 100000.00,
            'total' => 200000.00,
            'created_at' => '2025-04-07 10:00:00',
            'updated_at' => '2025-04-07 10:00:00'
        ],
        [
            'id' => 'detail2-uuid',
            'order_id' => $order_id,
            'book_id' => 'book2-uuid',
            'book_title' => 'Book Title 2',
            'quantity' => 1,
            'price' => 30000.00,
            'total' => 30000.00,
            'created_at' => '2025-04-07 10:00:00',
            'updated_at' => '2025-04-07 10:00:00'
        ]
    ]
];

// Nếu bạn muốn gọi API, hãy bỏ comment đoạn code dưới đây và xóa mock data ở trên
/*
function call_api($url) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: ' . 'Bearer ' . ($_SESSION['access_token'] ?? ''),
        ],
    ]);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $data = json_decode($response, true);

    if ($http_code === 401 && isset($data['message']) && stripos($data['message'], 'expired') !== false) {
        session_destroy();
        header("Location: /login?error=expired_token");
        exit;
    }

    return $data;
}

$api_url = $api_base_url . "order?action=get-order-details&id=" . urlencode($order_id);
$response = call_api($api_url);
$order = ($response && $response['success']) ? ($response['data'] ?? []) : [];
$error_message = !$response || !$response['success'] ? ($response['message'] ?? 'Failed to fetch order details.') : '';
*/

// Check for error messages from redirect
$error_message = $_GET['error'] ?? '';
if (empty($order)) {
    $error_message = 'Order not found.';
}

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
                        <p><strong>Total Price:</strong> <?= number_format($order['total_price'], 0, ',', '.') ?> VND</p>
                        <p><strong>Status:</strong> 
                            <span class="badge <?= $order['status'] === 'Delivered' ? 'bg-success' : ($order['status'] === 'Cancelled' ? 'bg-danger' : 'bg-warning') ?>">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Shipping Address:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                        <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
                        <p><strong>Created At:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
                        <p><strong>Updated At:</strong> <?= htmlspecialchars($order['updated_at']) ?></p>
                    </div>
                </div>

                <!-- Actions to update status -->
                <div class="mt-3">
                    <h5>Update Status</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if (strtolower($order['status']) === 'pending'): ?>
                            <button type="button" class="btn btn-sm btn-primary toggle-order-status" 
                                    data-order-id="<?= htmlspecialchars($order['id']) ?>" data-status="processing">
                                Process
                            </button>
                        <?php elseif (strtolower($order['status']) === 'processing'): ?>
                            <button type="button" class="btn btn-sm btn-info toggle-order-status" 
                                    data-order-id="<?= htmlspecialchars($order['id']) ?>" data-status="shipped">
                                Ship
                            </button>
                        <?php elseif (strtolower($order['status']) === 'shipped'): ?>
                            <button type="button" class="btn btn-sm btn-success toggle-order-status" 
                                    data-order-id="<?= htmlspecialchars($order['id']) ?>" data-status="delivered">
                                Deliver
                            </button>
                        <?php endif; ?>
                        <?php if (strtolower($order['status']) !== 'delivered' && strtolower($order['status']) !== 'cancelled'): ?>
                            <button type="button" class="btn btn-sm btn-danger toggle-order-status" 
                                    data-order-id="<?= htmlspecialchars($order['id']) ?>" data-status="cancelled">
                                Cancel
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Details (Products in the Order) -->
        <div class="card">
            <div class="card-header">
                <h4>Order Products</h4>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Quantity</th>
                            <th>Price (VND)</th>
                            <th>Total (VND)</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($order['order_details'])): ?>
                            <?php foreach ($order['order_details'] as $detail): ?>
                                <tr>
                                    <td><?= htmlspecialchars($detail['book_title']) ?></td>
                                    <td><?= htmlspecialchars($detail['quantity']) ?></td>
                                    <td><?= number_format($detail['price'], 0, ',', '.') ?></td>
                                    <td><?= number_format($detail['total'], 0, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($detail['created_at']) ?></td>
                                    <td><?= htmlspecialchars($detail['updated_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No products found in this order.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
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

            const apiUrl = `<?= $api_base_url ?>order?action=update-status&id=${encodeURIComponent(orderId)}&status=${newStatus}`;

            try {
                const response = await fetch(apiUrl, {
                    method: 'PATCH',
                    headers: {
                        'Authorization': 'Bearer <?= $_SESSION['access_token'] ?? '' ?>',
                        'Content-Type: 'application/json'
                    },
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status} - ${response.statusText}`);
                }

                const data = await response.json();
                if (data.success) {
                    alert(`Order has been updated to ${newStatus} successfully.`);
                    location.reload();
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            } catch (err) {
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