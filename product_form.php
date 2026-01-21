<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$activePage = 'product';
$isEdit = false;
$id = "";
$name = "";
$price = "";
$qty = "";
$cat_id = "";
$sup_id = "";
$image = "";

// Fetch Data if Edit Mode
if (isset($_GET['edit'])) {
    $isEdit = true;
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $name = $row['product_name'];
        $price = $row['price_per_unit'];
        $qty = $row['quantity'];
        $cat_id = $row['category_id'];
        $sup_id = $row['supplier_id'];
        $image = $row['image'];
    }
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $qty = $_POST['qty'];
    $cat_id = $_POST['category'];
    $sup_id = $_POST['supplier'];
    
    // Image Upload Logic
    $target_dir = "uploads/";
    $image_path = $image; // Default to existing image
    
    if (!empty($_FILES["product_image"]["name"])) {
        $file_name = basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . time() . "_" . $file_name; // Unique name
        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        }
    }

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // UPDATE
        $id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE products SET product_name=?, price_per_unit=?, quantity=?, category_id=?, supplier_id=?, image=? WHERE id=?");
        $stmt->bind_param("sdiiisi", $name, $price, $qty, $cat_id, $sup_id, $image_path, $id);
    } else {
        // INSERT
        $stmt = $conn->prepare("INSERT INTO products (product_name, price_per_unit, quantity, category_id, supplier_id, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdiiss", $name, $price, $qty, $cat_id, $sup_id, $image_path);
    }
    
    if ($stmt->execute()) {
        header("Location: products.php");
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Fetch Categories and Suppliers for Dropdowns
$categories = $conn->query("SELECT * FROM categories");
$suppliers = $conn->query("SELECT * FROM suppliers");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $isEdit ? "Edit" : "Add"; ?> Product - Swift Inventory</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <i class="fa-solid fa-bars" style="margin-right: 15px; cursor: pointer;"></i>
            Product
        </div>

        <div class="content-area">
            <div class="panel" style="max-width: 800px; margin: 0 auto;">
                <div class="panel-header">
                    <?php echo $isEdit ? "Edit Product" : "Add New Product"; ?>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">

                    <div class="form-group">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" class="custom-input" value="<?php echo $name; ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="custom-input" required>
                            <option value="">Select Category</option>
                            <?php while($c = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $c['id']; ?>" <?php if($c['id'] == $cat_id) echo 'selected'; ?>>
                                    <?php echo $c['category_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Supplier</label>
                        <select name="supplier" class="custom-input" required>
                            <option value="">Select Supplier</option>
                            <?php while($s = $suppliers->fetch_assoc()): ?>
                                <option value="<?php echo $s['id']; ?>" <?php if($s['id'] == $sup_id) echo 'selected'; ?>>
                                    <?php echo $s['supplier_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="qty" class="custom-input" value="<?php echo $qty; ?>" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Price Per Unit</label>
                            <input type="number" step="0.01" name="price" class="custom-input" value="<?php echo $price; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Product Image</label>
                        <input type="file" name="product_image" class="custom-input" style="padding: 10px;">
                        <?php if($image): ?>
                            <div style="margin-top: 10px;">
                                <img src="<?php echo $image; ?>" alt="Current Image" style="width: 100px; height: 100px; object-fit: cover; border-radius: 5px;">
                                <p style="font-size: 12px; color: #ccc;">Current Image</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="btn-group" style="justify-content: flex-start; gap: 15px;">
                        <button type="submit" class="btn btn-ghost" style="background-color: cadetblue; color: black; width: 150px;">
                            <?php echo $isEdit ? "Update" : "Save"; ?>
                        </button>
                        <a href="products.php" class="btn btn-ghost" style="background-color: #DB7093; color: white; width: 150px; text-decoration: none; padding-top: 12px;">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>