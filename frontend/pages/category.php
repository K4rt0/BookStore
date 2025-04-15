<?php
$page_title = "Book Shop - Categories";
ob_start();

// Set up API connection
session_start();
$base_url = $_ENV['API_BASE_URL'];
$headers = [
    'Authorization: Bearer ' . ($_SESSION['access_token'] ?? '')
];

// Fetch categories from API
$category_url = $base_url . "/category?action=get-all-categories";
$ch = curl_init($category_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$category_response = curl_exec($ch);
$category_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$categories_list = [];
if ($category_http_code === 200) {
    $category_data = json_decode($category_response, true);
    if ($category_data['success'] && !empty($category_data['data'])) {
        foreach ($category_data['data'] as $cat) {
            $categories_list[$cat['id']] = $cat['name'];
        }
    }
}

// Initialize filter parameters from URL
$categories = array_filter((array)($_GET['category'] ?? []), function($id) use ($categories_list) {
    return isset($categories_list[$id]); // Only allow valid category IDs
});
$search = trim($_GET['search'] ?? '');
$is_featured = isset($_GET['is_featured']) ? 1 : null;
$is_new = isset($_GET['is_new']) ? 1 : null;
$is_best_seller = isset($_GET['is_best_seller']) ? 1 : null;
$is_discounted = isset($_GET['is_discounted']) ? 1 : null;
$sort = $_GET['sort'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, (int)($_GET['limit'] ?? 6));

// Build API URL for books
$api_url = $base_url . "/book?action=get-all-books-pagination";
$api_url .= "&page=" . urlencode($page);
$api_url .= "&limit=" . urlencode($limit);
$api_url .= "&is_deleted=0";

if (!empty($categories)) {
    foreach ($categories as $cat) {
        $api_url .= "&category[]=" . urlencode($cat);
    }
}
if (!empty($search)) {
    $api_url .= "&search=" . urlencode($search);
}
if ($is_featured !== null) {
    $api_url .= "&is_featured=" . $is_featured;
}
if ($is_new !== null) {
    $api_url .= "&is_new=" . $is_new;
}
if ($is_best_seller !== null) {
    $api_url .= "&is_best_seller=" . $is_best_seller;
}
if ($is_discounted !== null) {
    $api_url .= "&is_discounted=" . $is_discounted;
}
if ($sort !== 'all') {
    $api_url .= "&sort=" . urlencode($sort);
}

// Call API to fetch filtered books
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || empty($response)) {
    error_log("Book API failed: HTTP $http_code, URL: $api_url, Response: " . ($response ?: 'empty'));
}

$books_data = json_decode($response, true) ?: ['success' => false, 'message' => 'Invalid API response'];
$books = $books_data['success'] && isset($books_data['data']['books']) ? $books_data['data']['books'] : [];
$has_more = count($books) === $limit;

if (empty($books) && $http_code === 200) {
    error_log("No books found: URL: $api_url, Response: " . json_encode($books_data));
}

// Build query string for pagination links, preserving filters
$query_params = $_GET;
unset($query_params['page']);
$base_query = http_build_query($query_params);
?>

<style>
    .fa-star, .fa-star-half-alt {
        font-size: 18px;
        color: #ddd !important;
        margin-right: 2px;
    }
    .filled {
        color: #ffc107 !important;
    }
    .pagination .page-link {
        cursor: pointer;
    }
    .filter-form label {
        display: block;
        margin-bottom: 10px;
    }
    .filter-checkbox {
        margin-right: 8px;
    }
    .alert-info, .alert-warning {
        margin-top: 20px;
    }
</style>

<div class="container">
    <div class="row">
        <div class="col-xl-12">
            <div class="slider-area">
                <div class="slider-height2 slider-bg4 d-flex align-items-center justify-content-center">
                    <div class="hero-caption hero-caption2">
                        <h2>Book Category</h2>
                    </div>
                </div>
            </div>
        </div>
    </div> 
