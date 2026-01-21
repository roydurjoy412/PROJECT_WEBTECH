<?php
session_start();
require('includes/db_connect.php');
require('includes/fpdf/fpdf.php');

// 1. Security & Validation
if (!isset($_SESSION['user_id'])) {
    die("Access Denied");
}

if (!isset($_GET['id'])) {
    die("Error: Order ID missing.");
}

$order_id = intval($_GET['id']);

// 2. Fetch Order Data (Updated to LEFT JOIN)
// Using LEFT JOIN ensures the invoice generates even if the user/customer was deleted.
$sql = "SELECT o.*, c.name as customer_name, c.address as customer_address, c.email as customer_email, u.username 
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = $order_id";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Error: Order #$order_id not found in the database.");
}

$order = $result->fetch_assoc();

// 3. Fetch Products
$sql_items = "SELECT od.*, p.product_name 
              FROM order_details od
              LEFT JOIN products p ON od.product_id = p.id
              WHERE od.order_id = $order_id";
$items = $conn->query($sql_items);

// 4. Create PDF Class
class PDF extends FPDF {
    function Header() {
        // Logo
        if(file_exists('images/Logo.png')) {
            $this->Image('images/Logo.png', 10, 6, 30);
        }
        $this->SetFont('Arial','B',20);
        $this->Cell(0,10,'INVOICE',0,1,'C');
        $this->SetFont('Arial','',10);
        $this->Cell(0,5,'Swift Inventory System',0,1,'C');
        $this->Ln(20);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

// 5. Initialize PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);

// --- ORDER INFO SECTION ---
// Handle missing customer data gracefully
$cust_name = $order['customer_name'] ? $order['customer_name'] : 'Unknown Customer (Deleted)';
$cust_email = $order['customer_email'] ? $order['customer_email'] : 'N/A';
$cust_addr = $order['customer_address'] ? $order['customer_address'] : 'N/A';

$pdf->SetFont('Arial','B',12);
$pdf->Cell(100, 7, 'Bill To:', 0, 0);
$pdf->Cell(90, 7, 'Order Info:', 0, 1);

$pdf->SetFont('Arial','',11);
$pdf->Cell(100, 6, $cust_name, 0, 0);
$pdf->Cell(90, 6, 'Order ID: #' . $order['id'], 0, 1);

$pdf->Cell(100, 6, $cust_email, 0, 0);
$pdf->Cell(90, 6, 'Date: ' . date('d M Y', strtotime($order['order_date'])), 0, 1);

$pdf->MultiCell(100, 6, $cust_addr, 0, 'L');
$pdf->Ln(10);

// --- TABLE LAYOUT ---
$col_product = 80;
$col_qty = 20;
$col_price = 40;
$col_total = 40;

// Header
$pdf->SetFillColor(56, 56, 87);
$pdf->SetTextColor(255);
$pdf->SetFont('Arial','B',11);

$pdf->Cell($col_product, 10, 'Product Description', 1, 0, 'L', true);
$pdf->Cell($col_qty, 10, 'Qty', 1, 0, 'C', true);
$pdf->Cell($col_price, 10, 'Unit Price', 1, 0, 'R', true);
$pdf->Cell($col_total, 10, 'Total', 1, 1, 'R', true);

// Body
$pdf->SetTextColor(0);
$pdf->SetFont('Arial','',11);

while($item = $items->fetch_assoc()) {
    // Prevent division by zero if quantity is 0 (rare edge case)
    $qty = $item['quantity'] > 0 ? $item['quantity'] : 1;
    $unit_price = $item['subtotal'] / $qty;
    $prod_name = $item['product_name'] ? $item['product_name'] : 'Unknown Item';
    
    $pdf->Cell($col_product, 10, substr($prod_name, 0, 35), 1); 
    $pdf->Cell($col_qty, 10, $item['quantity'], 1, 0, 'C');
    $pdf->Cell($col_price, 10, '$' . number_format($unit_price, 2), 1, 0, 'R');
    $pdf->Cell($col_total, 10, '$' . number_format($item['subtotal'], 2), 1, 1, 'R');
}

// --- TOTALS ---
$pdf->Ln(5);
$label_width = $col_product + $col_qty + $col_price;

$pdf->SetFont('Arial','B',14);
$pdf->Cell($label_width, 10, 'Grand Total:', 0, 0, 'R');
$pdf->Cell($col_total, 10, '$' . number_format($order['total_amount'], 2), 0, 1, 'R');

// --- STATUS ---
$pdf->SetFont('Arial','B',12);

if ($order['payment_status'] == 'Paid') {
    $pdf->SetTextColor(46, 204, 113); 
} else {
    $pdf->SetTextColor(241, 196, 15);
}

$pdf->Cell(180, 10, 'Status: ' . $order['payment_status'], 0, 1, 'R');

// Output PDF
$pdf->Output('I', 'Invoice_#'.$order['id'].'.pdf');
?>