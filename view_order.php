<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_GET['id'])) { header("Location: orders.php"); exit(); }
$order_id = $_GET['id'];

// Fetch Order Info
$order = $conn->query("SELECT o.*, c.name as customer_name, u.username 
                       FROM orders o
                       LEFT JOIN customers c ON o.customer_id = c.id
                       LEFT JOIN users u ON o.user_id = u.id
                       WHERE o.id = $order_id")->fetch_assoc();

// Fetch Products
$items = $conn->query("SELECT od.*, p.product_name, p.image 
                       FROM order_details od
                       JOIN products p ON od.product_id = p.id
                       WHERE od.order_id = $order_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Order #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">View Order</div>
        <div class="content-area">
            <div class="panel" style="max-width: 700px; margin: 0 auto;">
                <div class="panel-header" style="border-bottom: 1px solid #555; padding-bottom: 15px;">
                    Order #<?php echo $order_id; ?>
                    <span style="float: right; font-size: 14px; color: #aaa;"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin: 20px 0;">
                    <div>
                        <strong style="color: #aaa;">Customer:</strong><br>
                        <span style="font-size: 18px;"><?php echo $order['customer_name']; ?></span>
                    </div>
                    <div style="text-align: right;">
                        <strong style="color: #aaa;">Status:</strong><br>
                        <span style="font-size: 18px; color: <?php echo ($order['payment_status']=='Paid')?'#2ecc71':'#f1c40f'; ?>">
                            <?php echo $order['payment_status']; ?>
                        </span>
                    </div>
                </div>

                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td style="display: flex; align-items: center; gap: 10px;">
                                <?php if($item['image']): ?>
                                    <img src="<?php echo $item['image']; ?>" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                <?php endif; ?>
                                <?php echo $item['product_name']; ?>
                            </td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" style="text-align: right; font-weight: bold; font-size: 18px; padding-top: 20px;">Total Amount:</td>
                            <td style="font-weight: bold; font-size: 18px; color: #87CEEB; padding-top: 20px;">$<?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>

                <div style="margin-top: 30px; text-align: center; display: flex; justify-content: center; gap: 20px;">
                    <a href="orders.php" class="btn btn-ghost" style="background-color: #DB7093; color: white; width: 120px; text-decoration: none;">
                        Close
                    </a>
                    
                    <a href="generate_invoice.php?id=<?php echo $order_id; ?>" target="_blank" class="btn btn-ghost" style="background-color: #ffffff; color: #000; width: 150px; text-decoration: none;">
                        <i class="fa-solid fa-file-pdf" style="color: #e74c3c; margin-right: 8px;"></i> Invoice
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>
</body>
</html>