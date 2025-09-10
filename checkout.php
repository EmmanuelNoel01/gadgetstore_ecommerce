<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = "Checkout - TechShop";
// include 'includes/header.php';

$cartItems = getCartItems();
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userData = [
        'first_name' => sanitize($_POST['first_name']),
        'last_name' => sanitize($_POST['last_name']),
        'email' => sanitize($_POST['email']),
        'phone' => sanitize($_POST['phone']),
        'address' => sanitize($_POST['address']),
        'city' => sanitize($_POST['city']),
        'state' => sanitize($_POST['state']),
        'zip' => sanitize($_POST['zip']),
        'payment_method' => sanitize($_POST['payment_method'])
    ];

    $orderId = processCheckout($userData);

    if ($orderId) {
        clearCart(); 
        header("Location: order-confirmation.php?id=$orderId");
        exit;
    } else {
        $error = "There was an error processing your order. Please try again.";
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-gradient-primary shadow">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-laptop-code"></i> Gadget Store
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
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
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" required>
                        </div>
                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="city" class="form-label">State</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="zip" class="form-label">Zip</label>
                                <input type="text" class="form-control" id="zip" name="zip" required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3">Payment Method</h6>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="credit" value="credit" checked>
                            <label class="form-check-label" for="credit">Cash</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="debit" value="debit">
                            <label class="form-check-label" for="debit">Mobile Money</label>
                        </div>

                        <hr class="my-4">

                        <button class="btn btn-primary w-100" type="submit">Complete Order</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Order Summary -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Order Summary</h5>
                    <?php foreach ($cartItems as $item): ?>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <div>
                                <h6 class="my-0"><?= htmlspecialchars($item['product']['name']) ?></h6>
                                <small class="text-muted">Quantity: <?= (int)$item['quantity'] ?></small>
                            </div>
                            <span class="text-muted"><?= formatPrice($item['product']['price'] * $item['quantity']) ?></span>
                        </div>
                    <?php endforeach; ?>

                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Subtotal</span>
                        <strong><?= formatPrice(getCartTotal()) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Shipping</span>
                        <strong><?= formatPrice(10) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Tax</span>
                        <strong><?= formatPrice(getCartTotal() * 0.08) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Total</span>
                        <strong><?= formatPrice(getCartTotal() + 10 + (getCartTotal() * 0.08)) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'includes/footer.php'; ?>
