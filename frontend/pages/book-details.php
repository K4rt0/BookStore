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
.services-area2 {
    padding: 20px 0;
}
.single-services {
    display: flex;
    align-items: center;
    border: none;
    padding: 20px;
    background: #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    border-radius: 5px;
}
.features-img img {
    max-width: 200px;
    height: auto;
    margin-right: 20px;
}
.features-caption {
    flex: 1;
}
.features-caption h3 {
    font-size: 24px;
    margin-bottom: 10px;
}
.price span {
    font-size: 20px;
    color: #e74c3c;
}
.review {
    margin: 10px 0;
}
.white-btn {
    background: #fff;
    border: 1px solid #ddd;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    color: #333;
}
.white-btn:hover {
    background: #f0f0f0;
}
.border-btn {
    border: 1px solid #ddd;
    padding: 5px 10px;
    border-radius: 5px;
    margin-left: 10px;
}
.share-btn i {
    color: #333;
}
.nav-tabs {
    border-bottom: 2px solid #ddd;
}
.nav-tabs .nav-link {
    margin-right: 10px;
    padding: 10px 20px;
    border: none;
    border-bottom: 2px solid transparent;
    color: #666;
}
.nav-tabs .nav-link.active {
    border-bottom: 2px solid #e74c3c;
    color: #333;
}
.tab-content {
    padding: 20px 0;
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
                                        <span>$<?= htmlspecialchars(number_format(($book['price'] ?? 0), 0)) ?></span>
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
                                        <p>(<?= htmlspecialchars($book['rating_count'] ?? '0') ?> Review)</p>
                                    </div>
                                    <a href="#" class="white-btn mr-10" onclick="addToCart('<?= htmlspecialchars($book['id'] ?? '') ?>')">Add to Cart</a>
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
                        <div class="offset-xl-1 col-xl-10">
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
                    <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-one" role="tabpanel" aria-labelledby="nav-one-tab">
                            <div class="row">
                                <div class="offset-xl-1 col-lg-9">
                                    <p><?= htmlspecialchars($book['description'] ?? 'No description available') ?></p>
                                    <p><?= htmlspecialchars($book['short_description'] ?? '') ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="nav-two" role="tabpanel" aria-labelledby="nav-two-tab">
                            <div class="row">
                                <div class="offset-xl-1 col-lg-9">
                                    <p>Author: <?= htmlspecialchars($book['author'] ?? 'Unknown Author') ?></p>
                                    <p>Publisher: <?= htmlspecialchars($book['publisher'] ?? 'Unknown Publisher') ?></p>
                                    <p>Publication Date: <?= htmlspecialchars($book['publication_date'] ?? 'Unknown Date') ?></p>
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