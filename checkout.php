<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = "Checkout";

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

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">

<div class="bg-light border-bottom small py-1">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
        <div class="d-flex align-items-center mb-1 mb-md-0 text-muted">
            <i class="fas fa-map-marker-alt text-primary me-2"></i> Mabirizi Complex Basement, Shop B-24, Kampala Road
        </div>
        <div class="d-flex align-items-center">
            <i class="fas fa-phone-alt text-success me-2"></i>
            <a href="tel:+256778485512" class="text-decoration-none text-dark fw-semibold">
                +256 778 485 512
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
            <!-- <form class="d-flex w-100" method="GET" action="index.php">
                    <div class="input-group" style="max-width: 800px;">
                        <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                        <input class="form-control me-2 product-search-input" type="search" name="search"
                            placeholder="Search products..."
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button class="btn btn-light text-primary fw-bold" type="submit">Search</button>
                    </div>
                </form> -->

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
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
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
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" name="state" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="zip" class="form-label">Zip</label>
                                <input type="text" class="form-control" id="zip" name="zip" required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3">Payment Method</h6>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="cash" value="cash" checked>
                            <label class="form-check-label" for="cash">Cash</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="mobile" value="mobile_money">
                            <label class="form-check-label" for="mobile">Mobile Money</label>
                        </div>

                        <hr class="my-4">

                        <button class="btn btn-primary w-100" type="submit">Complete Order</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


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

<?php include 'includes/footer.php'; ?>