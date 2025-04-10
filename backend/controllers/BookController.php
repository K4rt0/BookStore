<?php
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../helpers/ImgurService.php';

class BookController {
    private $book;
    private $imgur;
    private $category;

    public function __construct() {
        $this->book = new Book();
        $this->category = new Category();
        $this->imgur = new ImgurService();
    }

    // GET methods
    public function get_book($query) {
        $id = isset($query['id']) ? $query['id'] : null;
        if (!$id) return ApiResponse::error("Thiếu ID sách !", 400);
    
        $book = $this->book->find_by_id($id);
        if (!$book) return ApiResponse::error("Sách không tồn tại !", 404);
        if ($book['is_deleted'] == 1) return ApiResponse::error("Sách không tồn tại !", 404);
    
        ApiResponse::success("Lấy thông tin sách thành công !", 200, $book);
    }
    public function get_all_books() {
        $books = $this->book->get_all_books();
        ApiResponse::success("Lấy danh sách danh mục thành công !", 200, $books);
    }
    public function get_all_books_pagination($query) {
        $page = isset($query['page']) && is_numeric($query['page']) && $query['page'] > 0 ? (int)$query['page'] : 1;
        $limit = isset($query['limit']) && is_numeric($query['limit']) && $query['limit'] > 0 ? (int)$query['limit'] : 10;
        $offset = ($page - 1) * $limit;
        $filters = [];

        if (isset($query['search']) && is_string($query['search']) && trim($query['search']) !== '')
            $filters['search'] = trim($query['search']);

        $booleanFilters = ['is_deleted', 'is_featured', 'is_new', 'is_best_seller', 'is_discounted'];

        foreach ($booleanFilters as $filterKey) {
            if (isset($query[$filterKey])) {
                $value = $query[$filterKey];
                if ($value === '1' || $value === '0') {
                    $filters[$filterKey] = (int)$value;
                }
            }
        }

        if (isset($query['category'])) {
            if (is_array($query['category'])) {
                $filters['category'] = array_filter($query['category'], function ($categoryId) {
                    return is_string($categoryId) && !empty($categoryId);
                });
            } elseif (is_string($query['category']) && !empty($query['category'])) {
                $filters['category'] = [$query['category']];
            }
        }

        $validSortOptions = ['price_at_asc', 'price_at_desc', 'stock_qty_at_asc', 'stock_qty_at_desc'];
        $sort = isset($query['sort']) && in_array($query['sort'], $validSortOptions) ? $query['sort'] : 'price_at_desc';

        $books = $this->book->get_all_books_pagination($limit, $offset, $filters, $sort);

        if (empty($books))
            return ApiResponse::error("Không có cuốn sách nào !", 404);
        else
            ApiResponse::success("Lấy danh sách sách thành công !", 200, [
                "books" => $books,
            ]);
    }
    
    // POST methods
    public function create() {
        $id = bin2hex(random_bytes(16));
        $data = [
            'id' => $id,
            'title' => $_POST['title'] ?? '',
            'author' => $_POST['author'] ?? '',
            'publisher' => $_POST['publisher'] ?? '',
            'publication_date' => $_POST['publication_date'] ?? '',
            'price' => $_POST['price'] ?? 0,
            'stock_quantity' => $_POST['stock_quantity'] ?? 0,
            'description' => $_POST['description'] ?? '',
            'short_description' => $_POST['short_description'] ?? '',
            'category_id' => $_POST['category_id'] ?? null,
            'is_deleted' => isset($_POST['is_deleted']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_new' => isset($_POST['is_new']) ? 1 : 0,
            'is_best_seller' => isset($_POST['is_best_seller']) ? 1 : 0,
            'is_discounted' => isset($_POST['is_discounted']) ? 1 : 0
        ];

        if (empty($data['title']) || strlen($data['title']) < 3)
            return ApiResponse::error("Tiêu đề phải có ít nhất 3 ký tự !", 400);
        if (empty($data['author']))
            return ApiResponse::error("Tác giả không được để trống !", 400);
        if (empty($data['publisher']))
            return ApiResponse::error("Nhà xuất bản không được để trống !", 400);
        if (empty($data['publication_date']) || !strtotime($data['publication_date']))
            return ApiResponse::error("Ngày xuất bản không hợp lệ !", 400);
        if (!is_numeric($data['price']) || $data['price'] <= 0)
            return ApiResponse::error("Giá phải là số dương !", 400);
        if (!is_numeric($data['stock_quantity']) || $data['stock_quantity'] < 0)
            return ApiResponse::error("Số lượng tồn kho phải là số không âm !", 400);
        if (empty($data['category_id']) || !$this->category->find_by_id($data['category_id']))
            return ApiResponse::error("Danh mục không hợp lệ !", 400);
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK)
            return ApiResponse::error("Thiếu ảnh hoặc ảnh bị lỗi !", 400);

        $imageData = base64_encode(file_get_contents($_FILES['image']['tmp_name']));
        $uploadResult = $this->imgur->upload($imageData);

        $data['image_url'] = $uploadResult['data']['link'];
        $data['delete_hash'] = $uploadResult['data']['deletehash'];

        if (!$uploadResult['success'])
            return ApiResponse::error("Upload ảnh thất bại !", 500, $uploadResult);

        if ($this->book->create($data))
            ApiResponse::success("Tạo sách thành công !", 200);
        else
            ApiResponse::error("Tạo sách thất bại !", 500);
    }

