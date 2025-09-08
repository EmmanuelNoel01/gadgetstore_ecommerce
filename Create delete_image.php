<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['image_id'])) {
        $image_id = (int)$_POST['image_id'];
        
        // Check if the image exists
        $check_stmt = $conn->prepare("SELECT * FROM product_images WHERE id = ?");
        $check_stmt->bind_param("i", $image_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Delete the image
            $delete_stmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
            $delete_stmt->bind_param("i", $image_id);
            
            if ($delete_stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
            
            $delete_stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Image not found']);
        }
        
        $check_stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Image ID not provided']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>