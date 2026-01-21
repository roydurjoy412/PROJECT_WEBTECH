<?php
session_start();
include 'includes/db_connect.php';

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$activePage = 'category';
$editMode = false;
$editId = 0;
$editName = "";

// --- HANDLE FORM SUBMISSIONS ---

// 1. Add or Update Category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_category'])) {
    $name = $_POST['category_name'];
    
    if (!empty($name)) {
        if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
            // Update
            $id = $_POST['category_id'];
            $stmt = $conn->prepare("UPDATE categories SET category_name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
            $stmt->execute();
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
        }
        // Refresh to clear form
        header("Location: categories.php");
        exit();
    }
}

// 2. Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: categories.php");
    exit();
}

// 3. Handle Edit Mode (Populate Form)
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    $editMode = true;
    $result = $conn->query("SELECT * FROM categories WHERE id = $editId");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $editName = $row['category_name'];
    }
}

// Fetch All Categories for Table
$categories = $conn->query("SELECT * FROM categories ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Categories - Swift Inventory</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="app-container">
    
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <i class="fa-solid fa-bars" style="margin-right: 15px; cursor: pointer;"></i>
            Category
        </div>

        <div class="content-area">

            <div class="panel">
                <div class="panel-header">
                    <?php echo $editMode ? "Edit Category" : "Add New Category"; ?>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="category_id" value="<?php echo $editId; ?>">
                    
                    <div class="form-group">
                        <input type="text" name="category_name" class="custom-input" 
                               placeholder="Enter Category Name..." 
                               value="<?php echo $editName; ?>" required>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="save_category" class="btn btn-ghost" style="background-color: #87CEEB; color: black; width: 120px;">
                            <?php echo $editMode ? "Update" : "Add"; ?>
                        </button>

                        <?php if($editMode): ?>
                            <a href="categories.php" class="btn btn-ghost" style="background-color: #DB7093; color: white; width: 120px; text-decoration: none; display: inline-block; padding-top: 12px;">
                                Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="panel">
                <div class="panel-header">
                    Manage Category
                    
                    <div style="width: 250px;">
                        <input type="text" id="searchInput" class="custom-input" placeholder="Type to search..." onkeyup="filterTable()">
                    </div>
                </div>

                <table class="custom-table" id="categoryTable">
                    <thead>
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 70%;">Category Name</th>
                            <th style="width: 20%; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $categories->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['category_name']; ?></td>
                            <td style="text-align: center;">
                                <a href="categories.php?edit=<?php echo $row['id']; ?>" class="action-btn edit-btn">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
                                <a href="categories.php?delete=<?php echo $row['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this category?');">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if($categories->num_rows == 0): ?>
                            <tr><td colspan="3" style="text-align:center; padding: 20px;">No categories found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<script>
function filterTable() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("searchInput");
    filter = input.value.toUpperCase();
    table = document.getElementById("categoryTable");
    tr = table.getElementsByTagName("tr");

    for (i = 1; i < tr.length; i++) { // Start at 1 to skip header
        td = tr[i].getElementsByTagName("td")[1]; // Column 1 is Category Name
        if (td) {
            txtValue = td.textContent || td.innerText;
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