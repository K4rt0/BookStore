<?php
// Remove session_start() since it's already called in index.php
$page_title = "Edit Book";
$layout = 'admin';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

// Debug: Log session data
error_log("Auth Token: " . ($_SESSION['access_token'] ?? 'Not set'));

// Set the base URL (from environment variable)
$base_url = $_ENV['API_BASE_URL'];
if (empty($base_url)) {
    error_log("API_BASE_URL is not set");
    die('Error: API_BASE_URL is not set.');
}
error_log("Base URL: $base_url");

// Validate book ID
$book_id = isset($_GET['id']) ? trim($_GET['id']) : '';
if (empty($book_id)) {
    error_log("Book ID is required but not provided");
    die('Error: Book ID is required.');
}
error_log("Book ID: $book_id");

// Get book details using the correct action 'get-book'
$api_url = $base_url . "/book?action=get-book&id=" . urlencode($book_id);
error_log("API URL: $api_url");
$headers = [
    'Authorization: Bearer ' . ($_SESSION['access_token'] ?? ''),
    'Content-Type: application/json',
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error_message = '';
$success_message = '';

if ($response === false) {
    $error_message = 'CURL Error: ' . curl_error($ch);
    error_log("CURL Error fetching book: " . curl_error($ch));
} else {
    $result = json_decode($response, true);
    if ($http_code !== 200 || !$result || !isset($result['data'])) {
        $error_message = $result['message'] ?? 'Cannot fetch book details.';
        error_log("API Response Error: HTTP $http_code - " . json_encode($result));
    }
}
curl_close($ch);

$book = $result['data'] ?? null;
if (!$book) {
    $error_message = $error_message ?: 'Book not found.';
}

// Get categories list for dropdown
$categories_url = $base_url . "/category?action=get-all-categories";
$ch = curl_init($categories_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . ($_SESSION['access_token'] ?? '')
]);
$categories_response = curl_exec($ch);
curl_close($ch);

