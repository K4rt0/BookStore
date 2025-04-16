<?php
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../helpers/ApiResponse.php';
require_once __DIR__ . '/../helpers/payments/MomoService.php';
require_once __DIR__ . '/../helpers/payments/PayPalService.php';

class PaymentController {
    private $payment;
    private $order;
    private $book;
    
    public function __construct() {
        $this->payment = new Payment();
        $this->order = new Order();
        $this->book = new Book();
    }

    public function get_payment($query) {
        $payment_id = $query['payment_id'] ?? null;
        if (empty($payment_id)) return ApiResponse::error("Thiếu thông tin giao dịch !", 400);
        
        $payment = $this->payment->find_by_id($payment_id);
        if (!$payment) return ApiResponse::error("Giao dịch không tồn tại !", 404);
        
        return ApiResponse::success("Lấy thông tin giao dịch thành công !", 200, $payment);
    }

    public function result_payment($query) {
        $payment_id = $query['payment_id'] ?? null;
        $payment_method = $query['payment_method'] ?? null;
        $error_code = $query['errorCode'] ?? null;

        if(empty($payment_id) || empty($payment_method)) {
            return ApiResponse::error("Thiếu thông tin đơn hàng hoặc phương thức thanh toán !", 400);
        }

        $paymentExisting = $this->payment->find_by_id($payment_id);
        if (!$paymentExisting)
            return ApiResponse::error("Giao dịch không tồn tại !", 404);
 
        if($payment_method == 'momo') {
            if($paymentExisting['status'] == 'Pending') {
                $order_details = $this->order->get_order_details($paymentExisting['order_id']);
                foreach ($order_details as $order_detail) {
                    if($this->book->find_by_id($order_detail['book_id'])) {
                        $book = $this->book->find_by_id($order_detail['book_id']);
                        if ($book['stock_quantity'] < $order_detail['quantity'] || $book['stock_quantity']-$order_detail['quantity'] < 0)
                            $error_code = 1;
                        else
                            $this->order->minus_stock($order_detail['book_id'], $order_detail['quantity']);
                    } else $error_code = 1;
                }
            }
            if($error_code != 0)
                $this->order->update_status([
                    'order_id' => $paymentExisting['order_id'],
                    'status' => 'Cancelled'
                ]);
            $this->payment->update($payment_id, ['status' => $error_code == 0 ? 'Paid' : 'Failed']);
            header("Location: http://localhost:8000/order-confirmation?status=" . ($error_code == 0 ? 'success' : 'failed') . "&payment_id=" . $payment_id);
            exit();
        }
    }
    
    public function update_payment() {
        $input = json_decode(file_get_contents("php://input"), true);
        $payment_id = $input['payment_id'] ?? null;
        $payment_status = $input['payment_status'] ?? null;

        if (empty($payment_id) || empty($payment_status)) {
            return ApiResponse::error("Thiếu thông tin giao dịch hoặc trạng thái giao dịch !", 400);
        }

        $paymentExisting = $this->payment->find_by_id($payment_id);
        if (!$paymentExisting)
            return ApiResponse::error("Giao dịch không tồn tại !", 404);

        $updateData = [
            'id' => $payment_id,
            'status' => $payment_status
        ];

        if ($this->payment->update($payment_id, $updateData))
            return ApiResponse::success("Cập nhật trạng thái thanh toán thành công !", 200);
        else
            return ApiResponse::error("Cập nhật trạng thái thanh toán thất bại !", 500);
    }
    
    public function delete_payment($payment_id) {
        if (empty($payment_id))
            return ApiResponse::error("Thiếu thông tin giao dịch !", 400);
        if ($this->payment->delete($payment_id))
            return ApiResponse::success("Xóa giao dịch thành công !", 200);
        else
            return ApiResponse::error("Xóa giao dịch thất bại !", 500);
    }
}
