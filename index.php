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

$products_per_page = 21;
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($current_page - 1) * $products_per_page;

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

if (!empty($search_query)) {
    $sql .= " AND name LIKE '%$search_query%'";
}

$sql .= " LIMIT $products_per_page OFFSET $offset";

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

if ($price_max > 0 && $price_max < 10000) {
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
        #load-more-btn {
            border: 2px solid #03246b;
            color: #03246b;
            font-weight: 600;
            padding: 10px 30px;
            border-radius: 25px;
            transition: all 0.3s;
            text-decoration: underline;
        }

        #load-more-btn:hover {
            background-color: #03246b;
            color: white;
            text-decoration: none;
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

    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient-primary shadow">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-laptop-code"></i> Gadget Store
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
                        <button class="btn btn-primary" type="submit">Search</button>
                    </div>
                </form>

                <ul class="navbar-nav ms-auto d-flex align-items-center flex-nowrap">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="cart.php">
                            <i class="fas fa-shopping-cart me-1"></i> Cart
                            <span class="badge bg-primary ms-1" id="cart-count">
                                <?php echo isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : 0; ?>
                            </span>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user_id']) && isAdmin()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown"
                                role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i> <?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item d-flex align-items-center" href="#" data-bs-toggle="modal"
                                        data-bs-target="#adminModal">
                                        <i class="fas fa-cog me-2"></i> Admin Dashboard
                                    </a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item d-flex align-items-center" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                                    </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center" href="admin.php">
                                <i class="fas fa-sign-in-alt me-1"></i> <span>Admin</span>
                            </a>
                        </li>
                    <?php endif; ?>
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
                        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="0"
                            class="active"></button>
                        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="1"></button>
                        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="2"></button>
                    </div>
                    <div class="carousel-inner rounded shadow">
                        <div class="carousel-item active">
                            <img src="https://images.unsplash.com/photo-1498049794561-7780e7231661?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1670&q=80"
                                class="d-block w-100" alt="Tech Sale" style="height: 400px; object-fit: cover;">
                            <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-4">
                                <h2>Gadget Sale</h2>
                                <p>Check out the latest Gadgets in stock</p>
                                <a href="#products" class="btn btn-primary">Shop Now</a>
                            </div>
                        </div>
                        <div class="carousel-item">
                            <img src="https://images.unsplash.com/photo-1516387938699-a93567ec168e?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1670&q=80"
                                class="d-block w-100" alt="New Laptops" style="height: 400px; object-fit: cover;">
                            <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-4">
                                <h2>New Laptop Collection</h2>
                                <p>Check out the latest models from top brands.</p>
                                <a href="#products" class="btn btn-primary">Explore</a>
                            </div>
                        </div>
                        <div class="carousel-item">
                            <img src="https://images.unsplash.com/photo-1550745165-9bc0b252726f?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1670&q=80"
                                class="d-block w-100" alt="Accessories" style="height: 400px; object-fit: cover;">
                            <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-4">
                                <h2>Premium Accessories</h2>
                                <p>Upgrade your setup with our premium accessories.</p>
                                <a href="#products" class="btn btn-primary">Discover</a>
                            </div>
                        </div>
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
                                    id="priceValue">5000</span></label>
                            <input type="range" class="form-range price-range" min="0" max="5000" step="100"
                                value="5000" id="priceRange" name="price_max">
                            <div class="d-flex justify-content-between">
                                <span>UGX. 0</span>
                                <span>UGX. 5000</span>
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
                                value="<?php echo $price_min; ?>" min="0" max="5000">
                        </div>

                        <div class="mb-3">
                            <label for="price_max" class="form-label">Max Price</label>
                            <input type="number" class="form-control" id="price_max" name="price_max"
                                value="<?php echo $price_max; ?>" min="0" max="5000">
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
                                <div class="card h-100">
                                    <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'assets/images/default.png'); ?>"
                                        class="card-img-top product-image"
                                        alt="<?php echo htmlspecialchars($product['name'] ?? 'Product'); ?>">

                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <?php echo htmlspecialchars($product['name'] ?? 'Unnamed Product'); ?>
                                        </h5>

                                        <p class="card-text">
                                            UGX. <?php echo number_format((float) ($product['price'] ?? 0)); ?>
                                        </p>

                                        <form method="post" action="">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button class="btn btn-primary wider-add-to-cart btn-add-to-cart"
                                                data-product-id="<?php echo $product['id']; ?>">
                                                Add to Cart
                                            </button>
                                        </form>

                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No products available.</p>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > $current_page): ?>
                    <div class="text-center mt-4">
                        <button id="load-more-btn" class="btn btn-outline-primary">
                            Show More
                        </button>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12">
            <div class="card bg-dark text-white">
                <img src="https://images.unsplash.com/photo-1607083206968-13611e3d76db?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1670&q=80"
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

    <footer class="bg-dark text-white pt-5 pb-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-lg-3 col-xl-3 mx-auto mt-3">
                    <h5 class="text-uppercase mb-4 font-weight-bold"><b>Gadget Store</b></h5>
                    <p>Your one-stop shop for all electronics and tech gadgets. We offer the best products at
                        competitive prices.</p>
                    <div class="mt-3">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook-f fa-lg"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-linkedin-in fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mt-3">
                    <h5 class="text-uppercase mb-4 font-weight-bold"><b>Products</b></h5>
                    <p><a href="#products" class="text-white text-decoration-none">Laptops</a></p>
                    <p><a href="#products" class="text-white text-decoration-none">Smartphones</a></p>
                    <p><a href="#products" class="text-white text-decoration-none">Tablets</a></p>
                    <p><a href="#products" class="text-white text-decoration-none">Accessories</a></p>
                </div>
                <div class="col-md-3 col-lg-2 col-xl-2 mx-auto mt-3">
                    <h5 class="text-uppercase mb-4 font-weight-bold"><b>Useful links</b></h5>
                    <p><a href="#" class="text-white text-decoration-none">Your Account</a></p>
                    <p><a href="#" class="text-white text-decoration-none">Become an Affiliate</a></p>
                    <p><a href="#" class="text-white text-decoration-none">Shipping Rates</a></p>
                    <p><a href="#" class="text-white text-decoration-none">Help</a></p>
                </div>
                <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mt-3">
                    <h5 class="text-uppercase mb-4 font-weight-bold"><b>Contact</b></h5>
                    <p><i class="fas fa-home me-3"></i> Kampala Road, Mabirizi Complex</p>
                    <p><i class="fas fa-envelope me-3"></i> info@GadgetStore.com</p>
                    <p><i class="fas fa-phone me-3"></i> + 1 234 567 88</p>
                    <p><i class="fas fa-print me-3"></i> + 1 234 567 89</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-7 col-lg-8">
                    <p>Copyright ©2023 All rights reserved by:
                        <a href="#" class="text-white text-decoration-none"><strong>Gadget Store</strong></a>
                    </p>
                </div>
                <div class="col-md-5 col-lg-4">
                    <div class="text-center text-md-end">
                        <ul class="list-unstyled list-inline">
                            <li class="list-inline-item">
                                <a href="#" class="btn-floating btn-sm text-white" style="font-size: 23px;"><i
                                        class="fab fa-facebook-f"></i></a>
                            </li>
                            <li class="list-inline-item">
                                <a href="#" class="btn-floating btn-sm text-white" style="font-size: 23px;"><i
                                        class="fab fa-twitter"></i></a>
                            </li>
                            <li class="list-inline-item">
                                <a href="#" class="btn-floating btn-sm text-white" style="font-size: 23px;"><i
                                        class="fab fa-google-plus-g"></i></a>
                            </li>
                            <li class="list-inline-item">
                                <a href="#" class="btn-floating btn-sm text-white" style="font-size: 23px;"><i
                                        class="fab fa-linkedin-in"></i></a>
                            </li>
                            <li class="list-inline-item">
                                <a href="#" class="btn-floating btn-sm text-white" style="font-size: 23px;"><i
                                        class="fab fa-youtube"></i></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <?php if (isset($_SESSION['user_id']) && isAdmin()): ?>
        <div class="modal fade" id="adminModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Admin Dashboard - Product Management</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="admin-container">
                            <div class="admin-content">
                                <h2 class="mb-4">Product Management</h2>

                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Add New Product</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="name" class="form-label">Product Name</label>
                                                        <input type="text" class="form-control" id="name" name="name"
                                                            required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="description" class="form-label">Description</label>
                                                        <textarea class="form-control" id="description" name="description"
                                                            rows="3" required></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="price" class="form-label">Price</label>
                                                        <input type="number" step="0.01" class="form-control" id="price"
                                                            name="price" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="category" class="form-label">Category</label>
                                                        <select class="form-control" id="category" name="category" required>
                                                            <option value="">Select Category</option>
                                                            <option value="Laptops">Laptops</option>
                                                            <option value="Smartphones">Smartphones</option>
                                                            <option value="Tablets">Tablets</option>
                                                            <option value="Accessories">Accessories</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="stock" class="form-label">Stock Quantity</label>
                                                        <input type="number" class="form-control" id="stock" name="stock"
                                                            required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="image_url" class="form-label">Image URL</label>
                                                        <input type="url" class="form-control" id="image_url"
                                                            name="image_url" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" name="add_product" class="btn btn-primary">Add
                                                Product</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">All Products</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered admin-table">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Image</th>
                                                        <th>Name</th>
                                                        <th>Category</th>
                                                        <th>Price</th>
                                                        <th>Stock</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (count($products) > 0): ?>
                                                        <?php foreach ($products as $product): ?>
                                                            <tr>
                                                                <td><?php echo $product['id']; ?></td>
                                                                <td><img src="<?php echo $product['image_url']; ?>" width="50"
                                                                        alt="<?php echo $product['name']; ?>"></td>
                                                                <td><?php echo $product['name']; ?></td>
                                                                <td><?php echo $product['category']; ?></td>
                                                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                                                <td><?php echo $product['stock']; ?></td>
                                                                <td>
                                                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal"
                                                                        data-bs-target="#editProductModal<?php echo $product['id']; ?>">Edit</button>
                                                                    <form method="POST" action="" style="display: inline-block;">
                                                                        <input type="hidden" name="csrf_token"
                                                                            value="<?php echo $csrf_token; ?>">
                                                                        <input type="hidden" name="id"
                                                                            value="<?php echo $product['id']; ?>">
                                                                        <button type="submit" name="delete_product"
                                                                            class="btn btn-sm btn-danger"
                                                                            onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                                                                    </form>
                                                                </td>
                                                            </tr>

                                                            <div class="modal fade"
                                                                id="editProductModal<?php echo $product['id']; ?>" tabindex="-1"
                                                                aria-hidden="true">
                                                                <div class="modal-dialog">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title">Edit Product</h5>
                                                                            <button type="button" class="btn-close"
                                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <form method="POST" action="">
                                                                            <div class="modal-body">
                                                                                <input type="hidden" name="csrf_token"
                                                                                    value="<?php echo $csrf_token; ?>">
                                                                                <input type="hidden" name="id"
                                                                                    value="<?php echo $product['id']; ?>">
                                                                                <div class="mb-3">
                                                                                    <label
                                                                                        for="edit_name<?php echo $product['id']; ?>"
                                                                                        class="form-label">Product Name</label>
                                                                                    <input type="text" class="form-control"
                                                                                        id="edit_name<?php echo $product['id']; ?>"
                                                                                        name="name"
                                                                                        value="<?php echo $product['name']; ?>"
                                                                                        required>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label
                                                                                        for="edit_description<?php echo $product['id']; ?>"
                                                                                        class="form-label">Description</label>
                                                                                    <textarea class="form-control"
                                                                                        id="edit_description<?php echo $product['id']; ?>"
                                                                                        name="description" rows="3"
                                                                                        required><?php echo $product['description']; ?></textarea>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label
                                                                                        for="edit_price<?php echo $product['id']; ?>"
                                                                                        class="form-label">Price</label>
                                                                                    <input type="number" step="0.01"
                                                                                        class="form-control"
                                                                                        id="edit_price<?php echo $product['id']; ?>"
                                                                                        name="price"
                                                                                        value="<?php echo $product['price']; ?>"
                                                                                        required>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label
                                                                                        for="edit_category<?php echo $product['id']; ?>"
                                                                                        class="form-label">Category</label>
                                                                                    <select class="form-control"
                                                                                        id="edit_category<?php echo $product['id']; ?>"
                                                                                        name="category" required>
                                                                                        <option value="Laptops" <?php echo $product['category'] == 'Laptops' ? 'selected' : ''; ?>>Laptops</option>
                                                                                        <option value="Smartphones" <?php echo $product['category'] == 'Smartphones' ? 'selected' : ''; ?>>Smartphones</option>
                                                                                        <option value="Tablets" <?php echo $product['category'] == 'Tablets' ? 'selected' : ''; ?>>Tablets</option>
                                                                                        <option value="Accessories" <?php echo $product['category'] == 'Accessories' ? 'selected' : ''; ?>>Accessories</option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label
                                                                                        for="edit_stock<?php echo $product['id']; ?>"
                                                                                        class="form-label">Stock Quantity</label>
                                                                                    <input type="number" class="form-control"
                                                                                        id="edit_stock<?php echo $product['id']; ?>"
                                                                                        name="stock"
                                                                                        value="<?php echo $product['stock']; ?>"
                                                                                        required>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label
                                                                                        for="edit_image_url<?php echo $product['id']; ?>"
                                                                                        class="form-label">Image URL</label>
                                                                                    <input type="url" class="form-control"
                                                                                        id="edit_image_url<?php echo $product['id']; ?>"
                                                                                        name="image_url"
                                                                                        value="<?php echo $product['image_url']; ?>"
                                                                                        required>
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-secondary"
                                                                                    data-bs-dismiss="modal">Cancel</button>
                                                                                <button type="submit" name="update_product"
                                                                                    class="btn btn-primary">Save Changes</button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="7" class="text-center">No products found</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const priceRange = document.getElementById('priceRange');
            const priceValue = document.getElementById('priceValue');

            if (priceRange && priceValue) {
                priceRange.addEventListener('input', function () {
                    priceValue.textContent = this.value;
                    document.getElementById('price_max').value = this.value;
                });
            }

            const quantityControls = document.querySelectorAll('.quantity-control');
            quantityControls.forEach(control => {
                const minusBtn = control.querySelector('.quantity-minus');
                const plusBtn = control.querySelector('.quantity-plus');
                const quantityInput = control.querySelector('.quantity-input');

                minusBtn.addEventListener('click', function () {
                    let quantity = parseInt(quantityInput.value);
                    if (quantity > 1) {
                        quantityInput.value = quantity - 1;
                    }
                });

                plusBtn.addEventListener('click', function () {
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

        $(document).ready(function () {
            $('.btn-add-to-cart').click(function (e) {
                e.preventDefault();
                let productId = $(this).data('product-id');

                $.post('ajax_add_to_cart.php', { product_id: productId }, function (response) {
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
                        let toast = new bootstrap.Toast(toastEl, { delay: 2000 });
                        toast.show();

                        $('#cart-count').text(response.cart_count);
                    } else {
                        alert(response.message);
                    }
                }, 'json');
            });

            let currentPage = <?php echo $current_page; ?>;
            const totalPages = <?php echo $total_pages; ?>;
            const loadingText = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';

            $(document).on('click', '#load-more-btn', function () {
                const $btn = $(this);
                const $productsContainer = $('#products-container');

                $btn.html(loadingText).prop('disabled', true);

                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('page', currentPage + 1);

                $.ajax({
                    url: 'load_more_products.php?' + urlParams.toString(),
                    type: 'GET',
                    success: function (response) {
                        if (response.success) {
                            $productsContainer.append(response.html);
                            currentPage++;

                            if (currentPage >= totalPages) {
                                $btn.hide();
                            } else {
                                $btn.html('Show More').prop('disabled', false);
                            }
                        } else {
                            alert('Error loading more products');
                            $btn.html('Show More').prop('disabled', false);
                        }
                    },
                    error: function () {
                        alert('Error loading more products');
                        $btn.html('Show More').prop('disabled', false);
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