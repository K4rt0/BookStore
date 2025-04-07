<?php
// pages/home.php
session_start();
require_once __DIR__ . '/../includes/env-loader.php';

// Debug: Confirm this file is loaded
error_log("Loaded home.php");

$page_title = "Book Shop - Home";
$layout = 'main';
$api_base_url = $_ENV['API_BASE_URL'];

// Function to fetch data from the API
function fetchBooks($url) {
    $response = file_get_contents($url);
    if ($response === false) {
        error_log("Failed to fetch data from: $url");
        return [];
    }
    $data = json_decode($response, true);
    if (!$data || !isset($data['success']) || !$data['success']) {
        error_log("Invalid API response from: $url");
        return [];
    }
    return $data['data']['books'];
}

// Function to generate star ratings
function getStarRating($rating) {
    $fullStars = floor($rating);
    $halfStar = $rating % 1 >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;
    $starsHTML = '';
    for ($i = 0; $i < $fullStars; $i++) {
        $starsHTML .= '<i class="fas fa-star"></i>';
    }
    if ($halfStar) {
        $starsHTML .= '<i class="fas fa-star-half-alt"></i>';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $starsHTML .= '<i class="fas fa-star"></i>';
    }
    return $starsHTML;
}

// Fetch books for each section
$bestSellingBooks = fetchBooks("{$api_base_url}/book?action=get-all-books-pagination&page=1&limit=6&is_best_seller=1&sort=price_at_asc");
$featuredBooks = fetchBooks("{$api_base_url}/book?action=get-all-books-pagination&page=1&limit=2&is_featured=1&sort=price_at_asc");
$latestBooksAll = fetchBooks("{$api_base_url}/book?action=get-all-books-pagination&page=1&limit=6&is_new=1&sort=created_at_desc");

// Fetch books for each category tab
$categoryIds = [
    'horror' => '1', // Replace with actual category_id for Horror
    'thriller' => '2', // Replace with actual category_id for Thriller
    'scifi' => '3', // Replace with actual category_id for Science Fiction
    'history' => '4' // Replace with actual category_id for History
];

$latestBooksHorror = fetchBooks("{$api_base_url}/book?action=get-all-books-pagination&page=1&limit=6&is_new=1&sort=created_at_desc&category_id={$categoryIds['horror']}");
$latestBooksThriller = fetchBooks("{$api_base_url}/book?action=get-all-books-pagination&page=1&limit=6&is_new=1&sort=created_at_desc&category_id={$categoryIds['thriller']}");
$latestBooksScifi = fetchBooks("{$api_base_url}/book?action=get-all-books-pagination&page=1&limit=6&is_new=1&sort=created_at_desc&category_id={$categoryIds['scifi']}");
$latestBooksHistory = fetchBooks("{$api_base_url}/book?action=get-all-books-pagination&page=1&limit=6&is_new=1&sort=created_at_desc&category_id={$categoryIds['history']}");

ob_start();
?>

<!-- slider Area Start-->
<div class="slider-area">
    <div class="container">
        <div class="row">
            <div class="col-xl-12">
                <div class="slider-active dot-style">
                    <!-- Single Slider -->
                    <div class="single-slider slider-height slider-bg1 d-flex align-items-center">
                        <div class="container">
                            <div class="row justify-content-center">
                                <div class="col-xxl-4 col-xl-4 col-lg-5 col-md-6 col-sm-7">
                                    <div class="hero-caption text-center">
                                        <span data-animation="fadeInUp" data-delay=".2s">Science Fiction</span>
                                        <h1 data-animation="fadeInUp" data-delay=".4s">The History<br> of Phipino</h1>
                                        <a href="#" class="btn hero-btn" data-animation="bounceIn" data-delay=".8s">Browse Store</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Single Slider -->
                    <div class="single-slider slider-height slider-bg2 d-flex align-items-center">
                        <div class="container">
                            <div class="row justify-content-center">
                                <div class="col-xxl-4 col-xl-4 col-lg-5 col-md-6 col-sm-7">
                                    <div class="hero-caption text-center">
                                        <span data-animation="fadeInUp" data-delay=".2s">Science Fiction</span>
                                        <h1 data-animation="fadeInUp" data-delay=".4s">The History<br> of Phipino</h1>
                                        <a href="#" class="btn hero-btn" data-animation="bounceIn" data-delay=".8s">Browse Store</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Single Slider -->
                    <div class="single-slider slider-height slider-bg3 d-flex align-items-center">
                        <div class="container">
                            <div class="row justify-content-center">
                                <div class="col-xxl-4 col-xl-4 col-lg-5 col-md-6 col-sm-7">
                                    <div class="hero-caption text-center">
                                        <span data-animation="fadeInUp" data-delay=".2s">Science Fiction</span>
                                        <h1 data-animation="fadeInUp" data-delay=".4s">The History<br> of Phipino</h1>
                                        <a href="#" class="btn hero-btn" data-animation="bounceIn" data-delay=".8s">Browse Store</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- slider Area End-->

