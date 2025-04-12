<?php
$page_title = "Admin Dashboard - Review Management";
$layout = 'admin';
ob_start();

// Start session
session_start();

// API base URL and session variables
$api_base_url = $_ENV['API_BASE_URL'];
$access_token = $_SESSION['access_token'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
$is_admin = $_SESSION['is_admin'] ?? false;

// Redirect to login if not authenticated
if (empty($access_token) || empty($user_id)) {
    header("Location: /login?redirect=reviews-list");
    exit();
}

// Check admin role
if (!$is_admin) {
    header("Location: /?error=" . urlencode("Access denied: Admin privileges required"));
    exit();
}

// Get query parameters from the URL
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$filters = $_GET['filters'] ?? ''; // Rating filter (e.g., 1-5)
$sort = $_GET['sort'] ?? 'updated_at,desc';
$search = $_GET['search'] ?? '';

// Mock review data based on the reviews table schema
$all_reviews = [
    [
        'id' => '123e4567-e89b-12d3-a456-426614174000',
        'user_id' => 'user-001',
        'book_id' => 'book-001',
        'rating' => 5,
        'comment' => 'Great book, highly recommend!',
        'created_at' => '2025-04-07 10:00:00',
        'updated_at' => '2025-04-07 10:00:00'
    ],
    [
        'id' => '987fcdeb-51a2-4f3e-9d8a-7b5c4e2d1f9a',
        'user_id' => 'user-002',
        'book_id' => 'book-002',
        'rating' => 3,
        'comment' => 'It was okay, nothing special.',
        'created_at' => '2025-04-06 15:30:00',
        'updated_at' => '2025-04-06 15:30:00'
    ],
    [
        'id' => '456abcef-78d4-5e6f-1a2b-3c4d5e6f7a8b',
        'user_id' => 'user-003',
        'book_id' => 'book-003',
        'rating' => 4,
        'comment' => 'Really enjoyed it, fast shipping too!',
        'created_at' => '2025-04-05 08:45:00',
        'updated_at' => '2025-04-05 08:45:00'
    ]
];

// Filter and sort mock data
$reviews = $all_reviews;

// Filter by rating
if (!empty($filters)) {
    $reviews = array_filter($reviews, function($review) use ($filters) {
        return $review['rating'] == (int)$filters;
    });
}

// Search by comment, user_id, or book_id
if (!empty($search)) {
    $reviews = array_filter($reviews, function($review) use ($search) {
        return stripos($review['comment'], $search) !== false ||
               stripos($review['user_id'], $search) !== false ||
               stripos($review['book_id'], $search) !== false;
    });
}

// Sort data
if (!empty($sort)) {
    list($sort_field, $sort_direction) = explode(',', $sort);
    usort($reviews, function($a, $b) use ($sort_field, $sort_direction) {
        if ($sort_direction === 'asc') {
            return $a[$sort_field] <=> $b[$sort_field];
        } else {
            return $b[$sort_field] <=> $a[$sort_field];
        }
    });
}

// Pagination
$total_reviews = count($reviews);
$offset = ($page - 1) * $limit;
$reviews = array_slice($reviews, $offset, $limit);

// Uncomment and adjust this block for real API calls
/*
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

$api_url = $api_base_url . "reviews?action=get-all-reviews-pagination";
$query_params = array_filter([
    'page' => $page,
    'limit' => $limit,
    'filters' => $filters,
    'sort' => $sort,
    'search' => $search
]);
$api_url .= !empty($query_params) ? '&' . http_build_query($query_params) : '';

$response = call_api($api_url);
$reviews = ($response && $response['success']) ? ($response['data']['reviews'] ?? []) : [];
$total_reviews = ($response && $response['success']) ? ($response['data']['total'] ?? 0) : 0;
$error_message = !$response || !$response['success'] ? ($response['message'] ?? 'Failed to fetch review data.') : '';
*/

// Check for error messages from redirect
$error_message = $_GET['error'] ?? '';

?>

<div class="container mt-4 card">
    <h2 class="py-2">Review Management</h2>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search reviews (Comment, User ID, Book ID)..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="filters" class="form-control">
                    <option value="">All Ratings</option>
                    <option value="5" <?= $filters === '5' ? 'selected' : '' ?>>5 Stars</option>
                    <option value="4" <?= $filters === '4' ? 'selected' : '' ?>>4 Stars</option>
                    <option value="3" <?= $filters === '3' ? 'selected' : '' ?>>3 Stars</option>
                    <option value="2" <?= $filters === '2' ? 'selected' : '' ?>>2 Stars</option>
                    <option value="1" <?= $filters === '1' ? 'selected' : '' ?>>1 Star</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-control">
                    <option value="updated_at,desc" <?= $sort === 'updated_at,desc' ? 'selected' : '' ?>>Updated At (Desc)</option>
                    <option value="updated_at,asc" <?= $sort === 'updated_at,asc' ? 'selected' : '' ?>>Updated At (Asc)</option>
                    <option value="created_at,desc" <?= $sort === 'created_at,desc' ? 'selected' : '' ?>>Created At (Desc)</option>
                    <option value="created_at,asc" <?= $sort === 'created_at,asc' ? 'selected' : '' ?>>Created At (Asc)</option>
                    <option value="rating,desc" <?= $sort === 'rating,desc' ? 'selected' : '' ?>>Rating (Desc)</option>
                    <option value="rating,asc" <?= $sort === 'rating,asc' ? 'selected' : '' ?>>Rating (Asc)</option>
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
                <th>User ID</th>
                <th>Book ID</th>
                <th>Rating</th>
                <th>Comment</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $review): ?>
                    <tr>
                        <td><span title="<?= htmlspecialchars($review['id']) ?>"><?= htmlspecialchars(substr($review['id'], 0, 16)) ?>...</span></td>
                        <td><span title="<?= htmlspecialchars($review['user_id']) ?>"><?= htmlspecialchars(substr($review['user_id'], 0, 16)) ?>...</span></td>
                        <td><span title="<?= htmlspecialchars($review['book_id']) ?>"><?= htmlspecialchars(substr($review['book_id'], 0, 16)) ?>...</span></td>
                        <td>
                            <span class="badge <?= $review['rating'] >= 4 ? 'bg-success' : ($review['rating'] <= 2 ? 'bg-danger' : 'bg-warning') ?>">
                                <?= htmlspecialchars($review['rating']) ?> ★
                            </span>
                        </td>
                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($review['comment']) ?>">
                            <?= htmlspecialchars($review['comment']) ?>
                        </td>
                        <td><?= htmlspecialchars(substr($review['created_at'], 0, 10)) ?></td>
                        <td><?= htmlspecialchars(substr($review['updated_at'], 0, 10)) ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger delete-review" 
                                    data-review-id="<?= htmlspecialchars($review['id']) ?>">
                                Delete
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center">No reviews found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_reviews > $limit): ?>
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
    document.querySelectorAll('.delete-review').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const reviewId = this.getAttribute('data-review-id');

            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = 'Deleting...';

            const confirmMsg = 'Are you sure you want to delete this review?';

            if (!confirm(confirmMsg)) {
                btn.disabled = false;
                btn.textContent = originalText;
                return;
            }

            const apiUrl = `<?= $api_base_url ?>reviews?action=delete&id=${encodeURIComponent(reviewId)}`;

            try {
                const response = await fetch(apiUrl, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': 'Bearer <?= $_SESSION['access_token'] ?? '' ?>',
                        'Content-Type': 'application/json'
                    },
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status} - ${response.statusText}`);
                }

                const data = await response.json();
                if (data.success) {
                    alert('Review has been deleted successfully.');
                    location.reload();
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            } catch (err) {
                alert('Failed to delete review: ' + err.message);
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