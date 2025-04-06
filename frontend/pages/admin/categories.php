<?php
$page_title = "Category Management";
$layout = 'admin';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();

$base_url = $_ENV['API_BASE_URL'];

// Function to make the API call using cURL
function call_api($url, $method = 'GET', $data = null) {
    $curl = curl_init();
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $_SESSION['access_token'],
        ],
    ];

    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    } else {
        $options[CURLOPT_HTTPGET] = true;
    }

    curl_setopt_array($curl, $options);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    return json_decode($response, true);
}

// Fetch categories from API
$api_url = $base_url . "category?action=get-all-categories";
$response = call_api($api_url);

// Check if the API call was successful
if (!$response || !$response['success']) {
    $error_message = $response['message'] ?? 'Failed to fetch category data from the API.';
    $categories = [];
} else {
    $categories = $response['data']['categories'] ?? [];
}
?>

<!-- Chiếm full chiều cao trình duyệt -->
<div class="card" style="height: 100vh; display: flex; flex-direction: column;">
  <div class="card-body" style="flex: 1; display: flex; flex-direction: column; overflow: hidden;">
    
    <!-- Tiêu đề và nút thêm -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="card-title mb-0">📚 Danh sách danh mục</h4>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="ti ti-plus"></i> Thêm danh mục
      </button>
    </div>

    <!-- Display error message if API call failed -->
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Table scrollable khi nhiều danh mục -->
    <div class="table-responsive" style="flex: 1; overflow-y: auto;">
      <table class="table table-hover table-bordered align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>ID</th>
            <th>Tên danh mục</th>
            <th>Ngày tạo</th>
            <th>Ngày cập nhật</th>
            <th class="text-center">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $index => $category): ?>
              <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($category['id']); ?></td>
                <td><?php echo htmlspecialchars($category['name']); ?></td>
                <td><?php echo htmlspecialchars($category['created_at']); ?></td>
                <td><?php echo htmlspecialchars($category['updated_at']); ?></td>
                <td class="text-center">
                  <a href="#" class="btn btn-info btn-sm me-1">
                    <i class="ti ti-edit"></i> Sửa
                  </a>
                  <a href="#" class="btn btn-danger btn-sm delete-category"
                     data-category-id="<?php echo htmlspecialchars($category['id']); ?>">
                    <i class="ti ti-trash"></i> Xóa
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center">Không tìm thấy danh mục nào.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- Modal for Adding Category -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addCategoryModalLabel">Thêm danh mục mới</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addCategoryForm">
          <div class="mb-3">
            <label for="categoryName" class="form-label">Tên danh mục</label>
            <input type="text" class="form-control" id="categoryName" name="name" required>
          </div>
          <button type="submit" class="btn btn-primary">Thêm</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript for Adding and Deleting Categories -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submission to add a new category
    const addCategoryForm = document.getElementById('addCategoryForm');
    addCategoryForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        const categoryName = document.getElementById('categoryName').value.trim();
        if (!categoryName) {
            showToast('Vui lòng nhập tên danh mục!', 'danger');
            return;
        }

        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Đang thêm...';

        try {
            const response = await fetch('<?php echo $base_url; ?>category?action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer <?php echo $_SESSION['access_token']; ?>'
                },
                body: JSON.stringify({ name: categoryName }),
                mode: 'cors',
                credentials: 'include'
            });

            const result = await response.json();

            // Log the response for debugging
            console.log('API Response:', result);

            // Check both 'success' and 'code' to match the API response
            if (result.success && result.code === 200) {
                showToast('Thêm danh mục thành công!', 'success');
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addCategoryModal'));
                modal.hide();
                // Reset the form
                addCategoryForm.reset();
                // Refresh the page to update the table
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                // Handle specific error messages from the backend
                throw new Error(result.message || result.error || 'Không thể thêm danh mục');
            }
        } catch (error) {
            console.error('Error adding category:', error);
            showToast(error.message || 'Không thể thêm danh mục', 'danger');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Thêm';
        }
    });

    // Handle delete category
    const deleteButtons = document.querySelectorAll('.delete-category');
    deleteButtons.forEach(button => {
        button.addEventListener('click', async function(event) {
            event.preventDefault();
            if (!confirm('Bạn có chắc chắn muốn xóa danh mục này?')) return;

            const categoryId = this.getAttribute('data-category-id');
            this.disabled = true;
            this.textContent = 'Đang xóa...';

            try {
                const response = await fetch(`<?php echo $base_url; ?>category?action=delete-category&id=${categoryId}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer <?php echo $_SESSION['access_token']; ?>'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Xóa danh mục thành công!', 'success');
                    // Refresh the page to update the table
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(result.message || 'Không thể xóa danh mục');
                }
            } catch (error) {
                showToast(error.message || 'Không thể xóa danh mục', 'danger');
                this.disabled = false;
                this.innerHTML = '<i class="ti ti-trash"></i> Xóa';
            }
        });
    });

    // Function to show toast notifications
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.style.position = 'fixed';
        toast.style.top = '20px';
        toast.style.right = '20px';
        toast.style.zIndex = '1050';
        toast.style.minWidth = '300px';
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin-layout.php';
?>