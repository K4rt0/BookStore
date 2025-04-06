<?php
ob_start(); // Bắt đầu bộ đệm đầu ra để lưu nội dung trang
?>

<!DOCTYPE html>
<html class="no-js" lang="zxx">
<head>
    <?php include __DIR__ . '/../includes/head.php'; ?>
</head>
<body>

    <!-- Kiểm tra layout, nếu không phải layout 'auth' thì hiển thị header/footer -->
    <?php if (empty($layout) || $layout !== 'auth') : ?>
        <!-- Header -->
        <?php include __DIR__ . '/../includes/header.php'; ?>
    <?php endif; ?>

    <!-- Main Content -->
    <main>
        <?php echo $content; // Nội dung động của từng trang sẽ được chèn vào đây ?>
    </main>

    <!-- Footer -->
    <?php if (empty($layout) || $layout !== 'auth') : ?>
        <?php include __DIR__ . '/../includes/footer.php'; ?>
    <?php endif; ?>

    <!-- Scripts -->
    <?php include __DIR__ . '/../includes/scripts.php'; ?>
</body>
</html>

<?php
ob_end_flush(); // Kết thúc bộ đệm đầu ra
?>
