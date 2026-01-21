<?php
session_start();
include 'includes/db_connect.php';

// SECURITY: Admin Only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

if (!isset($_GET['edit'])) {
    header("Location: users.php");
    exit();
}

$user_id = $_GET['edit'];
$username = "";
$email = "";
$role = "";
$approved = 0;

// Fetch User Data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $username = $row['username'];
    $email = $row['email'];
    $role = $row['role'];
    $approved = $row['approved'];
} else {
    header("Location: users.php");
    exit();
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_role = $_POST['role'];
    $new_approved = isset($_POST['approved']) ? 1 : 0;

    $update = $conn->prepare("UPDATE users SET role = ?, approved = ? WHERE id = ?");
    $update->bind_param("sii", $new_role, $new_approved, $user_id);
    
    if ($update->execute()) {
        header("Location: users.php");
        exit();
    } else {
        $error = "Error updating user.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User - Swift Inventory</title>
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
            <div class="panel" style="max-width: 500px; margin: 0 auto;">
                <div class="panel-header">Edit User Details</div>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" class="custom-input" value="<?php echo $username; ?>" readonly style="background-color: #e0e0e0;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="custom-input" value="<?php echo $email; ?>" readonly style="background-color: #e0e0e0;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="custom-input">
                            <option value="Admin" <?php if($role=='Admin') echo 'selected'; ?>>Admin</option>
                            <option value="Manager" <?php if($role=='Manager') echo 'selected'; ?>>Manager</option>
                            <option value="Salesman" <?php if($role=='Salesman') echo 'selected'; ?>>Salesman</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-top: 20px;">
                        <label class="form-label" style="display: inline-block; margin-right: 10px;">Approved Account:</label>
                        <input type="checkbox" name="approved" value="1" <?php if($approved == 1) echo 'checked'; ?> 
                               style="transform: scale(1.5);">
                    </div>

                    <div class="btn-group">
                        <a href="users.php" class="btn btn-ghost" style="background-color: #DB7093; color: white;">Cancel</a>
                        <button type="submit" class="btn btn-ghost" style="background-color: cadetblue; color: black;">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>