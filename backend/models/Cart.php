<?php
require_once __DIR__ . '/../config/database.php';

class Cart {
    private $conn;
    private $table = 'carts';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function find_by_id($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function add_to_cart($data) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (id, user_id, book_id, quantity) VALUES (:id, :user_id, :book_id, :quantity)");
        return $stmt->execute($data);
    }

    public function find_by_user_and_book($user_id, $book_id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE user_id = :user_id AND book_id = :book_id LIMIT 1");
        $stmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update_quantity($id, $quantity) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET quantity = :quantity WHERE id = :id");
        return $stmt->execute(['id' => $id, 'quantity' => $quantity]);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function get_cart_by_user($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
