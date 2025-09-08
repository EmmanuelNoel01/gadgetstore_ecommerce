<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = (float) $_POST['price'];
        $category = sanitize($_POST['category']);
        $image_url = filter_var($_POST['image_url'], FILTER_SANITIZE_URL);
        $stock = (int) $_POST['stock'];

        $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, image_url, stock) 
                                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssi", $name, $description, $price, $category, $image_url, $stock);

        if ($stmt->execute()) {
            $success_message = "Product added successfully!";
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
        $image_url = filter_var($_POST['image_url'], FILTER_SANITIZE_URL);
        $stock = (int) $_POST['stock'];

        $stmt = $conn->prepare("UPDATE products 
                                SET name=?, description=?, price=?, category=?, image_url=?, stock=? 
                                WHERE id=?");
        $stmt->bind_param("ssdssii", $name, $description, $price, $category, $image_url, $stock, $id);

        if ($stmt->execute()) {
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
    }
}

// Get all products
$products = [];
$result = $conn->query("SELECT * FROM products ORDER BY id DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Get unique categories
$categories = [];
$categories_result = $conn->query("SELECT DISTINCT category FROM products");
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['category'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
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
    </style>
</head>

<body>
    <div class="header">
        <div class="container">
            <h1 class="text-center"><i class="fas fa-cogs"></i> Product Management</h1>
            <p class="text-center">Add, edit, or remove products from your store</p>
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

        <div class="row">
            <!-- Add product form -->
            <div class="col-md-5">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Add New Product</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"
                                    required></textarea>
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
                                <label for="image_url" class="form-label">Image URL</label>
                                <input type="url" class="form-control" id="image_url" name="image_url" required>
                            </div>
                            <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Products table -->
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">All Products</h5>
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
        <!-- Product Image -->
        <td>
            <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'default-image.png'); ?>" 
                 width="50" height="50" 
                 style="object-fit: cover;" 
                 alt="<?php echo htmlspecialchars($product['name'] ?? 'No Name'); ?>">
        </td>

        <!-- Product Name -->
        <td><?php echo htmlspecialchars($product['name'] ?? 'No Name'); ?></td>

        <!-- Product Price -->
        <td>UGX <?php echo number_format($product['price'] ?? 0, 2); ?></td>

        <!-- Stock Quantity -->
        <td><?php echo (int)($product['stock'] ?? 0); ?></td>

        <!-- Action Buttons -->
        <td class="action-buttons">
            <!-- Edit Button -->
            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editProductModal<?php echo $product['id'] ?? 0; ?>">
                <i class="fas fa-edit"></i> Edit
            </button>

            <!-- Delete Form -->
            <form method="POST" action="" style="display:inline-block;">
                <input type="hidden" name="id" value="<?php echo $product['id'] ?? 0; ?>">
                <button type="submit" name="delete_product" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </form>
        </td>
    </tr>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal<?php echo $product['id'] ?? 0; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?php echo $product['id'] ?? 0; ?>">

                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Price (UGX)</label>
                            <input type="number" step="0.01" class="form-control" name="price" value="<?php echo htmlspecialchars($product['price'] ?? '0'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-control" name="category" required>
                                <option value="Laptops" <?php echo ($product['category'] ?? '') == 'Laptops' ? 'selected' : ''; ?>>Laptops</option>
                                <option value="Smartphones" <?php echo ($product['category'] ?? '') == 'Smartphones' ? 'selected' : ''; ?>>Smartphones</option>
                                <option value="Tablets" <?php echo ($product['category'] ?? '') == 'Tablets' ? 'selected' : ''; ?>>Tablets</option>
                                <option value="Accessories" <?php echo ($product['category'] ?? '') == 'Accessories' ? 'selected' : ''; ?>>Accessories</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" class="form-control" name="stock" value="<?php echo (int)($product['stock'] ?? 0); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="url" class="form-control" name="image_url" value="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>" required>
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
                        <?php else: ?>
                            <p class="text-center">No products found. Add your first product using the form on the left.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>

</html>
<?php $conn->close(); ?>