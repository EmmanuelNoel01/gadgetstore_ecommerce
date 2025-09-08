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

<body>
    <?php include 'includes/header.php'; ?>

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