<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$products_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $products_per_page;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = (float) $_POST['price'];
        $category = sanitize($_POST['category']);
        $stock = (int) $_POST['stock'];
        
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, stock) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsi", $name, $description, $price, $category, $stock);
        
        if ($stmt->execute()) {
            $product_id = $stmt->insert_id;
            $success_message = "Product added successfully!";
            
            if (!empty($_POST['image_urls'])) {
                $image_urls = json_decode($_POST['image_urls'], true);
                $sort_order = 0;
                
                foreach ($image_urls as $image_url) {
                    $image_url = filter_var($image_url, FILTER_SANITIZE_URL);
                    if (!empty($image_url)) {
                        $img_stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, sort_order) VALUES (?, ?, ?)");
                        $img_stmt->bind_param("isi", $product_id, $image_url, $sort_order);
                        $img_stmt->execute();
                        $img_stmt->close();
                        $sort_order++;
                    }
                }
                
                if (!empty($image_urls[0])) {
                    $update_stmt = $conn->prepare("UPDATE products SET image_url = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $image_urls[0], $product_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }
        } else {
            $error_message = "Error adding product: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_product'])) {
        $id = (int) $_POST['id'];
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = (float) $_POST['price'];
        $category = sanitize($_POST['category']);
        $stock = (int) $_POST['stock'];

        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, category=?, stock=? WHERE id=?");
        $stmt->bind_param("ssdsii", $name, $description, $price, $category, $stock, $id);

        if ($stmt->execute()) {
            if (!empty($_POST['image_urls'])) {
                $delete_stmt = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
                $delete_stmt->bind_param("i", $id);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                $image_urls = json_decode($_POST['image_urls'], true);
                $sort_order = 0;
                
                foreach ($image_urls as $image_url) {
                    $image_url = filter_var($image_url, FILTER_SANITIZE_URL);
                    if (!empty($image_url)) {
                        $img_stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, sort_order) VALUES (?, ?, ?)");
                        $img_stmt->bind_param("isi", $id, $image_url, $sort_order);
                        $img_stmt->execute();
                        $img_stmt->close();
                        $sort_order++;
                    }
                }
                
                if (!empty($image_urls[0])) {
                    $update_stmt = $conn->prepare("UPDATE products SET image_url = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $image_urls[0], $id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }
            
            $success_message = "Product updated successfully!";
        } else {
            $error_message = "Error updating product: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_product'])) {
        $id = (int) $_POST['id'];

        $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $success_message = "Product deleted successfully!";
        } else {
            $error_message = "Error deleting product: " . $stmt->error;
        }
        $stmt->close();
   } elseif (isset($_POST['add_carousel_item']) 
          && isset($_POST['carousel_title'], $_POST['carousel_description'], $_POST['carousel_image_url'])) {

        $title = sanitize($_POST['carousel_title']);
        $description = sanitize($_POST['carousel_description']);
        $image_url = filter_var($_POST['carousel_image_url'], FILTER_SANITIZE_URL);
        $button_text = isset($_POST['carousel_button_text']) ? sanitize($_POST['carousel_button_text']) : 'Shop Now';
        
        // Get the next sort order
        $result = $conn->query("SELECT MAX(sort_order) as max_order FROM carousel_items");
        $max_order = $result->fetch_assoc()['max_order'];
        $sort_order = $max_order ? $max_order + 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO carousel_items (title, description, image_url, button_text, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $title, $description, $image_url, $button_text, $sort_order);
        
        if ($stmt->execute()) {
            $success_message = "Carousel item added successfully!";
        } else {
            $error_message = "Error adding carousel item: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_carousel_item'])) {
        $id = (int) $_POST['carousel_id'];
        $title = sanitize($_POST['carousel_title']);
        $description = sanitize($_POST['carousel_description']);
        $image_url = filter_var($_POST['carousel_image_url'], FILTER_SANITIZE_URL);
        $button_text = isset($_POST['carousel_button_text']) ? sanitize($_POST['carousel_button_text']) : 'Shop Now';
        
        $stmt = $conn->prepare("UPDATE carousel_items SET title=?, description=?, image_url=?, button_text=? WHERE id=?");
        $stmt->bind_param("ssssi", $title, $description, $image_url, $button_text, $id);
        
        if ($stmt->execute()) {
            $success_message = "Carousel item updated successfully!";
        } else {
            $error_message = "Error updating carousel item: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_carousel_item'])) {
        $id = (int) $_POST['delete_carousel_item'];
        
        $stmt = $conn->prepare("DELETE FROM carousel_items WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_message = "Carousel item deleted successfully!";
        } else {
            $error_message = "Error deleting carousel item: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_promo'])) {
        $promo_image = filter_var($_POST['promo_image'], FILTER_SANITIZE_URL);
        
        // Clear existing promo image
        $conn->query("DELETE FROM promo_images");
        
        // Insert new promo image
        $stmt = $conn->prepare("INSERT INTO promo_images (image_url) VALUES (?)");
        $stmt->bind_param("s", $promo_image);
        $stmt->execute();
        $stmt->close();
        
        $success_message = "Promo image updated successfully!";
    }
}

// Fetch total number of products for pagination
$total_products_result = $conn->query("SELECT COUNT(*) as total FROM products");
$total_products = $total_products_result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $products_per_page);

// Fetch products with pagination
$products = [];
$result = $conn->query("SELECT * FROM products ORDER BY id DESC LIMIT $offset, $products_per_page");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $product_id = $row['id'];
        
        $image_result = $conn->query("SELECT * FROM product_images WHERE product_id = $product_id ORDER BY sort_order");
        $images = [];
        while ($image_row = $image_result->fetch_assoc()) {
            $images[] = $image_row;
        }
        
        $row['images'] = $images;
        $products[] = $row;
    }
}

// Fetch categories
$categories = [];
$categories_result = $conn->query("SELECT DISTINCT category FROM products");
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['category'];
}

// Fetch carousel items
$carousel_items = [];
$carousel_result = $conn->query("SELECT * FROM carousel_items ORDER BY sort_order");
if ($carousel_result && $carousel_result->num_rows > 0) {
    while ($row = $carousel_result->fetch_assoc()) {
        $carousel_items[] = $row;
    }
}

// Fetch promo image
$promo_image = "";
$promo_result = $conn->query("SELECT * FROM promo_images LIMIT 1");
if ($promo_result && $promo_result->num_rows > 0) {
    $promo_image = $promo_result->fetch_assoc()['image_url'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', sans-serif;
        }
        .header {
            background: linear-gradient(87deg, #03246b 0, #03246b 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .product-card {
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
        .success-alert,
        .error-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        }
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .image-preview {
            position: relative;
            width: 100px;
            height: 100px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .image-preview .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .carousel-control-prev, .carousel-control-next {
            width: 30px;
            height: 30px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
        }
        .carousel-control-prev {
            left: 10px;
        }
        .carousel-control-next {
            right: 10px;
        }
        .nav-tabs .nav-link.active {
            background-color: #03246b;
            color: white;
            border-color: #03246b;
        }
        .nav-tabs .nav-link {
            color: #03246b;
        }
        .carousel-item-form {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }
        .carousel-preview {
            max-width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-top: 10px;
        }
        .carousel-add-form {
            background-color: #f0f8ff;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1 class="text-center"><i class="fas fa-cogs"></i> Admin Dashboard</h1>
            <p class="text-center">Manage products, carousel, and promotional content</p>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success success-alert alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger error-alert alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab">Products</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="carousel-tab" data-bs-toggle="tab" data-bs-target="#carousel" type="button" role="tab">Carousel</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="promo-tab" data-bs-toggle="tab" data-bs-target="#promo" type="button" role="tab">Promo Image</button>
            </li>
        </ul>

        <div class="tab-content" id="adminTabsContent">
            <div class="tab-pane fade show active" id="products" role="tabpanel">
                <div class="row">
                    <div class="col-md-5">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Add New Product</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" id="productForm">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Product Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price (UGX)</label>
                                        <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                                    </div>
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
                                        <input type="number" class="form-control" id="stock" name="stock" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Product Images (Max 4)</label>
                                        <div class="input-group mb-2">
                                            <input type="url" class="form-control" id="imageInput" placeholder="Enter image URL">
                                            <button type="button" class="btn btn-secondary" id="addImageBtn">Add Image</button>
                                        </div>
                                        <div class="image-preview-container" id="imagePreviewContainer"></div>
                                        <input type="hidden" name="image_urls" id="imageUrlsInput">
                                        <small class="text-muted">You can add up to 4 images. The first image will be the main product image.</small>
                                    </div>
                                    
                                    <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">All Products</h5>
                                <span class="badge bg-light text-dark">Total: <?php echo $total_products; ?></span>
                            </div>
                            <div class="card-body">
                                <?php if (count($products) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Image</th>
                                                    <th>Name</th>
                                                    <th>Price</th>
                                                    <th>Stock</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($products as $product): ?>
                                                    <tr>
                                                        <td>
                                                            <?php if (!empty($product['images'])): ?>
                                                                <img src="<?php echo htmlspecialchars($product['images'][0]['image_url']); ?>" 
                                                                     width="50" height="50" 
                                                                     style="object-fit: cover;" 
                                                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                                <?php if (count($product['images']) > 1): ?>
                                                                    <span class="badge bg-info">+<?php echo count($product['images']) - 1; ?></span>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">No image</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                        <td>UGX <?php echo number_format($product['price'], 2); ?></td>
                                                        <td><?php echo $product['stock']; ?></td>
                                                        <td class="action-buttons">
                                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editProductModal<?php echo $product['id']; ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                            <form method="POST" action="" style="display:inline-block;">
                                                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                                <button type="submit" name="delete_product" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">
                                                                    <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>

                                                    <div class="modal fade" id="editProductModal<?php echo $product['id']; ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Edit Product</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form method="POST" action="">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                                        
                                                                        <div class="row">
                                                                            <div class="col-md-6">
                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Product Name</label>
                                                                                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Description</label>
                                                                                    <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Price (UGX)</label>
                                                                                    <input type="number" step="0.01" class="form-control" name="price" value="<?php echo $product['price']; ?>" required>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Category</label>
                                                                                    <select class="form-control" name="category" required>
                                                                                        <option value="Laptops" <?php echo $product['category'] == 'Laptops' ? 'selected' : ''; ?>>Laptops</option>
                                                                                        <option value="Smartphones" <?php echo $product['category'] == 'Smartphones' ? 'selected' : ''; ?>>Smartphones</option>
                                                                                        <option value="Tablets" <?php echo $product['category'] == 'Tablets' ? 'selected' : ''; ?>>Tablets</option>
                                                                                        <option value="Accessories" <?php echo $product['category'] == 'Accessories' ? 'selected' : ''; ?>>Accessories</option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Stock Quantity</label>
                                                                                    <input type="number" class="form-control" name="stock" value="<?php echo $product['stock']; ?>" required>
                                                                                </div>
                                                                                
                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Product Images (Max 4)</label>
                                                                                    <div class="input-group mb-2">
                                                                                        <input type="url" class="form-control edit-image-input" 
                                                                                               data-product-id="<?php echo $product['id']; ?>" 
                                                                                               placeholder="Enter image URL">
                                                                                        <button type="button" class="btn btn-secondary edit-add-image-btn" 
                                                                                                data-product-id="<?php echo $product['id']; ?>">Add Image</button>
                                                                                    </div>
                                                                                    <div class="image-preview-container edit-image-preview-container" 
                                                                                         id="editImagePreviewContainer<?php echo $product['id']; ?>">
                                                                                        <?php foreach ($product['images'] as $image): ?>
                                                                                            <div class="image-preview">
                                                                                                <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="Product image">
                                                                                                <div class="remove-image" data-image-id="<?php echo $image['id']; ?>" data-product-id="<?php echo $product['id']; ?>">
                                                                                                    <i class="fas fa-times"></i>
                                                                                                </div>
                                                                                            </div>
                                                                                        <?php endforeach; ?>
                                                                                    </div>
                                                                                    <input type="hidden" name="image_urls" id="editImageUrlsInput<?php echo $product['id']; ?>" 
                                                                                           value='<?php echo json_encode(array_column($product['images'], 'image_url')); ?>'>
                                                                                    <small class="text-muted">You can add up to 4 images. The first image will be the main product image.</small>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="update_product" class="btn btn-primary">Save Changes</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Pagination -->
                                    <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination">
                                            <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                            
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                    <?php endif; ?>
                                    
                                <?php else: ?>
                                    <p class="text-center">No products found. Add your first product using the form on the left.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="carousel" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Manage Carousel</h5>
                    </div>
                    <div class="card-body">
                        <div class="carousel-add-form mb-4">
                            <h5>Add New Carousel Item</h5>
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Title</label>
                                            <input type="text" class="form-control" name="carousel_title" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="carousel_description" rows="2" required></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Image URL</label>
                                            <input type="url" class="form-control" name="carousel_image_url" required onchange="updatePreview(this, 'addPreview')">
                                            <img src="" class="carousel-preview" id="addPreview" alt="Preview" style="display: none;">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Button Text</label>
                                            <input type="text" class="form-control" name="carousel_button_text" value="Shop Now" required>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="add_carousel_item" class="btn btn-primary">Add Carousel Item</button>
                            </form>
                        </div>

                        <h5 class="mb-3">Existing Carousel Items</h5>
                        <?php if (count($carousel_items) > 0): ?>
                            <div class="row">
                                <?php foreach ($carousel_items as $item): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="carousel-item-form">
                                            <form method="POST" action="">
                                                <input type="hidden" name="carousel_id" value="<?php echo $item['id']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Title</label>
                                                    <input type="text" class="form-control" name="carousel_title" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Description</label>
                                                    <textarea class="form-control" name="carousel_description" rows="2" required><?php echo htmlspecialchars($item['description']); ?></textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Image URL</label>
                                                    <input type="url" class="form-control" name="carousel_image_url" value="<?php echo htmlspecialchars($item['image_url']); ?>" required onchange="updatePreview(this, 'preview<?php echo $item['id']; ?>')">
                                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="carousel-preview" id="preview<?php echo $item['id']; ?>" alt="Preview">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Button Text</label>
                                                    <input type="text" class="form-control" name="carousel_button_text" value="<?php echo htmlspecialchars($item['button_text']); ?>" required>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <button type="submit" name="update_carousel_item" class="btn btn-primary btn-sm">Update</button>
                                                    <button type="submit" name="delete_carousel_item" value="<?php echo $item['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this carousel item?')">Delete</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No carousel items found. Add your first carousel item using the form above.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="promo" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Manage Promo Image</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Promo Image URL</label>
                                <input type="url" class="form-control" name="promo_image" value="<?php echo htmlspecialchars($promo_image); ?>" required onchange="updatePreview(this, 'promoPreview')">
                                <small class="text-muted">This image appears in the "Summer Tech Sale" section</small>
                                <?php if (!empty($promo_image)): ?>
                                    <img src="<?php echo htmlspecialchars($promo_image); ?>" class="carousel-preview mt-2" id="promoPreview" alt="Promo Preview">
                                <?php else: ?>
                                    <img src="" class="carousel-preview mt-2" id="promoPreview" alt="Promo Preview" style="display: none;">
                                <?php endif; ?>
                            </div>
                            <button type="submit" name="update_promo" class="btn btn-primary">Save Promo Image</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        const imageUrls = [];
        const imagePreviewContainer = document.getElementById('imagePreviewContainer');
        const imageUrlsInput = document.getElementById('imageUrlsInput');
        const addImageBtn = document.getElementById('addImageBtn');
        const imageInput = document.getElementById('imageInput');
        
        function updateImageUrlsInput() {
            imageUrlsInput.value = JSON.stringify(imageUrls);
        }
        
        function addImagePreview(imageUrl) {
            if (imageUrls.length >= 4) {
                alert('You can only add up to 4 images.');
                return;
            }
            
            imageUrls.push(imageUrl);
            
            const preview = document.createElement('div');
            preview.className = 'image-preview';
            preview.innerHTML = `
                <img src="${imageUrl}" alt="Preview">
                <div class="remove-image" data-url="${imageUrl}">
                    <i class="fas fa-times"></i>
                </div>
            `;
            
            imagePreviewContainer.appendChild(preview);
            updateImageUrlsInput();
            
            preview.querySelector('.remove-image').addEventListener('click', function() {
                const urlToRemove = this.getAttribute('data-url');
                const index = imageUrls.indexOf(urlToRemove);
                if (index > -1) {
                    imageUrls.splice(index, 1);
                }
                preview.remove();
                updateImageUrlsInput();
            });
        }
        
        addImageBtn.addEventListener('click', function() {
            const imageUrl = imageInput.value.trim();
            if (imageUrl) {
                addImagePreview(imageUrl);
                imageInput.value = '';
            }
        });
        
        document.querySelectorAll('.edit-add-image-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const imageInput = document.querySelector(`.edit-image-input[data-product-id="${productId}"]`);
                const previewContainer = document.getElementById(`editImagePreviewContainer${productId}`);
                const urlsInput = document.getElementById(`editImageUrlsInput${productId}`);
                
                const imageUrl = imageInput.value.trim();
                if (!imageUrl) return;
                
                let imageUrls = JSON.parse(urlsInput.value || '[]');
                if (imageUrls.length >= 4) {
                    alert('You can only add up to 4 images.');
                    return;
                }
                
                imageUrls.push(imageUrl);
                urlsInput.value = JSON.stringify(imageUrls);
                
                const preview = document.createElement('div');
                preview.className = 'image-preview';
                preview.innerHTML = `
                    <img src="${imageUrl}" alt="Preview">
                    <div class="remove-image" data-url="${imageUrl}" data-product-id="${productId}">
                        <i class="fas fa-times"></i>
                    </div>
                `;
                
                previewContainer.appendChild(preview);
                imageInput.value = '';
                
                preview.querySelector('.remove-image').addEventListener('click', function() {
                    const urlToRemove = this.getAttribute('data-url');
                    const productId = this.getAttribute('data-product-id');
                    const urlsInput = document.getElementById(`editImageUrlsInput${productId}`);
                    
                    let imageUrls = JSON.parse(urlsInput.value || '[]');
                    const index = imageUrls.indexOf(urlToRemove);
                    if (index > -1) {
                        imageUrls.splice(index, 1);
                    }
                    urlsInput.value = JSON.stringify(imageUrls);
                    preview.remove();
                });
            });
        });
        
        document.querySelectorAll('.remove-image[data-image-id]').forEach(btn => {
            btn.addEventListener('click', function() {
                const imageId = this.getAttribute('data-image-id');
                const productId = this.getAttribute('data-product-id');
                const preview = this.parentElement;
                
                fetch('delete_image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `image_id=${imageId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        preview.remove();
                        
                        const urlsInput = document.getElementById(`editImageUrlsInput${productId}`);
                        let imageUrls = JSON.parse(urlsInput.value || '[]');
                        
                        const imgUrl = preview.querySelector('img').src;
                        const index = imageUrls.indexOf(imgUrl);
                        if (index > -1) {
                            imageUrls.splice(index, 1);
                        }
                        urlsInput.value = JSON.stringify(imageUrls);
                    } else {
                        alert('Error deleting image: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting image');
                });
            });
        });

        function updatePreview(input) {
            const preview = input.parentElement.querySelector('.carousel-preview');
            if (input.value) {
                preview.src = input.value;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }

        // Initialize preview images
        document.querySelectorAll('input[type="url"]').forEach(input => {
            if (input.value) {
                updatePreview(input);
            }
            
            input.addEventListener('change', function() {
                updatePreview(this);
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>