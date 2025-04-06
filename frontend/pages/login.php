<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/env-loader.php';

$page_title = "Book Shop - Login";
$layout = 'auth';
$api_base_url = $_ENV['API_BASE_URL'];

$login_error = '';
$login_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $postData = json_encode([
        'email' => $email,
        'password' => $password
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$api_base_url/users?action=login");
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

    if ($http_status === 200 && $result['success']) {
        $_SESSION['access_token'] = $result['data']['access_token'];
        $_SESSION['refresh_token'] = $result['data']['refresh_token'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$api_base_url/users/me");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $_SESSION['access_token'],
            'Content-Type: application/json'
        ]);
        $user_response = curl_exec($ch);
        $user_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $user_data = json_decode($user_response, true);
        if ($user_status === 200 && $user_data['success']) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $user_data['data']['username'] ?? $email;
            $_SESSION['user_id'] = $user_data['data']['id'] ?? null;
        } else {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $email;
            error_log("Failed to fetch user profile: " . ($user_data['message'] ?? 'Unknown error'));
        }

        $login_success = true;
        // Kiểm tra tham số redirect và chuyển hướng tương ứng
        $redirect = $_GET['redirect'] ?? '';
        if ($redirect === 'profile') {
            header("Location: /profile");
        } else {
            header("Location: /");
        }
        exit;
    } else {
        $login_error = $result['message'] ?? 'Login failed. Please check your credentials.';
    }
}

ob_start();
?>

<div class="login-form-area">
    <form method="POST" class="login-form">
        <div class="login-heading">
            <span>Login</span>
            <p>Enter Login details to get access</p>
        </div>

        <?php if ($login_error): ?>
            <div style="color: red; font-weight: bold;"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>

        <div class="input-box">
            <div class="single-input-fields">
                <label>Username or Email Address</label>
                <input type="text" name="email" placeholder="Username / Email address" required>
            </div>
            <div class="single-input-fields">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter Password" required>
            </div>
            <div class="single-input-fields login-check">
                <input type="checkbox" id="fruit1" name="keep-log">
                <label for="fruit1">Keep me logged in</label>
                <a href="#" class="f-right">Forgot Password?</a>
            </div>
        </div>

        <div class="login-footer">
            <p>Don’t have an account? <a href="register">Sign Up</a> here</p>
            <button type="submit" class="submit-btn3">Login</button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main-layout.php';
?>