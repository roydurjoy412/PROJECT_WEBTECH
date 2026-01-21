<?php
session_start();
include '../includes/db_connect.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // 1. Basic Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields";
    } 
    // 2. Password Match Check
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // 3. Check if Username exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Username already exists";
        } else {
            // 4. Insert New User
            // Note: 'approved' defaults to 0 in your DB, so they need admin approval to log in.
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $password, $email, $role);
            
            if ($stmt->execute()) {
                // Success! Redirect to login or show message
                echo "<script>alert('Account created successfully! Please wait for Admin approval.'); window.location.href='login.php';</script>";
                exit();
            } else {
                $error = "Error creating account: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up - Swift Inventory</title>
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
            <h2 class="login-title">Sign Up</h2>
            
            <?php if($error): ?>
                <div style="color: #ff6b6b; text-align: center; margin-bottom: 15px; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 4px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="custom-input" placeholder="Enter Name..." value="<?php echo isset($_POST['username']) ? $_POST['username'] : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="custom-input" placeholder="Enter Email..." value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="custom-input" placeholder="Enter Password..." required>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="custom-input" placeholder="Re-enter Password..." required>
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
                    <button type="button" class="btn btn-ghost" onclick="window.location.href='login.php'" style="background-color: #DB7093; color: white;">Cancel</button>
                    <button type="submit" class="btn btn-ghost" style="background-color: cadetblue; color: black;">Sign Up</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>