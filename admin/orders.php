<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Fetch orders from database
$orders = [
    [
        'id' => 'ORD-7852',
        'customer' => 'John Doe',
        'date' => '2023-06-15',
        'amount' => '$1,245.99',
        'status' => 'Completed',
        'items' => 3
    ],
    [
        'id' => 'ORD-7851',
        'customer' => 'Jane Smith',
        'date' => '2023-06-14',
        'amount' => '$899.50',
        'status' => 'Processing',
        'items' => 2
    ],
    [
        'id' => 'ORD-7850',
        'customer' => 'Robert Johnson',
        'date' => '2023-06-14',
        'amount' => '$2,450.00',
        'status' => 'Shipped',
        'items' => 1
    ],
    [
        'id' => 'ORD-7849',
        'customer' => 'Emily Davis',
        'date' => '2023-06-13',
        'amount' => '$545.75',
        'status' => 'Cancelled',
        'items' => 4
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - TechShop Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-content">
            <h2 class="mb-4">Order Management</h2>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">All Orders</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered admin-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td><?php echo $order['customer']; ?></td>
                                    <td><?php echo $order['date']; ?></td>
                                    <td><?php echo $order['items']; ?></td>
                                    <td><?php echo $order['amount']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            switch($order['status']) {
                                                case 'Completed': echo 'success'; break;
                                                case 'Processing': echo 'warning'; break;
                                                case 'Shipped': echo 'info'; break;
                                                case 'Cancelled': echo 'danger'; break;
                                                default: echo 'secondary';
                                            }
                                        ?> badge-status"><?php echo $order['status']; ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary">View</button>
                                        <button class="btn btn-sm btn-info">Edit</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>