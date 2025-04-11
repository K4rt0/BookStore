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
    /* public function get_all_categories_pagination($query) {
        $page = isset($query['page']) && is_numeric($query['page']) && $query['page'] > 0 ? (int)$query['page'] : 1;
        $limit = isset($query['limit']) && is_numeric($query['limit']) && $query['limit'] > 0 ? (int)$query['limit'] : 10;
        $offset = ($page - 1) * $limit;

        $filters = [
            'is_active' => isset($query['filters']) && is_string($query['filters']) ? $query['filters'] : null,
            'search' => isset($query['search']) && is_string($query['search']) && trim($query['search']) !== '' ? trim($query['search']) : null,
        ];

        $validSortOptions = ['created_at_asc', 'created_at_desc', 'updated_at_asc', 'updated_at_desc'];
        $sort = isset($query['sort']) && in_array($query['sort'], $validSortOptions) ? $query['sort'] : 'created_at_desc';

        $categories = $this->category->get_all_categories_pagination($limit, $offset, $filters, $sort);

        if (empty($categories))
            return ApiResponse::error("Không có danh mục nào !", 404);
        else
            ApiResponse::success("Lấy danh sách danh mục thành công !", 200, [
                "categories" => $categories,
            ]);
    } */
    public function get_order($params) {
        $id = $params['id'] ?? null;
        $order = null;

        if (empty($id) || !($order = $this->order->find_by_id($id)))
            return ApiResponse::error("Đơn hàng không tồn tại !", 404);

        ApiResponse::success("Lấy đơn hàng thành công !", 200, $order);
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
            $orderDetail = [
                'id' => bin2hex(random_bytes(16)),
                'order_id' => $order_id,
                'book_id' => $cart['book_id'],
                'quantity' => $cart['quantity'],
                'price' => (float)$cart['price'],
            ];
            $this->order->create_orders_detail($orderDetail);
            // $this->cart->delete($cart['id']);
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
            
            ApiResponse::success("Tạo đơn hàng thành công !", 200, [
                'payment_url' => 'http://localhost:8000/order-result.php?status=success&method=cod&order_id=' . $order_id,
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
    /* public function category_active($params) {
        $id = $params['id'] ?? null;
        $is_actidve = $params['is_active'] ?? null;

        if (empty($id) || !$this->category->find_by_id($id))
            return ApiResponse::error("Danh mục không tồn tại !", 404);
        if (!in_array($is_active, [0, 1]))
            return ApiResponse::error("Trạng thái không hợp lệ !", 400);

        $this->category->update($id, ['is_active' => $is_active]);

        ApiResponse::success("Cập nhật trạng thái danh mục thành công !", 200);
    } */
}
