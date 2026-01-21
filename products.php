<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$activePage = 'product';

// Handle Delete Request
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM products WHERE id = $id");
    header("Location: products.php");
    exit();
}

// Fetch Products with Category, Supplier, AND Image
// UPDATE 1: Added 'p.image' to the SELECT list below
$sql = "SELECT p.id, p.product_name, p.price_per_unit, p.quantity, p.image,
               c.category_name, s.supplier_name 
        FROM products p
        INNER JOIN categories c ON p.category_id = c.id
        INNER JOIN suppliers s ON p.supplier_id = s.id
        ORDER BY p.id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products - Swift Inventory</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <i class="fa-solid fa-bars" style="margin-right: 15px; cursor: pointer;"></i>
            Product
        </div>

        <div class="content-area">
            <div class="panel">
                <div class="panel-header">
                    Manage Product
                    
                    <div class="search-container" style="gap: 10px;">
                        <select id="filterColumn" class="custom-input" style="width: 150px; padding: 8px;">
                            <option value="1">Product Name</option>
                            <option value="4">Category</option>
                            <option value="5">Supplier</option>
                        </select>
                        <input type="text" id="searchInput" class="custom-input" 
                               placeholder="Type to search..." onkeyup="filterTable()" style="width: 200px;">
                        
                        <a href="product_form.php" class="btn btn-ghost" style="background-color: #87CEEB; color: black; padding: 10px 20px; text-decoration: none;">
                            Add
                        </a>
                    </div>
                </div>

                <table class="custom-table" id="productTable">
                    <thead>
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 25%;">Product Name</th>
                            <th style="width: 15%;">Price</th>
                            <th style="width: 10%;">Qty</th>
                            <th style="width: 15%;">Category</th>
                            <th style="width: 15%;">Supplier</th>
                            <th style="width: 15%; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php if(!empty($row['image'])): ?>
                                        <img src="<?php echo $row['image']; ?>" class="zoomable-img" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <div style="width:40px; height:40px; background: #333; border-radius:4px;"></div>
                                    <?php endif; ?>
                                    <span><?php echo $row['product_name']; ?></span>
                                </div>
                            </td>
                            <td>$<?php echo number_format($row['price_per_unit'], 2); ?></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td><?php echo $row['category_name']; ?></td>
                            <td><?php echo $row['supplier_name']; ?></td>
                            <td style="text-align: center;">
                                <a href="product_form.php?edit=<?php echo $row['id']; ?>" class="action-btn edit-btn">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
                                <a href="products.php?delete=<?php echo $row['id']; ?>" class="action-btn delete-btn" 
                                   onclick="return confirm('Are you sure you want to delete this product?');">
                                    <i class="fa-solid fa-trash"></i> Delete
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
    var table = document.getElementById("productTable");
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