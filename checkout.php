<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = "Checkout - TechShop";
include 'includes/header.php';

// Redirect if cart is empty
$cartItems = getCartItems();
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

// Process checkout form
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
        clearCart(); // Clear cart after successful checkout
        header("Location: order-confirmation.php?id=$orderId");
        exit;
    } else {
        $error = "There was an error processing your order. Please try again.";
    }
}
?>

<div class="container py-5">
    <h2 class="mb-4">Checkout</h2>

    <div class="row">
        <div class="col-lg-8">
            <!-- Shipping & Payment Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Checkout Process</h5>

                    <ul class="nav nav-pills justify-content-center checkout-steps mb-4">
                        <li class="nav-item step active"><span class="nav-link">1. Shipping</span></li>
                        <li class="nav-item step"><span class="nav-link">2. Payment</span></li>
                        <li class="nav-item step"><span class="nav-link">3. Confirmation</span></li>
                    </ul>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <!-- Shipping fields -->
                        <h6 class="mb-3">Shipping Information</h6>
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
                                <select class="form-control" id="state" name="state" required>
                                    <option value="">Choose...</option>
                                    <option value="CA">California</option>
                                    <option value="NY">New York</option>
                                    <option value="TX">Texas</option>
                                    <option value="FL">Florida</option>
                                </select>
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
                            <label class="form-check-label" for="credit">Credit card</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="debit" value="debit">
                            <label class="form-check-label" for="debit">Debit card</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                            <label class="form-check-label" for="paypal">PayPal</label>
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

<?php include 'includes/footer.php'; ?>
