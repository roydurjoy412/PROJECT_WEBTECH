<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$activePage = 'order';

// --- HANDLE DELETE LOGIC (With Stock Restoration) ---
if (isset($_GET['delete'])) {
    $order_id = $_GET['delete'];

    // 1. Check Payment Status
    $check = $conn->query("SELECT payment_status FROM orders WHERE id = $order_id");
    if($check->num_rows > 0) {
        $status = $check->fetch_assoc()['payment_status'];

        if ($status === 'Paid') {
            echo "<script>alert('Cannot delete a Paid order.'); window.location.href='orders.php';</script>";
        } else {
            // 2. Restore Stock
            $details = $conn->query("SELECT product_id, quantity FROM order_details WHERE order_id = $order_id");
            while ($item = $details->fetch_assoc()) {
                $pid = $item['product_id'];
                $qty = $item['quantity'];
                $conn->query("UPDATE products SET quantity = quantity + $qty WHERE id = $pid");
            }

            // 3. Delete Order
            $conn->query("DELETE FROM orders WHERE id = $order_id");
            header("Location: orders.php");
            exit();
        }
    }
}

// Fetch Orders
$sql = "SELECT o.id, c.name as customer_name, u.username as accepted_by, o.total_amount, o.payment_status, o.order_date 
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Orders - Swift Inventory</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <i class="fa-solid fa-bars" style="margin-right: 15px; cursor: pointer;"></i>
            Orders
        </div>

        <div class="content-area">
            <div class="panel">
                <div class="panel-header">
                    Manage Orders
                    
                    <div class="search-container" style="gap: 10px;">
                        <select id="filterColumn" class="custom-input" style="width: 150px; padding: 8px;">
                            <option value="1">Customer</option>
                            <option value="0">Order ID</option>
                            <option value="4">Status</option>
                        </select>
                        <input type="text" id="searchInput" class="custom-input" 
                               placeholder="Type to search..." onkeyup="filterTable()" style="width: 200px;">
                        
                        <a href="add_order.php" class="btn btn-ghost" style="background-color: #87CEEB; color: black; padding: 10px 20px; text-decoration: none;">
                            <i class="fa-solid fa-plus"></i> New Order
                        </a>
                    </div>
                </div>

                <table class="custom-table" id="orderTable">
                    <thead>
                        <tr>
                            <th style="width: 8%;">ID</th>
                            <th style="width: 20%;">Customer</th>
                            <th style="width: 15%;">Total Amount</th>
                            <th style="width: 15%;">Accepted By</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 15%;">Date</th>
                            <th style="width: 17%; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['customer_name']; ?></td>
                            <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                            <td><?php echo $row['accepted_by']; ?></td>
                            <td>
                                <span style="padding: 5px 10px; border-radius: 4px; background-color: <?php echo ($row['payment_status'] == 'Paid') ? '#2ecc71' : '#f1c40f'; ?>; color: black; font-weight: bold; font-size: 12px;">
                                    <?php echo $row['payment_status']; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['order_date'])); ?></td>
                            <td style="text-align: center;">
                                <a href="view_order.php?id=<?php echo $row['id']; ?>" class="action-btn" style="background-color: #4682B4; color: white;">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="edit_order.php?id=<?php echo $row['id']; ?>" class="action-btn edit-btn">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="orders.php?delete=<?php echo $row['id']; ?>" class="action-btn delete-btn" 
                                   onclick="return confirm('Are you sure? This will delete the order and restore product stock.');">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function filterTable() {
    var input = document.getElementById("searchInput");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("orderTable");
    var tr = table.getElementsByTagName("tr");
    var colIndex = document.getElementById("filterColumn").value;

    for (var i = 1; i < tr.length; i++) {
        var td = tr[i].getElementsByTagName("td")[colIndex];
        if (td) {
            var txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
</script>

</body>
</html>