<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// Fetch Suppliers and Products
$suppliers = $conn->query("SELECT * FROM suppliers");
$products = $conn->query("SELECT * FROM products");

// Prepare Product Data for JS
$product_data = [];
while ($p = $products->fetch_assoc()) {
    $product_data[] = $p;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supplier_id = $_POST['supplier_id'];
    $user_id = $_SESSION['user_id'];
    $total_amount = $_POST['total_amount'];
    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];
    $subtotals = $_POST['subtotal'];

    // 1. Insert Purchase Order
    $stmt = $conn->prepare("INSERT INTO purchase_orders (supplier_id, user_id, total_amount, payment_status) VALUES (?, ?, ?, 'Pending')");
    $stmt->bind_param("iid", $supplier_id, $user_id, $total_amount);
    
    if ($stmt->execute()) {
        $po_id = $conn->insert_id;

        // 2. Insert Details & ADD Stock
        for ($i = 0; $i < count($product_ids); $i++) {
            $pid = $product_ids[$i];
            $qty = $quantities[$i];
            $sub = $subtotals[$i];

            // ADD Stock (Buying increases inventory)
            $conn->query("UPDATE products SET quantity = quantity + $qty WHERE id = $pid");

            // Insert Detail
            $d_stmt = $conn->prepare("INSERT INTO purchase_order_details (purchase_order_id, product_id, quantity, subtotal) VALUES (?, ?, ?, ?)");
            $d_stmt->bind_param("iiid", $po_id, $pid, $qty, $sub);
            $d_stmt->execute();
        }
        
        header("Location: purchase_orders.php");
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Purchase Order - Swift Inventory</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        var products = <?php echo json_encode($product_data); ?>;
    </script>
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">New Purchase Order</div>

        <div class="content-area">
            <div class="panel">
                <div class="panel-header">Create Purchase Order</div>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Supplier</label>
                        <select name="supplier_id" class="custom-input" required>
                            <option value="">Select Supplier</option>
                            <?php foreach($suppliers as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo $s['supplier_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <table class="custom-table" id="itemsTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="width: 100px;">Cost</th>
                                <th style="width: 100px;">Current Stock</th>
                                <th style="width: 100px;">Qty</th>
                                <th style="width: 100px;">Subtotal</th>
                                <th style="width: 50px;">X</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody"></tbody>
                    </table>

                    <button type="button" class="btn btn-ghost" onclick="addRow()" style="background-color: #87CEEB; color: black; margin-top: 10px; width: auto; padding: 10px;">
                        + Add Product
                    </button>

                    <div style="margin-top: 30px; text-align: right; border-top: 1px solid #555; padding-top: 20px;">
                        <span style="font-size: 20px; font-weight: bold; margin-right: 10px;">Grand Total:</span>
                        <input type="text" name="total_amount" id="grandTotal" value="0.00" readonly 
                               style="background: transparent; border: none; color: white; font-size: 24px; font-weight: bold; width: 150px; text-align: right;">
                    </div>

                    <div class="btn-group" style="justify-content: flex-end;">
                        <a href="purchase_orders.php" class="btn btn-ghost" style="background-color: #DB7093; color: white; width: 150px;">Cancel</a>
                        <button type="submit" class="btn btn-ghost" style="background-color: cadetblue; color: black; width: 150px;">Submit Purchase</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function addRow() {
    var tbody = document.getElementById('itemsBody');
    var row = tbody.insertRow();

    // Product Dropdown
    var cell1 = row.insertCell(0);
    var selectHTML = '<select name="product_id[]" class="custom-input product-select" onchange="updateRow(this)" required>';
    selectHTML += '<option value="">Select Product</option>';
    products.forEach(function(p) {
        selectHTML += `<option value="${p.id}" data-price="${p.price_per_unit}" data-stock="${p.quantity}">${p.product_name}</option>`;
    });
    selectHTML += '</select>';
    cell1.innerHTML = selectHTML;

    // Cost (Readonly)
    row.insertCell(1).innerHTML = '<input type="text" class="custom-input price-display" readonly style="background: #333; border: none;">';
    
    // Current Stock (Readonly)
    row.insertCell(2).innerHTML = '<input type="text" class="custom-input stock-display" readonly style="background: #333; border: none;">';

    // Quantity Input
    row.insertCell(3).innerHTML = '<input type="number" name="quantity[]" class="custom-input qty-input" min="1" value="1" onchange="calcTotal()" onkeyup="calcTotal()" required>';

    // Subtotal
    row.insertCell(4).innerHTML = '<input type="text" name="subtotal[]" class="custom-input subtotal-display" readonly style="background: #333; border: none;">';

    // Delete Button
    row.insertCell(5).innerHTML = '<button type="button" onclick="removeRow(this)" style="background: #ff6b6b; border: none; color: white; border-radius: 50%; width: 25px; height: 25px; cursor: pointer;">x</button>';
}

function updateRow(selectObj) {
    var row = selectObj.parentNode.parentNode;
    var selectedOption = selectObj.options[selectObj.selectedIndex];
    
    row.querySelector('.price-display').value = selectedOption.getAttribute('data-price');
    row.querySelector('.stock-display').value = selectedOption.getAttribute('data-stock');
    calcTotal();
}

function calcTotal() {
    var rows = document.getElementById('itemsBody').rows;
    var grandTotal = 0;

    for (var i = 0; i < rows.length; i++) {
        var price = parseFloat(rows[i].querySelector('.price-display').value) || 0;
        var qty = parseInt(rows[i].querySelector('.qty-input').value) || 0;
        var subtotal = price * qty;
        rows[i].querySelector('.subtotal-display').value = subtotal.toFixed(2);
        grandTotal += subtotal;
    }
    document.getElementById('grandTotal').value = grandTotal.toFixed(2);
}

function removeRow(btn) {
    btn.parentNode.parentNode.remove();
    calcTotal();
}
window.onload = addRow;
</script>

</body>
</html>