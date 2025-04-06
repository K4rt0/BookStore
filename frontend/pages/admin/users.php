<?php
$page_title = "Admin Dashboard - User Management";
$layout = 'admin';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();

$base_url = $_ENV['API_BASE_URL'];

// Get query parameters from the URL
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$filters = isset($_GET['filters']) ? $_GET['filters'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'updated_at,desc';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$api_url = $base_url . "user?action=get-all-users-pagination";
$query_params = [
    'page' => $page,
    'limit' => $limit,
    'filters' => $filters,
    'sort' => $sort,
    'search' => $search
];
$query_params = array_filter($query_params, function($value) {
    return $value !== '';
});
$api_url .= !empty($query_params) ? '&' . http_build_query($query_params) : '';

// Function to make the API call using cURL
function call_api($url) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $_SESSION['access_token'],
        ],
    ]);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    return json_decode($response, true);
}

// Call the API
$response = call_api($api_url);

// Check if the API call was successful
if (!$response || !$response['success']) {
    $error_message = $response['message'] ?? 'Failed to fetch user data from the API.';
    $users = [];
} else {
    $users = $response['data']['users'] ?? [];
}
?>

<!-- HTML for the user management page -->
<div class="container mt-4">
    <h2>User Management</h2>

    <!-- Display error message if API call failed -->
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Filter and Search Form -->
    <form method="GET" action="" class="mb-4">
        <div class="row">
            <!-- Search -->
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <!-- Filter by Status -->
            <div class="col-md-3">
                <select name="filters" class="form-control">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $filters === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $filters === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <!-- Sort -->
            <div class="col-md-3">
                <select name="sort" class="form-control">
                    <option value="updated_at,desc" <?php echo $sort === 'updated_at,desc' ? 'selected' : ''; ?>>Updated At (Desc)</option>
                    <option value="updated_at,asc" <?php echo $sort === 'updated_at,asc' ? 'selected' : ''; ?>>Updated At (Asc)</option>
                    <option value="created_at,desc" <?php echo $sort === 'created_at,desc' ? 'selected' : ''; ?>>Created At (Desc)</option>
                    <option value="created_at,asc" <?php echo $sort === 'created_at,asc' ? 'selected' : ''; ?>>Created At (Asc)</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Apply</button>
            </div>
        </div>
        <input type="hidden" name="page" value="1">
        <input type="hidden" name="limit" value="<?php echo $limit; ?>">
    </form>

    <!-- Users Table -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td>
                            <span class="badge <?php echo $user['status'] === 'Active' ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo htmlspecialchars($user['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($user['updated_at']); ?></td>
                        <td>
                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">No users found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <nav aria-label="Page navigation">
        <ul class="pagination">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&filters=<?php echo $filters; ?>&sort=<?php echo $sort; ?>&search=<?php echo $search; ?>">Previous</a>
            </li>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&filters=<?php echo $filters; ?>&sort=<?php echo $sort; ?>&search=<?php echo $search; ?>">Next</a>
            </li>
        </ul>
    </nav>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin-layout.php';
?>