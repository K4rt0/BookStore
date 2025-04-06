<?php
require_once __DIR__ . '/../helpers/ImgurService.php';
require_once __DIR__ . '/../helpers/ApiResponse.php';

class ImgurController {
    private $imgur;

    public function __construct() {
        $this->imgur = new ImgurService();
    }

    public function upload() {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            return ApiResponse::error("Không tìm thấy ảnh hoặc upload lỗi", 400);
        }
    
        $imagePath = $_FILES['image']['tmp_name'];
        $imageData = file_get_contents($imagePath);
        $imageBase64 = base64_encode($imageData);
    
        $result = $this->imgur->upload($imageBase64);
    
        if ($result['success']) {
            return ApiResponse::success("Tải ảnh lên Imgur thành công", 200,
            [
                'link' => $result['data']['link'],
                'delete_hash' => $result['data']['deletehash']
            ]);
        } else {
            return ApiResponse::error("Tải ảnh thất bại", 500, $result);
        }
    }

    public function delete() {
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['delete_hash'])) {
            return ApiResponse::error("Thiếu delete_hash", 400);
        }

        $result = $this->imgur->delete($input['delete_hash']);

        if ($result['success']) {
            ApiResponse::success(null, "Đã xoá ảnh thành công");
        } else {
            ApiResponse::error("Xoá ảnh thất bại", 500, $result);
        }
    }
}
