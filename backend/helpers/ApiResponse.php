<?php

class ApiResponse {
    public static function success($message = 'Thành công', $statusCode = 200, $data = null) {
        http_response_code($statusCode);
        echo json_encode([
            'code' => $statusCode,
            'success' => true,
            'message' => $message,
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error($message = 'Đã xảy ra lỗi', $statusCode = 500, $data = null) {
        http_response_code($statusCode);
        echo json_encode([
            'code' => $statusCode,
            'success' => false,
            'message' => $message,
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
