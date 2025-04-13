<?php
session_start();

$page_title = "Book Reviews";
$layout = 'admin';
ob_start();

$base_url = $_ENV['API_BASE_URL'];
$error = '';
$success = '';

// Get book ID from URL
$book_id = isset($_GET['id']) ? $_GET['id'] : null;

// Pagination parameters
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($current_page < 1) $current_page = 1;
if ($limit < 1) $limit = 10;

$book = null;
$reviews = [];
$total_reviews = 0;
$total_pages = 0;

if ($book_id) {
    // Fetch book details and reviews
    $api_url = $base_url . "/book?action=get-book&id=" . urlencode($book_id);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . ($_SESSION['access_token'] ?? ""),
        "Content-Type: application/json"
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($response && $http_code === 200) {
        $result = json_decode($response, true);
        if ($result['success'] && $result['code'] == 200) {
            $book = $result['data']['book'];
            $reviews = $result['data']['reviews'] ?? [];
            
            // Pagination (client-side, as API doesn't support review pagination)
            $total_reviews = count($reviews);
            $total_pages = ceil($total_reviews / $limit);
            
            // Slice reviews for current page
            $offset = ($current_page - 1) * $limit;
            $reviews = array_slice($reviews, $offset, $limit);
        } else {
            $error = $result['message'] ?? 'Failed to fetch book reviews.';
        }
    } else {
        $error = "Failed to fetch book data: HTTP $http_code, cURL Error: " . ($curl_error ?: "None");
    }
} else {
    $error = "Book ID is missing.";
}

// Helper function to maintain pagination links
function buildQueryString($params = []) {
    $current_params = $_GET;
    unset($current_params['action']);
    
    $merged_params = array_merge($current_params, $params);
    return http_build_query($merged_params);
}
?>

<!-- Main content -->
<div class="card" style="display: flex; flex-direction: column;">
  <div class="card-body" style="flex: 1; display: flex; flex-direction: column; overflow: hidden;">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="card-title mb-0">📝 Reviews </h4>
      <a href="/admin/book-management" class="btn btn-secondary btn-sm">
        <i class="ti ti-arrow-left"></i> Back to Book Management
      </a>
    </div>

    <!-- Error or success messages -->
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Book Information -->
    <?php if ($book): ?>
        <div class="card mb-3">
            <div class="card-body d-flex align-items-center">
                <?php if (!empty($book['image_url'])): ?>
                    <img src="<?= htmlspecialchars($book['image_url']) ?>" alt="<?= htmlspecialchars($book['title']) ?>" style="max-width: 100px; max-height: 100px; object-fit: cover; margin-right: 20px;">
                <?php else: ?>
                    <div style="width: 100px; height: 100px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                        No Image
                    </div>
                <?php endif; ?>
                <div>
                    <h5 class="mb-1"><?= htmlspecialchars($book['title']) ?></h5>
                    <p class="mb-1"><strong>Author:</strong> <?= htmlspecialchars($book['author'] ?? 'N/A') ?></p>
                    <p class="mb-0"><strong>Average Rating:</strong> <?= number_format(floatval($book['rating'] ?? 0), 1) ?> (<?= htmlspecialchars($book['rating_count'] ?? 0) ?> reviews)</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Reviews Table -->
    <div class="table-responsive" style="flex: 1; overflow-y: auto;">
      <table class="table table-hover table-bordered align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>User ID</th>
            <th>Rating</th>
            <th>Comment</th>
            <th>Created At</th>
            <th>Updated At</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $index => $review): ?>
              <tr>
                <td><?= (($current_page - 1) * $limit) + $index + 1 ?></td>
                <td><?= htmlspecialchars($review['user_id']) ?></td>
                <td>
                  <?php 
                  $rating = intval($review['rating'] ?? 0);
                  for ($i = 1; $i <= 5; $i++): ?>
                    <i class="ti ti-star <?= $i <= $rating ? 'text-warning filled' : 'text-muted' ?>"></i>
                  <?php endfor; ?>
                </td>
                <td><?= htmlspecialchars($review['comment'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars(date('F j, Y H:i', strtotime($review['created_at']))) ?></td>
                <td><?= htmlspecialchars(date('F j, Y H:i', strtotime($review['updated_at']))) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center">No reviews found for this book.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
      <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
          Displaying <?= count($reviews) ?> / <?= $total_reviews ?> reviews
        </div>
        <nav aria-label="Page navigation">
          <ul class="pagination">
            <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="?<?= buildQueryString(['page' => $current_page - 1]) ?>" aria-label="Previous">
                <span aria-hidden="true">«</span>
              </a>
            </li>
            
            <?php 
            $start_page = max(1, min($current_page - 2, $total_pages - 4));
            $end_page = min($total_pages, max(5, $current_page + 2));
            
            if ($start_page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="?<?= buildQueryString(['page' => 1]) ?>">1</a>
              </li>
              <?php if ($start_page > 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
              <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
              <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= buildQueryString(['page' => $i]) ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            
            <?php if ($end_page < $total_pages): ?>
              <?php if ($end_page < $total_pages - 1): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
              <?php endif; ?>
              <li class="page-item">
                <a class="page-link" href="?<?= buildQueryString(['page' => $total_pages]) ?>"><?= $total_pages ?></a>
              </li>
            <?php endif; ?>
            
            <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
              <a class="page-link" href="?<?= buildQueryString(['page' => $current_page + 1]) ?>" aria-label="Next">
                <span aria-hidden="true">»</span>
              </a>
            </li>
          </ul>
        </nav>
        
        <!-- Limit selector -->
        <div class="d-flex align-items-center">
          <span class="me-2">Show:</span>
          <select class="form-select form-select-sm" style="width: auto;" onchange="window.location.href='?<?= buildQueryString(['limit' => '__limit__', 'page' => 1]) ?>'.replace('__limit__', this.value)">
            <?php foreach ([10, 20, 50, 100] as $limit_option): ?>
              <option value="<?= $limit_option ?>" <?= $limit == $limit_option ? 'selected' : '' ?>><?= $limit_option ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin-layout.php';
?>