<?php
$page_title = "Book Shop - Categories";
ob_start(); // Start buffer to store page content

// Set up API connection
session_start();
$base_url = $_ENV['API_BASE_URL'] ?? 'https://api.example.com/'; // Fallback if not set
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

// Initialize filter parameters
$categories = $_GET['category'] ?? [];
$search = $_GET['search'] ?? '';
$is_featured = isset($_GET['is_featured']) ? 1 : null;
$is_new = isset($_GET['is_new']) ? 1 : null;
$is_best_seller = isset($_GET['is_best_seller']) ? 1 : null;
$is_discounted = isset($_GET['is_discounted']) ? 1 : null;
$sort = $_GET['sort'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1)); // Ensure page >= 1
$limit = $_GET['limit'] ?? 6;

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

// Log API response for debugging
if ($http_code !== 200 || empty($response)) {
    error_log("Book API failed: HTTP $http_code, URL: $api_url");
}

$books_data = json_decode($response, true);

// Extract books
$books = $books_data['success'] && isset($books_data['data']['books']) ? $books_data['data']['books'] : [];

if (empty($books)) {
    error_log("No books found: URL: $api_url, Response: " . json_encode($books_data));
}
?>

<style>
    #bookList.loading::after {
        content: 'Loading...';
        display: block;
        text-align: center;
        padding: 20px;
        font-size: 1.2rem;
        color: #777;
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
<!-- Hero area End -->
<!-- listing Area Start -->
<div class="listing-area pt-50 pb-50">
    <div class="container">
        <div class="row">
            <!-- Left content -->
            <div class="col-xl-4 col-lg-4 col-md-6">
                <!-- Filter form -->
                <form id="filterForm" method="GET" action="">
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
                                    <p>No categories available.</p>
                                <?php else: ?>
                                    <?php foreach ($categories_list as $cat_id => $cat_name): ?>
                                        <label class="container"><?= htmlspecialchars($cat_name) ?>
                                            <input type="checkbox" name="category[]" value="<?= htmlspecialchars($cat_id) ?>" 
                                                <?= in_array($cat_id, $categories) ? 'checked' : '' ?> class="filter-checkbox">
                                            <span class="checkmark"></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Flags -->
                            <div class="select-Categories pt-20 pb-60">
                                <div class="small-tittle mb-20">
                                    <h4>Filter by Flags</h4>
                                </div>
                                <label class="container">Featured
                                    <input type="checkbox" name="is_featured" value="1" <?= $is_featured ? 'checked' : '' ?> class="filter-checkbox">
                                    <span class="checkmark"></span>
                                </label>
                                <label class="container">New
                                    <input type="checkbox" name="is_new" value="1" <?= $is_new ? 'checked' : '' ?> class="filter-checkbox">
                                    <span class="checkmark"></span>
                                </label>
                                <label class="container">Best Seller
                                    <input type="checkbox" name="is_best_seller" value="1" <?= $is_best_seller ? 'checked' : '' ?> class="filter-checkbox">
                                    <span class="checkmark"></span>
                                </label>
                                <label class="container">Discounted
                                    <input type="checkbox" name="is_discounted" value="1" <?= $is_discounted ? 'checked' : '' ?> class="filter-checkbox">
                                    <span class="checkmark"></span>
                                </label>
                            </div>

                            <!-- Reset -->
                            <div class="mt-30">
                                <button type="button" id="resetFilters" class="btn btn-outline-secondary w-100">Reset Filters</button>
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
                                <select name="sort" id="product_sort" class="form-select" onchange="applyFilters()">
                                    <option value="all" <?= $sort == 'all' ? 'selected' : '' ?>>All Books</option>
                                    <option value="price_at_asc" <?= $sort == 'price_at_asc' ? 'selected' : '' ?>>Price Low to High</option>
                                    <option value="price_at_desc" <?= $sort == 'price_at_desc' ? 'selected' : '' ?>>Price High to Low</option>
                                    <option value="stock_qty_at_asc" <?= $sort == 'stock_qty_at_asc' ? 'selected' : '' ?>>Stock Low to High</option>
                                    <option value="stock_qty_at_desc" <?= $sort == 'stock_qty_at_desc' ? 'selected' : '' ?>>Stock High to Low</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="best-selling p-0">
                    <div class="row" id="bookList">
                        <?php if ($http_code !== 200 || !$books_data['success'] || empty($books)): ?>
                            <div class="col-12">
                                <div class="alert alert-info" id="noBooksMessage">
                                    <?= htmlspecialchars($books_data['message'] ?? 'No books found matching your criteria. Try clearing some filters.') ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($books as $book): ?>
                                <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-12 col-sm-6">
                                    <div class="properties pb-30">
                                        <div class="properties-card" style="border: 1px solid #eee; border-radius: 8px; overflow: hidden; height: 100%; display: flex; flex-direction: column; cursor: pointer;" onclick="window.location.href='/book-details?id=<?= urlencode($book['id'] ?? '') ?>'">
                                            <!-- Book Image -->
                                            <div class="properties-img" style="height: 12rem; overflow: hidden; position: relative;">
                                                <a href="/book-details?id=<?= urlencode($book['id'] ?? '') ?>">
                                                    <img src="<?= htmlspecialchars($book['image_url'] ?? '/assets/img/gallery/default_book.jpg') ?>" 
                                                        alt="<?= htmlspecialchars($book['title'] ?? 'Book cover') ?>"
                                                        style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
                                                </a>
                                            </div>
                                            <!-- Book Info -->
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
                                                                    echo '<i class="fas fa-star"></i>';
                                                                } elseif ($i - $rating_value <= 0.5 && $i - $rating_value > 0) {
                                                                    echo '<i class="fas fa-star-half-alt"></i>';
                                                                } else {
                                                                    echo '<i class="fas fa-star"></i>';
                                                                }
                                                            }
                                                            ?>
                                                        </div>
                                                        <p style="margin: 5px 0 0; font-size: 0.8rem; color: #777;">(<span><?= htmlspecialchars($book['rating_count'] ?? 0) ?></span> Review)</p>
                                                    </div>
                                                    <div class="price">
                                                        <span style="font-weight: bold; color: #e83e8c; font-size: 1.1rem;">$<?= number_format(floatval($book['price'] ?? 0), 2) ?></span>
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
                <div id="paginationArea">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="pagination-area mt-15 d-flex justify-content-center">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination" id="paginationList">
                                        <!-- Pagination will be populated by JavaScript -->
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- listing-area Area End -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Base URL for API
    const baseUrl = '<?= $base_url ?>';
    const limit = <?= $limit ?>;

    // Debounce function for checkboxes
    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Function to apply filters via AJAX
    window.applyFilters = function(page = 1) {
        const form = document.getElementById('filterForm');
        const bookList = document.getElementById('bookList');
        const paginationArea = document.getElementById('paginationArea');
        const paginationList = document.getElementById('paginationList');
        const sort = document.getElementById('product_sort').value;
        const categories = Array.from(form.querySelectorAll('input[name="category[]"]:checked')).map(input => input.value);
        const search = form.querySelector('input[name="search"]').value;
        const isFeatured = form.querySelector('input[name="is_featured"]').checked ? 1 : null;
        const isNew = form.querySelector('input[name="is_new"]').checked ? 1 : null;
        const isBestSeller = form.querySelector('input[name="is_best_seller"]').checked ? 1 : null;
        const isDiscounted = form.querySelector('input[name="is_discounted"]').checked ? 1 : null;

        // Build API URL
        let apiUrl = `${baseUrl}/book?action=get-all-books-pagination&page=${page}&limit=${limit}&is_deleted=0`;
        if (categories.length > 0) {
            apiUrl += categories.map(cat => `&category[]=${encodeURIComponent(cat)}`).join('');
        }
        if (search) {
            apiUrl += `&search=${encodeURIComponent(search)}`;
        }
        if (isFeatured !== null) {
            apiUrl += `&is_featured=${isFeatured}`;
        }
        if (isNew !== null) {
            apiUrl += `&is_new=${isNew}`;
        }
        if (isBestSeller !== null) {
            apiUrl += `&is_best_seller=${isBestSeller}`;
        }
        if (isDiscounted !== null) {
            apiUrl += `&is_discounted=${isDiscounted}`;
        }
        if (sort !== 'all') {
            apiUrl += `&sort=${encodeURIComponent(sort)}`;
        }

        // Log API call for debugging
        console.log('Fetching books:', apiUrl);

        // Show loading state
        bookList.classList.add('loading');
        bookList.innerHTML = '';

        // Fetch books
        fetch(apiUrl, {
            headers: {
                'Authorization': 'Bearer <?= $_SESSION['access_token'] ?? '' ?>'
            }
        })
        .then(response => {
            bookList.classList.remove('loading');
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            bookList.innerHTML = '';
            if (data.success && data.data.books && data.data.books.length > 0) {
                data.data.books.forEach(book => {
                    const rating = parseFloat(book.rating || 0);
                    let stars = '';
                    for (let i = 1; i <= 5; i++) {
                        if (i <= Math.floor(rating)) {
                            stars += '<i class="fas fa-star"></i>';
                        } else if (i - rating <= 0.5 && i - rating > 0) {
                            stars += '<i class="fas fa-star-half-alt"></i>';
                        } else {
                            stars += '<i class="fas fa-star"></i>';
                        }
                    }

                    bookList.innerHTML += `
                        <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-12 col-sm-6">
                            <div class="properties pb-30">
                                <div class="properties-card" style="border: 1px solid #eee; border-radius: 8px; overflow: hidden; height: 100%; display: flex; flex-direction: column; cursor: pointer;" onclick="window.location.href='/book-details?id=${encodeURIComponent(book.id || '')}'">
                                    <div class="properties-img" style="height: 12rem; overflow: hidden; position: relative;">
                                        <a href="/book-details?id=${encodeURIComponent(book.id || '')}">
                                            <img src="${book.image_url || '/assets/img/gallery/default_book.jpg'}" 
                                                alt="${book.title || 'Book cover'}" 
                                                style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
                                        </a>
                                    </div>
                                    <div class="properties-caption properties-caption2" style="padding: 15px; flex-grow: 1; display: flex; flex-direction: column;">
                                        <h3 class="book-title" style="margin-bottom: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <a href="/book-details?id=${encodeURIComponent(book.id || '')}">
                                                ${book.title || 'Unknown Title'}
                                            </a>
                                        </h3>
                                        <p class="book-author" style="margin-bottom: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #777;">
                                            ${book.author || 'Unknown Author'}
                                        </p>
                                        <div class="properties-footer d-flex justify-content-between align-items-center" style="margin-top: auto;">
                                            <div class="review">
                                                <div class="rating" style="color: #ffc107; font-size: 0.9rem; line-height: 1;">
                                                    ${stars}
                                                </div>
                                                <p style="margin: 5px 0 0; font-size: 0.8rem; color: #777;">(<span>${book.rating_count || 0}</span> Review)</p>
                                            </div>
                                            <div class="price">
                                                <span style="font-weight: bold; color: #e83e8c; font-size: 1.1rem;">$${parseFloat(book.price || 0).toFixed(2)}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });

                // Update pagination
                let paginationHtml = '';
                const hasBooks = data.data.books.length > 0;
                const hasMoreBooks = data.data.books.length === limit; // Assume more pages if we got a full page
                const currentPage = page;

                // Previous button
                if (currentPage > 1) {
                    paginationHtml += `
                        <li class="page-item">
                            <a class="page-link" href="#" onclick="applyFilters(${currentPage - 1}); return false;">
                                <span aria-hidden="true">«</span>
                            </a>
                        </li>
                    `;
                }

                // Current page (always show at least the current page)
                paginationHtml += `
                    <li class="page-item active">
                        <a class="page-link" href="#" onclick="applyFilters(${currentPage}); return false;">
                            ${currentPage}
                        </a>
                    </li>
                `;

                // Next button
                if (hasMoreBooks) {
                    paginationHtml += `
                        <li class="page-item">
                            <a class="page-link" href="#" onclick="applyFilters(${currentPage + 1}); return false;">
                                <span aria-hidden="true">»</span>
                            </a>
                        </li>
                    `;
                }

                paginationList.innerHTML = paginationHtml;

                // Update pagination area
                paginationArea.innerHTML = `
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="pagination-area mt-15 d-flex justify-content-center">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination" id="paginationList">
                                        ${paginationHtml}
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                bookList.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-info" id="noBooksMessage">
                            ${data.message || 'No books found matching your criteria. Try clearing some filters.'}
                        </div>
                    </div>
                `;
                paginationArea.innerHTML = `
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="more-btn text-center mt-15">
                                <a href="#" class="border-btn border-btn2 more-btn2">Browse More</a>
                            </div>
                        </div>
                    </div>
                `;
                console.warn('No books found:', data);
            }
        })
        .catch(error => {
            bookList.classList.remove('loading');
            bookList.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">
                        Error loading books: ${error.message}. <a href="#" onclick="applyFilters(); return false;">Retry</a>
                    </div>
                </div>
            `;
            paginationArea.innerHTML = `
                <div class="row">
                    <div class="col-xl-12">
                        <div class="more-btn text-center mt-15">
                            <a href="#" class="border-btn border-btn2 more-btn2">Browse More</a>
                        </div>
                    </div>
                </div>
            `;
            console.error('Error fetching books:', error);
        });
    };

    // Debounced filter application for checkboxes
    const debouncedApplyFilters = debounce(applyFilters, 300);

    // Attach event listeners to checkboxes
    document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', debouncedApplyFilters);
    });

    // Reset filters
    document.getElementById('resetFilters').addEventListener('click', () => {
        document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        document.getElementById('product_sort').value = 'all';
        document.getElementById('filterForm').querySelector('input[name="search"]').value = '';
        applyFilters();
    });

    // Initial load
    applyFilters(<?= $page ?>);
});
</script>

<?php
$content = ob_get_clean(); // Get content from buffer
include __DIR__ . '/../layouts/main-layout.php'; // Include main layout
?>