    // PUT methods
    public function update() {
        $id = $_POST['id'] ?? null;
        if (!$id) return ApiResponse::error("Thiếu ID sách cần cập nhật !", 400);

        $existing = $this->book->find_by_id($id);
        if (!$existing) return ApiResponse::error("Sách không tồn tại !", 404);

        $data = [
            'title' => $_POST['title'] ?? $existing['title'],
            'author' => $_POST['author'] ?? $existing['author'],
            'publisher' => $_POST['publisher'] ?? $existing['publisher'],
            'publication_date' => $_POST['publication_date'] ?? $existing['publication_date'],
            'price' => $_POST['price'] ?? $existing['price'],
            'stock_quantity' => $_POST['stock_quantity'] ?? $existing['stock_quantity'],
            'description' => $_POST['description'] ?? $existing['description'],
            'short_description' => $_POST['short_description'] ?? $existing['short_description'],
            'category_id' => $_POST['category_id'] ?? $existing['category_id'],
            'is_deleted' => isset($_POST['is_deleted']) ? (int)$_POST['is_deleted'] : 0,
            'is_featured' => isset($_POST['is_featured']) ? (int)$_POST['is_featured'] : 0,
            'is_new' => isset($_POST['is_new']) ? (int)$_POST['is_new'] : 0,
            'is_best_seller' => isset($_POST['is_best_seller']) ? (int)$_POST['is_best_seller'] : 0,
            'is_discounted' => isset($_POST['is_discounted']) ? (int)$_POST['is_discounted'] : 0,
        ];

        if (strlen($data['title']) < 3)
            return ApiResponse::error("Tiêu đề phải có ít nhất 3 ký tự !", 400);
        if (empty($data['author']) || empty($data['publisher']))
            return ApiResponse::error("Tác giả và NXB không được để trống !", 400);
        if (!strtotime($data['publication_date']))
            return ApiResponse::error("Ngày xuất bản không hợp lệ !", 400);
        if (!is_numeric($data['price']) || $data['price'] <= 0)
            return ApiResponse::error("Giá phải là số dương !", 400);
        if (!is_numeric($data['stock_quantity']) || $data['stock_quantity'] < 0)
            return ApiResponse::error("Số lượng tồn kho phải lớn hơn 0 !", 400);
        if (empty($data['category_id']) || !$this->category->find_by_id($data['category_id']))
            return ApiResponse::error("Danh mục không hợp lệ !", 400);

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            if (!empty($existing['delete_hash']))
                $this->imgur->delete($existing['delete_hash']);

            $imageData = base64_encode(file_get_contents($_FILES['image']['tmp_name']));
            $uploadResult = $this->imgur->upload($imageData);

            if (!$uploadResult['success'])
                return ApiResponse::error("Upload ảnh thất bại !", 500, $uploadResult);

            $data['image_url'] = $uploadResult['data']['link'];
            $data['delete_hash'] = $uploadResult['data']['deletehash'];
        }

        if ($this->book->update($id, $data))
            ApiResponse::success("Cập nhật sách thành công !");
        else
            ApiResponse::error("Cập nhật sách thất bại !", 500);
    }

    // PATCH methods
    public function undo_delete($params) {
        $id = $params['id'] ?? null;
        if (!$id) return ApiResponse::error("Thiếu ID sách cần khôi phục !", 400);
    
        $existing = $this->book->find_by_id($id);
        if (!$existing) return ApiResponse::error("Sách không tồn tại !", 404);
    
        if ($this->book->update($id, ['is_deleted' => 0]))
            ApiResponse::success("Khôi phục sách thành công !", 200);
        else
            ApiResponse::error("Khôi phục sách thất bại !", 500);
    }

    // DELETE methods
    public function delete($params) {
        $id = $params['id'] ?? null;
        if (!$id) return ApiResponse::error("Thiếu ID sách cần xóa !", 400);
    
        $existing = $this->book->find_by_id($id);
        if (!$existing) return ApiResponse::error("Sách không tồn tại !", 404);
    
        if ($this->book->delete($id))
            ApiResponse::success("Xóa sách thành công !", 200);
        else
            ApiResponse::error("Xóa sách thất bại !", 500);
    }
}
