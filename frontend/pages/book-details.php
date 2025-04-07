<?php
$page_title = "Book Shop - Details";
ob_start(); // Start buffer to save page content

// Start the session to access user_id and access_token
session_start();

// Get book ID from URL parameter
$book_id = isset($_GET['id']) ? $_GET['id'] : null;
$base_url = $_ENV['API_BASE_URL'] ?? 'https://your-api.com';
$access_token = $_SESSION['access_token'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

// Log the access token and user_id for debugging
error_log("Access Token: " . ($access_token ?? "Not set"));
error_log("User ID: " . ($user_id ?? "Not set"));

// Initialize book data
$book = null;

if ($book_id) {
    // Fetch book data from API
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
            $book = $response['data'];
        }
    } else {
        error_log("Failed to fetch book data: HTTP $http_code, cURL Error: " . ($error ?: "None"));
    }
}

// If book data is not available, show error
if (!$book) {
    $error_message = "Book not found or has been deleted";
}
?>
<link rel="stylesheet" href="/assets/css/template/book-details.css">


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
                                        <span>₫<?= htmlspecialchars(number_format(($book['price'] ?? 0), 2)) ?></span>
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
                                    <a href="#" class="white-btn mr-10 p-4" onclick="addToCart('<?= htmlspecialchars($book['id'] ?? '') ?>'); return false;">Add to Cart</a>
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
document.addEventListener('DOMContentLoaded', function () {
    // Variables for API call
    const userId = "<?php echo htmlspecialchars($user_id ?? ''); ?>";
    const apiBaseUrl = "<?php echo htmlspecialchars($base_url); ?>";
    const accessToken = "<?php echo htmlspecialchars($access_token ?? ''); ?>";

    // Make addToCart function globally accessible
    window.addToCart = function(bookId) {
        // Check if user is logged in
        if (!userId || !accessToken) {
            alert('Please log in to add items to your cart.');
            window.location.href = '/login.php'; // Redirect to login page
            return;
        }

        // Prepare the request body
        const requestBody = {
            user_id: userId,
            book_id: bookId,
            quantity: 1
        };

        // Call the add-to-cart API
        fetch(`${apiBaseUrl}/cart?action=add-to-cart`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestBody)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Book added to cart successfully!');
                // Optionally, redirect to the cart page
                // window.location.href = '/cart.php';
            } else {
                alert('Failed to add book to cart: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error adding book to cart:', error);
            alert('Error adding book to cart. Please try again.');
        });
    };
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main-layout.php';
?>