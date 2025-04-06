<?php
$page_title = "Book Shop - Details";
ob_start(); // Start buffer to save page content

// Get book ID from URL parameter
$book_id = isset($_GET['id']) ? $_GET['id'] : null;
$base_url = $_ENV['API_BASE_URL'];

// Initialize book data
$book = null;

if ($book_id) {
    // Fetch book data from API
    $api_url = $base_url . "books?action=get-book&id=" . urlencode($book_id);
    $book_json = file_get_contents($api_url);
    
    if ($book_json) {
        $response = json_decode($book_json, true);
        if ($response['success'] && $response['code'] == 200) {
            $book = $response['data'];
        }
    }
}

// If book data is not available, show error
if (!$book) {
    $error_message = "Book not found or has been deleted";
}
?>

<style>
/* General Section Styling */
.services-area2 {
    padding: 40px 0;
    background: #f9f9f9;
}
.container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Book Details Section */
.single-services {
    display: flex;
    align-items: center;
    border: none;
    padding: 20px;
    background: #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    border-radius: 8px;
    margin-bottom: 30px;
}
.features-img img {
    max-width: 200px;
    height: auto;
    margin-right: 30px;
    border-radius: 5px;
}
.features-caption {
    flex: 1;
}
.features-caption h3 {
    font-size: 28px;
    margin-bottom: 10px;
    color: #333;
}
.features-caption p {
    font-size: 16px;
    color: #666;
    margin-bottom: 15px;
}
.price span {
    font-size: 22px;
    color: #e74c3c;
    font-weight: bold;
}
.review {
    margin: 15px 0;
    display: flex;
    align-items: center;
}
.review .rating {
    margin-right: 10px;
}
.review p {
    margin: 0;
    font-size: 14px;
    color: #666;
}
.white-btn {
    background: #fff;
    border: 1px solid #ddd;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    color: #333;
    transition: background 0.3s;
}
.white-btn:hover {
    background: #f0f0f0;
}
.border-btn {
    border: 1px solid #ddd;
    padding: 8px 12px;
    border-radius: 5px;
    margin-left: 10px;
    text-decoration: none;
}
.share-btn i {
    color: #333;
}

