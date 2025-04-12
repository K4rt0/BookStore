<?php
session_start();

$page_title = "Admin Dashboard - User Management";
$layout = 'admin';
ob_start();

$base_url = $_ENV['API_BASE_URL'];

// Get query parameters from the URL
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$filters = $_GET['filters'] ?? '';
$sort = $_GET['sort'] ?? 'updated_at,desc';
$search = $_GET['search'] ?? '';

$api_url = $base_url . "/user?action=get-all-users-pagination";
$query_params = array_filter([
    'page' => $page,
    'limit' => $limit,
    'filters' => $filters,
    'sort' => $sort,
    'search' => $search
]);
$api_url .= !empty($query_params) ? '&' . http_build_query($query_params) : '';

function call_api($url) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: ' . 'Bearer ' . ($_SESSION['access_token'] ?? ''),
        ],
    ]);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $data = json_decode($response, true);

    if ($http_code === 401 && isset($data['message']) && stripos($data['message'], 'expired') !== false) {
        session_destroy();
        header("Location: /login?error=expired_token");
        exit;
    }

    return $data;
}

$response = call_api($api_url);
$users = ($response && $response['success']) ? ($response['data']['users'] ?? []) : [];
$error_message = !$response || !$response['success'] ? ($response['message'] ?? 'Failed to fetch user data.') : '';
?>

<div class="container mt-4">
    <h2 class="py-2">User Management</h2>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="filters" class="form-control">
                    <option value="">All Status</option>
                    <option value="active" <?= $filters === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $filters === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-control">
                    <option value="updated_at,desc" <?= $sort === 'updated_at,desc' ? 'selected' : '' ?>>Updated At (Desc)</option>
                    <option value="updated_at,asc" <?= $sort === 'updated_at,asc' ? 'selected' : '' ?>>Updated At (Asc)</option>
                    <option value="created_at,desc" <?= $sort === 'created_at,desc' ? 'selected' : '' ?>>Created At (Desc)</option>
                    <option value="created_at,asc" <?= $sort === 'created_at,asc' ? 'selected' : '' ?>>Created At (Asc)</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Apply</button>
            </div>
        </div>
        <input type="hidden" name="page" value="1">
        <input type="hidden" name="limit" value="<?= $limit ?>">
    </form>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><span title="<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars(substr($user['id'], 0, 16)) ?>...</span></td>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['phone']) ?></td>
                        <td>
                            <span class="badge <?= $user['status'] === 'Active' ? 'bg-success' : 'bg-danger' ?>">
                                <?= htmlspecialchars($user['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars(substr($user['created_at'], 0, 10)) ?></td>
                        <td class="d-flex align-items-center gap-2 flex-wrap">
                            <?php if (strtolower($user['status']) === 'active'): ?>
                                <button type="button" class="btn btn-sm btn-danger toggle-user-status" 
                                        data-user-id="<?= htmlspecialchars($user['id']) ?>" data-status="inactive">
                                    Disable
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-success toggle-user-status" 
                                        data-user-id="<?= htmlspecialchars($user['id']) ?>" data-status="active">
                                    Activate
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center">No users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (count($users) > 10): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>&filters=<?= $filters ?>&sort=<?= $sort ?>&search=<?= $search ?>">Previous</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>&filters=<?= $filters ?>&sort=<?= $sort ?>&search=<?= $search ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.toggle-user-status').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const userId = this.getAttribute('data-user-id');
            const newStatus = this.getAttribute('data-status');

            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = 'Processing...';

            const confirmMsg = newStatus === 'active'
                ? 'Are you sure you want to activate this user?'
                : 'Are you sure you want to disable this user?';

            if (!confirm(confirmMsg)) {
                btn.disabled = false;
                btn.textContent = originalText;
                return;
            }

            const apiUrl = `<?= $base_url ?>/user?action=update-status&id=${encodeURIComponent(userId)}&status=${newStatus}`;

            try {
                const response = await fetch(apiUrl, {
                    method: 'PATCH',
                    headers: {
                        'Authorization': 'Bearer <?= $_SESSION['access_token'] ?? '' ?>',
                        'Content-Type': 'application/json'
                    },
                });

                console.log('Response status:', response.status); // Debugging: Log the status

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status} - ${response.statusText}`);
                }

                const data = await response.json();
                console.log('Response data:', data); // Debugging: Log the response data

                if (data.success) {
                    alert(`User has been ${newStatus === 'active' ? 'activated' : 'disabled'} successfully.`);
                    location.reload();
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            } catch (err) {
                console.error('Fetch error:', err); // Debugging: Log the error
                alert('Failed to update user status: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin-layout.php';
?>