<!-- Best Selling start -->
<div class="best-selling section-bg">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-7 col-lg-8">
                <div class="section-tittle text-center mb-55">
                    <h2>Best Selling Books Ever</h2>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="selling-active">
                    <?php foreach ($bestSellingBooks as $book): ?>
                        <!-- Single -->
                        <div class="properties pb-20">
                            <div class="properties-card" style="width: 100%;">
                                <div class="properties-img">
                                    <a href="/book-details?id=<?= htmlspecialchars($book['id']) ?>">
                                        <div class="img-wrapper">
                                            <img style="height: 12rem; object-fit: cover;" src="<?= htmlspecialchars($book['image_url']) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
                                        </div>
                                    </a>
                                </div>
                                <div class="properties-caption">
                                    <h3><a href="/book-details?id=<?= htmlspecialchars($book['id']) ?>"><?= htmlspecialchars($book['title']) ?></a></h3>
                                    <p><?= htmlspecialchars($book['author']) ?></p>
                                    <div class="properties-footer d-flex justify-content-between align-items-center">
                                        <div class="review">
                                            <div class="rating">
                                                <?= getStarRating(floatval($book['rating'])) ?>
                                            </div>
                                            <p>(<span><?= htmlspecialchars($book['rating_count']) ?></span> Review)</p>
                                        </div>
                                        <div class="price">
                                            <span>$<?= number_format(floatval($book['price']), 2, '.', '') ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Best Selling End -->

<!-- services-area start-->
<div class="services-area2 top-padding">
    <div class="container">
        <div class="row">
            <div class="col-xl-9 col-lg-9 col-md-8">
                <div class="row">
                    <!-- tittle -->
                    <div class="col-xl-12">
                        <div class="section-tittle d-flex justify-content-between align-items-center mb-40">
                            <h2 class="mb-0">Featured This Week</h2>
                            <a href="#" class="browse-btn">View All</a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-12">
                        <div class="services-active pb-2">
                            <?php foreach ($featuredBooks as $book): ?>
                                <!-- Single -->
                                <div class="single-services d-flex align-items-center">
                                    <div class="features-img" style="height: 26rem;  display: flex; align-items: center; justify-content: center;">
                                        <div class="img-wrapper" style="width: 20rem; height: 20rem; overflow: hidden; border-radius: 8px;">
                                            <img src="<?= htmlspecialchars($book['image_url']) ?>" alt="<?= htmlspecialchars($book['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    </div>
                                    <div class="features-caption">
                                        <img src="assets/img/icon/logo.html" alt="">
                                        <h3><?= htmlspecialchars($book['title']) ?></h3>
                                        <p>By <?= htmlspecialchars($book['author']) ?></p>
                                        <div class="price">
                                            <span>$<?= number_format(floatval($book['price']), 2, '.', '') ?></span>
                                        </div>
                                        <div class="review">
                                            <div class="rating">
                                                <?= getStarRating(floatval($book['rating'])) ?>
                                            </div>
                                            <p>(<?= htmlspecialchars($book['rating_count']) ?> Review)</p>
                                        </div>
                                        <a href="/book-details?id=<?= htmlspecialchars($book['id']) ?>" class="border-btn">View Details</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-3 col-md-4 col-sm-9">
                <!-- Google Addd -->
                <div class="google-add">
                    <img src="assets/img/gallery/ad.jpg" alt="" class="w-100">
                </div>
            </div>
        </div>
    </div>
</div>
<!-- services-area End-->

