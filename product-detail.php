<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get product ID from URL
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch product details
$product = getProduct($productId);

if (!$product) {
    header('Location: products.php');
    exit;
}

$pageTitle = $product['name'] . " - TechShop";
include 'includes/header.php';

// Process add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = intval($_POST['quantity']);
    addToCart($productId, $quantity);
    
    header('Location: cart.php');
    exit;
}
?>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="products.php">Products</a></li>
            <li class="breadcrumb-item active"><?php echo $product['name']; ?></li>
        </ol>
    </nav>
    
    <div class="row">
        <div class="col-md-6">
            <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="img-fluid product-detail-image rounded shadow">
        </div>
        
        <div class="col-md-6">
            <h2><?php echo $product['name']; ?></h2>
            
            <div class="d-flex align-items-center mb-3">
                <div class="rating me-3">
                    <i class="fas fa-star text-warning"></i>
                    <i class="fas fa-star text-warning"></i>
                    <i class="fas fa-star text-warning"></i>
                    <i class="fas fa-star text-warning"></i>
                    <i class="fas fa-star-half-alt text-warning"></i>
                </div>
                <span class="text-muted">(24 reviews)</span>
            </div>
            
            <h3 class="text-primary mb-3"><?php echo formatPrice($product['price']); ?></h3>
            
            <p class="mb-4"><?php echo $product['description']; ?></p>
            
            <div class="d-flex align-items-center mb-4">
                <span class="badge bg-<?php echo $product['stock'] > 0 ? 'success' : 'danger'; ?> me-3">
                    <?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                </span>
                <span class="text-muted">SKU: <?php echo $product['sku']; ?></span>
            </div>
            
            <form method="POST" action="">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="input-group quantity-control">
                            <button class="btn btn-outline-secondary quantity-minus" type="button">-</button>
                            <input type="number" class="form-control text-center quantity-input" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                            <button class="btn btn-outline-secondary quantity-plus" type="button">+</button>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex">
                    <button type="submit" name="add_to_cart" class="btn btn-primary me-md-2" <?php echo $product['stock'] > 0 ? '' : 'disabled'; ?>>
                        <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                    </button>
                    <button type="button" class="btn btn-outline-secondary">
                        <i class="fas fa-heart me-2"></i>Add to Wishlist
                    </button>
                </div>
            </form>
            
            <hr class="my-4">
            
            <div class="product-details">
                <h6>Product Details</h6>
                <ul class="list-unstyled">
                    <li><strong>Category:</strong> <?php echo $product['category']; ?></li>
                    <li><strong>Brand:</strong> <?php echo $product['brand']; ?></li>
                    <li><strong>Weight:</strong> <?php echo $product['weight']; ?> kg</li>
                    <li><strong>Dimensions:</strong> <?php echo $product['dimensions']; ?></li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="productTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab">Description</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="specs-tab" data-bs-toggle="tab" data-bs-target="#specs" type="button" role="tab">Specifications</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">Reviews</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="productTabsContent">
                        <div class="tab-pane fade show active" id="description" role="tabpanel">
                            <p><?php echo $product['description']; ?></p>
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam euismod, nisl eget ultricies ultricies, nunc nisl aliquam nunc, eget aliquam nisl nisl eget nisl. Nullam euismod, nisl eget ultricies ultricies, nunc nisl aliquam nunc, eget aliquam nisl nisl eget nisl.</p>
                        </div>
                        <div class="tab-pane fade" id="specs" role="tabpanel">
                            <table class="table">
                                <tr>
                                    <th>Processor</th>
                                    <td>Intel Core i7</td>
                                </tr>
                                <tr>
                                    <th>RAM</th>
                                    <td>16GB DDR4</td>
                                </tr>
                                <tr>
                                    <th>Storage</th>
                                    <td>512GB SSD</td>
                                </tr>
                                <tr>
                                    <th>Display</th>
                                    <td>15.6" Full HD</td>
                                </tr>
                                <tr>
                                    <th>Graphics</th>
                                    <td>NVIDIA GeForce GTX 1650</td>
                                </tr>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="reviews" role="tabpanel">
                            <div class="review">
                                <div class="d-flex justify-content-between">
                                    <h6>John Doe</h6>
                                    <div class="rating">
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                    </div>
                                </div>
                                <p class="text-muted">Posted on June 15, 2023</p>
                                <p>Great product! I've been using it for a month now and it's been working perfectly.</p>
                            </div>
                            <hr>
                            <div class="review">
                                <div class="d-flex justify-content-between">
                                    <h6>Jane Smith</h6>
                                    <div class="rating">
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="fas fa-star text-warning"></i>
                                        <i class="far fa-star text-warning"></i>
                                    </div>
                                </div>
                                <p class="text-muted">Posted on June 10, 2023</p>
                                <p>Good product, but the battery life could be better.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>