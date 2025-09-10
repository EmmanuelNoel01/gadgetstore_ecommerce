<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['cart_count'])) {
    updateCartCount();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid form submission. Please try again.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    if (isset($_POST['add_product'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = (float) $_POST['price'];
        $category = sanitize($_POST['category']);
        $image_url = filter_var($_POST['image_url'], FILTER_SANITIZE_URL);
        $stock = (int) $_POST['stock'];

        $sql = "INSERT INTO products (name, description, price, category, image_url, stock) 
                VALUES ('$name', '$description', $price, '$category', '$image_url', $stock)";

        if ($conn->query($sql)) {
            $_SESSION['success'] = "Product added successfully!";
        } else {
            $_SESSION['error'] = "Error adding product: " . $conn->error;
        }
    } elseif (isset($_POST['update_product'])) {
        $id = (int) $_POST['id'];
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = (float) $_POST['price'];
        $category = sanitize($_POST['category']);
        $image_url = filter_var($_POST['image_url'], FILTER_SANITIZE_URL);
        $stock = (int) $_POST['stock'];

        $sql = "UPDATE products SET 
                name='$name', description='$description', price=$price, 
                category='$category', image_url='$image_url', stock=$stock 
                WHERE id=$id";

        if ($conn->query($sql)) {
            $_SESSION['success'] = "Product updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating product: " . $conn->error;
        }
    } elseif (isset($_POST['delete_product'])) {
        $id = (int) $_POST['id'];

        $sql = "DELETE FROM products WHERE id=$id";

        if ($conn->query($sql)) {
            $_SESSION['success'] = "Product deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting product: " . $conn->error;
        }
    } elseif (isset($_POST['add_to_cart'])) {
        $product_id = (int) $_POST['product_id'];
        $quantity = 0;

        if (addToCart($product_id, $quantity)) {
            $_SESSION['success'] = "Product added to cart!";
        } else {
            $_SESSION['error'] = "Error adding product to cart.";
        }
    }

    header("Location: index.php");
    exit;
}

$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$price_min = isset($_GET['price_min']) ? (float) $_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (float) $_GET['price_max'] : 10000000;
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$products_per_page = 24;
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($current_page - 1) * $products_per_page;

$sql = "SELECT * FROM products WHERE 1=1";

if (!empty($category_filter)) {
    $sql .= " AND category = '$category_filter'";
}

if ($price_min > 0) {
    $sql .= " AND price >= $price_min";
}

if ($price_max > 0 && $price_max < 10000000) {
    $sql .= " AND price <= $price_max";
}

if (!empty($search_query)) {
    $sql .= " AND name LIKE '%$search_query%'";
}

$sql .= " ORDER BY created_at DESC, id DESC LIMIT $products_per_page OFFSET $offset";

$result = $conn->query($sql);
$products = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

$count_sql = "SELECT COUNT(*) as total FROM products WHERE 1=1";

if (!empty($category_filter)) {
    $count_sql .= " AND category = '$category_filter'";
}

if ($price_min > 0) {
    $count_sql .= " AND price >= $price_min";
}

if ($price_max > 0 && $price_max < 10000000) {
    $count_sql .= " AND price <= $price_max";
}

if (!empty($search_query)) {
    $count_sql .= " AND name LIKE '%$search_query%'";
}

$count_result = $conn->query($count_sql);
$total_products = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $products_per_page);

$categories_result = $conn->query("SELECT DISTINCT category FROM products");
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['category'];
}

$carousel_items = [];
$carousel_result = $conn->query("SELECT * FROM carousel_items ORDER BY sort_order");
if ($carousel_result && $carousel_result->num_rows > 0) {
    while ($row = $carousel_result->fetch_assoc()) {
        $carousel_items[] = $row;
    }
}

$promo_image = "";
$promo_result = $conn->query("SELECT * FROM promo_images LIMIT 1");
if ($promo_result && $promo_result->num_rows > 0) {
    $promo_image = $promo_result->fetch_assoc()['image_url'];
}

