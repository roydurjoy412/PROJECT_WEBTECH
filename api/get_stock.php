<?php
// api/get_stock.php
include '../includes/db_connect.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Fetch the specific product's quantity
    $stmt = $conn->prepare("SELECT quantity FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['status' => 'success', 'stock' => $row['quantity']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Product not found']);
    }
}
?>