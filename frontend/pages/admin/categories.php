<?php
session_start();

$page_title = "Category Management";
$layout = 'admin';
ob_start();

$base_url = $_ENV['API_BASE_URL'];

// Call API to fetch categories
$api_url = $base_url . "category?action=get-all-categories";
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$categories = json_decode($response, true);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Category List
                        <a href="/admin/category-create" class="btn btn-primary float-end">Add New</a>
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (isset($categories['success']) && $categories['success'] && !empty($categories['data'])): ?>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories['data'] as $category): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(substr($category['id'], 0, 8)) ?>...</td>
                                        <td><?= htmlspecialchars($category['name']) ?></td>
                                        <td><?= $category['description'] ? htmlspecialchars($category['description']) : 'None' ?></td>
                                        <td>
                                            <span class="badge bg-<?= $category['is_active'] ? 'success' : 'danger' ?>">
                                                <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($category['created_at']))) ?></td>
                                        <td class="d-flex gap-2 flex-wrap">
                                            <a href="/admin/category-edit/<?= urlencode($category['id']) ?>" class="btn btn-sm btn-primary">Edit</a>

                                            <!-- Toggle active/inactive button -->
                                            <button type="button"
                                                class="btn btn-sm <?= $category['is_active'] ? 'btn-danger' : 'btn-success' ?> toggle-category-status"
                                                data-id="<?= $category['id'] ?>"
                                                data-status="<?= $category['is_active'] ? '0' : '1' ?>">
                                                <?= $category['is_active'] ? 'Disable' : 'Enable' ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <?= htmlspecialchars($categories['message'] ?? 'No data found or failed to connect to API.') ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.toggle-category-status');

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const categoryId = this.getAttribute('data-id');
            const newStatus = this.getAttribute('data-status');

            const confirmText = newStatus === '1'
                ? 'Are you sure you want to activate this category?'
                : 'Are you sure you want to disable this category?';

            if (!confirm(confirmText)) return;

            fetch(`<?= $base_url ?>category?action=update-active&id=${categoryId}&is_active=${newStatus}`, {
                method: 'PATCH',
                headers: {
                    'Authorization': 'Bearer <?= $_SESSION['access_token'] ?>'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Category updated successfully.');
                    location.reload();
                } else {
                    alert('Failed: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                alert('Network or server error!');
                console.error(err);
            });
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin-layout.php';
?>
