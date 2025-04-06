<?php
$page_title = "Book Management";
$layout = 'admin';

ob_start();
?>

<!-- Chiếm full chiều cao trình duyệt -->
<div class="card" style="height: 100vh; display: flex; flex-direction: column;">
  <div class="card-body" style="flex: 1; display: flex; flex-direction: column; overflow: hidden;">
    
    <!-- Tiêu đề và nút thêm -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="card-title mb-0">📚 Danh sách sách</h4>
      <a href="#" class="btn btn-primary btn-sm">
        <i class="ti ti-plus"></i> Thêm sách
      </a>
    </div>

    <!-- Table scrollable khi nhiều sách -->
    <div class="table-responsive" style="flex: 1; overflow-y: auto;">
      <table class="table table-hover table-bordered align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Tiêu đề</th>
            <th>Tác giả</th>
            <th>Giá</th>
            <th class="text-center">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <?php for ($i = 1; $i <= 10; $i++): ?>
          <tr>
            <td><?= $i ?></td>
            <td>Sách <?= $i ?></td>
            <td>Tác giả <?= chr(64 + $i % 26) ?></td>
            <td><?= rand(50, 200) ?>.000đ</td>
            <td class="text-center">
              <a href="#" class="btn btn-info btn-sm me-1">
                <i class="ti ti-edit"></i> Sửa
              </a>
              <a href="#" class="btn btn-danger btn-sm">
                <i class="ti ti-trash"></i> Xóa
              </a>
            </td>
          </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin-layout.php';
