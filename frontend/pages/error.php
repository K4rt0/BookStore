<?php
// 404.php - Trang lỗi được cải thiện với điều hướng tùy chỉnh cho admin
session_start(); // Khởi tạo session để kiểm tra trạng thái đăng nhập
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }

        .error-container {
            width: 85%;
            max-width: 500px;
        }

        .error-page {
            text-align: center;
            padding: 40px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .error-page:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.15);
        }

        .error-page::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, #e74c3c, #f39c12, #3498db);
        }

        .error-icon {
            font-size: 90px;
            color: #e74c3c;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
            display: inline-block;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }

        .error-page h1 {
            font-size: 86px;
            margin: 0;
            background: linear-gradient(90deg, #e74c3c, #f39c12);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            line-height: 1;
        }

        .error-page h2 {
            font-size: 26px;
            margin: 15px 0;
            color: #2c3e50;
        }

        .error-page p {
            color: #7f8c8d;
            margin-bottom: 35px;
            font-size: 16px;
            line-height: 1.6;
        }

        .home-button {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(90deg, #3498db, #2980b9);
            color: #fff;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .home-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.5);
        }

        .home-button i {
            margin-right: 8px;
            vertical-align: middle;
        }
        
        .shapes {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
            overflow: hidden;
        }
        
        .shape {
            position: absolute;
            opacity: 0.5;
        }
        
        .shape-1 {
            top: 10%;
            left: 10%;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e74c3c;
            animation: float-shape 7s infinite alternate;
        }
        
        .shape-2 {
            bottom: 15%;
            right: 10%;
            width: 80px;
            height: 80px;
            background: #3498db;
            transform: rotate(45deg);
            animation: float-shape 9s infinite alternate-reverse;
        }
        
        .shape-3 {
            top: 60%;
            left: 15%;
            width: 40px;
            height: 40px;
            background: #f39c12;
            border-radius: 8px;
            animation: float-shape 8s infinite alternate;
        }
        
        @keyframes float-shape {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(20px, 20px) rotate(10deg); }
        }

        /* Thêm hiệu ứng đếm ngược và chuyển hướng tự động */
        #countdown {
            font-size: 18px;
            color: #7f8c8d;
            margin-top: 20px;
        }
        
        /* Hiển thị thông tin người dùng nếu đã đăng nhập */
        .user-info {
            margin-top: 20px;
            padding: 10px;
            background-color: rgba(52, 152, 219, 0.1);
            border-radius: 10px;
            font-size: 14px;
            color: #34495e;
        }
    </style>
</head>
<body>
    <div class="shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <div class="error-container">
        <div class="error-page">
            <i class='bx bx-ghost error-icon'></i>
            <h1>404</h1>
            <h2>Page Not Found</h2>
            <p>The page you are looking for might have been removed, renamed or is temporarily unavailable.</p>
            
            <?php
            // Kiểm tra nếu người dùng là admin
            $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
            $home_url = $is_admin ? "/admin" : "/";
            $button_text = $is_admin ? "Return to Admin Dashboard" : "Return to Homepage";
            $icon = $is_admin ? "bx-dashboard" : "bx-home-alt";
            ?>
            
            <a href="<?php echo $home_url; ?>" class="home-button"><i class='bx <?php echo $icon; ?>'></i> <?php echo $button_text; ?></a>
            
            <?php if ($is_admin): ?>
            <div class="user-info">
                <p>Đăng nhập với quyền Admin (<?php echo htmlspecialchars($_SESSION['username'] ?? 'Unknown'); ?>)</p>
            </div>
            <div id="countdown">Redirecting in <span id="timer">5</span> seconds...</div>
            
            <script>
                // Chuyển hướng tự động sau 5 giây nếu là admin
                let timeLeft = 5;
                const timerElement = document.getElementById('timer');
                
                const countdownTimer = setInterval(function() {
                    timeLeft -= 1;
                    timerElement.textContent = timeLeft;
                    
                    if (timeLeft <= 0) {
                        clearInterval(countdownTimer);
                        window.location.href = '<?php echo $home_url; ?>';
                    }
                }, 1000);
            </script>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>