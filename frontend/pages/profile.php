<?php
require_once __DIR__ . '/../includes/env-loader.php';

// Kiểm tra session để đảm bảo người dùng đã đăng nhập
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: /login?redirect=profile");
    exit;
}

$page_title = "Book Shop - Profile";
$layout = 'main-layout';
$api_base_url = $_ENV['API_BASE_URL'];

// Lấy thông tin từ session
$username = $_SESSION['username'] ?? 'N/A';
$user_id = $_SESSION['user_id'] ?? 'N/A';
$email = $_SESSION['email'] ?? 'N/A';
$phone = $_SESSION['phone'] ?? 'N/A';
$profile_picture = $_SESSION['profile_picture'] ?? '/assets/img/default-avatar.png';
$join_date = $_SESSION['created_at'] ?? 'April 2025';

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'orders';

$update_error = '';
$update_success = '';
$password_error = '';
$password_success = '';
$cancel_success = '';
$cancel_error = '';

// Hàm lấy chi tiết đơn hàng (di chuyển lên đầu để dùng ở cả Orders và Order Details)
function fetchOrderDetails($api_base_url, $order_id, $access_token) {
    $query_params = http_build_query([
        'action' => 'get-order',
        'id' => $order_id
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$api_base_url/order?$query_params");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);
    if ($http_status !== 200 || !$result['success']) {
        error_log("API Error (fetchOrderDetails): HTTP $http_status - " . ($result['message'] ?? 'Unknown error'));
        return ['success' => false, 'data' => null];
    }
    error_log("API Response (fetchOrderDetails): " . json_encode($result));
    return $result;
}

// Xử lý cập nhật thông tin cá nhân
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $active_tab === 'personal') {
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';

    $postData = json_encode([
        'full_name' => $full_name,
        'phone' => $phone
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$api_base_url/users?action=update-profile");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $_SESSION['access_token'],
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ]);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($http_status === 200 && $result['success']) {
        $_SESSION['username'] = $full_name;
        $_SESSION['phone'] = $phone;
        $username = $full_name;
        $phone = $phone;
        $update_success = 'Profile updated successfully!';
    } else {
        $update_error = $result['message'] ?? 'Failed to update profile. Please try again.';
    }
}

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $active_tab === 'password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $password_error = 'New password and confirm password do not match.';
    } else {
        $postData = json_encode([
            'old_password' => $current_password,
            'new_password' => $new_password,
            'confirm_password' => $confirm_password
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$api_base_url/users?action=change-password");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $_SESSION['access_token'],
            'Content-Type: application/json',
            'Content-Length: ' . strlen($postData)
        ]);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($http_status === 200 && $result['success']) {
            $password_success = 'Password updated successfully!';
        } else {
            $password_error = $result['message'] ?? 'Failed to update password. Please try again.';
        }
    }
}

// Xử lý hủy đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $order_id = $_POST['cancel_order_id'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$api_base_url/order?action=cancel-order&order_id=" . urlencode($order_id));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $_SESSION['access_token'],
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([]));

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($http_status === 200 && $result['success']) {
        $cancel_success = 'Order canceled successfully!';
    } else {
        $cancel_error = $result['message'] ?? 'Failed to cancel order. Please try again.';
    }
}

ob_start();
?>
<link rel="stylesheet" href="/assets/css/template/profile.css">

