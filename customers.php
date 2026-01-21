<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$activePage = 'customer';


if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM customers WHERE id = $id");
    header("Location: customers.php");
    exit();
}


$result = $conn->query("SELECT * FROM customers ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Customers - Swift Inventory</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <i class="fa-solid fa-bars" style="margin-right: 15px; cursor: pointer;"></i>
            Customer
        </div>

        <div class="content-area">
            <div class="panel">
                <div class="panel-header">
                    Manage Customer
                    
                    <div class="search-container" style="gap: 10px;">
                        <select id="filterColumn" class="custom-input" style="width: 120px; padding: 8px;">
                            <option value="1">Name</option>
                            <option value="2">Email</option>
                            <option value="3">Phone</option>
                            <option value="4">Address</option>
                        </select>
                        <input type="text" id="searchInput" class="custom-input" 
                               placeholder="Type to search..." onkeyup="filterTable()" style="width: 200px;">
                        
                        <a href="customer_form.php" class="btn btn-ghost" style="background-color: #87CEEB; color: black; padding: 10px 20px; text-decoration: none;">
                            Add
                        </a>
                    </div>
                </div>

                <table class="custom-table" id="customerTable">
                    <thead>
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 20%;">Name</th>
                            <th style="width: 20%;">Email</th>
                            <th style="width: 15%;">Phone</th>
                            <th style="width: 25%;">Address</th>
                            <th style="width: 15%; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td><?php echo $row['address']; ?></td>
                            <td style="text-align: center;">
                                <a href="customer_form.php?view=<?php echo $row['id']; ?>" class="action-btn" style="background-color: #4682B4; color: white;">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="customer_form.php?edit=<?php echo $row['id']; ?>" class="action-btn edit-btn">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="customers.php?delete=<?php echo $row['id']; ?>" class="action-btn delete-btn" 
                                   onclick="return confirm('Are you sure you want to delete this customer?');">
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
    var table = document.getElementById("customerTable");
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