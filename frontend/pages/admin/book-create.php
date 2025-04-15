<?php

$page_title = "Add New Book";
$layout = 'admin';
ob_start();

$base_url = $_ENV['API_BASE_URL'];
$error = '';
$success = '';

// Fetch categories for the dropdown
$category_url = $base_url . "/category?action=get-all-categories";
$headers = [
    'Authorization: Bearer ' . ($_SESSION['access_token'] ?? '')
];

$ch = curl_init($category_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$category_response = curl_exec($ch);
curl_close($ch);

$category_result = json_decode($category_response, true);
$categories = $category_result['success'] && !empty($category_result['data']) ? $category_result['data'] : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $publication_date = trim($_POST['publication_date'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $category_id = trim($_POST['category_id'] ?? '');
    $stock_quantity = trim($_POST['stock_quantity'] ?? '');
    $description = trim($_POST['description'] ?? ''); // New field: description
    $short_description = trim($_POST['short_description'] ?? ''); // New field: short_description
    $is_deleted = isset($_POST['is_deleted']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;
    $is_discounted = isset($_POST['is_discounted']) ? 1 : 0;

    // Handle file upload
    $image = $_FILES['image'] ?? null;
    if (!$image || $image['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Please upload a book image.';
    } elseif ($image['error'] !== UPLOAD_ERR_OK) {
        $error = 'Error uploading the image.';
    } elseif (!in_array($image['type'], ['image/jpeg', 'image/png', 'image/gif'])) {
        $error = 'Only JPEG, PNG, and GIF images are allowed.';
    }

    // Validate required fields
    if (!$error && empty($title)) $error = 'Title is required.';
    if (!$error && empty($author)) $error = 'Author is required.';
    if (!$error && empty($publisher)) $error = 'Publisher is required.';
    if (!$error && empty($publication_date)) $error = 'Publication date is required.';
    if (!$error && empty($price) || !is_numeric($price) || $price <= 0) $error = 'Price must be a positive number.';
    if (!$error && empty($category_id)) $error = 'Category is required.';
    if (!$error && empty($stock_quantity) || !is_numeric($stock_quantity) || $stock_quantity < 0) $error = 'Stock quantity must be a non-negative number.';
    if (!$error && empty($description)) $error = 'Description is required.'; // Validate description
    if (!$error && empty($short_description)) $error = 'Short description is required.'; // Validate short_description

    // If no errors, proceed with API call
    if (!$error) {
        $post_data = [
            'title' => $title,
            'author' => $author,
            'publisher' => $publisher,
            'publication_date' => $publication_date,
            'price' => $price,
            'category_id' => $category_id,
            'stock_quantity' => $stock_quantity,
            'description' => $description, 
            'short_description' => $short_description, 
            'is_deleted' => $is_deleted,
            'is_featured' => $is_featured,
            'is_new' => $is_new,
            'is_best_seller' => $is_best_seller,
            'is_discounted' => $is_discounted,
        ];

        // Add image to the form data
        $post_data['image'] = new CURLFile($image['tmp_name'], $image['type'], $image['name']);

        $create_url = $base_url . "/book?action=create";
        $headers = [
            'Authorization: Bearer ' . ($_SESSION['access_token'] ?? '')
        ];

        $ch = curl_init($create_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($http_code === 200 && $result && $result['success']) {
            header("Location: /admin/books"); // Redirect to /admin/books
            exit;
        } else {
            $error = $result['message'] ?? 'An error occurred while creating the book.';
        }
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Add New Book</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Book Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Author</label>
                            <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($_POST['author'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Publisher</label>
                            <input type="text" name="publisher" class="form-control" value="<?= htmlspecialchars($_POST['publisher'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Publication Date (YYYY-MM-DD)</label>
                            <input type="date" name="publication_date" class="form-control" value="<?= htmlspecialchars($_POST['publication_date'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-control" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['id']) ?>" <?= isset($_POST['category_id']) && $_POST['category_id'] === $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" name="stock_quantity" class="form-control" min="0" value="<?= htmlspecialchars($_POST['stock_quantity'] ?? '1') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="5" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Short Description</label>
                            <textarea name="short_description" class="form-control" rows="3" required><?= htmlspecialchars($_POST['short_description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_deleted" id="is_deleted" class="form-check-input" <?= isset($_POST['is_deleted']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_deleted">Is Deleted</label>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_featured" id="is_featured" class="form-check-input" <?= isset($_POST['is_featured']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_featured">Is Featured</label>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_new" id="is_new" class="form-check-input" <?= isset($_POST['is_new']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_new">Is New</label>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_best_seller" id="is_best_seller" class="form-check-input" <?= isset($_POST['is_best_seller']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_best_seller">Is Best Seller</label>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_discounted" id="is_discounted" class="form-check-input" <?= isset($_POST['is_discounted']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_discounted">Is Discounted</label>
                        </div>

                        <button type="submit" class="btn btn-primary">Add Book</button>
                        <a href="/admin/books" class="btn btn-secondary">Back</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin-layout.php';
?>