<?php
$page_title = "Admin Dashboard";
$layout = 'admin';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

$base_url = $_ENV['API_BASE_URL'];
$error = '';
$success = '';

// Get filter parameters
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$view = isset($_GET['view']) ? $_GET['view'] : 'monthly';

// Validate inputs
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');
if (!preg_match('/^\d{2}$/', $month) || $month < 1 || $month > 12) $month = date('m');
if (!preg_match('/^\d{4}$/', $year)) $year = date('Y');

// Fetch statistics
$api_url = match ($view) {
    'daily' => "$base_url/statistics?action=daily&date=" . urlencode($date),
    'monthly' => "$base_url/statistics?action=monthly&month=$month&year=$year",
    'yearly' => "$base_url/statistics?action=yearly&year=$year",
    default => "$base_url/statistics?action=monthly&month=$month&year=$year"
};

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . ($_SESSION['access_token'] ?? ""),
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

$stats = ['total_orders' => 0, 'total_revenue' => 0, 'details' => []];
$label = '';
if ($response && $http_code === 200) {
    $result = json_decode($response, true);
    if ($result['success'] && $result['code'] == 200) {
        $stats = $result['data'];
        $label = match ($view) {
            'daily' => "Daily Stats: " . date('F j, Y', strtotime($stats['date'])),
            'monthly' => "Monthly Stats: " . date('F Y', strtotime("{$stats['year']}-{$stats['month']}-01")),
            'yearly' => "Yearly Stats: {$stats['year']}",
            default => "Statistics"
        };
    } else {
        $error = $result['message'] ?? 'Failed to fetch statistics.';
    }
} else {
    $error = "API Error: HTTP $http_code, cURL Error: " . ($curl_error ?: "None");
}

// Format numbers
function formatCurrency($amount) {
    return number_format($amount, 0, '.', ',') . ' VND';
}
?>

<style>
/* Custom Styles for Dynamic UI */
.card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: none;
    border-radius: 15px;
    overflow: hidden;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}