<div class="profile-area">
    <div class="container">
        <div class="profile-header-container mb-5">
            <div class="profile-header-content d-flex align-items-center">
                <div class="profile-avatar">
                    <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Profile Picture">
                    <div class="avatar-edit">
                        <button class="btn btn-sm btn-light rounded-circle">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                </div>
                <div class="profile-info">
                    <h2><?= htmlspecialchars($username) ?></h2>
                    <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($email) ?></p>
                    <p><i class="fas fa-phone"></i> <?= htmlspecialchars($phone) ?></p>
                    <p><i class="fas fa-calendar-alt"></i> Member since <?= htmlspecialchars($join_date) ?></p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 mb-4">
                <div class="profile-sidebar sticky-top">
                    <div class="profile-nav">
                        <div class="nav-header">
                            <i class="fas fa-user-circle"></i> Account Management
                        </div>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?= $active_tab == 'orders' ? 'active' : '' ?>" href="/profile?tab=orders">
                                    <i class="fas fa-shopping-bag"></i> My Orders
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $active_tab == 'wishlist' ? 'active' : '' ?>" href="/profile?tab=wishlist">
                                    <i class="fas fa-heart"></i> Wishlist
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $active_tab == 'reviews' ? 'active' : '' ?>" href="/profile?tab=reviews">
                                    <i class="fas fa-star"></i> My Reviews
                                </a>
                            </li>
                        </ul>
                        <div class="nav-header mt-4">
                            <i class="fas fa-cog"></i> Settings
                        </div>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?= $active_tab == 'personal' ? 'active' : '' ?>" href="/profile?tab=personal">
                                    <i class="fas fa-user-edit"></i> Personal Info
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $active_tab == 'password' ? 'active' : '' ?>" href="/profile?tab=password">
                                    <i class="fas fa-lock"></i> Change Password
                                </a>
                            </li>
                        </ul>
                        <div class="nav-footer mt-4">
                            <a class="nav-link text-danger" href="/logout">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <?php if ($active_tab == 'orders' || $active_tab == ''): ?>
                    <div class="tab-content active" id="orders">
                        <div class="card profile-card">
                            <div class="card-header">
                                <h5><i class="fas fa-shopping-bag"></i> My Orders</h5>
                            </div>
                            <div class="card-body">
                                <!-- Hiển thị thông báo hủy đơn hàng -->
                                <?php if ($cancel_success): ?>
                                    <div class="message-success"><?= htmlspecialchars($cancel_success) ?></div>
                                <?php endif; ?>
                                <?php if ($cancel_error): ?>
                                    <div class="message-error"><?= htmlspecialchars($cancel_error) ?></div>
                                <?php endif; ?>

                                <!-- Form lọc đơn hàng -->
                                <form id="order-filter-form" class="mb-4">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <select class="form-select" name="status" id="status-filter">
                                                <option value="">All Status</option>
                                                <option value="Pending" <?= isset($_GET['status']) && $_GET['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="Processing" <?= isset($_GET['status']) && $_GET['status'] === 'Processing' ? 'selected' : '' ?>>Processing</option>
                                                <option value="Shipped" <?= isset($_GET['status']) && $_GET['status'] === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                                <option value="Delivered" <?= isset($_GET['status']) && $_GET['status'] === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                                <option value="Cancelled" <?= isset($_GET['status']) && $_GET['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select" name="payment_status" id="payment-filter">
                                                <option value="">All Payment Status</option>
                                                <option value="Paid" <?= isset($_GET['payment_status']) && $_GET['payment_status'] === 'Paid' ? 'selected' : '' ?>>Paid</option>
                                                <option value="Pending" <?= isset($_GET['payment_status']) && $_GET['payment_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="Failed" <?= isset($_GET['payment_status']) && $_GET['payment_status'] === 'Failed' ? 'selected' : '' ?>>Failed</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control" name="search" id="search-filter" placeholder="Search orders..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                                        </div>
                                    </div>
                                </form>

                                <!-- Danh sách đơn hàng -->
                                <div id="orders-list">
                                    <?php
                                    // Hàm lấy danh sách đơn hàng (phân trang)
                                    function fetchOrders($api_base_url, $user_id, $access_token, $page = 1, $limit = 5, $status = '', $payment_status = '', $search = '') {
                                        $query_params = http_build_query([
                                            'action' => 'get-all-my-orders-pagination',
                                            'page' => $page,
                                            'limit' => $limit,
                                            'status' => $status,
                                            'payment_status' => $payment_status,
                                            'search' => $search,
                                            'sort' => 'newest'
                                        ]);

                                        $ch = curl_init();
                                        curl_setopt($ch, CURLOPT_URL, "$api_base_url/order?$query_params");
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                            'Authorization: Bearer ' . $access_token,
                                            'Content-Type: application/json'
                                        ]);

                                        $response = curl_exec($ch);
                                        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                        curl_close($ch);

                                        $result = json_decode($response, true);
                                        if ($http_status !== 200 || !$result['success']) {
                                            error_log("API Error (fetchOrders): HTTP $http_status - " . ($result['message'] ?? 'Unknown error'));
                                            return ['success' => false, 'data' => ['orders' => [], 'total' => 0]];
                                        }
                                        error_log("API Response (fetchOrders): " . json_encode($result));
                                        return $result;
                                    }

                                    $status = $_GET['status'] ?? '';
                                    $payment_status = $_GET['payment_status'] ?? '';
                                    $search = $_GET['search'] ?? '';
                                    $page = max(1, (int)($_GET['page'] ?? 1));
                                    $limit = 5;

                                    $orders_data = fetchOrders($api_base_url, $user_id, $_SESSION['access_token'], $page, $limit, $status, $payment_status, $search);

                                    if ($orders_data['success'] && !empty($orders_data['data']['orders'])) {
                                        foreach ($orders_data['data']['orders'] as $order) {
                                            // Kiểm tra xem đơn hàng có thể hủy được không
                                            $can_cancel = in_array($order['status'], ['Pending', 'Processing']);

                                            // Lấy chi tiết đơn hàng để kiểm tra trạng thái đánh giá
                                            $review_status = '';
                                            $review_badge_class = '';
                                            if ($order['status'] === 'Delivered') {
                                                $order_details_data = fetchOrderDetails($api_base_url, $order['id'], $_SESSION['access_token']);
                                                if ($order_details_data['success'] && !empty($order_details_data['data']['order_details'])) {
                                                    $order_details = $order_details_data['data']['order_details'];
                                                    $total_items = count($order_details);
                                                    $reviewed_items = 0;

                                                    foreach ($order_details as $detail) {
                                                        if (($detail['is_commented'] ?? 0) == 1) {
                                                            $reviewed_items++;
                                                        }
                                                    }

                                                    if ($reviewed_items == 0) {
                                                        $review_status = 'Not Reviewed';
                                                        $review_badge_class = 'warning';
                                                    } elseif ($reviewed_items == $total_items) {
                                                        $review_status = 'Reviewed';
                                                        $review_badge_class = 'success';
                                                    } else {
                                                        $review_status = 'Partially Reviewed';
                                                        $review_badge_class = 'info';
                                                    }
                                                } else {
                                                    $review_status = 'No Items';
                                                    $review_badge_class = 'secondary';
                                                }
                                            } else {
                                                $review_status = 'Pending Review';
                                                $review_badge_class = 'secondary';
                                            }
                                    ?>
                                        <div class="order-item mb-3 p-3 border rounded">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <p><strong>Order ID:</strong> <?= htmlspecialchars($order['id']) ?></p>
                                                </div>
                                                <div class="col-md-3">
                                                    <p><strong>Status:</strong> 
                                                        <span class="badge bg-<?= $order['status'] === 'Delivered' ? 'success' : ($order['status'] === 'Pending' ? 'warning' : ($order['status'] === 'Cancelled' ? 'danger' : 'info')) ?>">
                                                            <?= htmlspecialchars($order['status']) ?>
                                                        </span>
                                                    </p>
                                                    <p><strong></strong> 
                                                        <span class="badge bg-<?= $review_badge_class ?>">
                                                            <?= htmlspecialchars($review_status) ?>
                                                        </span>
                                                    </p>
                                                    <p><strong>Total:</strong> $<?= number_format($order['total_price'], 2) ?></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <p><strong>Shipping to:</strong> <?= htmlspecialchars($order['full_name']) ?></p>
                                                    <p><?= htmlspecialchars($order['shipping_address']) ?></p>
                                                </div>
                                                <div class="col-md-2 text-end">
                                                    <a href="/profile?tab=order-details&id=<?= htmlspecialchars($order['id']) ?>" class="btn btn-sm px-4 py-3 bg-secondary">View</a>
                                                    <?php if ($can_cancel): ?>
                                                        <button class="btn btn-sm btn-outline-danger mt-2 px-4 py-3 cancel-order-btn" data-order-id="<?= htmlspecialchars($order['id']) ?>">Cancel</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php
                                        }
                                        // Phân trang
                                        $total_orders = isset($orders_data['data']['total']) && $orders_data['data']['total'] > 0 ? $orders_data['data']['total'] : count($orders_data['data']['orders']);
                                        if (count($orders_data['data']['orders']) == $limit && !isset($orders_data['data']['total'])) {
                                            $total_orders += $limit;
                                        }
                                        $total_pages = ceil($total_orders / $limit);

                                        error_log("Total orders: $total_orders, Limit: $limit, Total pages: $total_pages, Current page: $page, Orders returned: " . count($orders_data['data']['orders']));

                                        if ($total_pages > 1) {
                                    ?>
                                        <nav aria-label="Orders pagination">
                                            <ul class="pagination justify-content-center">
                                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="/profile?tab=orders&page=<?= $page - 1 ?>&status=<?= urlencode($status) ?>&payment_status=<?= urlencode($payment_status) ?>&search=<?= urlencode($search) ?>">Previous</a>
                                                </li>
                                                <?php
                                                $start_page = max(1, $page - 2);
                                                $end_page = min($total_pages, $page + 2);
                                                if ($end_page - $start_page < 4) {
                                                    $start_page = max(1, $end_page - 4);
                                                }
                                                for ($i = $start_page; $i <= $end_page; $i++):
                                                ?>
                                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                        <a class="page-link" href="/profile?tab=orders&page=<?= $i ?>&status=<?= urlencode($status) ?>&payment_status=<?= urlencode($payment_status) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                                    </li>
                                                <?php endfor; ?>
                                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="/profile?tab=orders&page=<?= $page + 1 ?>&status=<?= urlencode($status) ?>&payment_status=<?= urlencode($payment_status) ?>&search=<?= urlencode($search) ?>">Next</a>
                                                </li>
                                            </ul>
                                        </nav>
                                    <?php
                                        } else {
                                            error_log("Pagination not shown because total_pages ($total_pages) <= 1");
                                        }
                                    } else {
                                    ?>
                                        <div class="empty-state">
                                            <img src="/assets/img/empty-orders.svg" alt="No orders" class="empty-icon">
                                            <p>No orders found matching your criteria</p>
                                            <a href="/books" class="btn btn-primary mt-3">Explore Books</a>
                                        </div>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($active_tab == 'order-details'): ?>
                    <div class="tab-content active" id="order-details">
                        <div class="card profile-card">
                            <div class="card-header">
                                <h5><i class="fas fa-file-invoice"></i> Order Details</h5>
                                <a href="/profile?tab=orders" class="btn btn-sm btn-outline-secondary float-end">Back to Orders</a>
                            </div>
                            <div class="card-body">
                                <?php
                                // Hàm lấy thông tin sách
                                function fetchBookDetails($api_base_url, $book_id, $access_token) {
                                    $query_params = http_build_query([
                                        'action' => 'get-book',
                                        'id' => $book_id
                                    ]);

                                    $ch = curl_init();
                                    curl_setopt($ch, CURLOPT_URL, "$api_base_url/book?$query_params");
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                        'Authorization: Bearer ' . $access_token,
                                        'Content-Type: application/json'
                                    ]);

                                    $response = curl_exec($ch);
                                    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                    curl_close($ch);

                                    $result = json_decode($response, true);
                                    if ($http_status !== 200 || !$result['success']) {
                                        error_log("API Error (fetchBookDetails): HTTP $http_status - " . ($result['message'] ?? 'Unknown error'));
                                        return ['success' => false, 'data' => null];
                                    }
                                    error_log("API Response (fetchBookDetails): " . json_encode($result));
                                    return $result;
                                }

                                $order_id = $_GET['id'] ?? '';
                                if (empty($order_id)) {
                                    echo '<div class="message-error">No order ID provided.</div>';
                                } else {
                                    $order_data = fetchOrderDetails($api_base_url, $order_id, $_SESSION['access_token']);
                                    if ($order_data['success'] && !empty($order_data['data'])) {
                                        $order = $order_data['data']['order'];
                                        $order_details = $order_data['data']['order_details'] ?? [];

                                        // Lấy thông tin sách cho mỗi mục trong order_details
                                        $books = [];
                                        foreach ($order_details as &$detail) {
                                            $book_id = $detail['book_id'];
                                            if (!isset($books[$book_id])) {
                                                $book_response = fetchBookDetails($api_base_url, $book_id, $_SESSION['access_token']);
                                                if ($book_response['success'] && !empty($book_response['data']['book'])) {
                                                    $books[$book_id] = $book_response['data']['book'];
                                                } else {
                                                    $books[$book_id] = [
                                                        'title' => 'Unknown Book (' . $book_id . ')',
                                                        'image_url' => null
                                                    ];
                                                    error_log("Failed to fetch book $book_id: " . ($book_response['message'] ?? 'Unknown error'));
                                                }
                                            }
                                            $detail['book'] = $books[$book_id];
                                        }
                                        unset($detail); // Unset reference để tránh lỗi
                                ?>
                                    <div class="order-details">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <p><strong>Order ID:</strong> <?= htmlspecialchars($order['id']) ?></p>
                                                <p><strong>Full Name:</strong> <?= htmlspecialchars($order['full_name']) ?></p>
                                                <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                                                <p><strong>Shipping Address:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Total Price:</strong> $<?= number_format($order['total_price'], 2) ?></p>
                                                <p><strong>Status:</strong> 
                                                    <span class="badge bg-<?= $order['status'] === 'Delivered' ? 'success' : ($order['status'] === 'Pending' ? 'warning' : ($order['status'] === 'Cancelled' ? 'danger' : 'info')) ?>">
                                                        <?= htmlspecialchars($order['status']) ?>
                                                    </span>
                                                </p>
                                                <p><strong>Created At:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
                                                <p><strong>Updated At:</strong> <?= htmlspecialchars($order['updated_at']) ?></p>
                                            </div>
                                        </div>

                                        <!-- Bảng danh sách sách trong đơn hàng -->
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
                                                            <?php if ($order['status'] === 'Delivered'): ?>
                                                                <th style="vertical-align: middle;">Action</th>
                                                            <?php endif; ?>
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
                                                                    <td style="vertical-align: middle;">$<?= number_format((float)$detail['price'], 2) ?></td>
                                                                    <td style="vertical-align: middle;">$<?= number_format((float)$detail['price'] * (int)$detail['quantity'], 2) ?></td>
                                                                    <?php if ($order['status'] === 'Delivered'): ?>
                                                                        <td style="vertical-align: middle;">
                                                                            <?php if (($detail['is_commented'] ?? 0) == 0): ?>
                                                                                <button type="button" class="btn btn-sm btn-primary review-btn" 
                                                                                        data-bs-toggle="modal" 
                                                                                        data-bs-target="#reviewModal"
                                                                                        data-order-id="<?= htmlspecialchars($order['id']) ?>"
                                                                                        data-book-id="<?= htmlspecialchars($detail['book_id']) ?>"
                                                                                        data-book-title="<?= htmlspecialchars($detail['book']['title'] ?? $detail['book_id']) ?>">
                                                                                    Review
                                                                                </button>
                                                                            <?php else: ?>
                                                                                <span class="text-muted">Reviewed</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    <?php endif; ?>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <tr>
                                                                <td colspan="<?= $order['status'] === 'Delivered' ? 6 : 5 ?>" class="text-center" style="vertical-align: middle;">No items found in this order.</td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal để nhập đánh giá -->
                                    <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="reviewModalLabel">Review Book</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form id="reviewForm">
                                                        <input type="hidden" id="reviewOrderId" name="order_id">
                                                        <input type="hidden" id="reviewBookId" name="book_id">
                                                        <div class="mb-3">
                                                            <label class="form-label">Book</label>
                                                            <p id="reviewBookTitle" class="form-control-plaintext"></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="reviewRating" class="form-label">Rating (1-5)</label>
                                                            <select class="form-select" id="reviewRating" name="rate" required>
                                                                <option value="">Select rating</option>
                                                                <option value="1">1 - Poor</option>
                                                                <option value="2">2 - Fair</option>
                                                                <option value="3">3 - Good</option>
                                                                <option value="4">4 - Very Good</option>
                                                                <option value="5">5 - Excellent</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="reviewComment" class="form-label">Comment</label>
                                                            <textarea class="form-control" id="reviewComment" name="comment" rows="3" required></textarea>
                                                        </div>
                                                    </form>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="button" class="btn btn-primary" id="submitReviewBtn">Submit Review</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                    } else {
                                        echo '<div class="message-error">Failed to load order details. Please try again.</div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php elseif ($active_tab == 'wishlist'): ?>
                    <div class="tab-content active" id="wishlist">
                        <div class="card profile-card">
                            <div class="card-header">
                                <h5><i class="fas fa-heart"></i> My Wishlist</h5>
                            </div>
                            <div class="card-body">
                                <div class="empty-state">
                                    <img src="/assets/img/empty-wishlist.svg" alt="No wishlist items" class="empty-icon">
                                    <p>Your wishlist is empty</p>
                                    <a href="/books" class="btn btn-primary mt-3">Browse Books</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($active_tab == 'reviews'): ?>
                    <div class="tab-content active" id="reviews">
                        <div class="card profile-card">
                            <div class="card-header">
                                <h5><i class="fas fa-star"></i> My Reviews</h5>
                            </div>
                            <div class="card-body">
                                <div class="empty-state">
                                    <img src="/assets/img/empty-reviews.svg" alt="No reviews" class="empty-icon">
                                    <p>You haven't written any reviews yet</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($active_tab == 'personal'): ?>
                    <div class="tab-content active" id="personal">
                        <div class="card profile-card">
                            <div class="card-header">
                                <h5><i class="fas fa-user-edit"></i> Personal Information</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($update_success): ?>
                                    <div class="message-success"><?= htmlspecialchars($update_success) ?></div>
                                <?php endif; ?>
                                <?php if ($update_error): ?>
                                    <div class="message-error"><?= htmlspecialchars($update_error) ?></div>
                                <?php endif; ?>
                                <form id="personal-info-form" method="POST">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($username) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($email) ?>" readonly>
                                        <small class="text-muted">Contact support to change your email</small>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php elseif ($active_tab == 'password'): ?>
                    <div class="tab-content active" id="password">
                        <div class="card profile-card">
                            <div class="card-header">
                                <h5><i class="fas fa-lock"></i> Change Password</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($password_success): ?>
                                    <div class="message-success"><?= htmlspecialchars($password_success) ?></div>
                                <?php endif; ?>
                                <?php if ($password_error): ?>
                                    <div class="message-error"><?= htmlspecialchars($password_error) ?></div>
                                <?php endif; ?>
                                <form id="password-change-form" method="POST">
                                    <div class="mb-3">
                                        <label for="current-password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current-password" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new-password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new-password" name="new_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm-password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm-password" name="confirm_password" required>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">Update Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý form lọc đơn hàng
    const filterForm = document.getElementById('order-filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const status = document.getElementById('status-filter').value;
            const paymentStatus = document.getElementById('payment-filter').value;
            const search = document.getElementById('search-filter').value;
            
            const url = new URL(window.location);
            url.searchParams.set('tab', 'orders');
            url.searchParams.set('status', status);
            url.searchParams.set('payment_status', paymentStatus);
            url.searchParams.set('search', search);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });
    }

    // Xử lý nút hủy đơn hàng
    const cancelButtons = document.querySelectorAll('.cancel-order-btn');
    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            if (confirm('Are you sure you want to cancel order #' + orderId + '? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/profile?tab=orders';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'cancel_order_id';
                input.value = orderId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });

    // Xử lý nút Review
    const reviewButtons = document.querySelectorAll('.review-btn');
    reviewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            const bookId = this.getAttribute('data-book-id');
            const bookTitle = this.getAttribute('data-book-title');

            // Điền thông tin vào modal
            document.getElementById('reviewOrderId').value = orderId;
            document.getElementById('reviewBookId').value = bookId;
            document.getElementById('reviewBookTitle').textContent = bookTitle;
        });
    });

    // Xử lý gửi đánh giá
    const submitReviewBtn = document.getElementById('submitReviewBtn');
    if (submitReviewBtn) {
        submitReviewBtn.addEventListener('click', async function() {
            const orderId = document.getElementById('reviewOrderId').value;
            const bookId = document.getElementById('reviewBookId').value;
            const rating = document.getElementById('reviewRating').value;
            const comment = document.getElementById('reviewComment').value;

            // Kiểm tra dữ liệu
            if (!rating || !comment) {
                alert('Please provide both a rating and a comment.');
                return;
            }

            const reviewData = {
                order_id: orderId,
                book_id: bookId,
                rate: parseInt(rating),
                comment: comment
            };

            try {
                const response = await fetch('<?= $api_base_url ?>/review?action=create', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer <?= $_SESSION['access_token'] ?? '' ?>',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(reviewData)
                });

                const result = await response.json();
                if (response.ok && result.success) {
                    alert('Review submitted successfully!');
                    // Đóng modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
                    modal.hide();
                    // Làm mới trang để cập nhật giao diện
                    location.reload();
                } else {
                    throw new Error(result.message || 'Failed to submit review.');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/' . $layout . '.php';
?>