<?php
ob_start(); // Bắt đầu bộ đệm đầu ra để lưu nội dung trang
?>

<!doctype html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../includes/admin/head.php'; ?>
</head>

<body>
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6"
       data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">

    <!-- Sidebar Start -->
    <aside class="left-sidebar">
      <div>
        <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>
      </div>
    </aside>
    <!-- Sidebar End -->

    <!-- Main Wrapper -->
    <div class="body-wrapper">
      <!-- Header Start -->
      <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>
      <!-- Header End -->

      <!-- Content Start -->
      <div class="container-fluid h-100">
        <?= $content ?>
        <div class="py-6 px-6 text-center">
          <p class="mb-0 fs-4">Design and Developed by 
            <a href="https://adminmart.com/" target="_blank" class="pe-1 text-primary text-decoration-underline">AdminMart</a>
            Distributed by 
            <a href="https://themewagon.com" target="_blank">ThemeWagon</a>
          </p>
        </div>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../includes/admin/scripts.php'; ?>
</body>

</html>

<?php
ob_end_flush(); // Kết thúc bộ đệm đầu ra
?>
