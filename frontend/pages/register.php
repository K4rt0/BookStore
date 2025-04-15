<?php

require_once __DIR__ . '/../includes/env-loader.php';

$page_title = "Book Shop - Register";
$layout = 'auth';
$api_base_url = $_ENV['API_BASE_URL'];

$register_error = '';
$register_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Kiểm tra mật khẩu và xác nhận mật khẩu có khớp không
    if ($password !== $confirm_password) {
        $register_error = 'Passwords do not match. Please try again.';
    } else {
        // Chuẩn bị dữ liệu gửi đến API
        $postData = json_encode([
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password
        ]);

        // Gọi API đăng ký
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$api_base_url/users?action=register");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($postData)
        ]);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        // Kiểm tra phản hồi từ API
        if ($http_status === 201 && $result['success']) {
            $register_success = true;
            // Chuyển hướng đến trang đăng nhập sau khi đăng ký thành công
            header("Location: /login?success=Registration successful. Please login.");
            exit;
        } else {
            $register_error = $result['message'] ?? 'Registration failed. Please try again.';
        }
    }
}

ob_start();
?>


<div class="register-form-area">
    <div class="register-form text-center">
        <div class="register-heading">
            <span>Sign Up</span>
            <p>Create your account to get full access</p>
        </div>

        <?php if ($register_error): ?>
            <div class="error-message"><?= htmlspecialchars($register_error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div style="color: green; font-weight: 500; margin-bottom: 20px; text-align: center;">
                <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="register-form">
            <div class="input-box">
                <div class="single-input-fields">
                    <label>Full Name</label>
                    <input type="text" name="full_name" placeholder="Enter full name" value="<?= htmlspecialchars($full_name ?? '') ?>" required>
                </div>
                <div class="single-input-fields">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="Enter email address" value="<?= htmlspecialchars($email ?? '') ?>" required>
                </div>
                <div class="single-input-fields">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" placeholder="Enter phone number" value="<?= htmlspecialchars($phone ?? '') ?>" required>
                </div>
                <div class="single-input-fields">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter Password" required>
                </div>
                <div class="single-input-fields">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
            </div>

            <div class="register-footer">
                <p>Already have an account? <a href="/login">Login</a> here</p>
                <button type="submit" class="submit-btn3">Sign Up</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main-layout.php';
?>