<?php
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/AuthMiddleware.php';

class CategoryController {
    private $category;

    public function __construct() {
        $this->category = new Category();
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

    public function update() {
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['id']) || !$this->category->find_by_id($input['id']))
            return ApiResponse::error("Danh mục không tồn tại !", 404);
        if (array_key_exists('name', $input) && (empty($input['name']) || strlen($input['name']) < 3))
            return ApiResponse::error("Tên danh mục phải có ít nhất 3 ký tự !", 400);
        if ($this->category->find_by_name($input['name']))
            return ApiResponse::error("Tên danh mục đã tồn tại !", 409);

        $this->category->update($input['id'], $input);

        ApiResponse::success("Cập nhật danh mục thành công !", 200);
    }
}
