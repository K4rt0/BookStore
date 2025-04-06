<?php
session_start();
require_once __DIR__ . '/../includes/env-loader.php';

// Kiểm tra session để đảm bảo người dùng đã đăng nhập
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: /login?redirect=profile");
    exit;
}

error_log("Loaded profile.php");
error_log("Session in profile.php: " . print_r($_SESSION, true));

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
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); // Sử dụng phương thức PUT
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
        // Cập nhật session với thông tin mới
        $_SESSION['username'] = $full_name;
        $_SESSION['phone'] = $phone;
        $username = $full_name; // Cập nhật biến để hiển thị ngay
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

        $result = call_api_with_token(
            "$api_base_url/users?action=update-profile",
            'PUT',
            ['full_name' => $full_name, 'phone' => $phone]
        );

        if ($http_status === 200 && $result['success']) {
            $password_success = 'Password updated successfully!';
        } else {
            $password_error = $result['message'] ?? 'Failed to update password. Please try again.';
        }
    }
}

ob_start();
?>

<link rel="stylesheet" href="/assets/css/template/profile.css">
<style>
.message-success {
    color: green;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 20px;
    text-align: center;
}

.message-error {
    color: red;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 20px;
    text-align: center;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #ddd;
    padding: 12px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #6e8efb;
}

.form-label {
    font-weight: 500;
    color: #444;
    margin-bottom: 8px;
}

.btn-primary {
    background: #6e8efb;
    border: none;
    border-radius: 8px;
    padding: 10px 20px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: #5a78e4;
    transform: translateY(-2px);
}
</style>

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
                    <p><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($email) ?></p>
                    <p><i class="fas fa-phone me-2"></i><?= htmlspecialchars($phone) ?></p>
                    <p><i class="fas fa-calendar me-2"></i>Member since <?= htmlspecialchars($join_date) ?></p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 mb-4">
                <div class="profile-sidebar sticky-top">
                    <div class="profile-nav">
                        <div class="nav-header">
                            <i class="fas fa-user-circle me-2"></i> Account Management
                        </div>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?= $active_tab == 'orders' ? 'active' : '' ?>" href="/profile?tab=orders">
                                    <i class="fas fa-shopping-bag me-2"></i> My Orders
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $active_tab == 'wishlist' ? 'active' : '' ?>" href="/profile?tab=wishlist">
                                    <i class="fas fa-heart me-2"></i> Wishlist
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $active_tab == 'reviews' ? 'active' : '' ?>" href="/profile?tab=reviews">
                                    <i class="fas fa-star me-2"></i> My Reviews
                                </a>
                            </li>
                        </ul>
                        <div class="nav-header mt-4">
                            <i class="fas fa-cog me-2"></i> Settings
                        </div>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?= $active_tab == 'personal' ? 'active' : '' ?>" href="/profile?tab=personal">
                                    <i class="fas fa-user-edit me-2"></i> Personal Info
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $active_tab == 'password' ? 'active' : '' ?>" href="/profile?tab=password">
                                    <i class="fas fa-lock me-2"></i> Change Password
                                </a>
                            </li>
                        </ul>
                        <div class="nav-footer mt-4">
                            <a class="nav-link text-danger" href="/logout">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
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
                            <h5><i class="fas fa-shopping-bag me-2"></i> My Orders</h5>
                        </div>
                        <div class="card-body">
                            <div class="empty-state">
                                <img src="/assets/img/empty-orders.svg" alt="No orders" class="empty-icon">
                                <p>You haven't placed any orders yet</p>
                                <a href="/books" class="btn btn-primary mt-3">Explore Books</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php elseif ($active_tab == 'wishlist'): ?>
                <div class="tab-content active" id="wishlist">
                    <div class="card profile-card">
                        <div class="card-header">
                            <h5><i class="fas fa-heart me-2"></i> My Wishlist</h5>
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
                            <h5><i class="fas fa-star me-2"></i> My Reviews</h5>
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
                            <h5><i class="fas fa-user-edit me-2"></i> Personal Information</h5>
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
                                    <button type="submit" class="btn btn-primary p-4">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php elseif ($active_tab == 'password'): ?>
                <div class="tab-content active" id="password">
                    <div class="card profile-card">
                        <div class="card-header">
                            <h5><i class="fas fa-lock me-2"></i> Change Password</h5>
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
                                    <button type="submit" class="btn btn-primary p-4">Update Password</button>
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
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Xử lý form bằng PHP, không cần JavaScript hiển thị alert
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/' . $layout . '.php';
?>