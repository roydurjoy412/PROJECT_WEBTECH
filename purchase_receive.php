<?php
session_start();
include 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

if (isset($_GET['id'])) {
    $po_id = intval($_GET['id']);

    // 1. Check current status
    $check = $conn->query("SELECT payment_status FROM purchase_orders WHERE id = $po_id");
    if ($check->num_rows == 0) {
        $_SESSION['message_type'] = 'error';
        $_SESSION['message_text'] = 'Order not found.';
        header("Location: purchase_orders.php");
        exit();
    }
    
    $status = $check->fetch_assoc()['payment_status'];

    if ($status == 'Received') {
        $_SESSION['message_type'] = 'warning';
        $_SESSION['message_text'] = 'Order already received.';
        header("Location: purchase_orders.php");
        exit();
    }

    // 2. Start Transaction
    $conn->begin_transaction();

    try {
        // 3. Fetch Items
        $items = $conn->query("SELECT product_id, quantity FROM purchase_order_details WHERE purchase_order_id = $po_id");

        if ($items->num_rows == 0) {
            throw new Exception("Empty Order: No products found to receive.");
        }

        // 4. Update Stock
        while ($row = $items->fetch_assoc()) {
            $pid = $row['product_id'];
            $qty = $row['quantity'];
            
            // INCREASE Stock
            $conn->query("UPDATE products SET quantity = quantity + $qty WHERE id = $pid");
        }

        // 5. Update Status
        $conn->query("UPDATE purchase_orders SET payment_status = 'Received' WHERE id = $po_id");

        $conn->commit();
        
        // Success Message
        $_SESSION['message_type'] = 'success';
        $_SESSION['message_text'] = 'Stock updated successfully!';
        header("Location: purchase_orders.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message_type'] = 'error';
        $_SESSION['message_text'] = 'Error: ' . $e->getMessage();
        header("Location: purchase_orders.php");
        exit();
    }
}
?>