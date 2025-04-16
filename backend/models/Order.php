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
        $query = "SELECT o.* FROM {$this->table} o";
        $count_query = "SELECT COUNT(DISTINCT o.id) as total FROM {$this->table} o";
        $params = [];
        $where_conditions = [];

        if (!empty($filters['category_ids'])) {
            $query .= " LEFT JOIN order_details od ON o.id = od.order_id";
            $query .= " LEFT JOIN books b ON od.book_id = b.id";
            $query .= " LEFT JOIN categories c ON b.category_id = c.id";
            $count_query .= " LEFT JOIN order_details od ON o.id = od.order_id";
            $count_query .= " LEFT JOIN books b ON od.book_id = b.id";
            $count_query .= " LEFT JOIN categories c ON b.category_id = c.id";
        }

        if (!empty($filters['status'])) {
            $where_conditions[] = "o.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['category_ids'])) {
            $placeholders = implode(',', array_fill(0, count($filters['category_ids']), '?'));
            $where_conditions[] = "c.id IN ($placeholders)";
            foreach ($filters['category_ids'] as $catId) {
                $params[] = $catId;
            }
        }

        if (!empty($filters['search'])) {
            $where_conditions[] = "(o.full_name LIKE ? OR o.phone LIKE ? OR o.shipping_address LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($where_conditions)) {
            $query .= " WHERE " . implode(' AND ', $where_conditions);
            $count_query .= " WHERE " . implode(' AND ', $where_conditions);
        }

        $query .= $sort === 'newest' ? " ORDER BY o.created_at DESC" : " ORDER BY o.created_at ASC";

        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        try {
            error_log("Count Query: $count_query");
            error_log("Count Params: " . json_encode(array_slice($params, 0, count($params) - 2)));
            error_log("Main Query: $query");
            error_log("Main Params: " . json_encode($params));

            $count_stmt = $this->conn->prepare($count_query);
            for ($i = 0; $i < count($params) - 2; $i++) {
                $type = is_int($params[$i]) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $count_stmt->bindValue($i + 1, $params[$i], $type);
            }
            $count_stmt->execute();
            $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $this->conn->prepare($query);
            for ($i = 0; $i < count($params); $i++) {
                $type = is_int($params[$i]) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($i + 1, $params[$i], $type);
            }
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'orders' => $orders,
                'total' => (int)$total
            ];
        } catch (PDOException $e) {
            error_log("SQL Error: " . $e->getMessage());
            error_log("Query: $query");
            error_log("Params: " . json_encode($params));
            throw $e;
        }
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

    public function minus_stock($book_id, $quantity) {
        $stmt = $this->conn->prepare("UPDATE books SET stock_quantity = stock_quantity - :quantity WHERE id = :book_id");
        return $stmt->execute(['book_id' => $book_id, 'quantity' => $quantity]);
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

    public function get_detailed_statistics($type, $date = null, $month = null, $year = null) {
        $condition = "";
        $params = [];

        if ($type == 'daily') {
            $condition = "DATE(o.created_at) = ?";
            $params[] = $date;
        } elseif ($type == 'monthly') {
            $condition = "MONTH(o.created_at) = ? AND YEAR(o.created_at) = ?";
            $params = [$month, $year];
        } elseif ($type == 'yearly') {
            $condition = "YEAR(o.created_at) = ?";
            $params[] = $year;
        }

        $statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
        $details = [];

        $totalRevenueCheck = 0;
        $totalOrdersCheck = 0;

        foreach ($statuses as $status) {
            $stmt = $this->conn->prepare("
                SELECT
                    COUNT(DISTINCT o.id) AS total_orders,
                    IFNULL(SUM(o.total_price),0) AS total_revenue,
                    IFNULL(SUM(od.quantity),0) AS total_items
                FROM orders o
                LEFT JOIN order_details od ON o.id = od.order_id
                WHERE o.status = ? AND {$condition}
            ");

            $stmt->execute(array_merge([$status], $params));
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $details[$status] = [
                'total_orders' => (int)$result['total_orders'],
                'total_revenue' => (float)$result['total_revenue'],
                'total_items' => (int)$result['total_items']
            ];

            $totalRevenueCheck += (float)$result['total_revenue'];
            $totalOrdersCheck += (int)$result['total_orders'];
        }

        return [
            'total_orders' => $totalOrdersCheck,
            'total_revenue' => $totalRevenueCheck,
            'details' => $details
        ];
    }
}
