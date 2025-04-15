<?php

$page_title = "Book Management";
$layout = 'admin';
ob_start();

$base_url = $_ENV['API_BASE_URL'];
$error = '';
$success = '';

// Pagination parameters
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
if ($current_page < 1) $current_page = 1;
if ($limit < 1) $limit = 6;

// Filter parameters
$filters = [
    'is_deleted' => isset($_GET['is_deleted']) ? (int)$_GET['is_deleted'] : null,
    'is_featured' => isset($_GET['is_featured']) ? (int)$_GET['is_featured'] : null,
    'is_new' => isset($_GET['is_new']) ? (int)$_GET['is_new'] : null,
    'is_best_seller' => isset($_GET['is_best_seller']) ? (int)$_GET['is_best_seller'] : null,
    'is_discounted' => isset($_GET['is_discounted']) ? (int)$_GET['is_discounted'] : null,
    'search' => isset($_GET['search']) ? $_GET['search'] : '',
    'sort' => isset($_GET['sort']) ? $_GET['sort'] : 'price_at_asc'
];

// Step 1: Gọi API get-all-books để lấy tổng số sách
$count_url = $base_url . "/book?action=get-all-books";
$ch = curl_init($count_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . ($_SESSION['access_token'] ?? '')
]);
$count_response = curl_exec($ch);
$count_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$count_result = json_decode($count_response, true);
$total_books = ($count_http_code === 200 && $count_result['success'] && !empty($count_result['data'])) 
    ? count($count_result['data']) 
    : 0;

// Tính tổng số trang
$total_pages = ceil($total_books / $limit);

// Step 2: Gọi API phân trang để lấy sách cho trang hiện tại
$api_url = $base_url . "/book?action=get-all-books-pagination";
$api_url .= "&page=" . $current_page . "&limit=" . $limit;

// Add filters to API URL
foreach ($filters as $key => $value) {
    if ($value !== null && $value !== '') {
        $api_url .= "&" . $key . "=" . urlencode($value);
    }
}

$headers = [
    'Authorization: Bearer ' . ($_SESSION['access_token'] ?? '')
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$books_result = json_decode($response, true);
$books = ($http_code === 200 && $books_result['success'] && !empty($books_result['data']['books'])) 
    ? $books_result['data']['books'] 
    : [];

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $book_id = $_GET['id'] ?? '';

    if (!$book_id) {
        $error = 'Book ID is missing.';
    } else {
        $delete_url = $base_url . "/book?action=delete&id=" . urlencode($book_id);

        $ch = curl_init($delete_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $delete_response = curl_exec($ch);
        $delete_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $delete_result = json_decode($delete_response, true);

        if ($delete_http_code === 200 && $delete_result && $delete_result['success']) {
            $success = 'Book deleted successfully.';
            
            // Redirect to maintain filter state
            $redirect_url = getCurrentUrlPath();
            $query_params = array_intersect_key($_GET, array_flip([
                'page', 'limit', 'search', 'sort', 
                'is_deleted', 'is_featured', 'is_new', 
                'is_best_seller', 'is_discounted'
            ]));
            // Điều chỉnh trang nếu cần
            $total_books--; // Giảm tổng số sách
            $total_pages = ceil($total_books / $limit);
            if ($current_page > $total_pages && $total_pages > 0) {
                $query_params['page'] = $total_pages;
            }
            if (!empty($query_params)) {
                $redirect_url .= '?' . http_build_query($query_params);
            }
            header("Location: " . $redirect_url);
            exit;
        } else {
            $error = $delete_result['message'] ?? 'An error occurred while deleting the book.';
        }
    }
}

// Helper function to maintain current filters in pagination links
function buildQueryString($params = []) {
    $current_params = $_GET;
    unset($current_params['action'], $current_params['id'], $current_params['is_deleted']);
    
    $merged_params = array_merge($current_params, $params);
    return http_build_query($merged_params);
}

// Get current URL path without query string
function getCurrentUrlPath() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    // Remove query string if exists
    $uri = strtok($uri, '?');
    
    return $protocol . '://' . $host . $uri;
}

