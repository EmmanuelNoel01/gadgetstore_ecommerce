<?php
/**
 * E-commerce Helper Functions
 */

// Redirect to a specific page
function redirect($url)
{
    header("Location: $url");
    exit;
}

// Sanitize input data
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Check if user is logged in and redirect if not
function requireLogin()
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        $_SESSION['error'] = "Please log in to access this page.";
        redirect('login.php');
    }
}

// Check if user is admin and redirect if not
function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error'] = "You don't have permission to access this page.";
        redirect('index.php');
    }
}

// Format price
function formatPrice($price)
{
    return 'UGX. ' . number_format($price);
}

// Add product to cart with session management
// function addToCart($productId, $quantity = 1) {
//     if (!isset($_SESSION['cart'])) {
//         $_SESSION['cart'] = [];
//     }

//     if (isset($_SESSION['cart'][$productId])) {
//         $_SESSION['cart'][$productId] += $quantity;
//     } else {
//         $_SESSION['cart'][$productId] = $quantity;
//     }

//     // Update cart count in session
//     updateCartCount();
// }

function addToCart($product_id, $quantity) {
    global $conn;
    
    $sql = "SELECT p.id, p.name, p.price, p.image_url, 
                   COALESCE(pi.image_url, p.image_url) as display_image
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.sort_order = 0
            WHERE p.id = $product_id
            LIMIT 1";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image_url' => $product['display_image'] ?: $product['image_url'],
                'quantity' => $quantity
            ];
        }
        
        updateCartCount();
        return true;
    }
    
    return false;
}


function removeFromCart($productId)
{
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        updateCartCount();
        return true;
    }
    return false;
}

// Update cart quantity
// function updateCartQuantity($productId, $quantity) {
//     if ($quantity <= 0) {
//         return removeFromCart($productId);
//     }

//     if (isset($_SESSION['cart'][$productId])) {
//         $_SESSION['cart'][$productId] = $quantity;
//         updateCartCount();
//         return true;
//     }
//     return false;
// }

function updateCartQuantity($product_id, $quantity)
{
    if (isset($_SESSION['cart'][$product_id])) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } else {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        }
        updateCartCount();
        return true;
    }
    return false;
}


// Update cart count in session
function updateCartCount() {
    $count = 0;
    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if (is_array($item) && isset($item['quantity'])) {
                $count += $item['quantity'];
            }
        }
    }
    $_SESSION['cart_count'] = $count;
    return $count;
}



// Get cart items with product details
// function getCartItems() {
//     if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
//         return [];
//     }

//     // This would typically fetch product details from database
//     // For now, we'll return the session cart data
//     $cartItems = [];
//     foreach ($_SESSION['cart'] as $productId => $quantity) {
//         $cartItems[] = [
//             'product_id' => $productId,
//             'quantity' => $quantity
//         ];
//     }

//     return $cartItems;
// }
function getCartItems() {
    $cart = $_SESSION['cart'] ?? [];
    $items = [];
    foreach ($cart as $item) {
        $items[] = [
            'product' => $item,
            'quantity' => $item['quantity'] ?? 1
        ];
    }
    return $items;
}


// Get cart total
function getCartTotal() {
    $cart = getCartItems();
    $total = 0;
    foreach ($cart as $item) {
        $price = $item['product']['price'] ?? 0;
        $quantity = $item['quantity'] ?? 1;
        $total += $price * $quantity;
    }
    return $total;
}


// Clear cart
function clearCart()
{
    unset($_SESSION['cart']);
    updateCartCount();
}

// Flash message system for notifications
function flash($name = '', $message = '', $class = 'alert alert-success')
{
    if (!empty($name)) {
        if (!empty($message) && empty($_SESSION[$name])) {
            $_SESSION[$name] = $message;
            $_SESSION[$name . '_class'] = $class;
        } elseif (empty($message) && !empty($_SESSION[$name])) {
            $class = !empty($_SESSION[$name . '_class']) ? $_SESSION[$name . '_class'] : '';
            echo '<div class="' . $class . '" id="msg-flash">' . $_SESSION[$name] . '</div>';
            unset($_SESSION[$name]);
            unset($_SESSION[$name . '_class']);
        }
    }
}

// CSRF token generation and validation
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Login function
function loginUser($user)
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in'] = time();

    // Regenerate session ID for security
    session_regenerate_id(true);
}

// Logout function
function logoutUser()
{
    // Unset all session variables
    $_SESSION = [];

    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destroy session
    session_destroy();

    // Redirect to home page
    redirect('index.php');
}

// Check if user is logged in and has admin role
// function isAdmin() {
//     return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
// }

function getCartTotalItems()
{
    $total = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if (is_array($item)) {
                $total += $item['quantity'];
            }
        }
    }
    return $total;
}

function getCartTotalPrice()
{
    $total = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if (is_array($item)) {
                $total += $item['price'] * $item['quantity'];
            }
        }
    }
    return $total;
}

function encryptData($data, $key) {
    $method = 'aes-256-cbc';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
    $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function decryptData($data, $key) {
    $method = 'aes-256-cbc';
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encrypted_data, $method, $key, 0, $iv);
}

function generateEncryptionKey() {
    return bin2hex(random_bytes(32));
}

?>