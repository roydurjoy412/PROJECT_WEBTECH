<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$activePage = 'supplier';

// Handle Delete Request
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: suppliers.php");
    exit();
}

// Fetch All Suppliers
$result = $conn->query("SELECT * FROM suppliers ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Suppliers - Swift Inventory</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <i class="fa-solid fa-bars" style="margin-right: 15px; cursor: pointer;"></i>
            Supplier
        </div>

        <div class="content-area">
            <div class="panel">
                <div class="panel-header">
                    Manage Supplier
                    
                    <div class="search-container" style="gap: 10px;">
                        <select id="filterColumn" class="custom-input" style="width: 120px; padding: 8px;">
                            <option value="1">Name</option>
                            <option value="2">Phone</option>
                            <option value="3">Address</option>
                        </select>
                        <input type="text" id="searchInput" class="custom-input" 
                               placeholder="Type to search..." onkeyup="filterTable()" style="width: 200px;">
                        
                        <a href="supplier_form.php" class="btn btn-ghost" style="background-color: #87CEEB; color: black; padding: 10px 20px; text-decoration: none;">
                            Add
                        </a>
                    </div>
                </div>

                <table class="custom-table" id="supplierTable">
                    <thead>
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 25%;">Supplier Name</th>
                            <th style="width: 20%;">Phone</th>
                            <th style="width: 30%;">Address</th>
                            <th style="width: 15%; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['supplier_name']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td><?php echo $row['address']; ?></td>
                            <td style="text-align: center;">
                                <a href="supplier_form.php?view=<?php echo $row['id']; ?>" class="action-btn" style="background-color: #4682B4; color: white;">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="supplier_form.php?edit=<?php echo $row['id']; ?>" class="action-btn edit-btn">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="suppliers.php?delete=<?php echo $row['id']; ?>" class="action-btn delete-btn" 
                                   onclick="return confirm('Are you sure you want to delete this supplier?');">
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
    var table = document.getElementById("supplierTable");
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