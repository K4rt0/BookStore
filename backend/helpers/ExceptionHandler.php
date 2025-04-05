<?php
require_once __DIR__ . '/ApiResponse.php';

class ExceptionHandler {
    public static function handle() {
        set_exception_handler(function ($e) {
            ApiResponse::error("Lỗi hệ thống: " . $e->getMessage(), 500);
        });

        set_error_handler(function ($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error !== null) {
                ApiResponse::error("Lỗi nghiêm trọng: {$error['message']}", 500);
            }
        });
    }
}
