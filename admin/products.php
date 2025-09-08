<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../index.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $stock_quantity = $_POST['stock_quantity'];
        $image_url = $_POST['image_url'] ?: 'https://via.placeholder.com/300x200?text=Product+Image';
        
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id, stock_quantity, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $category_id, $stock_quantity, $image_url]);
        
        $_SESSION['message'] = 'Product added successfully!';
        header('Location: products.php');
        exit;
    } elseif (isset($_POST['update_product'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $stock_quantity = $_POST['stock_quantity'];
        $image_url = $_POST['image_url'];
        
        $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, category_id=?, stock_quantity=?, image_url=? WHERE id=?");
        $stmt->execute([$name, $description, $price, $category_id, $stock_quantity, $image_url, $id]);
        
        $_SESSION['message'] = 'Product updated successfully!';
        header('Location: products.php');
        exit;
    } elseif (isset($_POST['delete_product'])) {
        $id = $_POST['id'];
        
        $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
        $stmt->execute([$id]);
        
        $_SESSION['message'] = 'Product deleted successfully!';
        header('Location: products.php');
        exit;
    }
}

// Get all products
$products = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id")->fetchAll(PDO::FETCH_ASSOC);

// Get categories for dropdown
$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Product Management</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Add New Product</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="price" class="form-label">Price</label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-control" id="category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="stock_quantity" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required>
                            </div>
                            <div class="mb-3">
                                <label for="image_url" class="form-label">Image URL</label>
                                <input type="url" class="form-control" id="image_url" name="image_url" placeholder="https://example.com/image.jpg">
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>All Products</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td><img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>" width="50"></td>
                                    <td><?php echo $product['name']; ?></td>
                                    <td><?php echo $product['category_name'] ?? 'Uncategorized'; ?></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo $product['stock_quantity']; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editProductModal<?php echo $product['id']; ?>">Edit</button>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteProductModal<?php echo $product['id']; ?>">Delete</button>
                                    </td>
                                </tr>
                                
                                <!-- Edit Product Modal -->
                                <div class="modal fade" id="editProductModal<?php echo $product['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Product</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="edit_name<?php echo $product['id']; ?>" class="form-label">Product Name</label>
                                                                <input type="text" class="form-control" id="edit_name<?php echo $product['id']; ?>" name="name" value="<?php echo $product['name']; ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_description<?php echo $product['id']; ?>" class="form-label">Description</label>
                                                                <textarea class="form-control" id="edit_description<?php echo $product['id']; ?>" name="description" rows="3" required><?php echo $product['description']; ?></textarea>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_price<?php echo $product['id']; ?>" class="form-label">Price</label>
                                                                <input type="number" step="0.01" class="form-control" id="edit_price<?php echo $product['id']; ?>" name="price" value="<?php echo $product['price']; ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="edit_category_id<?php echo $product['id']; ?>" class="form-label">Category</label>
                                                                <select class="form-control" id="edit_category_id<?php echo $product['id']; ?>" name="category_id">
                                                                    <option value="">Select Category</option>
                                                                    <?php foreach ($categories as $category): ?>
                                                                        <option value="<?php echo $category['id']; ?>" <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                                            <?php echo $category['name']; ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_stock_quantity<?php echo $product['id']; ?>" class="form-label">Stock Quantity</label>
                                                                <input type="number" class="form-control" id="edit_stock_quantity<?php echo $product['id']; ?>" name="stock_quantity" value="<?php echo $product['stock_quantity']; ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_image_url<?php echo $product['id']; ?>" class="form-label">Image URL</label>
                                                                <input type="url" class="form-control" id="edit_image_url<?php echo $product['id']; ?>" name="image_url" value="<?php echo $product['image_url']; ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" name="update_product" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Delete Product Modal -->
                                <div class="modal fade" id="deleteProductModal<?php echo $product['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Delete</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to delete the product "<?php echo $product['name']; ?>"?</p>
                                            </div>
                                            <div class="modal-footer">
                                                <form method="POST">
                                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="delete_product" class="btn btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>