// Sort options
$sort_options = [
    'price_at_asc' => 'Price ascending',
    'price_at_desc' => 'Price descending',
    'title_at_asc' => 'Title A-Z',
    'title_at_desc' => 'Title Z-A',
    'created_at_desc' => 'Newest',
    'created_at_asc' => 'Oldest'
];
?>

<!-- Chiếm full chiều cao trình duyệt -->
<div class="card" style="display: flex; flex-direction: column;">
  <div class="card-body" style="flex: 1; display: flex; flex-direction: column; overflow: hidden;">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="card-title mb-0">📚 Book List</h4>
      <a href="/admin/book-create" class="btn btn-primary btn-sm">
        <i class="ti ti-plus"></i> Add book
      </a>
    </div>

    <!-- Hiển thị thông báo lỗi hoặc thành công -->
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Filter section -->
    <div class="card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3">
          <!-- Search -->
          <div class="col-md-4">
            <div class="input-group">
              <input type="text" class="form-control" placeholder="Tìm kiếm theo tên sách..." name="search" value="<?= htmlspecialchars($filters['search']) ?>">
              <button class="btn btn-outline-secondary" type="submit">
                <i class="ti ti-search"></i>
              </button>
            </div>
          </div>

          <!-- Sort -->
          <div class="col-md-3">
            <select name="sort" class="form-select" onchange="this.form.submit()">
              <?php foreach ($sort_options as $value => $label): ?>
                <option value="<?= $value ?>" <?= $filters['sort'] === $value ? 'selected' : '' ?>>
                  <?= $label ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Status filter -->
          <div class="col-md-2">
            <select name="is_deleted" class="form-select" onchange="this.form.submit()">
              <option value="">Status</option>
              <option value="0" <?= $filters['is_deleted'] === 0 ? 'selected' : '' ?>>Active</option>
              <option value="1" <?= $filters['is_deleted'] === 1 ? 'selected' : '' ?>>Deleted</option>
            </select>
          </div>

          <!-- Special filters -->
          <div class="col-md-3">
            <div class="btn-group" role="group">
              <input type="checkbox" class="btn-check" id="btn-featured" name="is_featured" value="1" <?= $filters['is_featured'] === 1 ? 'checked' : '' ?> onchange="this.form.submit()">
              <label class="btn btn-outline-primary btn-sm" for="btn-featured">Featured</label>
              
              <input type="checkbox" class="btn-check" id="btn-new" name="is_new" value="1" <?= $filters['is_new'] === 1 ? 'checked' : '' ?> onchange="this.form.submit()">
              <label class="btn btn-outline-success btn-sm" for="btn-new">New</label>
              
              <input type="checkbox" class="btn-check" id="btn-bestseller" name="is_best_seller" value="1" <?= $filters['is_best_seller'] === 1 ? 'checked' : '' ?> onchange="this.form.submit()">
              <label class="btn btn-outline-warning btn-sm" for="btn-bestseller">Best</label>
              
              <input type="checkbox" class="btn-check" id="btn-discounted" name="is_discounted" value="1" <?= $filters['is_discounted'] === 1 ? 'checked' : '' ?> onchange="this.form.submit()">
              <label class="btn btn-outline-danger btn-sm" for="btn-discounted">Discounted</label>
            </div>
          </div>
          
          <!-- Reset filters button -->
          <div class="col-md-12 text-end">
            <a href="<?= getCurrentUrlPath() ?>" class="btn btn-sm btn-secondary">
              <i class="ti ti-refresh"></i> Refresh filter
            </a>
          </div>
        </form>
      </div>
    </div>

    <!-- Table scrollable khi nhiều sách -->
    <div class="table-responsive" style="flex: 1; overflow-y: auto;">
      <table class="table table-hover table-bordered align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Image</th>
            <th>Title</th>
            <th>Author</th>
            <th>Price</th>
            <th>Stock Quantity</th> 
            <th>Status</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($books)): ?>
            <?php foreach ($books as $index => $book): ?>
              <tr>
                <td><?= (($current_page - 1) * $limit) + $index + 1 ?></td>
                <td>
                  <?php if (!empty($book['image_url'])): ?>
                    <img src="<?= htmlspecialchars($book['image_url']) ?>" alt="<?= htmlspecialchars($book['title']) ?>" style="max-width: 100px; max-height: 100px; object-fit: cover;">
                  <?php else: ?>
                    No Image
                  <?php endif; ?>
                </td>
                <td>
                  <div class="mb-2 text-center fw-semibold">
                    <?= htmlspecialchars($book['title'] ?? 'N/A') ?>
                  </div>
                  <div class="d-flex flex-wrap gap-1">
                    <?php if ($book['is_featured'] ?? false): ?>
                      <span class="badge bg-primary text-white">Featured</span>
                    <?php endif; ?>
                    <?php if ($book['is_new'] ?? false): ?>
                      <span class="badge bg-success text-white">New</span>
                    <?php endif; ?>
                    <?php if ($book['is_best_seller'] ?? false): ?>
                      <span class="badge bg-warning text-white">Best</span>
                    <?php endif; ?>
                    <?php if ($book['is_discounted'] ?? false): ?>
                      <span class="badge bg-danger text-white">Discounted</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($book['author'] ?? 'N/A') ?></td>
                <td><?= number_format($book['price'] ?? 0, 0, ',', '.') ?>đ</td>
                <td>
                  <span class="badge <?= ($book['stock_quantity'] ?? 0) > 0 ? 'bg-success' : 'bg-danger' ?>">
                    <?= htmlspecialchars($book['stock_quantity'] ?? 0) ?>
                  </span>
                </td>
                <td>
                  <span class="badge bg-<?= ($book['is_deleted'] ?? 0) ? 'danger' : 'success' ?>">
                    <?= ($book['is_deleted'] ?? 0) ? 'Deleted' : 'Active' ?>
                  </span>
                </td>
                <td class="text-center">
                  <a href="/admin/book-edit/<?= urlencode($book['id']) ?>" class="btn btn-info btn-sm me-1">
                    <i class="ti ti-edit"></i> Edit
                  </a>
                  <?php if (!($book['is_deleted'] ?? 0)): ?>
                    <a href="?<?= buildQueryString(['action' => 'delete', 'id' => $book['id']]) ?>" 
                       class="btn btn-danger btn-sm" 
                       onclick="return confirm('Are you sure you want to delete this book?');">
                      <i class="ti ti-trash"></i> Delete
                    </a>
                  <?php else: ?>
                    <!-- Có thể thêm nút khôi phục nếu cần -->
                    <span class="badge bg-secondary">Restore</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center">No books found.</td> 
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
      <div>
        Hiển thị <?= count($books) ?> / <?= $total_books ?> books
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
              <li class="page-item disabled"><a class="page-link">...</a></li>
            <?php endif; ?>
          <?php endif; ?>
          
          <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
              <a class="page-link" href="?<?= buildQueryString(['page' => $i]) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          
          <?php if ($end_page < $total_pages): ?>
            <?php if ($end_page < $total_pages - 1): ?>
              <li class="page-item disabled"><a class="page-link">...</a></li>
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
        <span class="me-2">Hiển thị:</span>
        <select class="form-select form-select-sm" style="width: auto;" onchange="window.location.href='?<?= buildQueryString(['limit' => '__limit__', 'page' => 1]) ?>'.replace('__limit__', this.value)">
          <?php foreach ([6, 10, 20, 50, 100] as $limit_option): ?>
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