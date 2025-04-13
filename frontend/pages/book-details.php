<?php
$page_title = "Book Shop - Details";
ob_start(); 

session_start();

// Get book ID from URL parameter
$book_id = isset($_GET['id']) ? $_GET['id'] : null;
$base_url = $_ENV['API_BASE_URL'];
$access_token = $_SESSION['access_token'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

$book = null;
$category = null;
$reviews = [];
if ($book_id) {
    // Fetch book details
    $api_url = $base_url . "/book?action=get-book&id=" . urlencode($book_id);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . ($access_token ?? ""),
        "Content-Type: application/json"
    ]);
    
    $book_json = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($book_json && $http_code === 200) {
        $response = json_decode($book_json, true);
        if ($response['success'] && $response['code'] == 200) {
            $book = $response['data']['book'];
            $reviews = $response['data']['reviews'] ?? []; // Store reviews
            
            // Fetch category details
            $category_id = $book['category_id'] ?? null;
            if ($category_id) {
                $category_url = $base_url . "/category?action=get-category&id=" . urlencode($category_id);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $category_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer " . ($access_token ?? ""),
                    "Content-Type: application/json"
                ]);
                
                $category_json = curl_exec($ch);
                $category_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $category_error = curl_error($ch);
                curl_close($ch);
                
                if ($category_json && $category_http_code === 200) {
                    $category_response = json_decode($category_json, true);
                    if ($category_response['success'] && $category_response['code'] == 200) {
                        $category = $category_response['data'];
                    }
                } else {
                    error_log("Failed to fetch category data: HTTP $category_http_code, cURL Error: " . ($category_error ?: "None"));
                }
            }
        }
    } else {
        error_log("Failed to fetch book data: HTTP $http_code, cURL Error: " . ($error ?: "None"));
    }
}

if (!$book) {
    $error_message = "Book not found or has been deleted";
}
?>
<link rel="stylesheet" href="/assets/css/template/book-details.css">
<!-- Add custom CSS for the redesigned tags -->
<style>
    /* Book Image Container Styles */
    .book-image-container {
        position: relative;
        margin-bottom: 20px;
        overflow: hidden;
    }
    
    /* Badge Styles */
    .badge-corner {
        position: absolute;
        top: 0;
        left: 0;
        overflow: hidden;
        height: 80px;
        width: 80px;
    }
    
    .badge-ribbon {
        position: absolute;
        top: 15px;
        left: -30px;
        padding: 5px 30px;
        transform: rotate(-45deg);
        font-weight: bold;
        text-transform: uppercase;
        font-size: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        z-index: 2;
    }
    
    .badge-new {
        background-color: #2ECC71;
        color: white;
    }
    
    .badge-featured {
        background-color: #3498DB;
        color: white;
    }
    
    .badge-bestseller {
        background-color: #F1C40F;
        color: #333;
    }
    
    /* Price tag styles */
    .price-container {
        display: flex;
        align-items: center;
        margin: 15px 0;
    }
    
    .discount-badge {
        display: inline-block;
        background-color: #E74C3C;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        margin-left: 10px;
        font-size: 14px;
        font-weight: bold;
    }
    
    .original-price {
        text-decoration: line-through;
        color: #95a5a6;
        margin-right: 10px;
        font-size: 16px;
    }
    
    .current-price {
        font-size: 24px;
        font-weight: bold;
        color: #2c3e50;
    }
    
    /* Additional badge on image */
    .tag-badge {
        position: absolute;
        right: 10px;
        top: 10px;
        padding: 5px 10px;
        border-radius: 3px;
        font-weight: bold;
        font-size: 12px;
        z-index: 2;
    }
    
    /* Customized tags for cards in related books section */
    .book-card {
        position: relative;
    }
    
    .book-card .tag-badge {
        font-size: 10px;
        padding: 3px 8px;
        right: 5px;
        top: 5px;
    }
    
    .book-card .discount-tag {
        position: absolute;
        left: 0;
        top: 10px;
        background-color: #E74C3C;
        color: white;
        padding: 3px 8px;
        font-size: 10px;
        font-weight: bold;
        z-index: 2;
    }
</style>

