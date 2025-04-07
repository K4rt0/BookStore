<?php
$page_title = "Book Shop - Categories";
ob_start(); // Start buffer to store page content

// Set up API connection
session_start();
$base_url = $_ENV['API_BASE_URL'] ;
$headers = [
    'Authorization: Bearer ' . ($_SESSION['access_token'] ?? '')
];

// Initialize filter parameters
$genre = $_GET['genre'] ?? '';
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';
$rating = $_GET['rating'] ?? '';
$publisher = $_GET['publisher'] ?? '';
$author = $_GET['author'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'popularity';

// Build API URL with query parameters
$api_url = $base_url . "books?action=get-all-books";

// Add filter parameters if set
if (!empty($genre)) $api_url .= "&genre=" . urlencode($genre);
if (!empty($price_min)) $api_url .= "&price_min=" . urlencode($price_min);
if (!empty($price_max)) $api_url .= "&price_max=" . urlencode($price_max);
if (!empty($rating)) $api_url .= "&rating=" . urlencode($rating);
if (!empty($publisher)) $api_url .= "&publisher=" . urlencode($publisher);
if (!empty($author)) $api_url .= "&author=" . urlencode($author);
if (!empty($sort_by)) $api_url .= "&sort_by=" . urlencode($sort_by);

// Call API to fetch filtered books
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$books_data = json_decode($response, true);

// Get available filter options from API (or use static lists for now)
$genres = ['History', 'Horror - Thriller', 'Love Stories', 'Science Fiction', 'Biography'];
$publishers = ['Green Publications', 'Anondo Publications', 'Rinku Publications', 'Sheba Publications', 'Red Publications'];
$authors = ['Buster Hyman', 'Phil Harmonic', 'Cam L. Toe', 'Otto Matic', 'Juan Annatoo'];
?>

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
<!--  Hero area End -->
<!-- listing Area Start -->
<div class="listing-area pt-50 pb-50">
    <div class="container">
        <div class="row">
            <!--? Left content -->
            <div class="col-xl-4 col-lg-4 col-md-6">
                <!-- Filter form -->
                <form id="filterForm" method="GET" action="">
                    <!-- Job Category Listing start -->
                    <div class="category-listing mb-50">
                        <!-- single one -->
                        <div class="single-listing">
                            <!-- select-Categories  -->
                            <div class="select-Categories pb-30">
                                <div class="small-tittle mb-20">
                                    <h4>Filter by Genres</h4>
                                </div>
                                <?php foreach($genres as $g): ?>
                                <label class="container"><?= htmlspecialchars($g) ?>
                                    <input type="checkbox" name="genre[]" value="<?= htmlspecialchars($g) ?>" 
                                        <?= (is_array($_GET['genre'] ?? null) && in_array($g, $_GET['genre'])) ? 'checked' : '' ?>>
                                    <span class="checkmark"></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <!-- select-Categories End -->

                            <!-- Range Slider Start -->
                            <aside class="left_widgets p_filter_widgets price_rangs_aside sidebar_box_shadow mb-40">
                                <div class="small-tittle">
                                    <h4>Filter by Price</h4>
                                </div>
                                <div class="widgets_inner">
                                    <div class="range_item">
                                        <div class="d-flex align-items-center">
                                            <div class="price_value d-flex justify-content-center">
                                                <input type="number" name="price_min" value="<?= htmlspecialchars($price_min) ?>" placeholder="Min" />
                                                <span>to</span>
                                                <input type="number" name="price_max" value="<?= htmlspecialchars($price_max) ?>" placeholder="Max" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </aside>
                            <!-- range end -->

                            <!-- Select Rating items start -->
                            <div class="select-job-items2 mb-30">
                                <div class="col-xl-12">
                                    <select name="rating" class="form-select">
                                        <option value="">Filter by Rating</option>
                                        <option value="5" <?= $rating == '5' ? 'selected' : '' ?>>5 Star Rating</option>
                                        <option value="4" <?= $rating == '4' ? 'selected' : '' ?>>4 Star Rating</option>
                                        <option value="3" <?= $rating == '3' ? 'selected' : '' ?>>3 Star Rating</option>
                                        <option value="2" <?= $rating == '2' ? 'selected' : '' ?>>2 Star Rating</option>
                                        <option value="1" <?= $rating == '1' ? 'selected' : '' ?>>1 Star Rating</option>
                                    </select>
                                </div>
                            </div>
                            <!--  Select Rating items End-->

                            <!-- select-Categories start -->
                            <div class="select-Categories pt-100 pb-60">
                                <div class="small-tittle mb-20">
                                    <h4>Filter by Publisher</h4>
                                </div>
                                <?php foreach($publishers as $pub): ?>
                                <label class="container"><?= htmlspecialchars($pub) ?>
                                    <input type="checkbox" name="publisher[]" value="<?= htmlspecialchars($pub) ?>"
                                        <?= (is_array($_GET['publisher'] ?? null) && in_array($pub, $_GET['publisher'])) ? 'checked' : '' ?>>
                                    <span class="checkmark"></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <!-- select-Categories End -->
                            
                            <!-- select-Categories start -->
                            <div class="select-Categories">
                                <div class="small-tittle mb-20">
                                    <h4>Filter by Author Name</h4>
                                </div>
                                <?php foreach($authors as $auth): ?>
                                <label class="container"><?= htmlspecialchars($auth) ?>
                                    <input type="checkbox" name="author[]" value="<?= htmlspecialchars($auth) ?>"
                                        <?= (is_array($_GET['author'] ?? null) && in_array($auth, $_GET['author'])) ? 'checked' : '' ?>>
                                    <span class="checkmark"></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <!-- select-Categories End -->
                            
                            <!-- Submit filters button -->
                            <div class="mt-30">
                                <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                                <button type="button" id="resetFilters" class="btn btn-outline-secondary w-100 mt-2">Reset Filters</button>
                            </div>
                        </div>
                    </div>
                </form>
                <!-- Job Category Listing End -->
            </div>
            <!--?  Right content -->
            <div class="col-xl-8 col-lg-8 col-md-6">
                <div class="row justify-content-end">
                    <div class="col-xl-4">
                        <div class="product_page_tittle">
                            <div class="short_by">
                                <select name="sort_by" id="product_sort" class="form-select">
                                    <option value="popularity" <?= $sort_by == 'popularity' ? 'selected' : '' ?>>Browse by popularity</option>
                                    <option value="name" <?= $sort_by == 'name' ? 'selected' : '' ?>>Name</option>
                                    <option value="newest" <?= $sort_by == 'newest' ? 'selected' : '' ?>>Newest</option>
                                    <option value="oldest" <?= $sort_by == 'oldest' ? 'selected' : '' ?>>Oldest</option>
                                    <option value="price_low" <?= $sort_by == 'price_low' ? 'selected' : '' ?>>Price Low to High</option>
                                    <option value="price_high" <?= $sort_by == 'price_high' ? 'selected' : '' ?>>Price High to Low</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="best-selling p-0">
                    <div class="row">
                        <?php if ($http_code !== 200 || !isset($books_data['success']) || !$books_data['success'] || empty($books_data['data'])): ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <?= htmlspecialchars($books_data['message'] ?? 'No books found matching your criteria.') ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($books_data['data'] as $book): ?>
                                <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-12 col-sm-6">
                                    <div class="properties pb-30">
                                        <div class="properties-card" style="border: 1px solid #eee; border-radius: 8px; overflow: hidden; height: 100%; display: flex; flex-direction: column; cursor: pointer;" onclick="window.location.href='/book-details?id=<?= urlencode($book['id'] ?? '') ?>'">
                                            <!-- Book Image Container with Fixed Height -->
                                            <div class="properties-img" style="height: 12rem; overflow: hidden; position: relative;">
                                                <a href="/book-details?id=<?= urlencode($book['id'] ?? '') ?>">
                                                    <img src="<?= htmlspecialchars($book['image_url'] ?? '/assets/img/gallery/default_book.jpg') ?>" 
                                                        alt="<?= htmlspecialchars($book['title'] ?? 'Book cover') ?>"
                                                        style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
                                                </a>
                                            </div>
                                            <!-- Book Info Container with Fixed Height -->
                                            <div class="properties-caption properties-caption2" style="padding: 15px; flex-grow: 1; display: flex; flex-direction: column;">
                                                <!-- Title with line clamp (2 lines max) -->
                                                <h3 class="book-title" style="margin-bottom: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                                                    <a href="/book-details?id=<?= urlencode($book['id'] ?? '') ?>">
                                                                                        <?= htmlspecialchars($book['title'] ?? 'Unknown Title') ?>
                                                                                    </a>
                                                                                </h3>
                                                <!-- Author with text overflow handling -->
                                                <p class="book-author" style="margin-bottom: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #777;">
                                                    <?= htmlspecialchars($book['author'] ?? 'Unknown Author') ?>
                                                </p>
                                                <!-- Rating and Price Footer - push to bottom -->
                                                <div class="properties-footer d-flex justify-content-between align-items-center" style="margin-top: auto;">
                                                    <div class="review">
                                                        <div class="rating" style="color: #ffc107; font-size: 0.9rem; line-height: 1;">
                                                            <?php 
                                                            $rating = $book['rating'] ?? 0;
                                                            for($i = 1; $i <= 5; $i++) {
                                                                if($i <= floor($rating)) {
                                                                    echo '<i class="fas fa-star"></i>';
                                                                } elseif($i - $rating <= 0.5 && $i - $rating > 0) {
                                                                    echo '<i class="fas fa-star-half-alt"></i>';
                                                                } else {
                                                                    echo '<i class="fas fa-star"></i>';
                                                                }
                                                            }
                                                            ?>
                                                        </div>
                                                        <p style="margin: 5px 0 0; font-size: 0.8rem; color: #777;">(<span><?= htmlspecialchars($book['review_count'] ?? 0) ?></span> Review)</p>
                                                    </div>
                                                    <div class="price">
                                                        <span style="font-weight: bold; color: #e83e8c; font-size: 1.1rem;">$<?= htmlspecialchars($book['price'] ?? '0.00') ?></span>
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
                <?php if (isset($books_data['pagination']) && $books_data['pagination']['total_pages'] > 1): ?>
                <div class="row">
                    <div class="col-xl-12">
                        <div class="pagination-area mt-15 d-flex justify-content-center">
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <?php if($books_data['pagination']['current_page'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $books_data['pagination']['current_page']-1 ?>&<?= http_build_query(array_filter($_GET, function($key) { return $key != 'page'; }, ARRAY_FILTER_USE_KEY)) ?>">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for($i = 1; $i <= $books_data['pagination']['total_pages']; $i++): ?>
                                        <li class="page-item <?= $i == $books_data['pagination']['current_page'] ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_filter($_GET, function($key) { return $key != 'page'; }, ARRAY_FILTER_USE_KEY)) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if($books_data['pagination']['current_page'] < $books_data['pagination']['total_pages']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $books_data['pagination']['current_page']+1 ?>&<?= http_build_query(array_filter($_GET, function($key) { return $key != 'page'; }, ARRAY_FILTER_USE_KEY)) ?>">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- Browse More button when no pagination -->
                <div class="row">
                    <div class="col-xl-12">
                        <div class="more-btn text-center mt-15">
                            <a href="#" class="border-btn border-btn2 more-btn2">Browse More</a>
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
document.addEventListener('DOMContentLoaded', function () {
    // Sort by change handler
    const sortSelect = document.getElementById('product_sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const form = document.getElementById('filterForm');
            const sortByInput = document.createElement('input');
            sortByInput.type = 'hidden';
            sortByInput.name = 'sort_by';
            sortByInput.value = this.value;
            form.appendChild(sortByInput);
            form.submit();
        });
    }
    
    // Reset filters button
    const resetButton = document.getElementById('resetFilters');
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            window.location.href = window.location.pathname;
        });
    }
});
</script>

<?php
$content = ob_get_clean(); // Get content from buffer
include __DIR__ . '/../layouts/main-layout.php'; // Include main layout
?>