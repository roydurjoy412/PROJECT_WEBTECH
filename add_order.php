<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// Fetch Customers
$customers = $conn->query("SELECT * FROM customers");


$products = $conn->query("SELECT * FROM products WHERE quantity > 0");
$product_data = [];
while ($p = $products->fetch_assoc()) {
    $product_data[] = $p;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_POST['customer_id'];
    $user_id = $_SESSION['user_id'];
    $total_amount = $_POST['total_amount'];
    $product_ids = $_POST['product_id']; 
    $quantities = $_POST['quantity']; 
    $subtotals = $_POST['subtotal']; 

   
    $conn->begin_transaction();

    try {
        // 2. Insert Order Header
        $stmt = $conn->prepare("INSERT INTO orders (customer_id, user_id, total_amount, payment_status) VALUES (?, ?, ?, 'Pending')");
        $stmt->bind_param("iid", $customer_id, $user_id, $total_amount);
        $stmt->execute();
        $order_id = $conn->insert_id;

        // 3. Process Items & Lock Stock
        for ($i = 0; $i < count($product_ids); $i++) {
            $pid = $product_ids[$i];
            $qty = $quantities[$i];
            $sub = $subtotals[$i];

            
            $check = $conn->query("SELECT quantity FROM products WHERE id = $pid FOR UPDATE");
            if ($check->num_rows == 0) throw new Exception("Product ID $pid not found.");
            
            $current_stock = $check->fetch_assoc()['quantity'];
            
            if ($qty > $current_stock) {
                throw new Exception("Insufficient stock for Product ID: $pid. Available: $current_stock");
            }

            // Deduct Stock
            $conn->query("UPDATE products SET quantity = quantity - $qty WHERE id = $pid");

            // Insert Detail
            $d_stmt = $conn->prepare("INSERT INTO order_details (order_id, product_id, quantity, subtotal) VALUES (?, ?, ?, ?)");
            $d_stmt->bind_param("iiid", $order_id, $pid, $qty, $sub);
            $d_stmt->execute();
        }
        
        // 4. Commit (Save Everything)
        $conn->commit();

        // Success Alert
        $_SESSION['message_type'] = 'success';
        $_SESSION['message_text'] = 'Order created successfully!';
        header("Location: orders.php");
        exit();

    } catch (Exception $e) {
        // 5. Rollback (Undo Everything on Error)
        $conn->rollback();
        echo "<script>alert('Transaction Failed: " . $e->getMessage() . "'); window.history.back();</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Order - Swift Inventory</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Pass PHP Product Names/Prices to JS (Stock will be checked via AJAX)
        var products = <?php echo json_encode($product_data); ?>;
    </script>
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">New Order</div>

        <div class="content-area">
            <div class="panel">
                <div class="panel-header">Create New Order</div>
                
                <form method="POST" id="orderForm">
                    <div class="form-group">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="custom-input" required>
                            <option value="">Select Customer</option>
                            <?php foreach($customers as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <table class="custom-table" id="itemsTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="width: 100px;">Price</th>
                                <th style="width: 100px;">Stock</th>
                                <th style="width: 100px;">Qty</th>
                                <th style="width: 100px;">Subtotal</th>
                                <th style="width: 50px;">X</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            </tbody>
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
                        <a href="orders.php" class="btn btn-ghost" style="background-color: #DB7093; color: white; width: 150px;">Cancel</a>
                        <button type="submit" id="submitBtn" class="btn btn-ghost" style="background-color: cadetblue; color: black; width: 150px;">Submit Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function addRow() {
    var tbody = document.getElementById('itemsBody');
    var rowCount = tbody.rows.length;
    var row = tbody.insertRow(rowCount);

    
    var cell1 = row.insertCell(0);
    var selectHTML = '<select name="product_id[]" class="custom-input product-select" onchange="updateRow(this)" required>';
    selectHTML += '<option value="">Select Product</option>';
    products.forEach(function(p) {
        selectHTML += `<option value="${p.id}" data-price="${p.price_per_unit}">${p.product_name}</option>`;
    });
    selectHTML += '</select>';
    cell1.innerHTML = selectHTML;

    
    var cell2 = row.insertCell(1);
    cell2.innerHTML = '<input type="text" class="custom-input price-display" readonly style="background: #333; border: none;">';

    
    var cell3 = row.insertCell(2);
    cell3.innerHTML = '<input type="text" class="custom-input stock-display" readonly style="background: #333; border: none;">';

    
    var cell4 = row.insertCell(3);
    cell4.innerHTML = `
        <input type="number" name="quantity[]" class="custom-input qty-input" 
               min="1" value="1" 
               onchange="validateStock(this); calcTotal();" 
               onkeyup="validateStock(this); calcTotal();" 
               required>
        <div class="stock-warning" style="color: #ff6b6b; font-size: 11px; display: none; margin-top: 4px;">Out of Stock!</div>
    `;

    
    var cell5 = row.insertCell(4);
    cell5.innerHTML = '<input type="text" name="subtotal[]" class="custom-input subtotal-display" readonly style="background: #333; border: none;">';

    
    var cell6 = row.insertCell(5);
    cell6.innerHTML = '<button type="button" onclick="removeRow(this)" style="background: #ff6b6b; border: none; color: white; border-radius: 50%; width: 25px; height: 25px; cursor: pointer;">x</button>';
}


function validateStock(inputElement) {
    // Find the parent row
    var row = inputElement.closest('tr');
    var productSelect = row.querySelector('.product-select');
    var qtyInput = row.querySelector('.qty-input');
    var stockDisplay = row.querySelector('.stock-display');
    var warningText = row.querySelector('.stock-warning');
    
    var productId = productSelect.value;
    var requestedQty = parseInt(qtyInput.value) || 0;

    if (!productId) return;

    
    fetch(`api/get_stock.php?id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                var realStock = parseInt(data.stock);
                
                
                stockDisplay.value = realStock;

                
                if (requestedQty > realStock) {
                    // ERROR STATE
                    qtyInput.style.border = "2px solid #ff6b6b";
                    qtyInput.style.backgroundColor = "rgba(255, 107, 107, 0.1)";
                    qtyInput.style.color = "#ff6b6b";
                    if(warningText) warningText.style.display = 'block';
                    
                    toggleSubmit(false); // Disable Submit
                } else {
                    // NORMAL STATE
                    qtyInput.style.border = "1px solid #444";
                    qtyInput.style.backgroundColor = "#222";
                    qtyInput.style.color = "white";
                    if(warningText) warningText.style.display = 'none';
                    
                    toggleSubmit(true); // Enable Submit
                }
            }
        })
        .catch(err => console.error('Stock check failed', err));
}

function toggleSubmit(enable) {
    var btn = document.getElementById('submitBtn');
    
    
    var errors = document.querySelectorAll('.qty-input[style*="border: 2px solid rgb(255, 107, 107)"]');
    
    if (errors.length > 0) {
        btn.disabled = true;
        btn.style.opacity = "0.5";
        btn.style.cursor = "not-allowed";
        btn.innerHTML = "⚠️ Stock Error";
    } else {
        btn.disabled = false;
        btn.style.opacity = "1";
        btn.style.cursor = "pointer";
        btn.innerHTML = "Submit Order";
    }
}

function updateRow(selectObj) {
    var row = selectObj.parentNode.parentNode;
    var selectedOption = selectObj.options[selectObj.selectedIndex];
    var price = selectedOption.getAttribute('data-price');

    
    row.querySelector('.price-display').value = price;
    
    
    validateStock(selectObj);
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
    toggleSubmit(true); 
}

window.onload = addRow;
</script>

</body>
</html>