<?php
require_once __DIR__ . '/../helpers/ApiResponse.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

class AdminController {
    public function login() {
        $input = json_decode(file_get_contents("php://input"), true);
        $username = $input['username'] ?? null;
        $password = $input['password'] ?? null;

        if (empty($username) || empty($password)) {
            ApiResponse::error("Tên đăng nhập và mật khẩu không được để trống", 400);
            return;
        }

        $adminUsername = $_ENV['ADMIN_USER'] ?? null;
        $adminPassword = $_ENV['ADMIN_PASSWORD'] ?? null;

        if (empty($adminUsername) || empty($adminPassword)) {
            ApiResponse::error("Hệ thống đang bị lỗi, vui lòng thử lại sau", 500);
            return;
        }

        if ($username === $adminUsername && $password === $adminPassword) {
            $access = AuthHelper::generateAccessToken('admin', $_ENV['ADMIN_JWT_EXPIRATION'], $_ENV['ADMIN_JWT_SECRET']);
            ApiResponse::success("Đăng nhập admin thành công !", 200,
            [
                "access_token" => $access,
            ]);
        } else {
            ApiResponse::error("Thông tin đăng nhập sai", 401);
        }
    }

    /* public function getAllUsers() {
        $user = new UserController();
        $users = $user->getAllUsers();

        if ($users) {
            ApiResponse::success("Lấy danh sách người dùng thành công !", 200, [
                "users" => $users,
            ]);
        } else {
            ApiResponse::error("Không có người dùng nào !", 404);
        }
    } */

    /* public function getAllUsers($page = 1, $limit = 10, $filter = 'all', $sort = 'all', $search = '') {
        $user = new UserController();
        $users = $user->getAllUsers();

        if ($users) {
            // Apply search filter
            if (!empty($search)) {
                $users = array_filter($users, function ($u) use ($search) {
                    return stripos($u['name'], $search) !== false ||
                           stripos($u['email'], $search) !== false ||
                           stripos($u['phone'], $search) !== false;
                });
            }

            // Apply block filter
            if ($filter === 'is_blocked') {
                $users = array_filter($users, function ($u) {
                    return $u['is_blocked'] ?? false;
                });
            }

            // Apply sorting
            if ($sort === 'update_desc') {
                usort($users, function ($a, $b) {
                    return strtotime($b['updated_at']) - strtotime($a['updated_at']);
                });
            } elseif ($sort === 'update_asc') {
                usort($users, function ($a, $b) {
                    return strtotime($a['updated_at']) - strtotime($b['updated_at']);
                });
            } elseif ($sort === 'create_desc') {
                usort($users, function ($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
            } elseif ($sort === 'create_asc') {
                usort($users, function ($a, $b) {
                    return strtotime($a['created_at']) - strtotime($b['created_at']);
                });
            }

            // Pagination logic
            $totalUsers = count($users);
            $totalPages = ceil($totalUsers / $limit);
            $offset = ($page - 1) * $limit;

            $paginatedUsers = array_slice($users, $offset, $limit);

            ApiResponse::success("Lấy danh sách người dùng thành công !", 200, [
                "users" => $paginatedUsers,
                "pagination" => [
                    "current_page" => $page,
                    "total_pages" => $totalPages,
                    "total_users" => $totalUsers,
                    "limit" => $limit,
                ],
            ]);
        } else {
            ApiResponse::error("Không có người dùng nào !", 404);
        }
    } */
}