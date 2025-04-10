<?php
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../helpers/ApiResponse.php';
require_once __DIR__ . '/../helpers/payments/MomoService.php';
require_once __DIR__ . '/../helpers/payments/PayPalService.php';

class PaymentController {
    private $payment;

    public function __construct() {
        $this->payment = new Payment();
    }

    public function result_payment($query) {
        $order_id = $query['order_id'] ?? null;
        $payment_method = $query['payment_method'] ?? null;
        $error_code = $query['errorCode'] ?? null;

        if(empty($order_id) || empty($payment_method)) {
            return ApiResponse::error("Thiếu thông tin đơn hàng hoặc phương thức thanh toán !", 400);
        }

        if($payment_method == 'momo') {
            if(empty($error_code) || $error_code != 0) {
                $this->payment->update($order_id, ['status' => 'Paid']);
                header("Location: http://localhost:8000/order-result.php?status=success&method=momo&order_id=$order_id");
                exit();
            } else {
                $this->payment->update($order_id, ['status' => 'Failed']);
                header("Location: http://localhost:8000/order-result.php?status=failed&method=momo&order_id=$order_id");
                exit();
            }
        }
    }
    /* public function update_payment($order_id, $payment_status) {
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['order_id']) || empty($input['payment_status'])) {
            return ApiResponse::error("Thiếu thông tin đơn hàng hoặc trạng thái thanh toán !", 400);
        }

        $order = $this->order->find_by_id($input['order_id']);
        if (!$order) {
            return ApiResponse::error("Đơn hàng không tồn tại !", 404);
        }

        $updateData = [
            'id' => $input['order_id'],
            'payment_status' => $input['payment_status']
        ];

        if ($this->order->update_payment_status($updateData)) {
            return ApiResponse::success("Cập nhật trạng thái thanh toán thành công !", 200);
        } else {
            return ApiResponse::error("Cập nhật trạng thái thanh toán thất bại !", 500);
        }
    } */
    
    public function delete_payment($payment_id) {
        if (empty($payment_id))
            return ApiResponse::error("Thiếu thông tin giao dịch !", 400);
        if ($this->payment->delete($payment_id))
            return ApiResponse::success("Xóa giao dịch thành công !", 200);
        else
            return ApiResponse::error("Xóa giao dịch thất bại !", 500);
    }
}
