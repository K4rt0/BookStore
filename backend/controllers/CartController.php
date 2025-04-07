<?php
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../models/User.php';

class CartController {
    private $cart;
    private $book;
    private $user;

    public function __construct() {
        $this->cart = new Cart();
        $this->book = new Book();
        $this->user = new User();
    }

    // GET methods
    public function get_cart($user_id) {
        // var_dump($user_id);
        if (!$user_id) return ApiResponse::error("Thiếu ID người dùng !", 400);

        $cart_items = $this->cart->get_cart_by_user($user_id);
        if (!$cart_items) return ApiResponse::error("Giỏ hàng trống !", 404);

        return ApiResponse::success("Lấy giỏ hàng thành công !", 200, $cart_items);
    }
    
    // POST methods
    public function add_to_cart() {
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['user_id']) || (!empty($input['user_id']) && !$this->user->find_by_id($input['user_id'])))
            return ApiResponse::error("Không tìm thấy người dùng này !", 404);
        if (empty($input['book_id']) || (!empty($input['book_id']) && !$this->book->find_by_id($input['book_id'])))
            return ApiResponse::error("Không tìm thấy cuốn sách này !", 404);

        $existing_item = $this->cart->find_by_user_and_book($input['user_id'], $input['book_id']);
        $quantity = $input['quantity'] ?? 1;

        if ($existing_item) {
            $new_quantity = $existing_item['quantity'] + $quantity;
            $this->cart->update_quantity($existing_item['id'], $new_quantity);
            return ApiResponse::success("Đã cập nhật số lượng trong giỏ hàng !", 200);
        } else {
            $input['id'] = bin2hex(random_bytes(16));
            $input['quantity'] = $quantity;
            $this->cart->add_to_cart($input);
            return ApiResponse::success("Đã thêm vào giỏ hàng !", 200);
        }
    }

    // PATCH methods
    public function update_cart($user_id) {
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['id']) || !$this->cart->find_by_id($input['id']))
            return ApiResponse::error("Giỏ hàng không tồn tại !", 404);
        if (empty($input['quantity']) || $input['quantity'] < 1)
            return ApiResponse::error("Số lượng không hợp lệ !", 400);

        $existing_item = $this->cart->find_by_id($input['id']);
        if ($existing_item['user_id'] !== $user_id)
            return ApiResponse::error("Bạn không có quyền cập nhật giỏ hàng này !", 403);

        $this->cart->update_quantity($input['id'], $input['quantity']);
        return ApiResponse::success("Cập nhật giỏ hàng thành công !", 200);
    }

    // DELETE methods
    public function delete($query, $user_id) {
        $id = $query['id'] ?? null;
        if (!$id) return ApiResponse::error("Giỏ hàng không tồn tại !", 400);

        $existing = $this->cart->find_by_id($id);
        if (!$existing) return ApiResponse::error("Giỏ hàng không tồn tại !", 404);

        if ($existing['user_id'] !== $user_id) 
            return ApiResponse::error("Bạn không có quyền xóa giỏ hàng này !", 403);

        if ($this->cart->delete($id))
            return ApiResponse::success("Xóa giỏ hàng thành công !", 200);
        else
            return ApiResponse::error("Xóa giỏ hàng thất bại !", 500);
    }
}
