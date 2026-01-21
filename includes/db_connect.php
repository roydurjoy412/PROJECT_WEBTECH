<?php
$servername = "localhost";
$username = "root";
$password = "root"; // Default MAMP password
$dbname = "swift_inventory_db"; // Must match the SQL script name
$port = 8889; // Default MAMP MySQL port

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>