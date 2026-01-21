<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_GET['id'])) { header("Location: purchase_orders.php"); exit(); }
$po_id = $_GET['id'];

$order = $conn->query("SELECT po.*, s.supplier_name, u.username 
                       FROM purchase_orders po
                       LEFT JOIN suppliers s ON po.supplier_id = s.id
                       LEFT JOIN users u ON po.user_id = u.id
                       WHERE po.id = $po_id")->fetch_assoc();

$items = $conn->query("SELECT pod.*, p.product_name, p.image 
                       FROM purchase_order_details pod
                       JOIN products p ON pod.product_id = p.id
                       WHERE pod.purchase_order_id = $po_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Purchase #<?php echo $po_id; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">View Purchase Order</div>
        <div class="content-area">
            <div class="panel" style="max-width: 700px; margin: 0 auto;">
                <div class="panel-header" style="border-bottom: 1px solid #555; padding-bottom: 15px;">
                    Purchase Order #<?php echo $po_id; ?>
                    <span style="float: right; font-size: 14px; color: #aaa;"><?php echo $order['created_at']; ?></span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin: 20px 0;">
                    <div>
                        <strong style="color: #aaa;">Supplier:</strong><br>
                        <span style="font-size: 18px;"><?php echo $order['supplier_name']; ?></span>
                    </div>
                    <div style="text-align: right;">
                        <strong style="color: #aaa;">Status:</strong><br>
                        <span style="font-size: 18px; color: <?php echo ($order['payment_status']=='Received')?'#2ecc71':'#f1c40f'; ?>">
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
                            <td colspan="2" style="text-align: right; font-weight: bold; font-size: 18px; padding-top: 20px;">Total Cost:</td>
                            <td style="font-weight: bold; font-size: 18px; color: #87CEEB; padding-top: 20px;">$<?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>

                <div style="margin-top: 30px; text-align: center;">
                    <a href="purchase_orders.php" class="btn btn-ghost" style="background-color: #DB7093; color: white; width: 100px; text-decoration: none; display: inline-block;">Close</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>