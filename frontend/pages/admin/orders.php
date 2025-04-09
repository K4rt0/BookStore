<?php
$page_title = "Admin Dashboard - Order Management";
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
    header("Location: /login?redirect=order-list");
    exit();
}

// Check admin role
if (!$is_admin) {
    // Redirect to homepage with error if user is not admin
    header("Location: /?error=" . urlencode("Access denied: Admin privileges required"));
    exit();
}

// Get query parameters from the URL
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$filters = $_GET['filters'] ?? '';
$sort = $_GET['sort'] ?? 'updated_at,desc';
$search = $_GET['search'] ?? '';

// Tạo dữ liệu mẫu (mock data) với các trường mới
$all_orders = [
    [
        'id' => '123e4567-e89b-12d3-a456-426614174000',
        'full_name' => 'Nguyen Van A',
        'phone' => '0901234567',
        'total_price' => 230000.00,
        'status' => 'Pending',
        'shipping_address' => '123 Main Street, District 1, Ho Chi Minh City',
        'payment_method' => 'VNPay',
        'created_at' => '2025-04-07 10:00:00',
        'updated_at' => '2025-04-07 10:00:00'
    ],
    [
        'id' => '987fcdeb-51a2-4f3e-9d8a-7b5c4e2d1f9a',
        'full_name' => 'Tran Thi B',
        'phone' => '0912345678',
        'total_price' => 450000.00,
        'status' => 'Processing',
        'shipping_address' => '456 Oak Avenue, District 3, Ho Chi Minh City',
        'payment_method' => 'COD',
        'created_at' => '2025-04-06 15:30:00',
        'updated_at' => '2025-04-07 09:15:00'
    ],
    [
        'id' => '456abcef-78d4-5e6f-1a2b-3c4d5e6f7a8b',
        'full_name' => 'Le Van C',
        'phone' => '0923456789',
        'total_price' => 120000.00,
        'status' => 'Shipped',
        'shipping_address' => '789 Pine Road, District 7, Ho Chi Minh City',
        'payment_method' => 'VNPay',
        'created_at' => '2025-04-05 08:45:00',
        'updated_at' => '2025-04-06 14:20:00'
    ]
];

// Lọc và sắp xếp dữ liệu mẫu
$orders = $all_orders;

// Lọc theo trạng thái
if (!empty($filters)) {
    $orders = array_filter($orders, function($order) use ($filters) {
        return strtolower($order['status']) === strtolower($filters);
    });
}

// Tìm kiếm theo từ khóa (trong full_name, phone hoặc shipping_address)
if (!empty($search)) {
    $orders = array_filter($orders, function($order) use ($search) {
        return stripos($order['full_name'], $search) !== false || 
               stripos($order['phone'], $search) !== false || 
               stripos($order['shipping_address'], $search) !== false;
    });
}

// Sắp xếp
if (!empty($sort)) {
    list($sort_field, $sort_direction) = explode(',', $sort);
    usort($orders, function($a, $b) use ($sort_field, $sort_direction) {
        if ($sort_direction === 'asc') {
            return $a[$sort_field] <=> $b[$sort_field];
        } else {
            return $b[$sort_field] <=> $a[$sort_field];
        }
    });
}

// Phân trang
$total_orders = count($orders);
$offset = ($page - 1) * $limit;
$orders = array_slice($orders, $offset, $limit);

// Nếu bạn muốn quay lại gọi API, hãy bỏ comment đoạn code dưới đây và xóa mock data ở trên
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

$api_url = $api_base_url . "order?action=get-all-orders-pagination";
$query_params = array_filter([
    'page' => $page,
    'limit' => $limit,
    'filters' => $filters,
    'sort' => $sort,
    'search' => $search
]);
$api_url .= !empty($query_params) ? '&' . http_build_query($query_params) : '';

$response = call_api($api_url);
$orders = ($response && $response['success']) ? ($response['data']['orders'] ?? []) : [];
$total_orders = ($response && $response['success']) ? ($response['data']['total'] ?? 0) : 0;
$error_message = !$response || !$response['success'] ? ($response['message'] ?? 'Failed to fetch order data.') : '';
*/

// Check for error messages from redirect
$error_message = $_GET['error'] ?? '';

?>

<div class="container mt-4 card">
    <h2 class="py-2">Order Management</h2>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search orders (Name, Phone, Address)..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="filters" class="form-control">
                    <option value="">All Status</option>
                    <option value="pending" <?= $filters === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="processing" <?= $filters === 'processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="shipped" <?= $filters === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="delivered" <?= $filters === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="cancelled" <?= $filters === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-control">
                    <option value="updated_at,desc" <?= $sort === 'updated_at,desc' ? 'selected' : '' ?>>Updated At (Desc)</option>
                    <option value="updated_at,asc" <?= $sort === 'updated_at,asc' ? 'selected' : '' ?>>Updated At (Asc)</option>
                    <option value="created_at,desc" <?= $sort === 'created_at,desc' ? 'selected' : '' ?>>Created At (Desc)</option>
                    <option value="created_at,asc" <?= $sort === 'created_at,asc' ? 'selected' : '' ?>>Created At (Asc)</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Apply</button>
            </div>
        </div>
        <input type="hidden" name="page" value="1">
        <input type="hidden" name="limit" value="<?= $limit ?>">
    </form>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Total Price (VND)</th>
                <th>Status</th>
                <th>Shipping Address</th>
                <th>Payment Method</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><span title="<?= htmlspecialchars($order['id']) ?>"><?= htmlspecialchars(substr($order['id'], 0, 16)) ?>...</span></td>
                        <td><?= htmlspecialchars($order['full_name']) ?></td>
                        <td><?= htmlspecialchars($order['phone']) ?></td>
                        <td><?= number_format($order['total_price'], 0, ',', '.'); ?></td>
                        <td>
                            <span class="badge <?= $order['status'] === 'Delivered' ? 'bg-success' : ($order['status'] === 'Cancelled' ? 'bg-danger' : 'bg-warning') ?>">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($order['shipping_address']) ?></td>
                        <td><?= htmlspecialchars($order['payment_method']) ?></td>
                        <td><?= htmlspecialchars(substr($order['created_at'], 0, 10)) ?></td>
                        <td><?= htmlspecialchars(substr($order['updated_at'], 0, 10)) ?></td>
                        <td class="d-flex align-items-center gap-2 flex-wrap">
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
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="10" class="text-center">No orders found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_orders > $limit): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>&filters=<?= $filters ?>&sort=<?= $sort ?>&search=<?= $search ?>">Previous</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>&filters=<?= $filters ?>&sort=<?= $sort ?>&search=<?= $search ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
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
                        'Content-Type': 'application/json'
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