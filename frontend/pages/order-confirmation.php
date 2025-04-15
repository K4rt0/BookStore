<?php
require_once __DIR__ . '/../includes/env-loader.php';

$page_title = "Book Shop - Order Status";

ob_start();

// Lấy tham số từ URL
$status = $_GET['status'] ?? '';
$message = $_GET['message'] ?? '';
$order_id = $_GET['order_id'] ?? '';
$api_base_url = $_ENV['API_BASE_URL'];
$api_endpoint = $api_base_url . '/api/verify-payment';
$payment_data = $_GET; // For payment gateway callbacks
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
    .animate-in { animation: fadeInUp 0.6s ease-out forwards; }
    .pulse-icon { animation: pulse 2s infinite; }
    .btn-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.2) !important;
    }
    .loading-spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #007bff;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div class="container py-5" style="min-height: 70vh; display: flex; align-items: center; background: linear-gradient(135deg, #f0f2f5 0%, #ffffff 100%);">
    <div class="row justify-content-center w-100">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden; background: white;">
                <div class="card-body text-center p-5 position-relative" style="background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);">
                    <!-- Loading State -->
                    <div id="loading-state">
                        <div class="mb-4">
                            <div class="loading-spinner"></div>
                        </div>
                        <h2 class="fw-bold text-primary mb-3" style="font-size: 2rem;">Processing Your Payment...</h2>
                        <p class="text-muted mb-4" style="font-size: 1.15rem;">Please wait while we verify your transaction.</p>
                    </div>

                    <!-- Result State (Hidden Initially) -->
                    <div id="result-state" style="display: none;">
                        <div class="position-absolute" id="bg-circle" style="top: -50px; right: -50px; width: 150px; height: 150px; border-radius: 50%; transform: scale(1); animation: pulse 3s infinite;"></div>
                        <div class="mb-4 position-relative animate-in" id="result-icon-container" style="animation-delay: 0.1s;"></div>
                        <h2 class="fw-bold mb-3 animate-in" id="result-title" style="font-size: 2.2rem; letter-spacing: 1px; animation-delay: 0.2s;"></h2>
                        <p class="text-muted mb-4 animate-in" id="result-message" style="font-size: 1.15rem; line-height: 1.6; animation-delay: 0.3s;"></p>
                        <div class="d-flex justify-content-center gap-3 animate-in" id="result-buttons" style="animation-delay: 0.4s;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const status = '<?php echo htmlspecialchars($status); ?>';
    const message = '<?php echo htmlspecialchars($message); ?>';
    const orderId = '<?php echo htmlspecialchars($order_id); ?>';
    const paymentData = <?php echo json_encode($payment_data); ?>;
    const apiEndpoint = '<?php echo $api_endpoint; ?>';

    // Hàm hiển thị kết quả
    function showResult(isSuccess, title, messageText, orderId) {
        document.getElementById('loading-state').style.display = 'none';
        document.getElementById('result-state').style.display = 'block';

        document.getElementById('bg-circle').style.background = isSuccess ? 'rgba(40, 167, 69, 0.1)' : 'rgba(220, 53, 69, 0.1)';
        document.getElementById('result-icon-container').innerHTML = `
            <i class="fas ${isSuccess ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'} pulse-icon" style="font-size: 5.5rem;"></i>
        `;
        document.getElementById('result-title').innerText = title;
        document.getElementById('result-title').classList.add(isSuccess ? 'text-success' : 'text-danger');
        document.getElementById('result-message').innerHTML = `
            ${messageText} ${orderId ? 'Order #' + orderId + ' ' : ''}${isSuccess ? 'is confirmed.' : 'could not be processed.'}
        `;
        document.getElementById('result-buttons').innerHTML = isSuccess ? `
            <a href="/category" class="btn btn-primary px-5 py-4 btn-hover" style="border-radius: 30px; font-weight: 600; background: linear-gradient(90deg, #007bff, #00b4ff); transition: all 0.3s ease;">Continue Shopping</a>
            <a href="/orders" class="btn btn-outline-success px-5 py-4 btn-hover" style="border-radius: 30px; font-weight: 600; border-width: 2px; transition: all 0.3s ease;">View Orders</a>
        ` : `
            <a href="/cart" class="btn btn-primary px-5 py-4 btn-hover" style="border-radius: 30px; font-weight: 600; background: linear-gradient(90deg, #007bff, #00b4ff); transition: all 0.3s ease;">Return to Cart</a>
            <a href="/support" class="btn btn-outline-danger px-5 py-4 btn-hover" style="border-radius: 30px; font-weight: 600; border-width: 2px; transition: all 0.3s ease;">Contact Support</a>
        `;
    }

    // Kiểm tra nếu là COD hoặc trạng thái từ checkout
    if (status) {
        const isSuccess = status.toLowerCase() === 'success';
        const title = isSuccess ? 'Order Successful!' : 'Order Failed';
        const messageText = message || (isSuccess ? 'Thank you for shopping with us!' : 'Oops! Something went wrong.');
        showResult(isSuccess, title, messageText, orderId);
    }
    // Kiểm tra nếu là callback từ cổng thanh toán
    else if (orderId) {
        fetch(apiEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                payment_data: paymentData
            })
        })
        .then(response => response.json())
        .then(data => {
            const isSuccess = data.status === 'success';
            const messageText = data.message || (isSuccess ? 'Thank you for shopping with us!' : 'Oops! Something went wrong.');
            showResult(isSuccess, isSuccess ? 'Order Successful!' : 'Order Failed', messageText, orderId);
        })
        .catch(error => {
            console.error('Error:', error);
            showResult(false, 'Error', 'Unable to verify payment. Please try again or contact support.', orderId);
        });
    }
    // Trường hợp không có status hoặc order_id
    else {
        showResult(false, 'Error', 'Invalid order information. Please try again or contact support.', '');
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main-layout.php';
?>