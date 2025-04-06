<?php
session_start();

$page_title = "Edit Category";
$layout = 'admin';
ob_start();

$base_url = $_ENV['API_BASE_URL'];
$error = '';
$success = '';

// Get category ID from URL (?id= or /category-edit/{id})
$category_id = $_GET['id'] ?? '';
if (!$category_id) {
    die('Category ID is missing.');
}

// Call API to get category details
$api_url = $base_url . "category?action=get-category&id=" . urlencode($category_id);
$headers = [
    'Authorization: Bearer ' . ($_SESSION['access_token'] ?? '')
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
$category = $result['data'] ?? null;

if (!$category) {
    $error = 'Category not found.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $postData = [
        'id' => $category_id,
        'description' => $description,
        'is_active' => $is_active
    ];

    $update_url = $base_url . "category?action=update";

    $headers[] = 'Content-Type: application/json';

    $ch = curl_init($update_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $update_response = curl_exec($ch);
    curl_close($ch);

    $update_result = json_decode($update_response, true);

    if ($update_result && $update_result['success']) {
        header("Location: /admin/categories");
        exit;
    } else {
        $error = $update_result['message'] ?? 'An unknown error occurred while updating.';
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Edit Category</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if ($category): ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($category['name']) ?>" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($category['description']) ?></textarea>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input"
                                    <?= $category['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>

                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="/admin/categories" class="btn btn-secondary">Back</a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin-layout.php';
?>
