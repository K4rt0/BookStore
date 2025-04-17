<?php
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

    $is_admin = false;
    $login_endpoint = "$api_base_url/users?action=login"; // Mặc định cho người dùng thường

    if (strtolower($email) === 'root') {
        $is_admin = true;
        $login_endpoint = "$api_base_url/admin?action=login";
    }

    $postData = $is_admin
        ? json_encode(['username' => $email, 'password' => $password])
        : json_encode(['email' => $email, 'password' => $password]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $login_endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ]);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    error_log("HTTP Status: $http_status");
    error_log("API Response: $response");
    if ($curl_error) {
        error_log("cURL Error: $curl_error");
    }
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    error_log("cURL Verbose Log: $verboseLog");

    $result = json_decode($response, true);

    if ($http_status === 200 && $result['success']) {
        $_SESSION['access_token'] = $result['data']['access_token'];
        $_SESSION['refresh_token'] = $result['data']['refresh_token'] ?? null; // Nếu không có refresh_token, để null

        if (!$is_admin) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$api_base_url/users?action=profile");
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
                $_SESSION['user_id'] = $user_data['data']['id'] ?? null;
                $_SESSION['email'] = $user_data['data']['email'] ?? $email;
                $_SESSION['username'] = $user_data['data']['full_name'] ?? $email;
                $_SESSION['phone'] = $user_data['data']['phone'] ?? null;
                $_SESSION['created_at'] = $user_data['data']['created_at'] ?? null;
                $_SESSION['updated_at'] = $user_data['data']['updated_at'] ?? null;
                $_SESSION['is_admin'] = false;
            } else {
                $_SESSION['logged_in'] = true;
                $_SESSION['email'] = $email;
                $_SESSION['username'] = $email;
                $_SESSION['is_admin'] = false;
                error_log("Failed to fetch user profile: " . ($user_data['message'] ?? 'Unknown error'));
            }
        } else {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = 'admin_root';
            $_SESSION['email'] = 'admin';
            $_SESSION['username'] = 'root';
            $_SESSION['phone'] = 'N/A';
            $_SESSION['created_at'] = '2023-01-01 00:00:00';
            $_SESSION['updated_at'] = date('Y-m-d H:i:s');
            $_SESSION['is_admin'] = true;
        }

        $login_success = true;
        $redirect = $_GET['redirect'] ?? '';
        if ($redirect === 'profile') {
            header("Location: /profile");
        } else {
            header("Location: /");
        }
        exit;
    } else {
        $login_error = $result['message'] ?? 'Login failed. Please check your credentials or API availability.';
    }
}

ob_start();
?>

<div class="login-form-area">
    <form method="POST" class="login-form">
        <div class="login-heading">
            <span>Đăng nhập</span>
            <p>Nhập thông tin đăng nhập để truy cập</p>
        </div>

        <?php if ($login_error): ?>
            <div style="color: red; font-weight: bold;"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>

        <div class="input-box">
            <div class="single-input-fields">
                <label>Tên đăng nhập hoặc Email</label>
                <input type="text" name="email" placeholder="Tên đăng nhập / Email" required>
            </div>
            <div class="single-input-fields">
                <label>Mật khẩu</label>
                <input type="password" name="password" placeholder="Nhập mật khẩu" required>
            </div>
            <div class="single-input-fields login-check">
                <input type="checkbox" id="fruit1" name="keep-log">
                <label for="fruit1">Ghi nhớ đăng nhập</label>
                <a href="#" class="f-right">Quên mật khẩu?</a>
            </div>
            <div class="social-login">
                <div class="divider">
                    <span>Hoặc đăng nhập với</span>
                </div>
                
                <div class="google-login-container">
                    <script src="https://accounts.google.com/gsi/client" async defer></script>
                    <div id="g_id_onload"
                        data-client_id="<?= htmlspecialchars($_ENV['GOOGLE_CLIENT_ID']) ?>"
                        data-context="signin"
                        data-ux_mode="popup"
                        data-callback="handleCredentialResponse">
                    </div>
                    <div class="g_id_signin" data-type="standard"></div>
                </div>
            </div>
        </div>
        <div class="login-footer">
            <p>Chưa có tài khoản? <a href="register">Đăng ký</a> ngay</p>
            <button type="submit" class="submit-btn3">Đăng nhập</button>
        </div>
    </form>

    <script>
    function handleCredentialResponse(response) {
        const token = response.credential; // JWT access token
        
        if (token) {
            fetch('<?= htmlspecialchars($api_base_url) ?>/user?action=google-login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: token })
            })
            .then(res => res.json())
            .then(data => {
                console.log('Login success:', data);
                if (data.success) {
                    window.location.href = '/'; // Redirect to homepage or desired page
                } else {
                    alert('Đăng nhập thất bại: ' + (data.message || 'Lỗi không xác định'));
                }
            })
            .catch(error => {
                console.error('Error during login:', error);
                alert('Đã xảy ra lỗi trong quá trình đăng nhập. Vui lòng thử lại.');
            });
        } else {
            alert('Không nhận được token. Vui lòng thử lại.');
        }
    }
    </script>
    
    <style>
    .social-login {
        margin-top: 30px;
        text-align: center;
    }
    
    .divider {
        display: flex;
        align-items: center;
        margin: 20px 0;
    }
    
    .divider::before, .divider::after {
        content: "";
        flex: 1;
        border-bottom: 1px solid #ddd;
    }
    
    .divider span {
        padding: 0 10px;
        color: #777;
        font-size: 14px;
    }
    
    .google-login-container {
        display: flex;
        justify-content: center;
        margin-top: 15px;
    }
    </style>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main-layout.php';
?>