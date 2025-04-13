<?php
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../models/Order.php';

class ReviewController {
    private $review;
    private $book;
    private $order;

    public function __construct() {
        $this->review = new Review();
        $this->book = new Book();
        $this->order = new Order();
    }

    public function get_reviews($params) {
        $bookId = $params['book_id'] ?? null;
        if ($bookId) {
            $reviews = $this->review->get_reviews_by_book($bookId);
            echo json_encode($reviews);
        } else {
            echo json_encode(['error' => 'Book ID is required']);
        }
    }

    public function create_review($userId) {
        $input = json_decode(file_get_contents("php://input"), true);
        $orderId = $input['order_id'] ?? null;
        $bookId = $input['book_id'] ?? null;
        $rate = isset($input['rate']) ? (int)$input['rate'] : null;
        $comment = $input['comment'] ?? '';

        if (empty($orderId) || empty($bookId) || $rate === null) {
            return ApiResponse::error("Thiếu thông tin đơn hàng hoặc đánh giá !", 400);
        }

        $validation = $this->review->validate_review($userId, $orderId, $bookId);
        if (empty($validation))
            return ApiResponse::error("Bạn không có quyền đánh giá sản phẩm này !", 400);
        if(!empty($validation) && $validation['is_commented'] == true)
            return ApiResponse::error("Bạn đã đánh giá sản phẩm này rồi !", 400);

        $data = [
            'id' => bin2hex(random_bytes(16)),
            'user_id' => $userId,
            'book_id' => $bookId,
            'rating' => $rate,
            'comment' => $comment
        ];
        $this->review->create($data);
        $this->book->update_book_rating($bookId, $rate);
        $this->order->update_is_commented($validation['id']);
        ApiResponse::success("Đánh giá thành công !", 200);
    }
}
?>