$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gadget Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        #load-more-link {
            color: #03246b;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        #load-more-link:hover {
            color: #021a4d;
            text-decoration: underline !important;
        }

        .disabled {
            opacity: 0.6;
            pointer-events: none;
        }

        :root {
            --primary: #03246b;
            --secondary: #03246b;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --light: #f8f9fc;
            --dark: #5a5c69;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fc;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.8rem;
        }

        .bg-gradient-primary {
            background: linear-gradient(87deg, var(--primary) 0, #03246b 100%) !important;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: #3a5fc8;
            border-color: #3a5fc8;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.35rem;
            font-weight: 700;
        }

        .product-card {
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .category-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--primary);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.7rem;
        }

        .product-image {
            height: 200px;
            object-fit: cover;
        }

        .admin-table {
            font-size: 0.9rem;
        }

        .admin-table th {
            font-weight: 600;
            color: var(--dark);
        }

        .badge-status {
            padding: 0.4em 0.6em;
            font-size: 0.75rem;
        }

        .filter-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .price-range {
            accent-color: var(--primary);
        }

        .quantity-control {
            width: 120px;
        }

        .product-search-input {
            width: 100px;
        }

        .wider-add-to-cart {
            width: 100%;
            padding: 10px 20px;
            font-size: 1rem;
            margin-top: 10px;
        }

        .carousel-indicators {
            position: absolute;
            bottom: 10px;
            left: 0;
            right: 0;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            list-style: none;
        }

        .carousel-indicators button {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.5);
            border: none;
            margin: 0 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .carousel-indicators .active {
            background-color: #fff;
            transform: scale(1.2);
        }

        .carousel {
            position: relative;
        }

        .product-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .btn-details {
            width: 44px;
            flex-shrink: 0;
        }

        .btn-details:hover {
            background-color: #5a6268;
        }

        .product-actions {
            display: flex;
            gap: 0.5rem;
            align-items: stretch;
            height: 44px;
        }

        .product-actions form {
            margin: 0;
            flex: 1;
            height: 100%;
        }

        .product-actions button {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-product-image {
            width: 100%;
            max-height: 300px;
            object-fit: contain;
            margin-bottom: 20px;
        }

        .btn-add-to-cart,
        .btn-details {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
        }

        .logo-img {
            max-height: 40px;
            width: auto;
            object-fit: contain;
        }
    </style>
</head>

<body>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success'];
                                            unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                        unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="bg-light border-bottom small py-1">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div class="d-flex align-items-center mb-1 mb-md-0 text-muted">
                <i class="fas fa-map-marker-alt text-primary me-2"></i> Mabirizi Complex Basement, Shop B-24, Kampala Road
            </div>
            <div class="d-flex align-items-center">
                <i class="fas fa-phone-alt text-success me-2"></i>
                <a href="tel:+256778485512" class="text-decoration-none text-dark fw-semibold">
                    +256 706 839 462
                </a>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient-primary shadow">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/images/logo.jpg" alt="Gadget Store Logo" class="logo-img me-2">
                <span>Gadget Store</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex w-100" method="GET" action="index.php">
                    <div class="input-group" style="max-width: 800px;">
                        <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                        <input class="form-control me-2 product-search-input" type="search" name="search"
                            placeholder="Search products..."
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button class="btn btn-light text-primary fw-bold" type="submit">Search</button>
                    </div>
                </form>

                <ul class="navbar-nav ms-auto d-flex align-items-center flex-nowrap">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center position-relative" href="cart.php">
                            <i class="fas fa-shopping-cart me-1"></i> Cart
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-light text-primary fw-bold"
                                id="cart-count">
                                <?php echo isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : 0; ?>
                            </span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>


    <div class="container mt-4">
        <?php
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
            echo $_SESSION['success'];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            unset($_SESSION['success']);
        }

        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
            echo $_SESSION['error'];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            unset($_SESSION['error']);
        }
        ?>

        <div class="row mb-5">
            <div class="col-12">
                <div id="mainCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-indicators">
                        <?php for ($i = 0; $i < count($carousel_items); $i++): ?>
                            <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="<?php echo $i; ?>"
                                class="<?php echo $i === 0 ? 'active' : ''; ?>"></button>
                        <?php endfor; ?>
                    </div>
                    <div class="carousel-inner rounded shadow">
                        <?php foreach ($carousel_items as $index => $item): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>"
                                    class="d-block w-100" alt="<?php echo htmlspecialchars($item['title']); ?>" style="height: 400px; object-fit: cover;">
                                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-4">
                                    <h2><?php echo htmlspecialchars($item['title']); ?></h2>
                                    <p><?php echo htmlspecialchars($item['description']); ?></p>
                                    <a href="#products" class="btn btn-primary"><?php echo htmlspecialchars($item['button_text']); ?></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel"
                        data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel"
                        data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="row mb-5" id="products">
            <div class="col-12">
                <h2 class="mb-4">All Products</h2>
            </div>

            <div class="col-md-3">
                <div class="filter-section">
                    <h5>Filters</h5>
                    <form method="GET" action="">
                        <input type="hidden" name="search"
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <div class="mb-3">
                            <label for="priceRange" class="form-label">Price Range: UGX. <span
                                    id="priceValue">10,000,000</span></label>
                            <input type="range" class="form-range price-range" min="0" max="10000000" step="50000"
                                value="10000000" id="priceRange" name="price_max">
                            <div class="d-flex justify-content-between">
                                <span>UGX. 0</span>
                                <span>UGX. 10,000,000</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Categories</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="category" value="" id="allCategories"
                                    <?php echo empty($category_filter) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="allCategories">All Categories</label>
                            </div>
                            <?php foreach ($categories as $category): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="category"
                                        value="<?php echo $category; ?>" id="<?php echo $category; ?>Check" <?php echo $category_filter === $category ? 'checked' : ''; ?>>
                                    <label class="form-check-label"
                                        for="<?php echo $category; ?>Check"><?php echo $category; ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mb-3">
                            <label for="price_min" class="form-label">Min Price</label>
                            <input type="number" class="form-control" id="price_min" name="price_min"
                                value="<?php echo $price_min; ?>" min="0" max="10000000">
                        </div>

                        <div class="mb-3">
                            <label for="price_max" class="form-label">Max Price</label>
                            <input type="number" class="form-control" id="price_max" name="price_max"
                                value="<?php echo $price_max; ?>" min="0" max="10000000">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Clear Filters</a>
                    </form>
                </div>
            </div>

            <div class="col-md-9">
                <div class="row" id="products-container">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 product-card">
                                    <?php
                                    $product_id = $product['id'];
                                    $image_result = $conn->query("SELECT * FROM product_images WHERE product_id = $product_id ORDER BY sort_order");
                                    $product_images = [];
                                    while ($image_row = $image_result->fetch_assoc()) {
                                        $product_images[] = $image_row;
                                    }
                                    ?>

                                    <?php if (!empty($product_images)): ?>
                                        <div id="productCarousel<?php echo $product_id; ?>" class="carousel slide"
                                            data-bs-ride="carousel">
                                            <div class="carousel-inner">
                                                <?php foreach ($product_images as $index => $image): ?>
                                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                                        <img src="<?php echo htmlspecialchars($image['image_url']); ?>"
                                                            class="card-img-top product-image"
                                                            alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <?php if (count($product_images) > 1): ?>
                                                <div class="carousel-indicators">
                                                    <?php foreach ($product_images as $index => $image): ?>
                                                        <button type="button" data-bs-target="#productCarousel<?php echo $product_id; ?>"
                                                            data-bs-slide-to="<?php echo $index; ?>"
                                                            class="<?php echo $index === 0 ? 'active' : ''; ?>"
                                                            aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                                                            aria-label="Slide <?php echo $index + 1; ?>"></button>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'assets/images/default.png'); ?>"
                                            class="card-img-top product-image"
                                            alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php endif; ?>

                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <p class="card-text">UGX. <?php echo number_format((float) $product['price']); ?></p>

                                        <div class="product-actions">
                                            <form method="post" action="" class="flex-fill">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <button class="btn btn-primary w-100 btn-add-to-cart"
                                                    data-product-id="<?php echo $product['id']; ?>">
                                                    <i class="fas fa-shopping-cart me-2"></i> Add to Cart
                                                </button>
                                            </form>

                                            <button class="btn btn-secondary btn-details view-details ms-2"
                                                data-product-id="<?php echo $product['id']; ?>">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                        </div>


                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No products available.</p>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > $current_page): ?>
                    <div class="text-end mt-4">
                        <a href="#" id="load-more-link" class="text-primary text-decoration-underline fs-6 fw-semibold">
                            Show More
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
    <!-- Product Details Modal -->
    <div class="modal fade" id="productDetailsModal" tabindex="-1" aria-labelledby="productDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productDetailsModalLabel">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <img id="modalProductImage" src="" class="modal-product-image img-fluid" alt="Product Image">
                        </div>
                        <div class="col-md-6">
                            <h3 id="modalProductName"></h3>
                            <h4 class="text-primary" id="modalProductPrice"></h4>
                            <p id="modalProductDescription"></p>

                            <form method="post" action="" class="mt-4">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="product_id" id="modalProductId">
                                <div class="d-flex align-items-center mb-3">
                                    <label class="me-2">Quantity:</label>
                                    <div class="quantity-control d-flex">
                                        <button type="button" class="btn btn-outline-secondary quantity-minus">-</button>
                                        <input type="number" name="quantity" class="form-control quantity-input text-center mx-1" value="1" min="1" max="10" style="width: 60px;">
                                        <button type="button" class="btn btn-outline-secondary quantity-plus">+</button>
                                    </div>
                                </div>
                                <button type="submit" name="add_to_cart" class="btn btn-primary wider-add-to-cart">
                                    <i class="fas fa-shopping-cart me-2"></i> Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-5">
        <div class="col-12">
            <div class="card bg-dark text-white">
                <img src="<?php echo !empty($promo_image) ? htmlspecialchars($promo_image) : 'https://images.unsplash.com/photo-1607083206968-13611e3d76db?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1670&q=80'; ?>"
                    class="card-img" alt="Summer Sale" style="height: 300px; object-fit: cover; opacity: 0.6;">
                <div class="card-img-overlay d-flex flex-column justify-content-center text-center">
                    <h2 class="card-title">Summer Tech Sale</h2>
                    <p class="card-text fs-5">Up to 30% off on selected electronics. Limited time offer!</p>
                    <div class="mt-3">
                        <a href="#products" class="btn btn-primary btn-lg me-2">Shop Now</a>
                        <a href="#products" class="btn btn-outline-light btn-lg">View All Deals</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <?php include 'includes/footer.php'; ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const priceRange = document.getElementById('priceRange');
            const priceValue = document.getElementById('priceValue');

            if (priceRange && priceValue) {
                priceRange.addEventListener('input', function() {
                    priceValue.textContent = this.value;
                    document.getElementById('price_max').value = this.value;
                });
            }

            const quantityControls = document.querySelectorAll('.quantity-control');
            quantityControls.forEach(control => {
                const minusBtn = control.querySelector('.quantity-minus');
                const plusBtn = control.querySelector('.quantity-plus');
                const quantityInput = control.querySelector('.quantity-input');

                minusBtn.addEventListener('click', function() {
                    let quantity = parseInt(quantityInput.value);
                    if (quantity > 1) {
                        quantityInput.value = quantity - 1;
                    }
                });

                plusBtn.addEventListener('click', function() {
                    let quantity = parseInt(quantityInput.value);
                    const max = parseInt(quantityInput.getAttribute('max')) || 100;
                    if (quantity < max) {
                        quantityInput.value = quantity + 1;
                    }
                });
            });
        });

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show`;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            notification.style.minWidth = '300px';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }

        $(document).ready(function() {
            $('.btn-add-to-cart').click(function(e) {
                e.preventDefault();
                let productId = $(this).data('product-id');

                $.post('ajax_add_to_cart.php', {
                    product_id: productId
                }, function(response) {
                    if (response.status === 'success') {
                        let toastHtml = `
                    <div class="toast align-items-center text-bg-success border-0 position-fixed top-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">${response.message}</div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>`;
                        $('body').append(toastHtml);
                        let toastEl = document.querySelector('.toast:last-child');
                        let toast = new bootstrap.Toast(toastEl, {
                            delay: 2000
                        });
                        toast.show();

                        $('#cart-count').text(response.cart_count);
                    } else {
                        alert(response.message);
                    }
                }, 'json');
            });

            $('.view-details').click(function() {
                let productId = $(this).data('product-id');

                $.ajax({
                    url: 'get_product_details.php',
                    type: 'GET',
                    data: {
                        product_id: productId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#modalProductName').text(response.product.name);
                            $('#modalProductPrice').text('UGX. ' + parseFloat(response.product.price).toLocaleString());
                            $('#modalProductDescription').text(response.product.description);
                            $('#modalProductImage').attr('src', response.product.image_url);
                            $('#modalProductId').val(response.product.id);

                            $('#productDetailsModal').modal('show');
                        } else {
                            alert('Error loading product details: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error loading product details. Please try again.');
                        console.error('AJAX Error:', status, error);
                    }
                });
            });

            let currentPage = <?php echo $current_page; ?>;
            const totalPages = <?php echo $total_pages; ?>;
            const loadingText = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';

            $(document).on('click', '#load-more-link', function(e) {
                e.preventDefault();
                const $link = $(this);
                const $productsContainer = $('#products-container');

                $link.html(loadingText).addClass('disabled');

                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('page', currentPage + 1);

                $.ajax({
                    url: 'load_more_products.php?' + urlParams.toString(),
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            $productsContainer.append(response.html);
                            currentPage++;

                            if (currentPage >= totalPages) {
                                $link.hide();
                            } else {
                                $link.html('Show More').removeClass('disabled');
                            }
                        } else {
                            alert('Error loading more products');
                            $link.html('Show More').removeClass('disabled');
                        }
                    },
                    error: function() {
                        alert('Error loading more products');
                        $link.html('Show More').removeClass('disabled');
                    }
                });
            });
        });
    </script>
</body>

</html>
<?php
$conn->close();
?>