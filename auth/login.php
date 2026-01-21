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
        
        
        if (password_verify($password, $row['password'])) {
            
            if (strcasecmp($role, $row['role']) == 0) {
                if ($row['approved'] == 0) {
                    $error = "Account not approved by Admin.";
                } else {
                    
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = $row['role'];

                    if (isset($_POST['remember'])) {
                        setcookie('user_login', $row['username'], time() + (86400 * 30), "/"); 
                    }

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
    <style>
        /* CSS for Splash Screen (Ensure these are in your style.css or here) */
        #splash-screen {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: #1e1e2f; /* Dark background matching theme */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.8s ease-out, visibility 0.8s;
        }
        
        .splash-hidden {
            opacity: 0;
            visibility: hidden;
        }

        .splash-logo-img {
            width: 120px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        .splash-text {
            font-size: 24px;
            color: #fff;
            font-weight: bold;
            margin-bottom: 30px;
            letter-spacing: 1px;
        }

        .splash-loader {
            width: 200px;
            height: 4px;
            background: #333;
            border-radius: 2px;
            overflow: hidden;
            position: relative;
        }

        .splash-progress {
            width: 0%;
            height: 100%;
            background: #87CEEB; 
            animation: loadProgress 2.5s linear forwards;
        }

        @keyframes loadProgress {
            0% { width: 0%; }
            100% { width: 100%; }
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-20px);}
            60% {transform: translateY(-10px);}
        }
    </style>
</head>
<body>

<div id="splash-screen">
    <img src="../images/Logo.png" alt="Logo" class="splash-logo-img">
    <div class="splash-text">Swift Inventory</div>
    <div class="splash-loader">
        <div class="splash-progress"></div>
    </div>
</div>

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
<input type="text" name="username" class="custom-input" 
       placeholder="Enter Name..." 
       value="<?php echo isset($_COOKIE['user_login']) ? $_COOKIE['user_login'] : ''; ?>" 
       required>                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="custom-input" placeholder="Enter Password..." required>
                </div>
                
                <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
    <input type="checkbox" name="remember" id="remember" style="width: 16px; height: 16px; accent-color: #DB7093;">
    <label for="remember" style="color: #ccc; font-size: 14px; cursor: pointer;">Remember Me</label>
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

<script>
    
    window.addEventListener('load', function() {
       
        setTimeout(function() {
            var splash = document.getElementById('splash-screen');
            splash.classList.add('splash-hidden'); 
            
            
            setTimeout(() => { splash.style.display = 'none'; }, 800);
            
        }, 2500); 
    });
</script>

</body>
</html>