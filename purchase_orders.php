<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$activePage = 'purchase';

// --- HANDLE DELETE LOGIC ---
if (isset($_GET['delete'])) {
    $po_id = $_GET['delete'];

    // 1. Check Status
    $check = $conn->query("SELECT payment_status FROM purchase_orders WHERE id = $po_id");
    if($check->num_rows > 0) {
        $status = $check->fetch_assoc()['payment_status'];

        if ($status === 'Received') {
            echo "<script>alert('Cannot delete a Received order. The stock is already finalized.'); window.location.href='purchase_orders.php';</script>";
        } else {
            // 2. DELETE RECORD ONLY
            // Since the status is 'Pending', stock was never added to the system.
            // Therefore, we do NOT subtract stock here. Just delete the order.
            
            $conn->query("DELETE FROM purchase_orders WHERE id = $po_id");
            header("Location: purchase_orders.php");
            exit();
        }
    }
}

// Fetch Purchase Orders
$sql = "SELECT po.id, s.supplier_name, u.username as accepted_by, po.total_amount, po.payment_status, po.created_at 
        FROM purchase_orders po
        LEFT JOIN suppliers s ON po.supplier_id = s.id
        LEFT JOIN users u ON po.user_id = u.id
        ORDER BY po.id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Purchase Orders - Swift Inventory</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <i class="fa-solid fa-bars" style="margin-right: 15px; cursor: pointer;"></i>
            Purchase Orders
        </div>

        <div class="content-area">
            <div class="panel">
                <div class="panel-header">
                    Manage Purchase Orders
                    
                    <div class="search-container" style="gap: 10px;">
                        <select id="filterColumn" class="custom-input" style="width: 150px; padding: 8px;">
                            <option value="1">Supplier</option>
                            <option value="0">Order ID</option>
                            <option value="4">Status</option>
                        </select>
                        <input type="text" id="searchInput" class="custom-input" 
                               placeholder="Type to search..." onkeyup="filterTable()" style="width: 200px;">
                        
                        <a href="add_purchase_order.php" class="btn btn-ghost" style="background-color: #87CEEB; color: black; padding: 10px 20px; text-decoration: none;">
                            <i class="fa-solid fa-plus"></i> New Purchase
                        </a>
                    </div>
                </div>

                <table class="custom-table" id="poTable">
                    <thead>
                        <tr>
                            <th style="width: 8%;">ID</th>
                            <th style="width: 20%;">Supplier</th>
                            <th style="width: 15%;">Total Amount</th>
                            <th style="width: 15%;">Accepted By</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 15%;">Date</th>
                            <th style="width: 25%; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['supplier_name']; ?></td>
                            <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                            <td><?php echo $row['accepted_by']; ?></td>
                            <td>
                                <span style="padding: 5px 10px; border-radius: 4px; background-color: <?php echo ($row['payment_status'] == 'Received') ? '#2ecc71' : '#f1c40f'; ?>; color: black; font-weight: bold; font-size: 12px;">
                                    <?php echo $row['payment_status']; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            
                            <td style="text-align: center; display: flex; justify-content: center; gap: 5px;">
                                <?php if ($row['payment_status'] != 'Received'): ?>
                                    <a href="purchase_receive.php?id=<?php echo $row['id']; ?>" 
                                       class="action-btn" 
                                       style="background-color: #2ecc71; color: white; width: auto; padding: 5px 10px;"
                                       onclick="return confirm('Are you sure the items have arrived? This will update your stock.');">
                                       <i class="fa-solid fa-box-open"></i> Receive
                                    </a>
                                <?php else: ?>
                                    <span style="color: #2ecc71; font-weight: bold; padding: 5px;">
                                        <i class="fa-solid fa-check"></i> Done
                                    </span>
                                <?php endif; ?>
                                <a href="view_purchase_order.php?id=<?php echo $row['id']; ?>" class="action-btn" style="background-color: #4682B4; color: white;">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                
                                <?php if ($row['payment_status'] != 'Received'): ?>
                                    <a href="edit_purchase_order.php?id=<?php echo $row['id']; ?>" class="action-btn edit-btn">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="purchase_orders.php?delete=<?php echo $row['id']; ?>" class="action-btn delete-btn" 
                                       onclick="return confirm('Are you sure? This will delete the pending order.');">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                <?php endif; ?>
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
    var table = document.getElementById("poTable");
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