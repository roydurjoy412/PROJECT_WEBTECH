<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_GET['id'])) { header("Location: purchase_orders.php"); exit(); }
$po_id = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $status = $_POST['status'];
    $conn->query("UPDATE purchase_orders SET payment_status = '$status' WHERE id = $po_id");
    header("Location: purchase_orders.php");
    exit();
}

$order = $conn->query("SELECT * FROM purchase_orders WHERE id = $po_id")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Purchase - Swift Inventory</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">Edit Purchase Order</div>
        <div class="content-area">
            <div class="panel" style="max-width: 500px; margin: 0 auto;">
                <div class="panel-header">Update Status</div>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Order ID</label>
                        <input type="text" class="custom-input" value="<?php echo $order['id']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Total Amount</label>
                        <input type="text" class="custom-input" value="$<?php echo $order['total_amount']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="custom-input">
                            <option value="Pending" <?php if($order['payment_status']=='Pending') echo 'selected'; ?>>Pending</option>
                            <option value="Received" <?php if($order['payment_status']=='Received') echo 'selected'; ?>>Received</option>
                        </select>
                    </div>

                    <div class="btn-group">
                        <a href="purchase_orders.php" class="btn btn-ghost" style="background-color: #DB7093; color: white;">Cancel</a>
                        <button type="submit" class="btn btn-ghost" style="background-color: cadetblue; color: black;">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>