<!-- Latest-items Start -->
<section class="our-client section-padding best-selling">
    <div class="container">
        <div class="row justify-content-between">
            <div class="col-xl-5 col-lg-5 col-md-12">
                <!-- Section Tittle -->
                <div class="section-tittle mb-40">
                    <h2>Latest Published items</h2>
                </div>
            </div>
            <div class="col-xl-7 col-lg-7 col-md-12">
                <div class="nav-button mb-40">
                    <!--Nav Button  -->
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <a class="nav-link active" id="nav-one-tab" data-bs-toggle="tab" href="#nav-one" role="tab" aria-controls="nav-one" aria-selected="true">All</a>
                            <a class="nav-link" id="nav-two-tab" data-bs-toggle="tab" href="#nav-two" role="tab" aria-controls="nav-two" aria-selected="false">Horror</a>
                            <a class="nav-link" id="nav-three-tab" data-bs-toggle="tab" href="#nav-three" role="tab" aria-controls="nav-three" aria-selected="false">Thriller</a>
                            <a class="nav-link" id="nav-four-tab" data-bs-toggle="tab" href="#nav-four" role="tab" aria-controls="nav-four" aria-selected="false">Science Fiction</a>
                            <a class="nav-link" id="nav-five-tab" data-bs-toggle="tab" href="#nav-five" role="tab" aria-controls="nav-five" aria-selected="false">History</a>
                        </div>
                    </nav>
                    <!--End Nav Button  -->
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <!-- Nav Card -->
        <div class="tab-content" id="nav-tabContent">
            <!-- Tab 1: All -->
            <div class="tab-pane fade show active" id="nav-one" role="tabpanel" aria-labelledby="nav-one-tab">
                <div class="row">
                    <?php foreach ($latestBooksAll as $book): ?>
                        <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                            <div class="properties pb-30">
                                <div class="properties-card">
                                    <div class="properties-img">
                                        <a href="/book-details?id=<?= htmlspecialchars($book['id']) ?>">
                                            <div class="img-wrapper">
                                                <img style="height: 14rem; object-fit: cover;" src="<?= htmlspecialchars($book['image_url']) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
                                            </div>
                                        </a>
                                    </div>
                                    <div class="properties-caption properties-caption2">
                                        <h3><a href="/book-details?id=<?= htmlspecialchars($book['id']) ?>"><?= htmlspecialchars($book['title']) ?></a></h3>
                                        <p><?= htmlspecialchars($book['author']) ?></p>
                                        <div class="properties-footer d-flex justify-content-between align-items-center">
                                            <div class="review">
                                                <div class="rating">
                                                    <?= getStarRating(floatval($book['rating'])) ?>
                                                </div>
                                                <p>(<span><?= htmlspecialchars($book['rating_count']) ?></span> Review)</p>
                                            </div>
                                            <div class="price">
                                                <span>$<?= number_format(floatval($book['price']), 2, '.', '') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Tab 2: Horror -->
            <div class="tab-pane fade" id="nav-two" role="tabpanel" aria-labelledby="nav-two-tab">
                <div class="row">
                    <?php foreach ($latestBooksHorror as $book): ?>
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <div class="properties pb-30">
                                <div class="properties-card">
                                    <div class="properties-img">
                                        <a href="/book-details?id=<?= htmlspecialchars($book['id']) ?>">
                                            <div class="img-wrapper">
                                                <img src="<?= htmlspecialchars($book['image_url']) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
                                            </div>
                                        </a>
                                    </div>
                                    <div class="properties-caption properties-caption2">
                                        <h3><a href="/book-details?id=<?= htmlspecialchars($book['id']) ?>"><?= htmlspecialchars($book['title']) ?></a></h3>
                                        <p><?= htmlspecialchars($book['author']) ?></p>
                                        <div class="properties-footer d-flex justify-content-between align-items-center">
                                            <div class="review">
                                                <div class="rating">
                                                    <?= getStarRating(floatval($book['rating'])) ?>
                                                </div>
                                                <p>(<span><?= htmlspecialchars($book['rating_count']) ?></span> Review)</p>
                                            </div>
                                            <div class="price">
                                                <span>$<?= number_format(floatval($book['price']), 2, '.', '') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Tab 3: Thriller -->
            <div class="tab-pane fade" id="nav-three" role="tabpanel" aria-labelledby="nav-three-tab">
                <div class="row">
                    <?php foreach ($latestBooksThriller as $book): ?>
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <div class="properties pb-30">
                                <div class="properties-card">
                                    <div class="properties-img">
                                        <a href="/book-details?id=<?= htmlspecialchars($book['id']) ?>">
                                            <div class="img-wrapper">
                                                <img src="<?= htmlspecialchars($book['image_url']) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
                                            </div>
                                        </a>
                                    </div>
                                    <div class="properties-caption properties-caption2">
                                        <h3><a href="/book-details?id=<?= htmlspecialchars($book['id']) ?>"><?= htmlspecialchars($book['title']) ?></a></h3>
                                        <p><?= htmlspecialchars($book['author']) ?></p>
                                        <div class="properties-footer d-flex justify-content-between align-items-center">
                                            <div class="review">
                                                <div class="rating">
                                                    <?= getStarRating(floatval($book['rating'])) ?>
                                                </div>
                                                <p>(<span><?= htmlspecialchars($book['rating_count']) ?></span> Review)</p>
                                            </div>
                                            <div class="price">
                                                <span>$<?= number_format(floatval($book['price']), 2, '.', '') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Tab 4: Science Fiction -->
            <div class="tab-pane fade" id="nav-four" role="tabpanel" aria-labelledby="nav-four-tab">
                <div class="row">
                    <?php foreach ($latestBooksScifi as $book): ?>
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <div class="properties pb-30">
                                <div class="properties-card">
                                    <div class="properties-img">
                                        <a href="/book-details?id=<?= htmlspecialchars($book['id']) ?>">
                                            <div class="img-wrapper">
                                                <img src="<?= htmlspecialchars($book['image_url']) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
                                            </div>
                                        </a>
                                    </div>
                                    <div class="properties-caption properties-caption2">
                                        <h3><a href="/book-details?id=<?= htmlspecialchars($book['id']) ?>"><?= htmlspecialchars($book['title']) ?></a></h3>
                                        <p><?= htmlspecialchars($book['author']) ?></p>
                                        <div class="properties-footer d-flex justify-content-between align-items-center">
                                            <div class="review">
                                                <div class="rating">
                                                    <?= getStarRating(floatval($book['rating'])) ?>
                                                </div>
                                                <p>(<span><?= htmlspecialchars($book['rating_count']) ?></span> Review)</p>
                                            </div>
                                            <div class="price">
                                                <span>$<?= number_format(floatval($book['price']), 2, '.', '') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Tab 5: History -->
            <div class="tab-pane fade" id="nav-five" role="tabpanel" aria-labelledby="nav-five-tab">
                <div class="row">
                    <?php foreach ($latestBooksHistory as $book): ?>
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <div class="properties pb-30">
                                <div class="properties-card">
                                    <div class="properties-img">
                                        <a href="/book-details?id=<?= htmlspecialchars($book['id']) ?>">
                                            <div class="img-wrapper">
                                                <img src="<?= htmlspecialchars($book['image_url']) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
                                            </div>
                                        </a>
                                    </div>
                                    <div class="properties-caption properties-caption2">
                                        <h3><a href="/book-details?id=<?= htmlspecialchars($book['id']) ?>"><?= htmlspecialchars($book['title']) ?></a></h3>
                                        <p><?= htmlspecialchars($book['author']) ?></p>
                                        <div class="properties-footer d-flex justify-content-between align-items-center">
                                            <div class="review">
                                                <div class="rating">
                                                    <?= getStarRating(floatval($book['rating'])) ?>
                                                </div>
                                                <p>(<span><?= htmlspecialchars($book['rating_count']) ?></span> Review)</p>
                                            </div>
                                            <div class="price">
                                                <span>$<?= number_format(floatval($book['price']), 2, '.', '') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="more-btn text-center mt-15">
                    <a href="#" class="border-btn border-btn2 more-btn2">Browse More</a>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Latest-items End -->

