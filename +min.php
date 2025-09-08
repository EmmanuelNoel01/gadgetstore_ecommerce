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
        $stock = (int) $_POST['stock'];
        
        // Insert product with NULL image_url initially
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, stock) 
                                VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsi", $name, $description, $price, $category, $stock);
        
        if ($stmt->execute()) {
            $product_id = $stmt->insert_id;
            $success_message = "Product added successfully!";
            
            // Handle image uploads
            if (!empty($_POST['image_urls'])) {
                $image_urls = json_decode($_POST['image_urls'], true);
                $sort_order = 0;
                
                foreach ($image_urls as $image_url) {
                    $image_url = filter_var($image_url, FILTER_SANITIZE_URL);
                    if (!empty($image_url)) {
                        $img_stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, sort_order) 
                                                    VALUES (?, ?, ?)");
                        $img_stmt->bind_param("isi", $product_id, $image_url, $sort_order);
                        $img_stmt->execute();
                        $img_stmt->close();
                        $sort_order++;
                    }
                }
                
                // Set the first image as the main image for backward compatibility
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

        $stmt = $conn->prepare("UPDATE products 
                                SET name=?, description=?, price=?, category=?, stock=? 
                                WHERE id=?");
        $stmt->bind_param("ssdsii", $name, $description, $price, $category, $stock, $id);

        if ($stmt->execute()) {
            // Handle image updates
            if (!empty($_POST['image_urls'])) {
                // First delete existing images
                $delete_stmt = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
                $delete_stmt->bind_param("i", $id);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                // Insert new images
                $image_urls = json_decode($_POST['image_urls'], true);
                $sort_order = 0;
                
                foreach ($image_urls as $image_url) {
                    $image_url = filter_var($image_url, FILTER_SANITIZE_URL);
                    if (!empty($image_url)) {
                        $img_stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, sort_order) 
                                                    VALUES (?, ?, ?)");
                        $img_stmt->bind_param("isi", $id, $image_url, $sort_order);
                        $img_stmt->execute();
                        $img_stmt->close();
                        $sort_order++;
                    }
                }
                
                // Update main image
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
    }
}

// Get all products with their images
$products = [];
$result = $conn->query("SELECT * FROM products ORDER BY id DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $product_id = $row['id'];
        
        // Get images for this product
        $image_result = $conn->query("SELECT * FROM product_images WHERE product_id = $product_id ORDER BY sort_order");
        $images = [];
        while ($image_row = $image_result->fetch_assoc()) {
            $images[] = $image_row;
        }
        
        $row['images'] = $images;
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
                        <form method="POST" action="" id="productForm">
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
                            
                            <!-- Image upload section -->
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

                                            <!-- Edit Product Modal -->
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
                                                                        
                                                                        <!-- Image upload section for editing -->
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
        
        // Image management for adding new products
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
            
            // Add event listener to remove button
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
        
        // Image management for editing products
        document.querySelectorAll('.edit-add-image-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const imageInput = document.querySelector(`.edit-image-input[data-product-id="${productId}"]`);
                const previewContainer = document.getElementById(`editImagePreviewContainer${productId}`);
                const urlsInput = document.getElementById(`editImageUrlsInput${productId}`);
                
                const imageUrl = imageInput.value.trim();
                if (!imageUrl) return;
                
                // Get current images
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
                
                // Add event listener to remove button
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
        
        // Handle image removal in edit mode (for existing images from database)
        document.querySelectorAll('.remove-image[data-image-id]').forEach(btn => {
            btn.addEventListener('click', function() {
                const imageId = this.getAttribute('data-image-id');
                const productId = this.getAttribute('data-product-id');
                const preview = this.parentElement;
                
                // Send AJAX request to delete the image
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
                        
                        // Update the hidden input with image URLs
                        const urlsInput = document.getElementById(`editImageUrlsInput${productId}`);
                        let imageUrls = JSON.parse(urlsInput.value || '[]');
                        
                        // Find the URL to remove by looking at the img src
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
    </script>
</body>

</html>
<?php $conn->close(); ?>