<?php
require_once __DIR__ . '/AuthHelper.php';

class AuthMiddleware {
    public static function requireAuth($requireAdmin = false) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            ApiResponse::error("Không tìm thấy Token !", 401);
            exit();
        }

        $token = $matches[1];
        $decoded = AuthHelper::verifyToken($token);

        if (!$decoded) {
            ApiResponse::error("Token không hợp lệ !", 401);
            exit();
        }

        if (isset($decoded->exp) && $decoded->exp < time()) {
            ApiResponse::error("Token đã hết hạn !", 401);
            exit();
        }

        if ($requireAdmin && $decoded->sub !== 'admin') {
            ApiResponse::error("Không có quyền truy cập", 403);
        }

        return $decoded;
    }
}
