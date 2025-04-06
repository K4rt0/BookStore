<?php
require_once __DIR__ . '/../config/database.php';

class Category {
    private $conn;
    private $table = 'categories';

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
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (id, name, description, is_active) VALUES (:id, :name, :description, :is_active)");
        return $stmt->execute($data);
    }

    public function update($id, $data) {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
        }
        $fields = implode(', ', $fields);
        $data['id'] = $id;

        $stmt = $this->conn->prepare("UPDATE {$this->table} SET {$fields} WHERE id = :id");
        return $stmt->execute($data);
    }
    public function get_all_categories() {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_all_categories_pagination($limit, $offset, $filters = [], $sort = 'created_at_desc') {
        $query = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if (array_key_exists('is_active', $filters)) {
            $query .= " AND is_active = :is_active";
            $params['is_active'] = $filters['is_active'];
        }

        if (!empty($filters['search'])) {
            $query .= " AND name LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        switch ($sort) {
            case 'created_at_asc':
                $query .= " ORDER BY created_at ASC";
                break;
            case 'created_at_desc':
                $query .= " ORDER BY created_at DESC";
                break;
            case 'updated_at_asc':
                $query .= " ORDER BY updated_at ASC";
                break;
            case 'updated_at_desc':
                $query .= " ORDER BY updated_at DESC";
                break;
            default:
                $query .= " ORDER BY created_at DESC";
                break;
        }

        $query .= " LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => &$value) {
            $stmt->bindValue(":{$key}", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        return $stmt->execute() ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;
    }
}
