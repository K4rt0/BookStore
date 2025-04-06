<?php
$page_title = "Book Shop - Login";
$layout = 'auth';
ob_start(); // Bắt đầu bộ đệm để lưu nội dung trang
?>
<div class="login-form-area">
    <div class="login-form">
        <!-- Login Heading -->
        <div class="login-heading">
            <span>Login</span>
            <p>Enter Login details to get access</p>
        </div>
        <!-- Single Input Fields -->
        <div class="input-box">
            <div class="single-input-fields">
                <label>Username or Email Address</label>
                <input type="text" placeholder="Username / Email address">
            </div>
            <div class="single-input-fields">
                <label>Password</label>
                <input type="password" placeholder="Enter Password">
            </div>
            <div class="single-input-fields login-check">
                <input type="checkbox" id="fruit1" name="keep-log">
                <label for="fruit1">Keep me logged in</label>
            <a href="#" class="f-right">Forgot Password?</a>
            </div>
        </div>
        <!-- form Footer -->
        <div class="login-footer">
            <p>Don’t have an account? <a href="/pages/register.php">Sign Up</a>  here</p>
            <button class="submit-btn3">Login</button>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean(); // Lấy nội dung từ bộ đệm và gán vào biến $content
include __DIR__ . '/../layouts/main-layout.php'; // Bao gồm layout chính
?>