/* Tabs Section */
.our-client {
    padding: 40px 0;
}
.nav-tabs {
    border-bottom: 2px solid #eee;
    margin-bottom: 20px;
}
.nav-tabs .nav-link {
    margin-right: 20px;
    padding: 12px 25px;
    border: none;
    border-radius: 5px 5px 0 0;
    color: #666;
    font-size: 16px;
    font-weight: 500;
    transition: all 0.3s;
}
.nav-tabs .nav-link:hover {
    background: #f5f5f5;
    color: #333;
}
.nav-tabs .nav-link.active {
    background: #fff;
    border-bottom: 3px solid #e74c3c;
    color: #333;
    font-weight: 600;
}
.tab-content {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* Description Tab Styling */
.description-content h4 {
    font-size: 22px;
    color: #333;
    margin-bottom: 15px;
    border-left: 4px solid #e74c3c;
    padding-left: 15px;
}
.description-content .main-description {
    font-size: 16px;
    line-height: 1.8;
    color: #555;
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 5px;
}
.description-content .short-description {
    font-size: 15px;
    line-height: 1.6;
    color: #777;
    font-style: italic;
    padding: 10px 15px;
    border-left: 2px solid #ddd;
}

/* Author Tab Styling */
.author-content h4 {
    font-size: 22px;
    color: #333;
    margin-bottom: 15px;
    border-left: 4px solid #e74c3c;
    padding-left: 15px;
}
.author-content .author-info {
    padding: 15px;
    background: #f9f9f9;
    border-radius: 5px;
}
.author-content .author-info p {
    font-size: 16px;
    line-height: 1.8;
    color: #555;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
}
.author-content .author-info p i {
    margin-right: 10px;
    color: #e74c3c;
    font-size: 18px;
}
.author-content .author-info p strong {
    color: #333;
    font-weight: 600;
    min-width: 150px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .single-services {
        flex-direction: column;
        text-align: center;
    }
    .features-img img {
        margin-right: 0;
        margin-bottom: 20px;
    }
    .nav-tabs .nav-link {
        padding: 10px 15px;
        font-size: 14px;
    }
    .tab-content {
        padding: 20px;
    }
    .description-content h4,
    .author-content h4 {
        font-size: 20px;
    }
    .author-content .author-info p {
        flex-direction: column;
        align-items: flex-start;
    }
    .author-content .author-info p strong {
        margin-bottom: 5px;
    }
}
</style>

<div class="services-area2">
    <div class="container">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-xl-12">
                    <div class="row">
                        <div class="col-xl-12">
                            <!-- Single -->
                            <div class="single-services d-flex align-items-center mb-0">
                                <div class="features-img">
                                    <img src="<?= htmlspecialchars($book['image_url'] ?? '/assets/img/gallery/best-books1.jpg') ?>" alt="Book cover">
                                </div>
                                <div class="features-caption">
                                    <h3><?= htmlspecialchars($book['title'] ?? 'Unknown Title') ?></h3>
                                    <p>By <?= htmlspecialchars($book['author'] ?? 'Unknown Author') ?></p>
                                    <div class="price">
                                        <span>$<?= htmlspecialchars(number_format(($book['price'] ?? 0) / 1000, 2)) ?></span>
                                    </div>
                                    <div class="review">
                                        <div class="rating">
                                            <?php 
                                            $rating = floatval($book['rating'] ?? 0);
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
                                        <p class="mb-0">(<?= htmlspecialchars($book['rating_count'] ?? '0') ?> Review)</p>
                                    </div>
                                    <a href="#" class="white-btn mr-10 p-4" onclick="addToCart('<?= htmlspecialchars($book['id'] ?? '') ?>')">Add to Cart</a>
                                    <a href="#" class="border-btn share-btn"><i class="fas fa-share-alt"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Books review Start -->
            <section class="our-client section-padding best-selling">
                <div class="container">
                    <div class="row">
                        <div class="offset-xl-1 col-xl-10 col-lg-12">
                            <div class="nav-button f-left">
                                <nav>
                                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                        <a class="nav-link active" id="nav-one-tab" data-bs-toggle="tab" href="#nav-one" role="tab" aria-controls="nav-one" aria-selected="true">Description</a>
                                        <a class="nav-link" id="nav-two-tab" data-bs-toggle="tab" href="#nav-two" role="tab" aria-controls="nav-two" aria-selected="false">Author</a>
                                    </div>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="offset-xl-1 col-xl-10 col-lg-12">
                            <div class="tab-content" id="nav-tabContent">
                                <div class="tab-pane fade show active" id="nav-one" role="tabpanel" aria-labelledby="nav-one-tab">
                                    <div class="description-content">
                                        <h4>Book Description</h4>
                                        <p class="main-description"><?= htmlspecialchars($book['description'] ?? 'No description available') ?></p>
                                        <?php if (!empty($book['short_description'])): ?>
                                            <p class="short-description"><?= htmlspecialchars($book['short_description']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="nav-two" role="tabpanel" aria-labelledby="nav-two-tab">
                                    <div class="author-content">
                                        <h4>About the Author</h4>
                                        <div class="author-info">
                                            <p><i class="fas fa-user"></i> <strong>Author:</strong> <?= htmlspecialchars($book['author'] ?? 'Unknown Author') ?></p>
                                            <p><i class="fas fa-book"></i> <strong>Publisher:</strong> <?= htmlspecialchars($book['publisher'] ?? 'Unknown Publisher') ?></p>
                                            <p><i class="fas fa-calendar-alt"></i> <strong>Publication Date:</strong> <?= htmlspecialchars($book['publication_date'] ?? 'Unknown Date') ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>

<script>
function addToCart(bookId) {
    console.log("Adding book to cart:", bookId);
    alert("Book added to cart!");
    return false;
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main-layout.php';
?>