<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get filter parameters
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$price_min = isset($_GET['price_min']) ? (float) $_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (float) $_GET['price_max'] : 10000000;

// Pagination parameters
$products_per_page = 10; // Load 10 more each time
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $products_per_page;

// Build query with filters
$sql = "SELECT * FROM products WHERE 1=1";

if (!empty($category_filter)) {
    $sql .= " AND category = '$category_filter'";
}

if ($price_min > 0) {
    $sql .= " AND price >= $price_min";
}

if ($price_max > 0 && $price_max < 10000) {
    $sql .= " AND price <= $price_max";
}

// Add pagination
$sql .= " LIMIT $products_per_page OFFSET $offset";

$result = $conn->query($sql);
$products = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Generate HTML for products
$html = '';
foreach ($products as $product) {
    $html .= '
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <img src="' . htmlspecialchars($product['image_url'] ?? 'assets/images/default.png') . '"
                 class="card-img-top product-image"
                 alt="' . htmlspecialchars($product['name'] ?? 'Product') . '">
            <div class="card-body">
                <h5 class="card-title">' . htmlspecialchars($product['name'] ?? 'Unnamed Product') . '</h5>
                <p class="card-text">UGX. ' . number_format((float) ($product['price'] ?? 0)) . '</p>
                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">
                    <input type="hidden" name="product_id" value="' . $product['id'] . '">
                    <input type="hidden" name="quantity" value="1">
                    <button class="btn btn-primary btn-add-to-cart"
                            data-product-id="' . $product['id'] . '">
                        Add to Cart
                    </button>
                </form>
            </div>
        </div>
    </div>';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'html' => $html,
    'page' => $current_page
]);

$conn->close();
?>