<div class="book-details-container">
    <div class="container">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger mt-4">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php else: ?>
            <div class="breadcrumb-wrapper">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <?php if ($category): ?>
                            <li class="breadcrumb-item"><a href="/category?category_id=<?= htmlspecialchars($category['id']) ?>"><?= htmlspecialchars($category['name'] ?? 'Books') ?></a></li>
                        <?php else: ?>
                            <li class="breadcrumb-item"><a href="/books">Books</a></li>
                        <?php endif; ?>
                        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($book['title'] ?? 'Book Details') ?></li>
                    </ol>
                </nav>
            </div>

            <div class="book-details-wrapper">
                <div class="row">
                    <div class="col-lg-5">
                        <div class="book-image-container">
                            <img src="<?= htmlspecialchars($book['image_url'] ?? '/assets/img/gallery/best-books1.jpg') ?>" alt="<?= htmlspecialchars($book['title'] ?? 'Book cover') ?>" class="book-cover-image">
                            
                            <!-- Redesigned corner ribbon badges -->
                            <?php if ($book['is_new']): ?>
                                <div class="badge-corner">
                                    <div class="badge-ribbon badge-new">
                                        <i class="fas fa-bolt"></i> New
                                    </div>
                                </div>
                            <?php elseif ($book['is_featured']): ?>
                                <div class="badge-corner">
                                    <div class="badge-ribbon badge-featured">
                                        <i class="fas fa-award"></i> Featured
                                    </div>
                                </div>
                            <?php elseif ($book['is_best_seller']): ?>
                                <div class="badge-corner">
                                    <div class="badge-ribbon badge-bestseller">
                                        <i class="fas fa-crown"></i> Best Seller
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Additional tag badge on the right side of the image if needed -->
                            <?php if ($book['is_best_seller'] && $book['is_featured']): ?>
                                <div class="tag-badge" style="background-color: #3498DB; color: white;">
                                    <i class="fas fa-award"></i> Featured
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="book-info-container">
                            <h1 class="book-title"><?= htmlspecialchars($book['title'] ?? 'Unknown Title') ?></h1>
                            <p class="book-author">By <span class="author-name"><?= htmlspecialchars($book['author'] ?? 'Unknown Author') ?></span></p>
                            
                            <div class="rating-container">
                                <div class="rating">
                                    <?php 
                                    $rating = floatval($book['rating'] ?? 0);
                                    for($i = 1; $i <= 5; $i++) {
                                        if($i <= floor($rating)) {
                                            echo '<i class="fas fa-star filled"></i>';
                                        } elseif($i - $rating <= 0.5 && $i - $rating > 0) {
                                            echo '<i class="fas fa-star-half-alt filled"></i>';
                                        } else {
                                            echo '<i class="fas fa-star"></i>';
                                        }
                                    }
                                    ?>
                                    <span class="rating-number"><?= number_format($rating, 1) ?></span>
                                </div>
                                <span class="rating-count">(<?= htmlspecialchars($book['rating_count'] ?? '0') ?> Reviews)</span>
                            </div>
                            
                            <!-- Redesigned price display with discount tag -->
                            <div class="price-container">
                                <?php if ($book['is_discounted'] && isset($book['original_price'])): ?>
                                    <span class="original-price">₫<?= htmlspecialchars(number_format(($book['original_price'] ?? 0), 0)) ?></span>
                                    <span class="current-price">₫<?= htmlspecialchars(number_format(($book['price'] ?? 0), 0)) ?></span>
                                    <?php
                                        // Calculate discount percentage
                                        $original = floatval($book['original_price'] ?? 0);
                                        $current = floatval($book['price'] ?? 0);
                                        $discount_percent = 0;
                                        if ($original > 0) {
                                            $discount_percent = round((($original - $current) / $original) * 100);
                                        }
                                    ?>
                                    <span class="discount-badge">
                                        <i class="fas fa-tag"></i> -<?= $discount_percent ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="current-price">₫<?= htmlspecialchars(number_format(($book['price'] ?? 0), 0)) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="book-meta">
                                <div class="meta-item">
                                    <span class="meta-label"><i class="fas fa-layer-group"></i> Category:</span>
                                    <span class="meta-value"><a href="/category?id=<?= htmlspecialchars($category['id'] ?? '') ?>"><?= htmlspecialchars($category['name'] ?? 'Uncategorized') ?></a></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label"><i class="fas fa-boxes"></i> Availability:</span>
                                    <span class="meta-value <?= ($book['stock_quantity'] > 0) ? 'in-stock' : 'out-of-stock' ?>">
                                        <?= ($book['stock_quantity'] > 0) ? 'In Stock (' . htmlspecialchars($book['stock_quantity']) . ' units)' : 'Out of Stock' ?>
                                    </span>
                                </div>
                                <?php if (!empty($book['publisher'])): ?>
                                <div class="meta-item">
                                    <span class="meta-label"><i class="fas fa-building"></i> Publisher:</span>
                                    <span class="meta-value"><?= htmlspecialchars($book['publisher']) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($book['publication_date'])): ?>
                                <div class="meta-item">
                                    <span class="meta-label"><i class="fas fa-calendar-alt"></i> Published:</span>
                                    <span class="meta-value"><?= htmlspecialchars($book['publication_date']) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="short-description">
                                <?php if (!empty($book['short_description'])): ?>
                                    <p><?= htmlspecialchars($book['short_description']) ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="action-buttons">
                                <button class="add-to-cart-btn" onclick="addToCart('<?= htmlspecialchars($book['id'] ?? '') ?>')">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                                <button class="wishlist-btn">
                                    <i class="fas fa-heart"></i>
                                </button>
                                <button class="share-btn">
                                    <i class="fas fa-share-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Book Details Tabs Section -->
            <div class="book-details-tabs">
                <ul class="nav nav-tabs" id="bookDetailsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab" aria-controls="description" aria-selected="true">Description</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="author-tab" data-bs-toggle="tab" data-bs-target="#author" type="button" role="tab" aria-controls="author" aria-selected="false">Author Info</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab" aria-controls="reviews" aria-selected="false">Reviews</button>
                    </li>
                </ul>
                <div class="tab-content" id="bookDetailsTabsContent">
                    <div class="tab-pane fade show active" id="description" role="tabpanel" aria-labelledby="description-tab">
                        <div class="description-content">
                            <h3>Book Description</h3>
                            <div class="description-text">
                                <?= nl2br(htmlspecialchars($book['description'] ?? 'No description available')) ?>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="author" role="tabpanel" aria-labelledby="author-tab">
                        <div class="author-content">
                            <h3>About the Author</h3>
                            <div class="author-info">
                                <div class="author-image">
                                    <img src="/assets/img/author/default-author.png" alt="<?= htmlspecialchars($book['author'] ?? 'Author') ?>">
                                </div>
                                <div class="author-details">
                                    <h4><?= htmlspecialchars($book['author'] ?? 'Unknown Author') ?></h4>
                                    <div class="author-meta">
                                        <p><i class="fas fa-book"></i> <strong>Publisher:</strong> <?= htmlspecialchars($book['publisher'] ?? 'Unknown Publisher') ?></p>
                                        <p><i class="fas fa-calendar-alt"></i> <strong>Publication Date:</strong> <?= htmlspecialchars($book['publication_date'] ?? 'Unknown Date') ?></p>
                                    </div>
                                    <div class="author-bio">
                                        <p>Author biography is not available at the moment.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
                        <div class="reviews-content">
                            <h3>Customer Reviews</h3>
                            <div class="reviews-summary">
                                <div class="overall-rating">
                                    <div class="rating-big"><?= number_format(floatval($book['rating'] ?? 0), 1) ?></div>
                                    <div class="stars-big">
                                        <?php 
                                        $rating = floatval($book['rating'] ?? 0);
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= floor($rating)) {
                                                echo '<i class="fas fa-star filled"></i>';
                                            } elseif ($i - $rating <= 0.5 && $i - $rating > 0) {
                                                echo '<i class="fas fa-star-half-alt filled"></i>';
                                            } else {
                                                echo '<i class="fas fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <div class="total-reviews"><?= htmlspecialchars($book['rating_count'] ?? '0') ?> Reviews</div>
                                </div>
                            </div>

                            <!-- List of Reviews -->
                            <div class="customer-reviews mb-4">
                            <?php if (empty($reviews)): ?>
                                <div class="no-reviews">
                                    <p>No reviews yet. Be the first to review this book!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($reviews as $review): ?>
                                    <div class="review-item mb-3 p-3 border rounded">
                                        <div class="review-header d-flex justify-content-between">
                                            <div class="review-author">
                                                <strong><?= htmlspecialchars($review['full_name'] ?? 'Anonymous') ?></strong>
                                            </div>
                                            <div class="review-date">
                                                <?= htmlspecialchars(date('F j, Y', strtotime($review['created_at'] ?? 'now'))) ?>
                                            </div>
                                        </div>
                                        <div class="review-rating">
                                            <?php 
                                            $review_rating = intval($review['rating'] ?? 0);
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $review_rating) {
                                                    echo '<i class="fas fa-star filled"></i>';
                                                } else {
                                                    echo '<i class="fas fa-star"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                        <div class="review-comment mt-2">
                                            <p><?= htmlspecialchars($review['comment'] ?? '') ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Related Books Section -->
            <div class="related-books">
                <h3>You May Also Like</h3>
                <div class="related-books-slider">
                    <div class="row" id="related-books-container">
                        <!-- Books will be dynamically loaded here -->
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<style>
    .review-item {
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 15px;
    }

    .review-header {
        margin-bottom: 10px;
    }

    .review-author strong {
        font-size: 16px;
        color: #2c3e50;
    }

    .review-date {
        font-size: 14px;
        color: #7f8c8d;
    }

    .review-rating {
        margin-bottom: 10px;
    }

    .review-comment p {
        margin: 0;
        font-size: 14px;
        color: #34495e;
    }

    .no-reviews p {
        font-size: 16px;
        color: #7f8c8d;
        text-align: center;
    }
</style>
<script>
// Quantity selector functionality
function incrementQuantity(max) {
    let quantityInput = document.getElementById('quantity');
    let currentValue = parseInt(quantityInput.value);
    if (currentValue < max) {
        quantityInput.value = currentValue + 1;
    }
}

function decrementQuantity() {
    let quantityInput = document.getElementById('quantity');
    let currentValue = parseInt(quantityInput.value);
    if (currentValue > 1) {
        quantityInput.value = currentValue - 1;
    }
}

// Rating selector functionality
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.rating-stars i');
    const ratingInput = document.getElementById('selected-rating');
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.getAttribute('data-rating');
            ratingInput.value = rating;
            
            // Reset all stars
            stars.forEach(s => s.className = 'fas fa-star');
            
            // Fill stars up to selected rating
            for (let i = 0; i < stars.length; i++) {
                if (i < rating) {
                    stars[i].className = 'fas fa-star active';
                }
            }
        });
    });

    // Fetch related books
    const categoryId = "<?php echo htmlspecialchars($book['category_id'] ?? ''); ?>";
    const apiBaseUrl = "<?php echo htmlspecialchars($base_url); ?>";
    const accessToken = "<?php echo htmlspecialchars($access_token ?? ''); ?>";
    const relatedBooksContainer = document.getElementById('related-books-container');

    if (categoryId) {
        fetch(`${apiBaseUrl}/book?action=get-all-books-pagination&page=1&limit=4&is_deleted=0&category[]=${categoryId}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.books && data.data.books.length > 0) {
                const books = data.data.books.filter(book => book.id !== "<?php echo htmlspecialchars($book_id); ?>"); // Exclude current book
                if (books.length > 0) {
                    relatedBooksContainer.innerHTML = books.map(book => `
                        <div class="col-lg-3 col-md-4 col-6">
                            <div class="book-card">
                                <a href="/book-details?id=${book.id}" class="book-link">
                                    <div class="book-image">
                                        <img src="${book.image_url || '/assets/img/gallery/best-books1.jpg'}" alt="${book.title}">
                                        ${book.is_new ? '<div class="tag-badge" style="background-color: #2ECC71; color: white;"><i class="fas fa-bolt"></i> New</div>' : ''}
                                        ${book.is_discounted ? '<div class="discount-tag"><i class="fas fa-tag"></i> Sale</div>' : ''}
                                    </div>
                                    <div class="book-info">
                                        <h4 class="book-card-title">${book.title}</h4>
                                        <p class="book-card-author">${book.author}</p>
                                        <div class="book-card-price">
                                            ${book.is_discounted && book.original_price ? 
                                                `<span style="text-decoration: line-through; color: #95a5a6; font-size: 12px; margin-right: 5px;">₫${parseFloat(book.original_price).toLocaleString('vi-VN')}</span>` : ''}
                                            <span style="color: ${book.is_discounted ? '#E74C3C' : '#2c3e50'}; font-weight: bold;">₫${parseFloat(book.price).toLocaleString('vi-VN')}</span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    `).join('');
                } else {
                    relatedBooksContainer.innerHTML = '<div class="col-12"><p class="text-center">No related books found.</p></div>';
                }
            } else {
                relatedBooksContainer.innerHTML = '<div class="col-12"><p class="text-center">No related books found.</p></div>';
            }
        })
        .catch(error => {
            console.error('Error fetching related books:', error);
            relatedBooksContainer.innerHTML = '<div class="col-12"><p class="text-center">Error loading related books.</p></div>';
        });
    } else {
        relatedBooksContainer.innerHTML = '<div class="col-12"><p class="text-center">No related books available.</p></div>';
    }
});

// Add to cart functionality
function addToCart(bookId) {
    const quantity = document.getElementById('quantity') ? document.getElementById('quantity').value : 1;
    
    // Check if user is logged in
    const userId = '<?= $user_id ?>';
    if (!userId) {
        window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
        return;
    }
    
    // API call to add item to cart
    const requestData = {
        user_id: userId,
        book_id: bookId,
        quantity: quantity
    };
    
    fetch('<?= $base_url ?>/cart?action=add-to-cart', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer <?= $access_token ?>'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert('Book added to cart successfully!');
            
            // Update cart count in header if exists
            if (typeof window.updateCartCount === 'function') {
                window.updateCartCount();
            }
        } else {
            alert(data.message || 'Failed to add book to cart. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        alert('An error occurred. Please try again later.');
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main-layout.php';
?>