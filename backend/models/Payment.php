<?php
require_once __DIR__ . '/../config/database.php';

class Payment {
    private $conn;
    private $table = 'payments';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (id, order_id, payment_method, status) VALUES (:id, :order_id, :payment_method, :status)");
        return $stmt->execute($data);
    }

    public function find_by_id($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
        }
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
