<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$activePage = 'reports'; // You can add a link in sidebar if you want
$from_date = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01'); // Default: 1st of this month
$to_date = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d'); // Default: Today

// Fetch Orders in Range
$sql = "SELECT o.id, c.name, o.order_date, o.total_amount, o.payment_status 
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        WHERE DATE(o.order_date) BETWEEN '$from_date' AND '$to_date'
        ORDER BY o.order_date DESC";
$result = $conn->query($sql);

// Calculate Totals
$total_revenue = 0;
$total_orders = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Reports - Swift Inventory</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <i class="fa-solid fa-bars" style="margin-right: 15px; cursor: pointer;"></i>
            Sales Reports
        </div>

        <div class="content-area">
            
            <div class="panel">
                <form method="GET" style="display: flex; gap: 20px; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from" class="custom-input" value="<?php echo $from_date; ?>">
                    </div>
                    <div style="flex: 1;">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to" class="custom-input" value="<?php echo $to_date; ?>">
                    </div>
                    <button type="submit" class="btn btn-ghost" style="background-color: cadetblue; color: black; height: 42px;">
                        <i class="fa-solid fa-filter"></i> Generate Report
                    </button>
                </form>
            </div>

            <div class="panel">
                <div class="panel-header">Report Results</div>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $total_revenue += $row['total_amount'];
                                $total_orders++;
                            ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['order_date'])); ?></td>
                                <td><?php echo $row['name']; ?></td>
                                <td>
                                    <span style="color: <?php echo ($row['payment_status']=='Paid')?'#2ecc71':'#f1c40f'; ?>">
                                        <?php echo $row['payment_status']; ?>
                                    </span>
                                </td>
                                <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;">No records found for this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background-color: rgba(255,255,255,0.05); font-weight: bold; font-size: 16px;">
                            <td colspan="4" style="text-align: right; padding: 20px;">Total Revenue:</td>
                            <td style="padding: 20px; color: #2ecc71;">$<?php echo number_format($total_revenue, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    </div>
</div>

</body>
</html>