<?php
session_start();
include 'includes/db_connect.php';

// SECURITY: Only allow Admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

$activePage = 'users';

// --- HANDLE ACTIONS ---

// 1. Approve User
if (isset($_GET['approve'])) {
    $id = $_GET['approve'];
    $conn->query("UPDATE users SET approved = 1 WHERE id = $id");
    header("Location: users.php");
    exit();
}

// 2. Delete User
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Prevent deleting yourself
    if ($id == $_SESSION['user_id']) {
        echo "<script>alert('You cannot delete your own account.'); window.location.href='users.php';</script>";
    } else {
        $conn->query("DELETE FROM users WHERE id = $id");
        header("Location: users.php");
        exit();
    }
}

// Fetch All Users
$result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Users - Swift Inventory</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <i class="fa-solid fa-bars" style="margin-right: 15px; cursor: pointer;"></i>
            System Users
        </div>

        <div class="content-area">
            <div class="panel">
                <div class="panel-header">
                    Manage System Users
                    <div class="search-container">
                        <input type="text" id="searchInput" class="custom-input" 
                               placeholder="Search users..." onkeyup="filterTable()" style="width: 250px;">
                    </div>
                </div>

                <table class="custom-table" id="userTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['username']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td>
                                <?php 
                                    $roleColor = '#aaa';
                                    if($row['role'] == 'Admin') $roleColor = '#DB7093'; // Pink
                                    if($row['role'] == 'Manager') $roleColor = '#87CEEB'; // Blue
                                    if($row['role'] == 'Salesman') $roleColor = '#90EE90'; // Green
                                ?>
                                <span style="color: <?php echo $roleColor; ?>; font-weight: bold;">
                                    <?php echo $row['role']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if($row['approved'] == 1): ?>
                                    <span style="color: #2ecc71; font-weight: bold;">Active</span>
                                <?php else: ?>
                                    <span style="color: #f1c40f; font-weight: bold;">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if($row['approved'] == 0): ?>
                                    <a href="users.php?approve=<?php echo $row['id']; ?>" class="action-btn" style="background-color: #2ecc71; color: black;" title="Approve">
                                        <i class="fa-solid fa-check"></i>
                                    </a>
                                <?php endif; ?>

                                <a href="user_form.php?edit=<?php echo $row['id']; ?>" class="action-btn edit-btn">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>

                                <a href="users.php?delete=<?php echo $row['id']; ?>" class="action-btn delete-btn" 
                                   onclick="return confirm('Are you sure you want to delete this user?');">
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
    var table = document.getElementById("userTable");
    var tr = table.getElementsByTagName("tr");

    for (var i = 1; i < tr.length; i++) {
        var tdUsername = tr[i].getElementsByTagName("td")[1];
        var tdEmail = tr[i].getElementsByTagName("td")[2];
        if (tdUsername || tdEmail) {
            var txtValueUser = tdUsername.textContent || tdUsername.innerText;
            var txtValueEmail = tdEmail.textContent || tdEmail.innerText;
            if (txtValueUser.toUpperCase().indexOf(filter) > -1 || txtValueEmail.toUpperCase().indexOf(filter) > -1) {
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