</div>
<!-- listing Area Start -->
<div class="listing-area pt-50 pb-50">
    <div class="container">
        <div class="row">
            <!-- Left content -->
            <div class="col-xl-4 col-lg-4 col-md-6">
                <form id="filterForm" method="GET" action="" class="filter-form">
                    <div class="category-listing mb-50">
                        <div class="single-listing">
                            <!-- Search -->
                            <div class="select-Categories pb-30">
                                <div class="small-tittle mb-20">
                                    <h4>Search Books</h4>
                                </div>
                                <div class="input-group">
                                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by title..." class="form-control">
                                    <button type="submit" class="btn btn-primary">Search</button>
                                </div>
                            </div>
                            <!-- Categories -->
                            <div class="select-Categories pb-30">
                                <div class="small-tittle mb-20">
                                    <h4>Filter by Categories</h4>
                                </div>
                                <?php if (empty($categories_list)): ?>
                                    <div class="alert alert-warning">
                                        No categories available. Please try again later.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($categories_list as $cat_id => $cat_name): ?>
                                        <label>
                                            <input type="checkbox" name="category[]" value="<?= htmlspecialchars($cat_id) ?>" 
                                                <?= in_array((string)$cat_id, $categories) ? 'checked' : '' ?> class="filter-checkbox">
                                            <?= htmlspecialchars($cat_name) ?>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <!-- Flags -->
                            <div class="select-Categories pt-20 pb-60">
                                <div class="small-tittle mb-20">
                                    <h4>Filter by Flags</h4>
                                </div>
                                <label>
                                    <input type="checkbox" name="is_featured" value="1" <?= $is_featured ? 'checked' : '' ?> class="filter-checkbox">
                                    Featured
                                </label>
                                <label>
                                    <input type="checkbox" name="is_new" value="1" <?= $is_new ? 'checked' : '' ?> class="filter-checkbox">
                                    New
                                </label>
                                <label>
                                    <input type="checkbox" name="is_best_seller" value="1" <?= $is_best_seller ? 'checked' : '' ?> class="filter-checkbox">
                                    Best Seller
                                </label>
                                <label>
                                    <input type="checkbox" name="is_discounted" value="1" <?= $is_discounted ? 'checked' : '' ?> class="filter-checkbox">
                                    Discounted
                                </label>
                            </div>
                            <!-- Submit and Reset -->
                            <div class="mt-30">
                                <button type="submit" class="btn btn-primary w-100 mb-2">Apply Filters</button>
                                <a href="?" class="btn btn-outline-secondary w-100">Reset Filters</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <!-- Right content -->
            <div class="col-xl-8 col-lg-8 col-md-6">
                <div class="row justify-content-end">
                    <div class="col-xl-4">
                        <div class="product_page_tittle">
                            <div class="short_by">
                                <form method="GET" action="">
                                    <?php foreach ($_GET as $key => $value): ?>
                                        <?php if ($key !== 'sort'): ?>
                                            <?php if (is_array($value)): ?>
                                                <?php foreach ($value as $val): ?>
                                                    <input type="hidden" name="<?= htmlspecialchars($key) ?>[]" value="<?= htmlspecialchars($val) ?>">
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <select name="sort" onchange="this.form.submit()" class="form-select">
                                        <option value="all" <?= $sort == 'all' ? 'selected' : '' ?>>All Books</option>
                                        <option value="price_at_asc" <?= $sort == 'price_at_asc' ? 'selected' : '' ?>>Price Low to High</option>
                                        <option value="price_at_desc" <?= $sort == 'price_at_desc' ? 'selected' : '' ?>>Price High to Low</option>
                                        <option value="stock_qty_at_asc" <?= $sort == 'stock_qty_at_asc' ? 'selected' : '' ?>>Stock Low to High</option>
                                        <option value="stock_qty_at_desc" <?= $sort == 'stock_qty_at_desc' ? 'selected' : '' ?>>Stock High to Low</option>
                                    </select>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="best-selling p-0">
                    <div class="row" id="bookList">
                        <?php if ($http_code !== 200): ?>
                            <div class="col-12">
                                <div class="alert alert-danger">
                                    Error loading books. Please try again later.
                                </div>
                            </div>
                        <?php elseif (empty($books)): ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <?php
                                    if (!empty($categories) && empty(array_intersect($categories, array_keys($categories_list)))) {
                                        echo 'Selected categories are invalid or unavailable.';
                                    } elseif ($books_data['message']) {
                                        echo htmlspecialchars($books_data['message']);
                                    } else {
                                        echo 'No books found for the selected filters. Try different categories or fewer filters.';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($books as $book): ?>
                                <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-12 col-sm-6">
                                    <div class="properties pb-30">
                                        <div class="properties-card" style="border: 1px solid #eee; border-radius: 8px; overflow: hidden; height: 100%; display: flex; flex-direction: column; cursor: pointer;" onclick="window.location.href='/book-details?id=<?= urlencode($book['id'] ?? '') ?>'">
                                            <div class="properties-img" style="height: 12rem; overflow: hidden; position: relative;">
                                                <a href="/book-details?id=<?= urlencode($book['id'] ?? '') ?>">
                                                    <img src="<?= htmlspecialchars($book['image_url'] ?? '/assets/img/gallery/default_book.jpg') ?>" 
                                                        alt="<?= htmlspecialchars($book['title'] ?? 'Book cover') ?>"
                                                        style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
                                                </a>
                                            </div>
                                            <div class="properties-caption properties-caption2" style="padding: 15px; flex-grow: 1; display: flex; flex-direction: column;">
                                                <h3 class="book-title" style="margin-bottom: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    <a href="/book-details?id=<?= urlencode($book['id'] ?? '') ?>">
                                                        <?= htmlspecialchars($book['title'] ?? 'Unknown Title') ?>
                                                    </a>
                                                </h3>
                                                <p class="book-author" style="margin-bottom: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #777;">
                                                    <?= htmlspecialchars($book['author'] ?? 'Unknown Author') ?>
                                                </p>
                                                <div class="properties-footer d-flex justify-content-between align-items-center" style="margin-top: auto;">
                                                    <div class="review">
                                                        <div class="rating" style="color: #ffc107; font-size: 0.9rem; line-height: 1;">
                                                            <?php 
                                                            $rating_value = floatval($book['rating'] ?? 0);
                                                            for ($i = 1; $i <= 5; $i++) {
                                                                if ($i <= floor($rating_value)) {
                                                                    echo '<i class="fas fa-star filled"></i>';
                                                                } elseif ($i - $rating_value <= 0.5 && $i - $rating_value > 0) {
                                                                    echo '<i class="fas fa-star-half-alt filled"></i>';
                                                                } else {
                                                                    echo '<i class="fas fa-star"></i>';
                                                                }
                                                            }
                                                            ?>
                                                        </div>
                                                        <p style="margin: 5px 0 0; font-size: 0.8rem; color: #777;">(<span><?= htmlspecialchars($book['rating_count'] ?? 0) ?></span> Review)</p>
                                                    </div>
                                                    <div class="price">
                                                        <span style="font-weight: bold; color: #e83e8c; font-size: 1.1rem;"><?= number_format(floatval($book['price'] ?? 0), 0) ?>₫</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Pagination -->
                <?php if (!empty($books)): ?>
                    <div id="paginationArea">
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="pagination-area mt-15 d-flex justify-content-center">
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= $base_query ? $base_query . '&' : '' ?>page=<?= $page - 1 ?>" aria-label="Previous">
                                                        <span aria-hidden="true">«</span>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <li class="page-item active">
                                                <span class="page-link"><?= $page ?></span>
                                            </li>
                                            <?php if ($has_more): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= $base_query ? $base_query . '&' : '' ?>page=<?= $page + 1 ?>" aria-label="Next">
                                                        <span aria-hidden="true">»</span>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- listing-area Area End -->

<script>
// Minimal JavaScript for checkbox auto-submit with debounce
document.addEventListener('DOMContentLoaded', function () {
    let timeout;
    document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 300);
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main-layout.php';
?>