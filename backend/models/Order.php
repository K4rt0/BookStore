<?php
require_once __DIR__ . '/../config/database.php';

class Order {
    private $conn;
    private $table = 'orders';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function find_by_id($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (id, user_id, total_price, full_name, phone, status, shipping_address) VALUES (:id, :user_id, :total_price, :full_name, :phone, :status, :shipping_address)");
        return $stmt->execute($data);
    }

    public function create_orders_detail($data) {
        $stmt = $this->conn->prepare("INSERT INTO order_details (id, order_id, book_id, quantity, price) VALUES (:id, :order_id, :book_id, :quantity, :price)");
        return $stmt->execute($data);
    }

    public function delete($id) {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("DELETE FROM order_details WHERE order_id = :order_id");
            $stmt->execute(['order_id' => $id]);

            $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}
