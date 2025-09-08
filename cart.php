<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid form submission. Please try again.";
        redirect('cart.php');
    }

    // Handle cart actions
    if (isset($_POST['update_quantity'])) {
        $product_id = (int) $_POST['product_id'];
        $quantity = (int) $_POST['quantity'];
        if (updateCartQuantity($product_id, $quantity)) {
            $_SESSION['success'] = "Cart updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating cart.";
        }
    } elseif (isset($_POST['remove_item'])) {
        $product_id = (int) $_POST['product_id'];
        if (removeFromCart($product_id)) {
            $_SESSION['success'] = "Item removed from cart!";
        } else {
            $_SESSION['error'] = "Error removing item from cart.";
        }
    } elseif (isset($_POST['clear_cart'])) {
        clearCart();
        $_SESSION['success'] = "Cart cleared successfully!";
    } elseif (isset($_POST['checkout'])) {
        redirect('checkout.php');
    }

    redirect('cart.php');
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - TechShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
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

        /* Add this to your existing CSS */
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
    </style>
</head>
<body>
   
<nav class="navbar navbar-expand-lg navbar-dark bg-gradient-primary shadow">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-laptop-code"></i> Gadget Store
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- <form class="d-flex w-100" method="GET" action="index.php">
                    <div class="input-group" style="max-width: 800px;">
                        <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                        <input class="form-control me-2 product-search-input" type="search" name="search"
                            placeholder="Search products..."
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button class="btn btn-primary" type="submit">Search</button>
                    </div>
                </form> -->

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

    <div class="container py-5">

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (empty($_SESSION['cart'])): ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="index.php">Continue shopping</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <?php foreach (getCartItems() as $item):
                                $id = $item['id'] ?? 0;
                                $name = $item['name'] ?? 'Product';
                                $price = $item['price'] ?? 0;
                                $image = $item['image'] ?? 'default.png';
                                ?>
                                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                    <div class="d-flex align-items-center">
                                        <img src="uploads/<?= htmlspecialchars($image) ?>" width="60" class="me-3">
                                        <div>
                                            <strong><?= htmlspecialchars($name) ?></strong><br>
                                            <?= number_format($price) ?>
                                        </div>
                                    </div>
                                    <div>
                                        <form action="cart.php" method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <input type="hidden" name="product_id" value="<?= (int) $id ?>">
                                            <button type="submit" name="remove_item"
                                                class="btn btn-sm btn-danger">Remove</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><b>Order Summary</b></h5>
                            <p>Total items: <?= getCartTotalItems() ?></p>
                            <p>Total price: <b>UGX. </b><?= number_format(getCartTotalPrice()) ?></p>
                            <form action="cart.php" method="post">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <button type="submit" name="clear_cart" class="btn btn-warning w-100">Clear Cart</button>
                                <button type="submit" name="checkout" class="btn btn-success w-100 mt-3">Proceed to
                                    Checkout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>