<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($product_id > 0 && addToCart($product_id, $quantity)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Product added to cart!',
            'cart_count' => getCartTotalItems()
        ]);
        exit;
    }
}

echo json_encode([
    'status' => 'error',
    'message' => 'Failed to add product to cart.'
]);
exit;
