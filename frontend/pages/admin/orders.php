<?php
session_start();

$page_title = "Order Management";
$layout = 'admin';
ob_start();

// Load environment variables
$base_url = $_ENV['API_BASE_URL']; // Fallback if env not loaded
error_log("Base URL: $base_url");

// Define session variables
$access_token = $_SESSION['access_token'] ?? '';
$user_id = $_SESSION['user_id'] ?? '';
$is_admin = $_SESSION['is_admin'] ?? false;

// Redirect to login if not authenticated
if (empty($access_token) || empty($user_id)) {
    header("Location: /login?redirect=/admin/orders");
    exit();
}

// Check admin role (optional, remove if not needed)
if (!$is_admin) {
    header("Location: /?error=" . urlencode("Access denied: Admin privileges required"));
    exit();
}

// Get query parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10; // Display 10 orders per page
$filters = $_GET['filters'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$search = $_GET['search'] ?? '';

// Function to call API with error handling
function fetch_orders($base_url, $page, $limit, $filters, $sort, $search, $access_token) {
    // Adjust this endpoint if needed (e.g., change 'get-all-orders-pagination' to 'get-all-orders')
    $api_url = $base_url . "/order?action=get-all-orders-pagination&page=$page&limit=$limit" .
               ($filters ? "&filters=" . urlencode($filters) : "") .
               "&sort=" . urlencode($sort) .
               ($search ? "&search=" . urlencode($search) : "");

    $headers = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Log for debugging
    error_log("API Request URL: $api_url");
    error_log("Access Token: " . $access_token);
    error_log("HTTP Code: $http_code");
    error_log("Raw Response: " . ($response ?: 'No response'));
    if ($curl_error) {
        error_log("cURL Error: $curl_error");
    }

    $orders_data = json_decode($response, true);

    if ($http_code === 404) {
        return [
            'success' => false,
            'message' => "API endpoint not found (404). Check if '$api_url' is correct.",
            'orders' => [],
            'total' => 0
        ];
    }

    if ($http_code !== 200 || !isset($orders_data['success']) || !$orders_data['success']) {
        return [
            'success' => false,
            'message' => $orders_data['message'] ?? "Failed to fetch orders. HTTP Code: $http_code" . ($curl_error ? " - cURL Error: $curl_error" : ""),
            'orders' => [],
            'total' => 0
        ];
    }

    return [
        'success' => true,
        'message' => $orders_data['message'] ?? 'Success',
        'orders' => $orders_data['data']['orders'] ?? [],
        'total' => $orders_data['data']['total'] ?? count($orders_data['data']['orders'] ?? [])
    ];
}

// Fetch orders
$result = fetch_orders($base_url, $page, $limit, $filters, $sort, $search, $access_token);
$orders = $result['orders'];
$total_orders = $result['total'];
$error_message = !$result['success'] ? $result['message'] : '';

// Log pagination details
error_log("Total orders: $total_orders, Limit: $limit, Page: $page, Orders returned: " . count($orders));
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Order List</h4>
                </div>
                <div class="card-body">
                    <!-- Filter Form -->
                    <form id="order-filter-form" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <select class="form-select" name="filters" id="status-filter">
                                    <option value="">All Status</option>
                                    <option value="Pending" <?= $filters === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="Processing" <?= $filters === 'Processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="Shipped" <?= $filters === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="Delivered" <?= $filters === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="Cancelled" <?= $filters === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="sort" id="sort-filter">
                                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="search" id="search-filter" placeholder="Search orders..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </div>
                    </form>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-warning">
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php elseif (empty($orders)): ?>
                        <div class="alert alert-info">
                            No orders found matching your criteria.
                        </div>
                    <?php else: ?>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Total Price</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><span title="<?= htmlspecialchars($order['id']) ?>"><?= htmlspecialchars(substr($order['id'], 0, 8)) ?>...</span></td>
                                        <td><?= htmlspecialchars($order['full_name']) ?></td>
                                        <td>$<?= number_format($order['total_price'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $order['status'] === 'Delivered' ? 'success' : ($order['status'] === 'Pending' ? 'warning' : 'info') ?>">
                                                <?= htmlspecialchars($order['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
                                        <td>
                                            <a href="/admin/order-details?id=<?= urlencode($order['id']) ?>" class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <?php
                        $total_pages = ceil($total_orders / $limit);
                        if ($total_pages > 1):
                        ?>
                            <nav aria-label="Orders pagination">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="/admin/orders?page=<?= $page - 1 ?>&filters=<?= urlencode($filters) ?>&sort=<?= urlencode($sort) ?>&search=<?= urlencode($search) ?>">Previous</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="/admin/orders?page=<?= $i ?>&filters=<?= urlencode($filters) ?>&sort=<?= urlencode($sort) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="/admin/orders?page=<?= $page + 1 ?>&filters=<?= urlencode($filters) ?>&sort=<?= urlencode($sort) ?>&search=<?= urlencode($search) ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php else: ?>
                            <?php error_log("Pagination not shown because total_pages ($total_pages) <= 1"); ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('order-filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const filters = document.getElementById('status-filter').value;
            const sort = document.getElementById('sort-filter').value;
            const search = document.getElementById('search-filter').value;

            const url = new URL(window.location);
            url.pathname = '/admin/orders';
            url.searchParams.set('page', '1');
            url.searchParams.set('filters', filters);
            url.searchParams.set('sort', sort);
            url.searchParams.set('search', search);
            window.location.href = url.toString();
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin-layout.php';
?>