-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 08, 2025 at 08:12 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(100) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `created_at`, `category`, `stock`, `image_url`) VALUES
(4, 'Edgar Kasirye', NULL, 500.00, '2025-09-05 20:51:35', 'Electronics', 10, NULL),
(5, 'Emenyu Joel', NULL, 500.00, '2025-09-05 20:51:42', 'Electronics', 10, NULL),
(7, 'Smartphone X', 'Latest model with high-resolution camera and fast processor.', 499.99, '2025-09-06 14:04:52', 'Electronics', 15, NULL),
(8, 'Laptop Pro 15', 'Powerful laptop for professionals and gaming.', 1299.50, '2025-09-06 14:04:52', 'Electronics', 8, NULL),
(9, 'Wireless Headphones', 'Noise-cancelling over-ear headphones.', 199.99, '2025-09-06 14:04:52', 'Electronics', 20, NULL),
(10, 'Smartwatch Z', 'Water-resistant smartwatch with fitness tracking.', 149.99, '2025-09-06 14:04:52', 'Electronics', 12, NULL),
(11, 'Tablet Plus', 'High-resolution tablet perfect for media and work.', 349.00, '2025-09-06 14:04:52', 'Electronics', 10, NULL),
(12, 'Gaming Console Y', 'Next-gen gaming console with 4K support.', 599.00, '2025-09-06 14:04:52', 'Gaming', 5, NULL),
(13, 'Bluetooth Speaker', 'Portable speaker with excellent sound quality.', 89.99, '2025-09-06 14:04:52', 'Electronics', 25, NULL),
(14, 'Fitness Tracker', 'Tracks heart rate, steps, and sleep patterns.', 79.99, '2025-09-06 14:04:52', 'Fitness', 30, NULL),
(15, '4K TV 55\"', 'Ultra HD smart TV with streaming apps.', 699.00, '2025-09-06 14:04:52', 'Electronics', 7, NULL),
(16, 'Digital Camera', 'DSLR camera with 24MP sensor and lens kit.', 849.99, '2025-09-06 14:04:52', 'Photography', 6, NULL),
(17, 'iPhone 15 Pro', 'Latest Apple smartphone with A17 Bionic chip.', 1199.00, '2025-09-06 19:20:36', 'Electronics', 12, NULL),
(18, 'Samsung Galaxy S23', 'Flagship Android phone with powerful performance.', 999.00, '2025-09-06 19:20:36', 'Electronics', 15, NULL),
(19, 'HP Pavilion 14', 'Lightweight laptop for students and professionals.', 749.00, '2025-09-06 19:20:36', 'Electronics', 10, NULL),
(20, 'Dell XPS 13', 'Premium ultrabook with sleek design.', 1399.00, '2025-09-06 19:20:36', 'Electronics', 8, NULL),
(21, 'Lenovo ThinkPad X1', 'Business laptop with long battery life.', 1299.00, '2025-09-06 19:20:36', 'Electronics', 6, NULL),
(22, 'Logitech MX Master 3', 'Advanced wireless mouse for productivity.', 99.99, '2025-09-06 19:20:36', 'Accessories', 20, NULL),
(23, 'Mechanical Keyboard', 'RGB backlit keyboard for gaming and work.', 79.99, '2025-09-06 19:20:36', 'Accessories', 25, NULL),
(24, 'External Hard Drive 1TB', 'Portable storage with fast USB 3.0.', 59.99, '2025-09-06 19:20:36', 'Accessories', 18, NULL),
(25, 'USB-C Charger 65W', 'Fast charging adapter for laptops and phones.', 39.99, '2025-09-06 19:20:36', 'Accessories', 30, NULL),
(26, 'Laptop Backpack', 'Water-resistant backpack with laptop compartment.', 49.99, '2025-09-06 19:20:36', 'Accessories', 22, NULL),
(27, 'PlayStation 5', 'Sony next-gen gaming console with SSD.', 599.00, '2025-09-06 19:20:36', 'Gaming', 5, NULL),
(28, 'Xbox Series X', 'Microsoft flagship gaming console.', 549.00, '2025-09-06 19:20:36', 'Gaming', 7, NULL),
(29, 'Nintendo Switch OLED', 'Hybrid handheld and TV console.', 349.00, '2025-09-06 19:20:36', 'Gaming', 12, NULL),
(30, 'Gaming Chair', 'Ergonomic chair for long gaming sessions.', 199.00, '2025-09-06 19:20:36', 'Gaming', 9, NULL),
(31, 'Razer Gaming Headset', 'Surround sound headset with microphone.', 129.00, '2025-09-06 19:20:36', 'Gaming', 15, NULL),
(32, 'Microwave Oven', 'Compact microwave with grill function.', 149.00, '2025-09-06 19:20:36', 'Home Appliances', 10, NULL),
(33, 'Air Fryer', 'Healthy cooking with little to no oil.', 129.00, '2025-09-06 19:20:36', 'Home Appliances', 12, NULL),
(34, 'Vacuum Cleaner', 'Powerful vacuum with HEPA filter.', 179.00, '2025-09-06 19:20:36', 'Home Appliances', 8, NULL),
(35, 'Smart Air Purifier', 'Cleans air and connects with mobile app.', 120000.00, '2025-09-06 19:20:36', 'Laptops', 10, 'https://plus.unsplash.com/premium_photo-1690291494818-068ed0f63c42?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'),
(36, 'Electric Kettle', '1.5L stainless steel fast boiling kettle.', 39.99, '2025-09-06 19:20:36', 'Laptops', 20, 'https://images.unsplash.com/photo-1643114786355-ff9e52736eab?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `encryption_key` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `username`, `password`, `is_admin`, `encryption_key`, `created_at`) VALUES
(1, 'RUTAHIGWA EMMANUEL NOEL', 'rutahigwaemmanuelnoel@gmail.com', 'EmmanuelNoel', '$2y$10$r4bF6ztmNUcYAwmvHjZC2O0wetjlgndGDRyipfQB4SR/00RN2Q8Tq', 0, '', '2025-09-06 21:24:02'),
(2, 'philip', 'noel@gmail.com', 'deo', 'WHlrNmVYVFZPWXhjMFBGZEJVelBjZz09OjpDv3SrurLaWj99xUD88tqk', 1, '32db2481a16ebea36030e7289c40afbe7be9eec8b0c19f44e14f8eecc5345eda', '2025-09-06 21:36:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD UNIQUE KEY `unique_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
