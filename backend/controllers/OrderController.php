<?php
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../helpers/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/payments/MomoService.php';
require_once __DIR__ . '/../helpers/payments/PayPalService.php';

class OrderController {
    private $order;
    private $book;
    private $cart;
    private $payment;

    public function __construct() {
        $this->order = new Order();
        $this->book = new Book();
        $this->cart = new Cart();
        $this->payment = new Payment();
    }

    // GET methods
    public function get_all_orders() {
        $orders = $this->order->find_all_orders();
        ApiResponse::success("Lấy danh sách danh mục thành công !", 200, $orders);
    }
    public function get_all_orders_pagination($query) {
        $page = isset($query['page']) && is_numeric($query['page']) && $query['page'] > 0 ? (int)$query['page'] : 1;
        $limit = isset($query['limit']) && is_numeric($query['limit']) && $query['limit'] > 0 ? (int)$query['limit'] : 10;
        $offset = ($page - 1) * $limit;

        $rawCategoryIds = $query['category_id'] ?? [];
        $validCategoryIds = is_array($rawCategoryIds) 
            ? array_filter($rawCategoryIds, fn($id) => preg_match('/^[a-f0-9]{32}$/i', $id)) 
            : [];

        if (!empty($rawCategoryIds) && empty($validCategoryIds)) {
            return ApiResponse::error("Tất cả category_id đều sai định dạng.", 400);
        }

        $filters = [
            'status' => isset($query['filters']) && in_array($query['filters'], ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled']) ? $query['filters'] : null,
            'category_ids' => $validCategoryIds,
            'search' => isset($query['search']) && is_string($query['search']) && trim($query['search']) !== '' ? trim($query['search']) : null,
        ];

        // Validate sort
        $validSortOptions = ['newest', 'oldest'];
        $sort = isset($query['sort']) && in_array($query['sort'], $validSortOptions) ? $query['sort'] : 'newest';

        // Call model
        $result = $this->order->get_all_orders_pagination($limit, $offset, $filters, $sort);

        if (empty($result['orders'])) {
            return ApiResponse::error("Không có đơn hàng nào !", 404);
        }

        return ApiResponse::success("Lấy danh sách đơn hàng thành công !", 200, [
            "orders" => $result['orders'],
            "total" => $result['total']
        ]);
    }
    public function get_order($params) {
        $id = $params['id'] ?? null;
        $order = null;
    
        if (empty($id) || !($order = $this->order->find_by_id($id)))
            return ApiResponse::error("Đơn hàng không tồn tại !", 404);
    
        $order_details = $this->order->get_order_details($id);
        // $reviews = $this->order->get_order_reviews($id);

        $data['order'] = $order;
        $data['order_details'] = $order_details;
        // $data['reviews'] = $reviews;
        ApiResponse::success("Lấy đơn hàng thành công !", 200, $data);
    }
    public function get_all_my_orders($user_id) {
        $orders = $this->order->find_all_orders_by_user_id($user_id);
        if (empty($orders))
            return ApiResponse::error("Không có đơn hàng nào !", 404);
        ApiResponse::success("Lấy danh sách đơn hàng thành công !", 200, $orders);
    }
    public function get_all_my_orders_pagination($query, $userId) {
        $page = isset($query['page']) && is_numeric($query['page']) && $query['page'] > 0 ? (int)$query['page'] : 1;
        $limit = isset($query['limit']) && is_numeric($query['limit']) && $query['limit'] > 0 ? (int)$query['limit'] : 10;
        $offset = ($page - 1) * $limit;
    
        $rawCategoryIds = $query['category_id'] ?? [];
        $validCategoryIds = is_array($rawCategoryIds)
            ? array_filter($rawCategoryIds, fn($id) => preg_match('/^[a-f0-9]{32}$/i', $id))
            : [];
    
        if (!empty($rawCategoryIds) && empty($validCategoryIds))
            return ApiResponse::error("Tất cả category_id đều sai định dạng.", 400);
    
        $allowedStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
        $statusInput = $query['status'] ?? null;
        if (!empty($statusInput) && !in_array($statusInput, $allowedStatuses, true))
            return ApiResponse::error("Trạng thái đơn hàng không hợp lệ: $statusInput", 400);
    
        $allowedPayments = ['Pending', 'Paid', 'Failed', 'Refunded'];
        $paymentInput = $query['payment_status'] ?? null;
        if (!empty($paymentInput) && !in_array($paymentInput, $allowedPayments, true))
            return ApiResponse::error("Trạng thái thanh toán không hợp lệ: $paymentInput", 400);
    
        $filters = [
            'status' => $statusInput,
            'payment_status' => $paymentInput,
            'category_ids' => $validCategoryIds,
            'search' => isset($query['search']) && is_string($query['search']) && trim($query['search']) !== '' ? trim($query['search']) : null,
        ];
    
        $sort = isset($query['sort']) && $query['sort'] === 'oldest' ? 'oldest' : 'newest';
        $orders = $this->order->get_all_my_orders_pagination($userId, $limit, $offset, $filters, $sort);
    
        if (empty($orders))
            return ApiResponse::error("Không có đơn hàng nào!", 404);
    
        return ApiResponse::success("Lấy danh sách đơn hàng thành công!", 200, [
            "orders" => $orders
        ]);
    }
    
    // POST methods
    public function create() {
        $input = json_decode(file_get_contents("php://input"), true);

        $requiredOrderInfoKeys = ['user_id', 'total_price', 'full_name', 'phone', 'shipping_address', 'payment_method'];
        if (!isset($input['order_info']) || !is_array($input['order_info']))
            return ApiResponse::error("Thông tin đơn hàng không hợp lệ hoặc bị thiếu !", 400);
    
        foreach ($requiredOrderInfoKeys as $key) {
            if (!array_key_exists($key, $input['order_info']) || empty($input['order_info'][$key]))
                return ApiResponse::error("Thiếu thông tin bắt buộc: $key trong order_info !", 400);
        }

        if (!isset($input['carts']) || !is_array($input['carts']) || empty(array_filter($input['carts'], function($cart) { return !empty($cart); })))
            return ApiResponse::error("Giỏ hàng không được để trống hoặc chứa toàn giá trị rỗng !", 400);

        $user_id = $input['order_info']['user_id'];
        $carts = array_filter(array_map(function($cart) use ($user_id) {
            $cartData = $this->cart->find_by_id($cart);
            if (!$cartData)
                return null;
            $bookData = $this->book->find_by_id($cartData['book_id']);
            if (!$bookData) {
                $this->cart->delete($cartData['id']);
                return null;
            }
            
            if ($cartData && $cartData['user_id'] === $user_id) {
                $cartData['price'] = $bookData['price'];
                return $cartData;
            }
            return null;
        }, $input['carts']));
        
        if (empty($carts))
            return ApiResponse::error("Không tìm thấy giỏ hàng hợp lệ cho người dùng này !", 400);
    
        $order_id = bin2hex(random_bytes(16));
        $order = [
            'id' => $order_id,
            'user_id' => $input['order_info']['user_id'],
            'total_price' => (float)$input['order_info']['total_price'],
            'full_name' => $input['order_info']['full_name'],
            'phone' => $input['order_info']['phone'],
            'status' => 'Pending',
            'shipping_address' => $input['order_info']['shipping_address']
        ];
        $this->order->create($order);

        foreach ($carts as $cart) {
            if($this->book->find_by_id($cart['book_id'])['stock_quantity'] < $cart['quantity']) {
                $this->order->delete($order_id);
                return ApiResponse::error("Số lượng sách không đủ trong kho !", 400);
            }
            $orderDetail = [
                'id' => bin2hex(random_bytes(16)),
                'order_id' => $order_id,
                'book_id' => $cart['book_id'],
                'quantity' => $cart['quantity'],
                'price' => (float)$cart['price'],
            ];
            $this->order->create_orders_detail($orderDetail);
            $this->cart->delete($cart['id']);
        }
        
        $payment_method = strtolower($input['order_info']['payment_method']);
        $orderInfo = "Thanh toan don hang " . $order_id;
        AuthMiddleware::requireAuth();
        
        if ($payment_method === 'cod') {
            $payment = [
                'id' => bin2hex(random_bytes(16)),
                'order_id' => $order_id,
                'payment_method' => 'COD',
                'status' => 'Paid'
            ];
            $this->payment->create($payment);

            foreach ($carts as $cart) {
                $this->order->minus_stock($cart['book_id'], $cart['quantity']);
            }
            
            ApiResponse::success("Tạo đơn hàng thành công !", 200, [
                'payment_url' => 'http://localhost:8000/order-confirmation?status=success',
            ]);
        }
        else if ($payment_method === 'momo') {
            $id = bin2hex(random_bytes(16));
            $payment = [
                'id' => $id,
                'order_id' => $order_id,
                'payment_method' => 'Momo',
                'status' => 'Pending'
            ];
            $this->payment->create($payment);

            $result = MomoService::createPaymentUrl($id, $order['total_price'], $orderInfo);
            if ($result['success']) {
                ApiResponse::success("Tạo đơn hàng thành công !", 200, [
                    'payment_url' => $result['payment_url'],
                ]);
            } else {
                $this->order->delete($order_id);
                ApiResponse::error("Không thể tạo URL thanh toán: " . $result['message'], 400);
            }
        }
        else {
            $this->order->delete($order_id);
            ApiResponse::error("Phương thức thanh toán không hợp lệ !", 400);
        }
    }

    // PUT methods

    // PATCH methods
    public function update_status($params) {
        $order_id = $params['order_id'] ?? null;
        $status = $params['status'] ?? null;

        if (empty($order_id) || empty($status))
            return ApiResponse::error("Thiếu thông tin đơn hàng hoặc trạng thái đơn hàng !", 400);

        if (!$this->order->find_by_id($order_id))
            return ApiResponse::error("Đơn hàng không tồn tại !", 404);

        if (!in_array($status, ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled']))
            return ApiResponse::error("Trạng thái không hợp lệ !", 400);

        $this->order->update_status([
            'order_id' => $order_id,
            'status' => $status
        ]);
        ApiResponse::success("Cập nhật trạng thái đơn hàng thành công !", 200);
    }
    public function cancel_order($userId, $params) {
        $order_id = $params['order_id'] ?? null;
        if (empty($order_id))
            return ApiResponse::error("Thiếu thông tin đơn hàng !", 400);
        
        $order = $this->order->find_by_id($order_id);
        if (!$order)
            return ApiResponse::error("Đơn hàng không tồn tại !", 404);
        
        if ($order['user_id'] !== $userId)
            return ApiResponse::error("Bạn không có quyền truy cập vào đơn hàng này !", 403);

        if ($order['status'] !== 'Pending')
            return ApiResponse::error("Chỉ có thể hủy đơn hàng ở trạng thái Pending !", 400);

        $this->order->update_status([
            'order_id' => $order_id,
            'status' => 'Cancelled'
        ]);
        ApiResponse::success("Hủy đơn hàng thành công !", 200);
    }
}
