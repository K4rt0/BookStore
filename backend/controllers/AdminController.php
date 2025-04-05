<?php
require_once __DIR__ . '/../helpers/ApiResponse.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

class AdminController {
    public function login() {
        $input = json_decode(file_get_contents("php://input"), true);
        $username = $input['username'] ?? null;
        $password = $input['password'] ?? null;

        if (empty($username) || empty($password)) {
            ApiResponse::error("Tên đăng nhập và mật khẩu không được để trống", 400);
            return;
        }

        $adminUsername = $_ENV['ADMIN_USER'] ?? null;
        $adminPassword = $_ENV['ADMIN_PASSWORD'] ?? null;

        if (empty($adminUsername) || empty($adminPassword)) {
            ApiResponse::error("Hệ thống đang bị lỗi, vui lòng thử lại sau", 500);
            return;
        }

        if ($username === $adminUsername && $password === $adminPassword) {
            $access = AuthHelper::generateAccessToken('admin', $_ENV['ADMIN_JWT_EXPIRATION'], $_ENV['ADMIN_JWT_SECRET']);
            ApiResponse::success("Đăng nhập admin thành công !", 200,
            [
                "access_token" => $access,
            ]);
        } else {
            ApiResponse::error("Thông tin đăng nhập sai", 401);
        }
    }
}