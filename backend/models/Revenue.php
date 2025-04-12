<?php
class Revenue {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getRevenueReport() {
        $sql = "SELECT 
                    SUM(od.quantity * od.price) AS total_revenue,
                    SUM(od.quantity) AS total_books_sold
                FROM orders o
                JOIN order_details od ON o.id = od.order_id
                WHERE o.status = 'completed'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}