<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/AuthHelper.php';

class AuthMiddleware {
    private static $user;

    public static function init() {
        self::$user = new User();
    }

    public static function requireAuth($requireAdmin = false) {
        try {
            $token = self::extractBearerToken();
            $decoded = self::verifyAndValidateToken($token);

            if ($requireAdmin) {
                self::authorizeAdmin($decoded);
            } else {
                self::authorizeUser($decoded);
            }

            return $decoded;

        } catch (Exception $e) {
            ApiResponse::error($e->getMessage(), $e->getCode() ?: 401);
            exit();
        }
    }

    private static function extractBearerToken() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            throw new Exception("Không tìm thấy Token !", 401);
        }

        return $matches[1];
    }

    private static function verifyAndValidateToken($token) {
        $decoded = AuthHelper::verifyToken($token);
        if (!$decoded) {
            throw new Exception("Token không hợp lệ !", 401);
        }

        if (!empty($decoded->exp) && $decoded->exp < time()) {
            throw new Exception("Token đã hết hạn !", 401);
        }

        return $decoded;
    }

    private static function authorizeUser($decoded) {
        $userId = $decoded->sub ?? null;
        if (!$userId) {
            throw new Exception("Không tìm thấy thông tin người dùng !", 401);
        }

        $user = self::$user->findById($userId);
        if (!$user) {
            throw new Exception("Người dùng không tồn tại !", 401);
        }

        if (!empty($user['is_blocked'])) {
            throw new Exception("Tài khoản đã bị khóa !", 403);
        }
    }

    private static function authorizeAdmin($decoded) {
        if (($decoded->sub ?? null) !== 'admin') {
            throw new Exception("Bạn không có quyền truy cập !", 403);
        }
    }
}

// Khởi tạo user model
AuthMiddleware::init();
