<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$activePage = 'dashboard';

// 1. Fetch Key Stats
$userCount = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$productCount = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
$orderCount = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$revenue = $conn->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'Paid'")->fetch_row()[0];

// 2. Chart Data: Sales Last 7 Days
$dates = [];
$totals = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $sql = "SELECT SUM(total_amount) as total FROM orders WHERE DATE(order_date) = '$date'";
    $res = $conn->query($sql)->fetch_assoc();
    $dates[] = date('M d', strtotime($date));
    $totals[] = $res['total'] ? $res['total'] : 0;
}

// 3. Chart Data: Top 5 Products
$prodNames = [];
$prodQtys = [];
$sqlTop = "SELECT p.product_name, SUM(od.quantity) as total_qty FROM order_details od JOIN products p ON od.product_id = p.id GROUP BY p.id ORDER BY total_qty DESC LIMIT 5";
$resTop = $conn->query($sqlTop);
while($row = $resTop->fetch_assoc()) {
    $prodNames[] = $row['product_name'];
    $prodQtys[] = $row['total_qty'];
}

// 4. Low Stock Alert (Less than 10 items)
$lowStock = $conn->query("SELECT product_name, quantity, image FROM products WHERE quantity < 10 LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Swift Inventory</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <i class="fa-solid fa-bars" style="margin-right: 15px; cursor: pointer;"></i>
            Dashboard Overview
        </div>

        <div class="content-area">
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #11998e, #38ef7d);"><i class="fa-solid fa-dollar-sign"></i></div>
                    <div class="stat-info"><h3>$<?php echo number_format($revenue ?? 0, 2); ?></h3><p>Revenue</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ff512f, #dd2476);"><i class="fa-solid fa-cart-shopping"></i></div>
                    <div class="stat-info"><h3><?php echo $orderCount; ?></h3><p>Orders</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #2193b0, #6dd5ed);"><i class="fa-solid fa-cubes"></i></div>
                    <div class="stat-info"><h3><?php echo $productCount; ?></h3><p>Products</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8e2de2, #4a00e0);"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-info"><h3><?php echo $userCount; ?></h3><p>Users</p></div>
                </div>
            </div>

            <div class="charts-wrapper">
                <div class="chart-container">
                    <div class="chart-header">Sales Trends (7 Days)</div>
                    <canvas id="salesChart"></canvas>
                </div>
                <div class="chart-container">
                    <div class="chart-header">Top Selling Products</div>
                    <canvas id="productsChart"></canvas>
                </div>
            </div>

            <?php if($lowStock->num_rows > 0): ?>
            <div class="panel" style="border-left: 5px solid #ff6b6b;">
                <div class="panel-header" style="color: #ff6b6b;">
                    <span><i class="fa-solid fa-triangle-exclamation"></i> Low Stock Alerts</span>
                </div>
                <table class="custom-table">
                    <thead><tr><th>Image</th><th>Product</th><th>Quantity</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php while($item = $lowStock->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if($item['image']): ?>
                                    <img src="<?php echo $item['image']; ?>" class="zoomable-img" style="width: 40px; height: 40px; object-fit: cover;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo $item['product_name']; ?></td>
                            <td style="font-weight: bold; color: #ff6b6b;"><?php echo $item['quantity']; ?></td>
                            <td><span style="background: #ff6b6b; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;">Restock</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include 'includes/alert.php'; ?>

<script>
    // Sales Chart
    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Revenue ($)',
                data: <?php echo json_encode($totals); ?>,
                borderColor: '#38ef7d',
                backgroundColor: 'rgba(56, 239, 125, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#38ef7d'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { labels: { color: 'white' } } },
            scales: {
                y: { ticks: { color: '#aaa' }, grid: { color: '#444' } },
                x: { ticks: { color: '#aaa' }, grid: { display: false } }
            }
        }
    });

    // Products Chart
    new Chart(document.getElementById('productsChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($prodNames); ?>,
            datasets: [{
                label: 'Sold',
                data: <?php echo json_encode($prodQtys); ?>,
                backgroundColor: ['#ff6b6b', '#4ecdc4', '#ffe66d', '#1a535c', '#ff9f43'],
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { ticks: { color: '#aaa' }, grid: { color: '#444' } },
                x: { ticks: { color: '#aaa' }, grid: { display: false } }
            }
        }
    });
</script>

</body>
</html>