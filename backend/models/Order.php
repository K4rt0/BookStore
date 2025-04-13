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

    public function get_order_details($order_id) {
        $stmt = $this->conn->prepare("
            SELECT * 
            FROM order_details
            WHERE order_id = :order_id
        ");
        $stmt->execute(['order_id' => $order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find_all_orders() {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
/* 
    public function get_order_reviews($order_id) {
        $stmt = $this->conn->prepare("
            SELECT od.*, r.*, b.title AS book_title, u.full_name AS user_name
            FROM order_details od
            LEFT JOIN reviews r ON od.book_id = r.book_id AND od.user_id = r.user_id
            LEFT JOIN books b ON od.book_id = b.id
            LEFT JOIN users u ON r.user_id = u.id
            WHERE od.order_id = :order_id
        ");
        $stmt->execute(['order_id' => $order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } */

    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (id, user_id, total_price, full_name, phone, status, shipping_address) VALUES (:id, :user_id, :total_price, :full_name, :phone, :status, :shipping_address)");
        return $stmt->execute($data);
    }

    public function get_all_orders_pagination($limit, $offset, $filters = [], $sort = 'newest') {
        $query = "
            SELECT o.*
            FROM {$this->table} o
            LEFT JOIN order_details od ON o.id = od.order_id
            LEFT JOIN books b ON od.book_id = b.id
            LEFT JOIN categories c ON b.category_id = c.id
            WHERE 1=1
        ";
    
        $params = [];
    
        if (!empty($filters['status'])) {
            $query .= " AND o.status = ?";
            $params[] = $filters['status'];
        }
    
        if (!empty($filters['category_ids'])) {
            $placeholders = implode(',', array_fill(0, count($filters['category_ids']), '?'));
            $query .= " AND c.id IN ($placeholders)";
            foreach ($filters['category_ids'] as $catId) {
                $params[] = $catId;
            }
        }
    
        if (!empty($filters['search'])) {
            $query .= " AND (o.full_name LIKE ? OR o.phone LIKE ? OR o.shipping_address LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
    
        $query .= $sort === 'newest' ? " ORDER BY o.created_at DESC" : " ORDER BY o.created_at ASC";
    
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
    
        $stmt = $this->conn->prepare($query);
        foreach ($params as $index => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($index + 1, $value, $type);
        }
    
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find_all_orders_by_user_id($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_all_my_orders_pagination($userId, $limit, $offset, $filters = [], $sort = 'newest') {
        $query = "
            SELECT DISTINCT o.*
            FROM {$this->table} o
            LEFT JOIN payments p ON o.id = p.order_id
            LEFT JOIN order_details od ON o.id = od.order_id
            LEFT JOIN books b ON od.book_id = b.id
            LEFT JOIN categories c ON b.category_id = c.id
            WHERE o.user_id = ?
        ";
    
        $params = [$userId];
    
        if (!empty($filters['status'])) {
            $query .= " AND o.status = ?";
            $params[] = $filters['status'];
        }
    
        if (!empty($filters['payment_status'])) {
            $query .= " AND p.status = ?";
            $params[] = $filters['payment_status'];
        }
    
        if (!empty($filters['category_ids'])) {
            $placeholders = implode(',', array_fill(0, count($filters['category_ids']), '?'));
            $query .= " AND c.id IN ($placeholders)";
            foreach ($filters['category_ids'] as $catId) {
                $params[] = $catId;
            }
        }
    
        if (!empty($filters['search'])) {
            $query .= " AND (o.full_name LIKE ? OR o.phone LIKE ? OR o.shipping_address LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
    
        $query .= $sort === 'newest' ? " ORDER BY o.created_at DESC" : " ORDER BY o.created_at ASC";
    
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
    
        $stmt = $this->conn->prepare($query);
        foreach ($params as $index => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($index + 1, $value, $type);
        }
    
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function update_status($query) {
        $order_id = $query['order_id'] ?? null;
        $status = $query['status'] ?? null;

        if (empty($order_id) || empty($status)) {
            return ApiResponse::error("Thiếu thông tin đơn hàng hoặc trạng thái đơn hàng !", 400);
        }

        $stmt = $this->conn->prepare("UPDATE {$this->table} SET status = :status WHERE id = :id");
        return $stmt->execute(['id' => $order_id, 'status' => $status]);
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

    public function update_is_commented($id) {
        $stmt = $this->conn->prepare('UPDATE order_details SET is_commented = TRUE WHERE id = ?');
        $stmt->execute([$id]);
    }
}
