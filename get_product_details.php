<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID not provided']);
    exit;
}

$product_id = (int)$_GET['product_id'];
$sql = "SELECT * FROM products WHERE id = $product_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
    echo json_encode(['success' => true, 'product' => $product]);
} else {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
}

$conn->close();
?>