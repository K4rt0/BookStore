<?php
session_start();
require_once __DIR__ . '/../includes/env-loader.php';

// Xác định trạng thái
$order_status = $_SESSION['order_status'] ?? ($_GET['status'] ?? 'failure');
$is_success = $order_status === 'success';
$page_title = "Book Shop - Order " . ($is_success ? "Success" : "Failure");
unset($_SESSION['order_status']);

ob_start(); 
?>
<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    .animate-in {
        animation: fadeInUp 0.6s ease-out forwards;
    }
    .pulse-icon {
        animation: pulse 2s infinite;
    }
    .btn-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.2) !important;
    }
</style>

<div class="container py-5" style="min-height: 70vh; display: flex; align-items: center; background: linear-gradient(135deg, #f0f2f5 0%, #ffffff 100%);">
    <div class="row justify-content-center w-100">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden; background: white;">
                <div class="card-body text-center p-5 position-relative" style="background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);">
                    <!-- Animated Background Element -->
                    <div class="position-absolute" style="top: -50px; right: -50px; width: 150px; height: 150px; background: <?php echo $is_success ? 'rgba(40, 167, 69, 0.1)' : 'rgba(220, 53, 69, 0.1)'; ?>; border-radius: 50%; transform: scale(1); animation: pulse 3s infinite;"></div>

                    <?php if ($is_success): ?>
                        <!-- Success Status -->
                        <div class="mb-4 position-relative animate-in" style="animation-delay: 0.1s;">
                            <i class="fas fa-check-circle text-success pulse-icon" style="font-size: 5.5rem;"></i>
                        </div>
                        <h2 class="fw-bold text-success mb-3 animate-in" style="font-size: 2.2rem; letter-spacing: 1px; animation-delay: 0.2s;">Order Successful!</h2>
                        <p class="text-muted mb-4 animate-in" style="font-size: 1.15rem; line-height: 1.6; animation-delay: 0.3s;">Thank you for shopping with us! Your order is confirmed and will be on its way soon.</p>
                        
                        <div class="d-flex justify-content-center gap-3 animate-in" style="animation-delay: 0.4s;">
                            <a href="/shop" class="btn btn-primary px-5 py-2 btn-hover" 
                               style="border-radius: 30px; font-weight: 600; background: linear-gradient(90deg, #007bff, #00b4ff); transition: all 0.3s ease;">Continue Shopping</a>
                            <a href="/orders" class="btn btn-outline-success px-5 py-2 btn-hover" 
                               style="border-radius: 30px; font-weight: 600; border-width: 2px; transition: all 0.3s ease;">View Orders</a>
                        </div>
                    <?php else: ?>
                        <!-- Error Status -->
                        <div class="mb-4 position-relative animate-in" style="animation-delay: 0.1s;">
                            <i class="fas fa-times-circle text-danger pulse-icon" style="font-size: 5.5rem;"></i>
                        </div>
                        <h2 class="fw-bold text-danger mb-3 animate-in" style="font-size: 2.2rem; letter-spacing: 1px; animation-delay: 0.2s;">Order Failed</h2>
                        <p class="text-muted mb-4 animate-in" style="font-size: 1.15rem; line-height: 1.6; animation-delay: 0.3s;">Oops! Something went wrong while processing your order. Let’s try again or reach out for help.</p>
                        
                        <div class="d-flex justify-content-center gap-3 animate-in" style="animation-delay: 0.4s;">
                            <a href="/cart" class="btn btn-primary px-5 py-4 btn-hover" 
                               style="border-radius: 30px; font-weight: 600; background: linear-gradient(90deg, #007bff, #00b4ff); transition: all 0.3s ease;">Return to Cart</a>
                            <a href="/support" class="btn btn-outline-danger px-5 py-4 btn-hover" 
                               style="border-radius: 30px; font-weight: 600; border-width: 2px; transition: all 0.3s ease;">Contact Support</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main-layout.php'; 
?>