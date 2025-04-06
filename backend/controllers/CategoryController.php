<?php
require_once __DIR__ . '/../models/Category.php';

class CategoryController {
    private $category;

    public function __construct() {
        $this->category = new Category();
    }

    // GET methods
    public function get_all_categories() {
        $categories = $this->category->get_all_categories();
        ApiResponse::success("Lấy danh sách danh mục thành công !", 200, $categories);
    }

    public function get_all_categories_pagination($query) {
        $page = isset($query['page']) && is_numeric($query['page']) && $query['page'] > 0 ? (int)$query['page'] : 1;
        $limit = isset($query['limit']) && is_numeric($query['limit']) && $query['limit'] > 0 ? (int)$query['limit'] : 10;
        $offset = ($page - 1) * $limit;

        $filters = [
            'is_active' => isset($query['filters']) && is_string($query['filters']) ? $query['filters'] : null,
            'search' => isset($query['search']) && is_string($query['search']) && trim($query['search']) !== '' ? trim($query['search']) : null,
        ];

        $validSortOptions = ['created_at_asc', 'created_at_desc', 'updated_at_asc', 'updated_at_desc'];
        $sort = isset($query['sort']) && in_array($query['sort'], $validSortOptions) ? $query['sort'] : 'created_at_desc';

        $categories = $this->category->get_all_categories_pagination($limit, $offset, $filters, $sort);

        if (empty($categories))
            return ApiResponse::error("Không có danh mục nào !", 404);
        else
            ApiResponse::success("Lấy danh sách danh mục thành công !", 200, [
                "categories" => $categories,
            ]);
    }
    
    // POST methods
    public function create() {
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['name']) || strlen($input['name']) < 3)
            return ApiResponse::error("Tên danh mục phải có ít nhất 3 ký tự !", 400);
        if ($this->category->find_by_name($input['name']))
            return ApiResponse::error("Tên danh mục đã tồn tại !", 409);

        $input['id'] = bin2hex(random_bytes(16));
        $input['is_active'] = $input['is_active'] ?? 1;
        $input['description'] = $input['description'] ?? null;

        $this->category->create($input);

        ApiResponse::success("Tạo thêm danh mục thành công !", 200);
    }

    // PUT methods
    public function update() {
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['id']) || !$this->category->find_by_id($input['id']))
            return ApiResponse::error("Danh mục không tồn tại !", 404);
        if (array_key_exists('name', $input) && (empty($input['name']) || strlen($input['name']) < 3))
            return ApiResponse::error("Tên danh mục phải có ít nhất 3 ký tự !", 400);
        if (array_key_exists('name', $input) && $this->category->find_by_name($input['name']))
            return ApiResponse::error("Tên danh mục đã tồn tại !", 409);

        $this->category->update($input['id'], $input);

        ApiResponse::success("Cập nhật danh mục thành công !", 200);
    }

    // PATCH methods
    public function category_active($params) {
        $id = $params['id'] ?? null;
        $is_active = $params['is_active'] ?? null;

        if (empty($id) || !$this->category->find_by_id($id))
            return ApiResponse::error("Danh mục không tồn tại !", 404);
        if (!in_array($is_active, [0, 1]))
            return ApiResponse::error("Trạng thái không hợp lệ !", 400);

        $this->category->update($id, ['is_active' => $is_active]);

        ApiResponse::success("Cập nhật trạng thái danh mục thành công !", 200);
    }
}