.card-header {
    background: linear-gradient(135deg, #6e8efb, #a777e3);
    color: white;
    border-radius: 15px 15px 0 0;
}
.metric-card {
    background: linear-gradient(135deg, #ffffff, #f8f9fa);
    position: relative;
}
.metric-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg, #6e8efb, #a777e3);
}
.badge {
    transition: transform 0.2s ease;
}
.badge.non-zero {
    animation: pulse 1.5s infinite;
}
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}
.filter-form {
    background: #fff;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    position: sticky;
    top: 20px;
    z-index: 10;
}
.table tr {
    opacity: 0;
    animation: slideUp 0.5s ease forwards;
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.table tr:nth-child(1) { animation-delay: 0.1s; }
.table tr:nth-child(2) { animation-delay: 0.2s; }
.table tr:nth-child(3) { animation-delay: 0.3s; }
.table tr:nth-child(4) { animation-delay: 0.4s; }
.table tr:nth-child(5) { animation-delay: 0.5s; }
.loading-spinner {
    display: none;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #6e8efb;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.chart-container {
    position: relative;
    min-height: 200px;
}
.chart-error {
    display: none;
    color: #dc3545;
    text-align: center;
    margin-top: 10px;
}
</style>

<div class="container-fluid">
    <!-- Filter Controls -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card filter-form" data-aos="fade-down">
                <form id="stats-form" class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="flex-grow-1">
                        <label class="form-label fw-semibold">View</label>
                        <select name="view" class="form-select" onchange="showLoading(); this.form.submit()">
                            <option value="daily" <?= $view == 'daily' ? 'selected' : '' ?>>Daily</option>
                            <option value="monthly" <?= $view == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            <option value="yearly" <?= $view == 'yearly' ? 'selected' : '' ?>>Yearly</option>
                        </select>
                    </div>
                    <?php if ($view == 'daily'): ?>
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold">Date</label>
                            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>" onchange="showLoading(); this.form.submit()">
                        </div>
                    <?php elseif ($view == 'monthly'): ?>
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold">Month</label>
                            <select name="month" class="form-select" onchange="showLoading(); this.form.submit()">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= sprintf('%02d', $m) ?>" <?= $month == sprintf('%02d', $m) ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold">Year</label>
                            <input type="number" name="year" class="form-control" value="<?= htmlspecialchars($year) ?>" min="2000" max="2099" onchange="showLoading(); this.form.submit()">
                        </div>
                    <?php else: ?>
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold">Year</label>
                            <input type="number" name="year" class="form-control" value="<?= htmlspecialchars($year) ?>" min="2000" max="2099" onchange="showLoading(); this.form.submit()">
                        </div>
                    <?php endif; ?>
                    <div class="ms-auto">
                        <button type="button" id="refresh-btn" class="btn btn-primary btn-sm" onclick="showLoading(); document.getElementById('stats-form').submit()">
                            <i class="ti ti-refresh"></i> Refresh
                            <span class="loading-spinner ms-2"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Error/Success Messages -->
    <?php if ($error): ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show" role="alert" data-aos="fade-right">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-success alert-dismissible fade show" role="alert" data-aos="fade-right">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Order Status Overview -->
    <div class="row">
        <div class="col-lg-8 d-flex align-items-stretch">
            <div class="card w-100" data-aos="fade-up">
                <div class="card-header">
                    <h5 class="mb-0"><?= htmlspecialchars($label) ?></h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="card metric-card" data-aos="zoom-in" data-bs-toggle="tooltip" title="Total orders placed">
                                <div class="card-body text-center">
                                    <i class="ti ti-shopping-cart fs-6 text-primary mb-2"></i>
                                    <h6 class="text-muted">Total Orders</h6>
                                    <h4 class="fw-semibold count-up" data-target="<?= htmlspecialchars($stats['total_orders']) ?>">0</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card metric-card" data-aos="zoom-in" data-bs-toggle="tooltip" title="Total revenue generated">
                                <div class="card-body text-center">
                                    <i class="ti ti-currency-dollar fs-6 text-success mb-2"></i>
                                    <h6 class="text-muted">Total Revenue</h6>
                                    <h4 class="fw-semibold count-up" data-target="<?= htmlspecialchars($stats['total_revenue']) ?>">0</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card metric-card" data-aos="zoom-in" data-bs-toggle="tooltip" title="Total items ordered">
                                <div class="card-body text-center">
                                    <i class="ti ti-package fs-6 text-info mb-2"></i>
                                    <h6 class="text-muted">Total Items</h6>
                                    <h4 class="fw-semibold count-up" data-target="<?php
                                        $total_items = array_sum(array_column($stats['details'], 'total_items'));
                                        echo htmlspecialchars($total_items);
                                    ?>">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="status-chart"></canvas>
                        <div id="status-chart-error" class="chart-error">Failed to load chart</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Breakdown -->
        <div class="col-lg-4 d-flex align-items-stretch">
            <div class="card w-100" data-aos="fade-up">
                <div class="card-header">
                    <h5 class="mb-0">Status Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="status-pie"></canvas>
                        <div id="status-pie-error" class="chart-error">Failed to load pie chart</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Table -->
    <div class="row">
        <div class="col-12">
            <div class="card" data-aos="fade-up">
                <div class="card-header">
                    <h5 class="mb-0">Order Details</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-nowrap mb-0 align-middle">
                            <thead class="text-dark fs-4">
                                <tr>
                                    <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Status</h6></th>
                                    <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Orders</h6></th>
                                    <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Revenue</h6></th>
                                    <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Items</h6></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
                                foreach ($statuses as $status):
                                    $data = $stats['details'][$status] ?? ['total_orders' => 0, 'total_revenue' => 0, 'total_items' => 0];
                                ?>
                                    <tr data-status="<?= strtolower($status) ?>">
                                        <td class="border-bottom-0">
                                            <h6 class="fw-semibold mb-0">
                                                <span class="badge bg-<?= match($status) {
                                                    'Pending' => 'warning',
                                                    'Processing' => 'info',
                                                    'Shipped' => 'primary',
                                                    'Delivered' => 'success',
                                                    'Cancelled' => 'danger'
                                                } ?> rounded-3 fw-semibold <?= $data['total_orders'] > 0 ? 'non-zero' : '' ?>">
                                                    <?= htmlspecialchars($status) ?>
                                                </span>
                                            </h6>
                                        </td>
                                        <td class="border-bottom-0"><h6 class="fw-semibold mb-0"><?= htmlspecialchars($data['total_orders']) ?></h6></td>
                                        <td class="border-bottom-0"><h6 class="fw-semibold mb-0"><?= formatCurrency($data['total_revenue']) ?></h6></td>
                                        <td class="border-bottom-0"><h6 class="fw-semibold mb-0"><?= htmlspecialchars($data['total_items']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">

<script>
// Initialize AOS
AOS.init({ duration: 800, once: true });

// Loading Spinner
function showLoading() {
    const spinners = document.querySelectorAll('.loading-spinner');
    spinners.forEach(spinner => spinner.style.display = 'inline-block');
    setTimeout(() => {
        spinners.forEach(spinner => spinner.style.display = 'none');
    }, 1000);
}

// Count-Up Animation
function countUp(el) {
    const target = parseInt(el.getAttribute('data-target'));
    let count = 0;
    const increment = target / 50;
    const updateCount = () => {
        count += increment;
        if (count > target) {
            el.textContent = target.toLocaleString();
            return;
        }
        el.textContent = Math.floor(count).toLocaleString();
        requestAnimationFrame(updateCount);
    };
    updateCount();
}
document.querySelectorAll('.count-up').forEach(el => countUp(el));

// Charts
document.addEventListener('DOMContentLoaded', function() {
    // Validate data
    const pieData = [
        <?= $stats['details']['Pending']['total_orders'] ?? 0 ?>,
        <?= $stats['details']['Processing']['total_orders'] ?? 0 ?>,
        <?= $stats['details']['Shipped']['total_orders'] ?? 0 ?>,
        <?= $stats['details']['Delivered']['total_orders'] ?? 0 ?>,
        <?= $stats['details']['Cancelled']['total_orders'] ?? 0 ?>
    ];
    const hasData = pieData.some(val => val > 0);

    // Status Bar Chart
    try {
        const statusChart = new Chart(document.getElementById('status-chart'), {
            type: 'bar',
            data: {
                labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
                datasets: [
                    {
                        label: 'Orders',
                        data: pieData,
                        backgroundColor: 'rgba(255, 193, 7, 0.5)',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Revenue (VND)',
                        data: [
                            <?= ($stats['details']['Pending']['total_revenue'] ?? 0) / 1000000 ?>,
                            <?= ($stats['details']['Processing']['total_revenue'] ?? 0) / 1000000 ?>,
                            <?= ($stats['details']['Shipped']['total_revenue'] ?? 0) / 1000000 ?>,
                            <?= ($stats['details']['Delivered']['total_revenue'] ?? 0) / 1000000 ?>,
                            <?= ($stats['details']['Cancelled']['total_revenue'] ?? 0) / 1000000 ?>
                        ],
                        backgroundColor: 'rgba(40, 167, 69, 0.5)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1,
                        yAxisID: 'y-revenue'
                    }
                ]
            },
            options: {
                animation: {
                    duration: 1500,
                    easing: 'easeOutQuart'
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Orders' }
                    },
                    'y-revenue': {
                        beginAtZero: true,
                        position: 'right',
                        title: { display: true, text: 'Revenue (M VND)' },
                        grid: { drawOnChartArea: false }
                    }
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label === 'Revenue (VND)') {
                                    return `${label}: ${(context.parsed.y * 1000000).toLocaleString()} VND`;
                                }
                                return `${label}: ${context.parsed.y}`;
                            }
                        }
                    }
                }
            }
        });
    } catch (e) {
        console.error('Bar Chart Error:', e);
        document.getElementById('status-chart-error').style.display = 'block';
    }

    // Status Pie Chart
    try {
        const ctx = document.getElementById('status-pie').getContext('2d');
        if (!ctx) throw new Error('Pie chart canvas context not found');
        if (!hasData) {
            document.getElementById('status-pie-error').textContent = 'No data to display';
            document.getElementById('status-pie-error').style.display = 'block';
            return;
        }

        const statusPie = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
                datasets: [{
                    label: 'Orders',
                    data: pieData,
                    backgroundColor: [
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(0, 123, 255, 0.8)',
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgba(255, 193, 7, 1)',
                        'rgba(23, 162, 184, 1)',
                        'rgba(0, 123, 255, 1)',
                        'rgba(40, 167, 69, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1500
                },
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                return `${label}: ${context.parsed} orders`;
                            }
                        }
                    }
                },
                onClick: (event, elements) => {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const status = statusPie.data.labels[index].toLowerCase();
                        document.querySelectorAll('.table tr').forEach(row => {
                            row.style.display = row.getAttribute('data-status') === status ? '' : 'none';
                        });
                        statusPie.setActiveElements([{ datasetIndex: 0, index }]);
                        statusPie.update();
                    }
                }
            }
        });

        // Reset table filter
        document.getElementById('status-pie').addEventListener('contextmenu', (e) => {
            e.preventDefault();
            document.querySelectorAll('.table tr').forEach(row => row.style.display = '');
            statusPie.setActiveElements([]);
            statusPie.update();
        });
    } catch (e) {
        console.error('Pie Chart Error:', e);
        document.getElementById('status-pie-error').style.display = 'block';
    }

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin-layout.php';
?>