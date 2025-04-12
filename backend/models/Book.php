<?php
require_once __DIR__ . '/../config/database.php';

class Book {
    private $conn;
    private $table = 'books';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function find_by_name($name) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE name = :name LIMIT 1");
        $stmt->execute(['name' => $name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function find_by_id($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $sql = "INSERT INTO books (
            id, title, author, publisher, publication_date, price, stock_quantity,
            description, short_description, image_url, delete_hash, category_id,
            is_deleted, is_featured, is_new, is_best_seller, is_discounted
        ) VALUES (
            :id, :title, :author, :publisher, :publication_date, :price, :stock_quantity,
            :description, :short_description, :image_url, :delete_hash, :category_id,
            :is_deleted, :is_featured, :is_new, :is_best_seller, :is_discounted
        )";
    
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }    

    public function update($id, $data) {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
        }
        $sql = "UPDATE books SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("UPDATE books SET is_deleted = 1 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function get_all_books() {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_all_books_pagination($limit, $offset, $filters = [], $sort = 'created_at_desc') {
        $query = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
    
        $booleanFields = ['is_deleted', 'is_featured', 'is_new', 'is_best_seller', 'is_discounted'];
    
        foreach ($booleanFields as $field) {
            if (array_key_exists($field, $filters)) {
                $query .= " AND {$field} = :{$field}";
                $params[$field] = $filters[$field];
            }
        }
    
        if (!empty($filters['search'])) {
            $query .= " AND (title LIKE :search OR author LIKE :search OR publisher LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['category'])) {
            $placeholders = [];
            foreach ($filters['category'] as $index => $categoryId) {
                $placeholder = "category_{$index}";
                $placeholders[] = ":{$placeholder}";
                $params[$placeholder] = $categoryId;
            }
            $query .= " AND category_id IN (" . implode(', ', $placeholders) . ")";
        }
    
        switch ($sort) {
            case 'price_at_asc':
                $query .= " ORDER BY price ASC";
                break;
            case 'price_at_desc':
                $query .= " ORDER BY price DESC";
                break;
            case 'stock_qty_at_asc':
                $query .= " ORDER BY stock_quantity ASC";
                break;
            case 'stock_qty_at_desc':
                $query .= " ORDER BY stock_quantity DESC";
                break;
            default:
                $query .= " ORDER BY created_at DESC";
                break;
        }
    
        $query .= " LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;
    
        // Thực thi câu lệnh
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => &$value) {
            $stmt->bindValue(":{$key}", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    
        return $stmt->execute() ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;
    }

    public function update_book_rating($bookId, $newRating) {
        $stmt = $this->conn->prepare('SELECT rating, rating_count FROM books WHERE id = ?');
        $stmt->execute([$bookId]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        $newRatingCount = $book['rating_count'] + 1;
        $newTotalRating = $book['rating'] * $book['rating_count'] + $newRating;
        $newAverageRating = $newTotalRating / $newRatingCount;

        $stmt = $this->conn->prepare('UPDATE books SET rating = ?, rating_count = ? WHERE id = ?');
        $stmt->execute([$newAverageRating, $newRatingCount, $bookId]);
    }
}
