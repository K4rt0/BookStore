<?php
// pages/home.php
session_start();
require_once __DIR__ . '/../includes/env-loader.php';

$page_title = "Book Shop - Home";
$layout = 'main';
$api_base_url = $_ENV['API_BASE_URL'];

function fetchData($url) {
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
    return $data['data'];
}

function getStarRating($rating) {
    $fullStars = floor($rating);
    $halfStar = $rating % 1 >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;
    $starsHTML = '';
    for ($i = 0; $i < $fullStars; $i++) {
        $starsHTML .= '<i class="fas fa-star filled"></i>';
    }
    if ($halfStar) {
        $starsHTML .= '<i class="fas fa-star-half-alt filled"></i>';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $starsHTML .= '<i class="fas fa-star"></i>';
    }
    return $starsHTML;
}

// Fetch books for each section
$bestSellingBooks = fetchData("{$api_base_url}/book?action=get-all-books-pagination&page=1&limit=6&is_best_seller=1&sort=price_at_asc&is_deleted=0")['books'] ?? [];
$featuredBooks = fetchData("{$api_base_url}/book?action=get-all-books-pagination&page=1&limit=2&is_featured=1&sort=price_at_asc&is_deleted=0")['books'] ?? [];
$latestBooksAll = fetchData("{$api_base_url}/book?action=get-all-books-pagination&page=1&limit=6&is_new=1&sort=created_at_desc&is_deleted=0")['books'] ?? [];

// Fetch categories
$categoriesData = fetchData("{$api_base_url}/category?action=get-all-categories");
$categories = $categoriesData ?? [];
$categories = array_slice($categories, 0, 4);

// Fetch books for each category dynamically
$latestBooksByCategory = [];
foreach ($categories as $category) {
    $categoryId = $category['id'];
    $latestBooksByCategory[$categoryId] = fetchData("{$api_base_url}/book?action=get-all-books-pagination&page=1&limit=4&is_new=1&sort=created_at_desc&is_deleted=0&category[]={$categoryId}")['books'] ?? [];
}
ob_start();
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
</style>
<!-- slider Area Start -->
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
<!-- slider Area End -->

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
            <?php if (empty($bestSellingBooks)): ?>
                <div class="col-12"><p>Không có sách bán chạy để hiển thị.</p></div>
            <?php else: ?>
                <?php foreach ($bestSellingBooks as $book): ?>
                    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                        <div class="properties pb-20">
                            <div class="properties-card">
                                <div class="properties-img">
                                    <a href="/book-details?id=<?= htmlspecialchars($book['id']) ?>">
                                        <div class="img-wrapper">
                                            <img style="height: 12rem; object-fit: cover;" src="<?= htmlspecialchars($book['image_url'] ?: 'assets/img/placeholder.jpg') ?>" alt="<?= htmlspecialchars($book['title']) ?>">
                                        </div>
                                    </a>
                                </div>
                                <div class="properties-caption">
                                    <h3 style="margin-bottom: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><a href="/book-details?id=<?= htmlspecialchars($book['id']) ?>"><?= htmlspecialchars($book['title']) ?></a></h3>
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
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Best Selling End -->

<!-- services-area start -->
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
                                        <h3 style="margin-bottom: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($book['title']) ?></h3>
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
<!-- services-area End -->

<!-- Latest-items Start -->
<section class="our-client section-padding best-selling">
    <div class="container">
        <div class="row justify-content-between">
            <div class="col-xl-5 col-lg-5 col-md-12">
                <!-- Section Tittle -->
                <div class="section-tittle mb-40">
                    <h2>Latest Published Items</h2>
                </div>
            </div>
            <div class="col-xl-7 col-lg-7 col-md-12">
                <div class="nav-button mb-40">
                    <!--Nav Button  -->
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <a class="nav-link active" id="nav-one-tab" data-bs-toggle="tab" href="#nav-one" role="tab" aria-controls="nav-one" aria-selected="true">All</a>
                            <?php foreach ($categories as $index => $category): ?>
                                <a class="nav-link" id="nav-tab-<?= $index + 2 ?>-tab" data-bs-toggle="tab" href="#nav-tab-<?= $index + 2 ?>" role="tab" aria-controls="nav-tab-<?= $index + 2 ?>" aria-selected="false">
                                    <?= htmlspecialchars($category['name']) ?>
                                </a>
                            <?php endforeach; ?>
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
                                        <h3 style="margin-bottom: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #777;"><a href="/book-details?id=<?= htmlspecialchars($book['id']) ?>"><?= htmlspecialchars($book['title']) ?></a></h3>
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
            <!-- Dynamic Tabs for Categories -->
            <?php foreach ($categories as $index => $category): ?>
                <div class="tab-pane fade" id="nav-tab-<?= $index + 2 ?>" role="tabpanel" aria-labelledby="nav-tab-<?= $index + 2 ?>-tab">
                    <div class="row">
                        <?php foreach ($latestBooksByCategory[$category['id']] as $book): ?>
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
                                            <h3 style="margin-bottom: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #777;"><a href="/book-details?id=<?= htmlspecialchars($book['id']) ?>"><?= htmlspecialchars($book['title']) ?></a></h3>
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
            <?php endforeach; ?>
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