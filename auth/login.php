<?php
session_start();
include '../includes/db_connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("SELECT id, username, password, role, approved FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if ($password === $row['password']) {
            if (strcasecmp($role, $row['role']) == 0) {
                if ($row['approved'] == 0) {
                    $error = "Account not approved by Admin.";
                } else {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = $row['role'];
                    header("Location: ../dashboard.php");
                    exit();
                }
            } else { $error = "Incorrect Role selected."; }
        } else { $error = "Invalid Password."; }
    } else { $error = "User not found."; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Swift Inventory</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-left">
        <img src="../images/Logo.png" alt="Swift Inventory" class="login-logo">
        <div class="brand-name">Swift Inventory</div>
    </div>

    <div class="login-right">
        <div class="login-container">
            <h2 class="login-title">Log In</h2>
            
            <?php if($error): ?>
                <div style="color: #ff6b6b; text-align: center; margin-bottom: 15px; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 4px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="custom-input" placeholder="Enter Name..." required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="custom-input" placeholder="Enter Password..." required>
                </div>

                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" class="custom-input" style="height: 42px;">
                        <option value="Admin">Admin</option>
                        <option value="Manager">Manager</option>
                        <option value="Salesman">Salesman</option>
                    </select>
                </div>

                <div class="btn-group">
<button type="button" class="btn btn-ghost" onclick="window.location.href='signup.php'">Sign Up</button>                   
 <button type="submit" class="btn btn-ghost">Log In</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>