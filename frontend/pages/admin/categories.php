<?php
session_start();

$page_title = "Category Management";
$layout = 'admin';
ob_start();

$base_url = $_ENV['API_BASE_URL'];

// Call API to fetch categories
$api_url = $base_url . "/category?action=get-all-categories";
$headers = [
    'Authorization: Bearer ' . ($_SESSION['access_token'] ?? '')
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$categories = json_decode($response, true);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Category List
                        <a href="/admin/category-create" class="btn btn-primary float-end fs-3 btn-sm">Add New</a>
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($http_code !== 200 || !isset($categories['success']) || !$categories['success'] || empty($categories['data'])): ?>
                        <div class="alert alert-warning">
                            <?= htmlspecialchars($categories['message'] ?? 'No data found or failed to connect to API.') ?>
                        </div>
                    <?php else: ?>
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
                                                data-id="<?= htmlspecialchars($category['id']) ?>"
                                                data-status="<?= $category['is_active'] ? '0' : '1' ?>">
                                                <?= $category['is_active'] ? 'Disable' : 'Enable' ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
        btn.addEventListener('click', async function () {
            const categoryId = this.getAttribute('data-id');
            const newStatus = this.getAttribute('data-status');

            // Disable the button to prevent multiple clicks
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = 'Processing...';

            const confirmText = newStatus === '1'
                ? 'Are you sure you want to activate this category?'
                : 'Are you sure you want to disable this category?';

            if (!confirm(confirmText)) {
                btn.disabled = false;
                btn.textContent = originalText;
                return;
            }

            const apiUrl = `<?= $base_url ?>/category?action=update-active&id=${encodeURIComponent(categoryId)}&is_active=${newStatus}`;
            console.log('Making PATCH request to:', apiUrl); // Debugging: Log the URL

            try {
                const response = await fetch(apiUrl, {
                    method: 'PATCH',
                    headers: {
                        'Authorization': 'Bearer <?= $_SESSION['access_token'] ?? '' ?>',
                        'Content-Type': 'application/json'
                    }
                });

                console.log('Response status:', response.status); // Debugging: Log the status

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status} - ${response.statusText}`);
                }

                const data = await response.json();
                console.log('Response data:', data); // Debugging: Log the response data

                if (data.success) {
                    alert('Category status updated successfully.');
                    location.reload();
                } else {
                    throw new Error(data.message || 'Unknown error occurred');
                }
            } catch (err) {
                console.error('Fetch error:', err); // Debugging: Log the error
                alert('Failed to update category status: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin-layout.php';
?>