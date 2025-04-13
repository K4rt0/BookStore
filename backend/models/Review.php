<?php
require_once __DIR__ . '/../config/database.php';

class Review {
    private $conn;
    private $table = 'reviews';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function get_reviews_by_book($bookId) {
        $stmt = $this->conn->prepare("
            SELECT reviews.id, reviews.book_id, reviews.rating, reviews.comment, users.full_name 
            FROM {$this->table} 
            INNER JOIN users ON reviews.user_id = users.id 
            WHERE reviews.book_id = ?
        ");
        $stmt->execute([$bookId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (id, user_id, book_id, rating, comment) VALUES (:id, :user_id, :book_id, :rating, :comment)");
        return $stmt->execute($data);
    }

    public function validate_review($userId, $orderId, $bookId) {
        $stmt = $this->conn->prepare('
            SELECT order_details.id, order_details.is_commented
            FROM orders 
            INNER JOIN order_details ON orders.id = order_details.order_id 
            WHERE orders.user_id = ? AND orders.id = ? AND order_details.book_id = ?
        ');
        $stmt->execute([$userId, $orderId, $bookId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?? null;
    }
}
