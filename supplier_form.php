<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$activePage = 'supplier';
$id = "";
$name = "";
$phone = "";
$address = "";

$isView = isset($_GET['view']);
$isEdit = isset($_GET['edit']);
$pageTitle = "Add New Supplier";

// Fetch Data for Edit or View
if ($isEdit || $isView) {
    $id = $isEdit ? $_GET['edit'] : $_GET['view'];
    $pageTitle = $isEdit ? "Edit Supplier" : "Supplier Details";
    
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $name = $row['supplier_name'];
        $phone = $row['phone'];
        $address = $row['address'];
    }
}

// Handle Form Submission (Only if NOT viewing)
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$isView) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // UPDATE
        $id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE suppliers SET supplier_name=?, phone=?, address=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $phone, $address, $id);
    } else {
        // INSERT
        $stmt = $conn->prepare("INSERT INTO suppliers (supplier_name, phone, address) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $phone, $address);
    }

    if ($stmt->execute()) {
        header("Location: suppliers.php");
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
            Supplier
        </div>

        <div class="content-area">
            <div class="panel" style="max-width: 600px; margin: 0 auto;">
                <div class="panel-header">
                    <?php echo $pageTitle; ?>
                </div>

                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">

                    <div class="form-group">
                        <label class="form-label">Supplier Name</label>
                        <input type="text" name="name" class="custom-input" value="<?php echo $name; ?>" 
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
                        
                        <a href="suppliers.php" class="btn btn-ghost" style="background-color: #DB7093; color: white; width: 120px; text-decoration: none; padding-top: 12px;">
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