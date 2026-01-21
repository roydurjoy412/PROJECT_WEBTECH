<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$activePage = 'customer';
$id = "";
$name = "";
$email = "";
$phone = "";
$address = "";

$isView = isset($_GET['view']);
$isEdit = isset($_GET['edit']);
$pageTitle = "Add New Customer";


if ($isEdit || $isView) {
    $id = $isEdit ? $_GET['edit'] : $_GET['view'];
    $pageTitle = $isEdit ? "Edit Customer" : "Customer Details";
    
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $name = $row['name'];
        $email = $row['email'];
        $phone = $row['phone'];
        $address = $row['address'];
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && !$isView) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // UPDATE
        $id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE customers SET name=?, email=?, phone=?, address=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $email, $phone, $address, $id);
    } else {
        // INSERT
        $stmt = $conn->prepare("INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $phone, $address);
    }

    if ($stmt->execute()) {
        header("Location: customers.php");
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $pageTitle; ?> - Swift Inventory</title>
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
            <div class="panel" style="max-width: 600px; margin: 0 auto;">
                <div class="panel-header">
                    <?php echo $pageTitle; ?>
                </div>

                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">

                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="custom-input" value="<?php echo $name; ?>" 
                               <?php echo $isView ? 'readonly' : 'required'; ?>>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="custom-input" value="<?php echo $email; ?>" 
                               <?php echo $isView ? 'readonly' : 'required'; ?>>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="custom-input" value="<?php echo $phone; ?>" 
                               <?php echo $isView ? 'readonly' : 'required'; ?>>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="custom-input" rows="4" 
                                  <?php echo $isView ? 'readonly' : 'required'; ?>><?php echo $address; ?></textarea>
                    </div>

                    <div class="btn-group" style="justify-content: flex-start; gap: 15px;">
                        <?php if(!$isView): ?>
                            <button type="submit" class="btn btn-ghost" style="background-color: cadetblue; color: black; width: 120px;">
                                <?php echo $isEdit ? "Update" : "Save"; ?>
                            </button>
                        <?php endif; ?>
                        
                        <a href="customers.php" class="btn btn-ghost" style="background-color: #DB7093; color: white; width: 120px; text-decoration: none; padding-top: 12px;">
                            <?php echo $isView ? "Close" : "Cancel"; ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>