$category_list = [];
$categories_result = json_decode($categories_response, true);
if ($categories_result && isset($categories_result['data'])) {
    $category_list = $categories_result['data'];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug form data
    error_log("Form submission: " . json_encode($_POST));
    
    $update_api_url = $base_url . "/book?action=update";
    $headers = [
        'Authorization: Bearer ' . ($_SESSION['access_token'] ?? ''),
    ];

    // Initialize multipart form data
    $postFields = [
        'id' => trim($_POST['id'] ?? ''),
        'title' => trim($_POST['title'] ?? ''),
        'author' => trim($_POST['author'] ?? ''),
        'publisher' => trim($_POST['publisher'] ?? ''),
        'publication_date' => trim($_POST['publication_date'] ?? ''),
        'price' => floatval($_POST['price'] ?? 0),
        'stock_quantity' => intval($_POST['stock_quantity'] ?? 0),
        'category_id' => trim($_POST['category_id'] ?? ''),
        'short_description' => trim($_POST['short_description'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        // Handle checkbox values properly - set to 1 if checked, 0 if not
        'is_featured' => isset($_POST['is_featured']) ? '1' : '0',
        'is_new' => isset($_POST['is_new']) ? '1' : '0',
        'is_best_seller' => isset($_POST['is_best_seller']) ? '1' : '0',
        'is_discounted' => isset($_POST['is_discounted']) ? '1' : '0',
        'is_deleted' => isset($_POST['is_deleted']) ? '1' : '0', // Added is_deleted handling
    ];

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        if (in_array($_FILES['image']['type'], $allowed_types)) {
            $postFields['image'] = new CURLFile(
                $_FILES['image']['tmp_name'],
                $_FILES['image']['type'],
                $_FILES['image']['name']
            );
        } else {
            $error_message = 'Invalid image format. Allowed formats: JPG, PNG, GIF';
        }
    }

    // Debug what's being sent to API
    error_log("Book update data: " . json_encode($postFields));

    // Only proceed if there are no errors
    if (empty($error_message)) {
        $ch = curl_init($update_api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Set verbose debugging
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        $update_response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        // Log verbose output
        rewind($verbose);
        $verbose_log = stream_get_contents($verbose);
        error_log("CURL Verbose: " . $verbose_log);
        
        if ($update_response === false) {
            $error_message = 'API Connection Error: ' . $curl_error;
            error_log("CURL Error updating book: " . $curl_error);
        } else {
            error_log("API Raw Response: " . $update_response);
            $update_result = json_decode($update_response, true);
            if ($http_code === 200 && isset($update_result['success']) && $update_result['success']) {
                // Success - redirect to books list
                header("Location: /admin/books");
                exit;
            } else {
                $error_message = $update_result['message'] ?? 'Failed to update book.';
                error_log("API Update Error: " . json_encode($update_result));
            }
        }
        curl_close($ch);
    }
}
?>

<div class="card" style="display: flex; flex-direction: column;">
  <div class="card-body" style="flex: 1; display: flex; flex-direction: column; overflow: auto;">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="card-title mb-0">📖 Edit Book</h4>
      <a href="/admin/books" class="btn btn-secondary btn-sm">
        <i class="ti ti-arrow-left"></i> Back to Book List
      </a>
    </div>

    <?php if ($error_message): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <?php if (!empty($book)): ?>
      <form method="POST" action="" enctype="multipart/form-data" class="mb-5">
        <input type="hidden" name="id" value="<?= htmlspecialchars($book['id']) ?>">
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label for="title" class="form-label">Title</label>
              <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($book['title']) ?>" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label for="author" class="form-label">Author</label>
              <input type="text" class="form-control" id="author" name="author" value="<?= htmlspecialchars($book['author']) ?>" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label for="price" class="form-label">Price (VND)</label>
              <input type="number" class="form-control" id="price" name="price" value="<?= htmlspecialchars($book['price']) ?>" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
                <label for="image" class="form-label">Upload Image</label>
                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                <small class="form-text text-muted">Leave empty to keep current image</small>
            </div>
            <?php if (!empty($book['image_url'])): ?>
                <div class="mt-2">
                    <img src="<?= htmlspecialchars($book['image_url']) ?>" alt="Current Image" style="max-height: 120px;">
                </div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label for="publisher" class="form-label">Publisher</label>
              <input type="text" class="form-control" id="publisher" name="publisher" value="<?= htmlspecialchars($book['publisher'] ?? '') ?>">
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label for="publication_date" class="form-label">Publication Date</label>
              <input type="date" class="form-control" id="publication_date" name="publication_date" value="<?= htmlspecialchars($book['publication_date'] ?? '') ?>">
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label for="stock_quantity" class="form-label">Stock Quantity</label>
              <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="<?= htmlspecialchars($book['stock_quantity'] ?? 0) ?>">
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label for="category_id" class="form-label">Category</label>
              <select class="form-control" id="category_id" name="category_id" required>
                <option value="">-- Select Category --</option>
                <?php foreach ($category_list as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['id']) ?>"
                        <?= ($book['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="col-md-12">
            <div class="mb-3">
              <label for="short_description" class="form-label">Short Description</label>
              <textarea class="form-control" id="short_description" name="short_description" rows="2"><?= htmlspecialchars($book['short_description'] ?? '') ?></textarea>
            </div>
          </div>
          <div class="col-md-12">
            <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($book['description'] ?? '') ?></textarea>
            </div>
          </div>
          <div class="col-md-12">
            <div class="mb-3">
              <label class="form-label">Book Flags</label>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                       <?= (isset($book['is_featured']) && ($book['is_featured'] == 1 || $book['is_featured'] === true)) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_featured">Featured</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_new" name="is_new" 
                       <?= (isset($book['is_new']) && ($book['is_new'] == 1 || $book['is_new'] === true)) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_new">New</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_best_seller" name="is_best_seller" 
                       <?= (isset($book['is_best_seller']) && ($book['is_best_seller'] == 1 || $book['is_best_seller'] === true)) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_best_seller">Best Seller</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_discounted" name="is_discounted" 
                       <?= (isset($book['is_discounted']) && ($book['is_discounted'] == 1 || $book['is_discounted'] === true)) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_discounted">Discounted</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_deleted" name="is_deleted" 
                       <?= (isset($book['is_deleted']) && ($book['is_deleted'] == 1 || $book['is_deleted'] === true)) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_deleted">Is Deleted</label>
              </div>
            </div>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">Update Book</button>
      </form>
    <?php else: ?>
      <div class="alert alert-warning">Book not found.</div>
    <?php endif; ?>
  </div>
</div>

<?php
$content = ob_get_clean();

// Check if admin-layout.php exists
$layout_path = __DIR__ . '/../../layouts/admin-layout.php';
if (!file_exists($layout_path)) {
    error_log("admin-layout.php not found at: $layout_path");
    die('Error: admin-layout.php not found.');
}
include $layout_path;
?>