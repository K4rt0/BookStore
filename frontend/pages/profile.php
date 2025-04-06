<?php
session_start();
require_once __DIR__ . '/../includes/env-loader.php';

// Kiểm tra session để đảm bảo người dùng đã đăng nhập
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    // Nếu chưa đăng nhập, chuyển hướng về trang login với tham số redirect
    header("Location: /login?redirect=profile");
    exit;
}

error_log("Loaded profile.php");
error_log("Session in profile.php: " . print_r($_SESSION, true));

$page_title = "Book Shop - Profile";
$layout = 'main-layout';
$api_base_url = $_ENV['API_BASE_URL'];

$username = $_SESSION['username'] ?? 'N/A';
$user_id = $_SESSION['user_id'] ?? 'N/A';
$email = $_SESSION['email'] ?? 'N/A';
$profile_picture = $_SESSION['profile_picture'] ?? '/assets/img/default-avatar.png';
$join_date = $_SESSION['join_date'] ?? 'April 2025';

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'orders';

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
                    <p><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($email) ?></p>
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
                            <form id="personal-info-form">
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="firstname" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="firstname" value="">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="lastname" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="lastname" value="">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($email) ?>" readonly>
                                    <small class="text-muted">Contact support to change your email</small>
                                </div>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($username) ?>" readonly>
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
                            <h5><i class="fas fa-lock me-2"></i> Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form id="password-change-form">
                                <div class="mb-3">
                                    <label for="current-password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current-password">
                                </div>
                                <div class="mb-3">
                                    <label for="new-password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new-password">
                                </div>
                                <div class="mb-3">
                                    <label for="confirm-password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm-password">
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
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Changes saved successfully!');
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/' . $layout . '.php';
?>