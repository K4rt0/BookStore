<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/AuthMiddleware.php';

class UserController {
    private $user;

    public function __construct() {
        $this->user = new User();
    }

    // GET methods
    public function get_all_users() {
        $users = $this->user->get_all_users();

        if (empty($users))
            return ApiResponse::error("Không có người dùng nào !", 404);
        else {
            $users = array_map(function($user) {
                unset($user['password'], $user['refresh_token']);
                return $user;
            }, $users);
            
            ApiResponse::success("Lấy danh sách người dùng thành công !", 200,
            [
                "users" => $users,
            ]);
        }
    }

    public function get_all_users_pagination($query) {
        $page = isset($query['page']) && is_numeric($query['page']) && $query['page'] > 0 ? (int)$query['page'] : 1;
        $limit = isset($query['limit']) && is_numeric($query['limit']) && $query['limit'] > 0 ? (int)$query['limit'] : 10;
        $offset = ($page - 1) * $limit;

        $filters = [
            'status' => isset($query['filters']) && is_string($query['filters']) ? $query['filters'] : null,
            'search' => isset($query['search']) && is_string($query['search']) && trim($query['search']) !== '' ? trim($query['search']) : null,
        ];

        
        $validSortOptions = ['created_at_asc', 'created_at_desc', 'updated_at_asc', 'updated_at_desc'];
        $sort = isset($query['sort']) && in_array($query['sort'], $validSortOptions) ? $query['sort'] : 'created_at_desc';

        $users = $this->user->get_all_users_pagination($limit, $offset, $filters, $sort);

        if (empty($users))
            return ApiResponse::error("Không có người dùng nào !", 404);
        else {
            $users = array_map(function($user) {
                unset($user['password'], $user['refresh_token']);
                return $user;
            }, $users);
            
            ApiResponse::success("Lấy danh sách người dùng thành công !", 200, [
                "users" => $users,
            ]);
        }
    }

    public function profile() {
        $userData = AuthMiddleware::requireAuth();
        $user = $this->user->find_by_id($userData->sub);
        
        if (!$user)
            return ApiResponse::error("Tài khoản không tồn tại !", 404);
        
        unset($user['password'], $user['refresh_token']);
        ApiResponse::success("Lấy thông tin tài khoản thành công !", 200, $user);
    }

    // POST methods
    public function login() {
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['email']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL))
            return ApiResponse::error("Email không hợp lệ !", 400);
        if (empty($input['password']))
            return ApiResponse::error("Mật khẩu không được để trống !", 400);

        $user = $this->user->find_by_email($input['email']);

        if (!$user || !password_verify($input['password'], $user['password']))
            return ApiResponse::error("Email hoặc mật khẩu không đúng", 401);

        
        $access = AuthHelper::generateAccessToken($user['id']);
        $refresh = AuthHelper::generateRefreshToken($user['id']);

        $this->user->update($user['id'], [
            'refresh_token' => $refresh,
        ]);

        ApiResponse::success("Đăng nhập thành công", 200, [
            'access_token' => $access,
            'refresh_token' => $refresh,
        ]);
    }

    public function register() {
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['email']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL))
            ApiResponse::error("Email không hợp lệ !", 400);
        if (empty($input['password']) || strlen($input['password']) < 6)
            ApiResponse::error("Mật khẩu phải có ít nhất 6 ký tự !", 400);
        if (empty($input['full_name']))
            ApiResponse::error("Họ và tên không được để trống !", 400);
        else {
            $input['id'] = bin2hex(random_bytes(16));
            $input['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
    
            $existing = $this->user->find_by_email($input['email']);
            if ($existing)
                return ApiResponse::error("Email đã tồn tại !", 409);
    
            if ($this->user->create($input))
                ApiResponse::success("Đăng ký thành công !", 201);
            else
                ApiResponse::error("Đăng ký thất bại !", 500);
        }
    }

    public function logout() {
        $userData = AuthMiddleware::requireAuth();

        $user = $this->user->find_by_id($userData->sub);
        if (!$user || empty($user['refresh_token'])) {
            return ApiResponse::error("Tài khoản đã được đăng xuất hoặc không hợp lệ", 400);
        }

        $this->user->update($userData->sub, [
            'refresh_token' => null,
        ]);
        ApiResponse::success("Đăng xuất thành công", 200);
    }

    public function refresh_token() {
        $input = json_decode(file_get_contents("php://input"), true);
        $refresh = $input['refresh_token'] ?? null;

        if (!$refresh) {
            ApiResponse::error("Không tìm thấy Refresh Token !", 400);
            return;
        }

        $decoded = AuthHelper::verifyToken($refresh);

        $userId = $decoded->sub;
        $user = $this->user->find_by_id($userId);
        if (!$user || $user['refresh_token'] !== $refresh) {
            ApiResponse::error("Refresh token không hợp lệ !", 401);
            return;
        }

        $newAccessToken = AuthHelper::generateAccessToken($userId);
        ApiResponse::success("Làm mới token thành công !", 200, [
            'access_token' => $newAccessToken,
        ]);
    }

    // PUT
    public function update_profile() {
        $userData = AuthMiddleware::requireAuth();
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['full_name']))
            ApiResponse::error("Họ và tên không được để trống !", 400);
        if (empty($input['phone']) || !preg_match('/^\d{10,11}$/', $input['phone']))
            ApiResponse::error("Số điện thoại không hợp lệ !", 400);

        if ($this->user->update($userData->sub, $input))
            ApiResponse::success("Cập nhật thông tin thành công !", 200);
        else
            ApiResponse::error("Cập nhật thông tin thất bại !", 500);
    }

    // PATCH
    public function update_password() {
        $userData = AuthMiddleware::requireAuth();
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['old_password']))
            ApiResponse::error("Mật khẩu cũ không được để trống !", 400);
        if (empty($input['new_password']) || strlen($input['new_password']) < 6)
            ApiResponse::error("Mật khẩu mới phải có ít nhất 6 ký tự !", 400);
        if (empty($input['old_password']) == $input['new_password'])
            ApiResponse::error("Mật khẩu mới không được giống mật khẩu cũ !", 400);
        if ($input['new_password'] !== $input['confirm_password'])
            ApiResponse::error("Mật khẩu xác nhận không khớp !", 400);

        $user = $this->user->find_by_id($userData->sub);
        if (!$user || !password_verify($input['old_password'], $user['password']))
            return ApiResponse::error("Mật khẩu cũ không đúng !", 401);

        $newPasswordHash = password_hash($input['new_password'], PASSWORD_DEFAULT);
        if ($this->user->update($userData->sub, ['password' => $newPasswordHash]))
            ApiResponse::success("Cập nhật mật khẩu thành công !", 200);
        else
            ApiResponse::error("Cập nhật mật khẩu thất bại !", 500);
    }

    public function update_status($query) {
        $userId = $query['id'] ?? null;
        $status = $query['status'] ?? null;

        if (empty($userId) || empty($status))
            return ApiResponse::error("Thiếu thông tin cần thiết !", 400);
        if (!in_array($status, ['active', 'inactive']))
            return ApiResponse::error("Trạng thái không hợp lệ !", 400);
        if (!$this->user->find_by_id($userId))
            return ApiResponse::error("Tài khoản không tồn tại !", 404);

        if ($this->user->update($userId, ['status' => $status]))
            ApiResponse::success("Cập nhật trạng thái thành công !", 200);
        else
            ApiResponse::error("Cập nhật trạng thái thất bại !", 500);
    }
}
