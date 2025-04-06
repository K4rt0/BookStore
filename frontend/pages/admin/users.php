<?php
$page_title = "Admin Dashboard";
$layout = 'admin';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();
?>


<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin-layout.php';