<!-- Want To work -->
<section class="container">
    <div class="row align-items-center justify-content-between">
        <div class="col-xl-6 col-lg-6">
            <div class="wantToWork-area w-padding2 mb-30" data-background="assets/img/gallery/wants-bg1.jpg">
                <h2>The History<br> of Phipino</h2>
                <div class="linking">
                    <a href="#" class="btn wantToWork-btn">View Details</a>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-lg-6">
            <div class="wantToWork-area w-padding2 mb-30" data-background="assets/img/gallery/wants-bg2.jpg">
                <h2>Wilma Mumduya</h2>
                <div class="linking">
                    <a href="#" class="btn wantToWork-btn">View Details</a>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Want To work End -->

<!-- Subscribe Area Start -->
<section class="subscribe-area">
    <div class="container">
        <div class="subscribe-caption text-center subscribe-padding section-img2-bg" data-background="assets/img/gallery/section-bg1.jpg">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-8 col-md-9">
                    <h3>Join Newsletter</h3>
                    <p>Lorem started its journey with cast iron (CI) products in 1980. The initial main objective was to ensure pure water and affordable irrigation.</p>
                    <form action="#">
                        <input type="text" placeholder="Enter your email">
                        <button class="subscribe-btn">Subscribe</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Subscribe Area End -->

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main-layout.php';
?>