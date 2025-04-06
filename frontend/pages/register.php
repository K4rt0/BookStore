<?php
$page_title = "Book Shop - Register";
$layout = 'auth';
ob_start(); // Bắt đầu bộ đệm để lưu nội dung trang
?>
<div class="register-form-area">
    <div class="register-form text-center">
        <!-- Login Heading -->
        <div class="register-heading">
            <span>Sign Up</span>
            <p>Create your account to get full access</p>
        </div>
        <!-- Single Input Fields -->
        <div class="input-box">
            <div class="single-input-fields">
                <label>Full name</label>
                <input type="text" placeholder="Enter full name">
            </div>
            <div class="single-input-fields">
                <label>Email Address</label>
                <input type="email" placeholder="Enter email address">
            </div>
            <div class="single-input-fields">
                <label>Password</label>
                <input type="password" placeholder="Enter Password">
            </div>
            <div class="single-input-fields">
                <label>Confirm Password</label>
                <input type="password" placeholder="Confirm Password">
            </div>
        </div>
        <!-- form Footer -->
        <div class="register-footer">
            <p> Already have an account? <a href="login"> Login</a> here</p>
            <button class="submit-btn3">Sign Up</button>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean(); // Lấy nội dung từ bộ đệm và gán vào biến $content
include __DIR__ . '/../layouts/main-layout.php'; // Bao gồm layout chính
?>