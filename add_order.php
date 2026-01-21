<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// Fetch Customers and Products
$customers = $conn->query("SELECT * FROM customers");
$products = $conn->query("SELECT * FROM products WHERE quantity > 0");

// Prepare Product Data for JS
$product_data = [];
while ($p = $products->fetch_assoc()) {
    $product_data[] = $p;
}

// --- PHP FORM HANDLING ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $total_amount = $_POST['total_amount'];
    $product_ids = $_POST['product_id']; 
    $quantities = $_POST['quantity']; 
    $subtotals = $_POST['subtotal']; 
    $customer_mode = $_POST['customer_mode']; // 'existing' or 'new'
    $customer_id = 0;

    $error = "";

    // 1. Handle Customer Logic
    if ($customer_mode === 'existing') {
        $customer_id = $_POST['customer_id'];
        if(empty($customer_id)) $error = "Please select a customer.";
    } else {
        // --- NEW CUSTOMER VALIDATION (PHP SIDE) ---
        $name = trim($_POST['new_name']);
        $mobile = trim($_POST['new_mobile']);
        $email = trim($_POST['new_email']);

        // Validate Name (Text only)
        if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
            $error = "Invalid Name: Only letters and spaces allowed.";
        }
        // Validate Mobile (Numbers, +, -)
        elseif (!preg_match("/^[0-9+\-]+$/", $mobile)) {
            $error = "Invalid Mobile: Only numbers, +, and - allowed.";
        }
        // Validate Email (@ symbol)
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid Email format.";
        }

        if (empty($error)) {
            // Insert New Customer
            $stmt = $conn->prepare("INSERT INTO customers (name, mobile, email) VALUES (?, ?, ?)");
            // Note: Ensure your DB has 'mobile' and 'email' columns
            $stmt->bind_param("sss", $name, $mobile, $email); 
            if ($stmt->execute()) {
                $customer_id = $conn->insert_id;
            } else {
                $error = "Error adding customer: " . $conn->error;
            }
        }
    }

    // 2. Insert Order (Only if no errors)
    if (empty($error)) {
        $stmt = $conn->prepare("INSERT INTO orders (customer_id, user_id, total_amount, payment_status) VALUES (?, ?, ?, 'Pending')");
        $stmt->bind_param("iid", $customer_id, $user_id, $total_amount);
        
        if ($stmt->execute()) {
            $order_id = $conn->insert_id;

            // 3. Insert Details & Deduct Stock
            for ($i = 0; $i < count($product_ids); $i++) {
                $pid = $product_ids[$i];
                $qty = $quantities[$i];
                $sub = $subtotals[$i];

                $check = $conn->query("SELECT quantity FROM products WHERE id = $pid");
                $stock = $check->fetch_assoc()['quantity'];
                
                if ($qty > $stock) {
                    die("Error: Not enough stock for Product ID: $pid");
                }

                $conn->query("UPDATE products SET quantity = quantity - $qty WHERE id = $pid");

                $d_stmt = $conn->prepare("INSERT INTO order_details (order_id, product_id, quantity, subtotal) VALUES (?, ?, ?, ?)");
                $d_stmt->bind_param("iiid", $order_id, $pid, $qty, $sub);
                $d_stmt->execute();
            }
            
            header("Location: orders.php");
            exit();
        } else {
            $error = "Error creating order: " . $conn->error;
        }
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
    <script>
        var products = <?php echo json_encode($product_data); ?>;
    </script>
    <style>
        .error-msg { color: #ff6b6b; font-size: 12px; display: none; margin-top: 5px; }
        .input-error { border: 1px solid #ff6b6b !important; }
        .toggle-btn { cursor: pointer; color: #87CEEB; text-decoration: underline; font-size: 14px; margin-bottom: 10px; display: inline-block; }
    </style>
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">New Order</div>

        <div class="content-area">
            <div class="panel">
                <div class="panel-header">Create New Order</div>
                
                <?php if(isset($error) && $error != ""): ?>
                    <div style="background: #ff6b6b; color: white; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="orderForm" onsubmit="return validateForm()">
                    
                    <div class="form-group">
                        <label class="form-label">Customer Details</label>
                        
                        <input type="hidden" name="customer_mode" id="customer_mode" value="existing">
                        <span class="toggle-btn" onclick="toggleCustomerMode()">+ Register New Customer</span>

                        <div id="existing_customer_div">
                            <select name="customer_id" id="customer_id" class="custom-input">
                                <option value="">Select Customer</option>
                                <?php foreach($customers as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span id="custError" class="error-msg">Please select a customer.</span>
                        </div>

                        <div id="new_customer_div" style="display: none; border: 1px solid #555; padding: 15px; border-radius: 5px; margin-top: 5px;">
                            
                            <label style="font-size: 12px; color: #aaa;">Full Name (Text Only)</label>
                            <input type="text" name="new_name" id="new_name" class="custom-input" placeholder="e.g. John Doe">
                            <span id="nameError" class="error-msg">Name must contain text/letters only.</span>
                            
                            <label style="font-size: 12px; color: #aaa; margin-top: 10px; display:block;">Mobile (+, -, Numbers)</label>
                            <input type="text" name="new_mobile" id="new_mobile" class="custom-input" placeholder="e.g. +1-555-0199">
                            <span id="mobileError" class="error-msg">Mobile allows numbers, +, and - only.</span>

                            <label style="font-size: 12px; color: #aaa; margin-top: 10px; display:block;">Email (Must have @)</label>
                            <input type="text" name="new_email" id="new_email" class="custom-input" placeholder="e.g. john@example.com">
                            <span id="emailError" class="error-msg">Please enter a valid email with '@'.</span>
                        </div>
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
                        <a href="orders.php" class="btn btn-ghost" style="background-color: #DB7093; color: white; width: 150px;">Cancel</a>
                        <button type="submit" class="btn btn-ghost" style="background-color: cadetblue; color: black; width: 150px;">Submit Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// --- 1. FIELD VALIDATION LOGIC ---
function validateForm() {
    let isValid = true;
    const mode = document.getElementById('customer_mode').value;

    // A. Validate Existing Customer Dropdown
    if (mode === 'existing') {
        const custDrop = document.getElementById('customer_id');
        const custError = document.getElementById('custError');
        if (custDrop.value === "") {
            custError.style.display = "block";
            custDrop.classList.add('input-error');
            isValid = false;
        } else {
            custError.style.display = "none";
            custDrop.classList.remove('input-error');
        }
    } 
    // B. Validate New Customer Fields
    else {
        // Name Validation (Letters & Spaces only)
        const nameIn = document.getElementById('new_name');
        const nameErr = document.getElementById('nameError');
        const nameRegex = /^[A-Za-z\s]+$/;
        
        if (!nameRegex.test(nameIn.value) || nameIn.value.trim() === "") {
            nameErr.style.display = "block";
            nameIn.classList.add('input-error');
            isValid = false;
        } else {
            nameErr.style.display = "none";
            nameIn.classList.remove('input-error');
        }

        // Mobile Validation (Numbers, +, - only)
        const mobIn = document.getElementById('new_mobile');
        const mobErr = document.getElementById('mobileError');
        const mobRegex = /^[0-9+\-]+$/;

        if (!mobRegex.test(mobIn.value) || mobIn.value.trim() === "") {
            mobErr.style.display = "block";
            mobIn.classList.add('input-error');
            isValid = false;
        } else {
            mobErr.style.display = "none";
            mobIn.classList.remove('input-error');
        }

        // Email Validation (Must contain @)
        const emailIn = document.getElementById('new_email');
        const emailErr = document.getElementById('emailError');
        // Simple regex: chars @ chars . chars
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailRegex.test(emailIn.value)) {
            emailErr.style.display = "block";
            emailIn.classList.add('input-error');
            isValid = false;
        } else {
            emailErr.style.display = "none";
            emailIn.classList.remove('input-error');
        }
    }

    return isValid;
}

// --- 2. TOGGLE CUSTOMER MODE ---
function toggleCustomerMode() {
    const modeInput = document.getElementById('customer_mode');
    const existingDiv = document.getElementById('existing_customer_div');
    const newDiv = document.getElementById('new_customer_div');
    const toggleBtn = document.querySelector('.toggle-btn');

    if (modeInput.value === 'existing') {
        modeInput.value = 'new';
        existingDiv.style.display = 'none';
        newDiv.style.display = 'block';
        toggleBtn.textContent = '← Back to Select Existing Customer';
    } else {
        modeInput.value = 'existing';
        existingDiv.style.display = 'block';
        newDiv.style.display = 'none';
        toggleBtn.textContent = '+ Register New Customer';
    }
}

// --- 3. PRODUCT ROW LOGIC (UNCHANGED) ---
function addRow() {
    var tbody = document.getElementById('itemsBody');
    var rowCount = tbody.rows.length;
    var row = tbody.insertRow(rowCount);

    var cell1 = row.insertCell(0);
    var selectHTML = '<select name="product_id[]" class="custom-input product-select" onchange="updateRow(this)" required>';
    selectHTML += '<option value="">Select Product</option>';
    products.forEach(function(p) {
        selectHTML += `<option value="${p.id}" data-price="${p.price_per_unit}" data-stock="${p.quantity}">${p.product_name}</option>`;
    });
    selectHTML += '</select>';
    cell1.innerHTML = selectHTML;

    var cell2 = row.insertCell(1);
    cell2.innerHTML = '<input type="text" class="custom-input price-display" readonly style="background: #333; border: none;">';

    var cell3 = row.insertCell(2);
    cell3.innerHTML = '<input type="text" class="custom-input stock-display" readonly style="background: #333; border: none;">';

    var cell4 = row.insertCell(3);
    cell4.innerHTML = '<input type="number" name="quantity[]" class="custom-input qty-input" min="1" value="1" onchange="calcTotal()" onkeyup="calcTotal()" required>';

    var cell5 = row.insertCell(4);
    cell5.innerHTML = '<input type="text" name="subtotal[]" class="custom-input subtotal-display" readonly style="background: #333; border: none;">';

    var cell6 = row.insertCell(5);
    cell6.innerHTML = '<button type="button" onclick="removeRow(this)" style="background: #ff6b6b; border: none; color: white; border-radius: 50%; width: 25px; height: 25px; cursor: pointer;">x</button>';
}

function updateRow(selectObj) {
    var row = selectObj.parentNode.parentNode;
    var selectedOption = selectObj.options[selectObj.selectedIndex];
    var price = selectedOption.getAttribute('data-price');
    var stock = selectedOption.getAttribute('data-stock');

    row.querySelector('.price-display').value = price;
    row.querySelector('.stock-display').value = stock;
    row.querySelector('.qty-input').setAttribute('max', stock);
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
    var row = btn.parentNode.parentNode;
    row.parentNode.removeChild(row);
    calcTotal();
}

window.onload = function() {
    addRow();
};
</script>

</body>
</html>