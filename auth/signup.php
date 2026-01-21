<?php
session_start();
include '../includes/db_connect.php';

$error = "";

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
        // 3. Check Duplicate
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Username already exists";
        } else {
            // 4. SECURITY: Hash Password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // 5. Insert User
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $hashed_password, $email, $role);
            
            if ($stmt->execute()) {
                echo "<script>alert('Account created! Please login.'); window.location.href='login.php';</script>";
                exit();
            } else {
                $error = "Error: " . $conn->error;
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Real-time Validation Styles */
        .validation-item {
            font-size: 12px;
            margin-bottom: 4px;
            color: #aaa; /* Default Grey */
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .validation-item i { font-size: 10px; }
        
        /* Success State */
        .valid { color: #2ecc71; } 
        
        /* Error State */
        .invalid { color: #ff6b6b; } 

        /* Disabled Button Style */
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
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

            <form method="POST" id="signupForm">
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
                    <input type="password" name="password" id="password" class="custom-input" placeholder="Enter Password..." required onkeyup="validatePassword()">
                    
                    <div id="password-feedback" style="margin-top: 10px; background: rgba(0,0,0,0.2); padding: 12px; border-radius: 6px; display: none;">
                        <div id="rule-length" class="validation-item"><i class="fa-solid fa-circle"></i> At least 8 characters</div>
                        <div id="rule-number" class="validation-item"><i class="fa-solid fa-circle"></i> At least 1 number</div>
                        <div id="rule-capital" class="validation-item"><i class="fa-solid fa-circle"></i> At least 1 capital letter</div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="custom-input" placeholder="Re-enter Password..." required onkeyup="checkMatch()">
                    <div id="match-feedback" style="font-size: 12px; margin-top: 5px; font-weight: bold; display: none;"></div>
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
                    <button type="submit" id="submitBtn" class="btn btn-ghost" style="background-color: cadetblue; color: black;" disabled>Sign Up</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function validatePassword() {
        var password = document.getElementById("password").value;
        var feedback = document.getElementById("password-feedback");
        
        // Show validation box when user starts typing
        if(password.length > 0) {
            feedback.style.display = "block";
        } else {
            feedback.style.display = "none";
        }

        // 1. Check Length
        var lengthValid = password.length >= 8;
        updateRule("rule-length", lengthValid);

        // 2. Check Number
        var numberValid = /\d/.test(password); // Regex for digit
        updateRule("rule-number", numberValid);

        // 3. Check Capital Letter
        var capitalValid = /[A-Z]/.test(password); // Regex for Uppercase
        updateRule("rule-capital", capitalValid);

        // Re-check confirm match whenever password changes
        checkMatch();
    }

    // Helper to update color/icon of rules
    function updateRule(elementId, isValid) {
        var element = document.getElementById(elementId);
        var icon = element.querySelector("i");

        if (isValid) {
            element.classList.remove("invalid");
            element.classList.add("valid");
            icon.className = "fa-solid fa-check-circle"; // Green Check
        } else {
            element.classList.remove("valid");
            element.classList.add("invalid");
            icon.className = "fa-solid fa-circle-xmark"; // Red X
        }
    }

    function checkMatch() {
        var password = document.getElementById("password").value;
        var confirm = document.getElementById("confirm_password").value;
        var matchMsg = document.getElementById("match-feedback");
        var submitBtn = document.getElementById("submitBtn");
        
        // Are rules met?
        var isStrong = password.length >= 8 && /\d/.test(password) && /[A-Z]/.test(password);

        if (confirm.length > 0) {
            matchMsg.style.display = "block";
            
            if (password === confirm) {
                matchMsg.innerHTML = '<i class="fa-solid fa-check"></i> Passwords Match';
                matchMsg.style.color = "#2ecc71";
                
                // Only enable button if BOTH strong AND matching
                if(isStrong) {
                    submitBtn.disabled = false;
                } else {
                    submitBtn.disabled = true;
                }
            } else {
                matchMsg.innerHTML = '<i class="fa-solid fa-xmark"></i> Passwords do not match';
                matchMsg.style.color = "#ff6b6b";
                submitBtn.disabled = true;
            }
        } else {
            matchMsg.style.display = "none";
            submitBtn.disabled = true;
        }
    }
</script>

</body>
</html>