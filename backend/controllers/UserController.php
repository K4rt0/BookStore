<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/AuthMiddleware.php';

class UserController {
    private $user;

    public function __construct() {
        $this->user = new User();
    }

    public function register() {
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['email']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL))
            ApiResponse::error("Email không hợp lệ !", 400);
        if (empty($input['password']) || strlen($input['password']) < 6)
            ApiResponse::error("Mật khẩu phải có ít nhất 6 ký tự !", 400);
        if (empty($input['full_name']))
            ApiResponse::error("Họ và tên không được để trống !", 400);
        else {
            $input['id'] = bin2hex(random_bytes(16));
            $input['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
    
            $existing = $this->user->findByEmail($input['email']);
            if ($existing)
                return ApiResponse::error("Email đã tồn tại !", 409);
    
            if ($this->user->create($input))
                ApiResponse::success("Đăng ký thành công !", 201);
            else
                ApiResponse::error("Đăng ký thất bại !", 500);
        }
    }

    public function login() {
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['email']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL))
            return ApiResponse::error("Email không hợp lệ !", 400);
        if (empty($input['password']))
            return ApiResponse::error("Mật khẩu không được để trống !", 400);

        $user = $this->user->findByEmail($input['email']);

        if (!$user || !password_verify($input['password'], $user['password']))
            return ApiResponse::error("Email hoặc mật khẩu không đúng", 401);

        
        $access = AuthHelper::generateAccessToken($user['id']);
        $refresh = AuthHelper::generateRefreshToken($user['id']);

        $this->user->update($user['id'], [
            'refresh_token' => $refresh,
        ]);

        ApiResponse::success("Đăng nhập thành công", 200, [
            'access_token' => $access,
            'refresh_token' => $refresh,
        ]);
    }

    public function logout() {
        $userData = AuthMiddleware::requireAuth();

        $user = $this->user->findById($userData->sub);
        if (!$user || empty($user['refresh_token'])) {
            return ApiResponse::error("Tài khoản đã được đăng xuất hoặc không hợp lệ", 400);
        }

        $this->user->update($userData->sub, [
            'refresh_token' => null,
        ]);
        ApiResponse::success("Đăng xuất thành công", 200);
    }

    public function refresh_token() {
        $input = json_decode(file_get_contents("php://input"), true);
        $refresh = $input['refresh_token'] ?? null;

        if (!$refresh) {
            ApiResponse::error("Không tìm thấy Refresh Token !", 400);
            return;
        }

        $decoded = AuthHelper::verifyToken($refresh);

        $userId = $decoded->sub;
        $user = $this->user->findById($userId);
        if (!$user || $user['refresh_token'] !== $refresh) {
            ApiResponse::error("Refresh token không hợp lệ !", 401);
            return;
        }

        $newAccessToken = AuthHelper::generateAccessToken($userId);
        ApiResponse::success("Làm mới token thành công !", 200, [
            'access_token' => $newAccessToken,
        ]);
    }
}
