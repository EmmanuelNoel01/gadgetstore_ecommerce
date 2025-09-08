<?php
$pageTitle = "Page Not Found - TechShop";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="error-page">
                <h1 class="display-1 text-primary">404</h1>
                <h2 class="mb-4">Page Not Found</h2>
                <p class="lead mb-4">The page you are looking for doesn't exist or has been moved.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="/" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Go Home
                    </a>
                    <a href="/products" class="btn btn-outline-primary">
                        <i class="fas fa-shopping-bag me-2"></i>Browse Products
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>