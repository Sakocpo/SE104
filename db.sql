-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 12, 2025 at 07:33 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `users_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `critical_level` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `options`
--

CREATE TABLE `options` (
  `id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL,
  `type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `options`
--

INSERT INTO `options` (`id`, `label`, `type_id`) VALUES
(1, 'Đá', 1),
(2, 'Nóng', 1),
(3, 'Ngọt', 2),
(4, 'Đắng', 2),
(5, 'Bình Thường', 2);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `status` enum('pending','served','paid') NOT NULL DEFAULT 'pending',
  `paid_amount` decimal(10,0) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `table_id`, `status`, `paid_amount`, `created_at`) VALUES
(27, 2, 'served', 0, '2025-05-05 20:55:52'),
(28, 2, 'served', 0, '2025-05-05 20:57:26'),
(36, 2, 'served', 0, '2025-05-05 18:55:19'),
(43, 7, 'served', 0, '2025-05-05 19:32:39'),
(44, 2, 'served', 0, '2025-05-05 19:33:36'),
(47, 7, 'served', 0, '2025-05-05 22:29:10'),
(49, 2, 'pending', 0, '2025-05-05 23:10:24'),
(50, 2, 'pending', 0, '2025-05-05 23:24:53'),
(51, 2, 'pending', 0, '2025-05-05 23:25:05'),
(52, 2, 'pending', 0, '2025-05-05 23:25:24'),
(53, 2, 'pending', 0, '2025-05-05 23:32:52'),
(55, 6, 'pending', 0, '2025-05-05 23:33:49'),
(57, 6, 'pending', 0, '2025-05-05 23:34:58'),
(58, 6, 'pending', 0, '2025-05-05 23:35:06'),
(60, 6, 'pending', 0, '2025-05-05 23:36:42'),
(61, 6, 'pending', 0, '2025-05-05 23:36:49'),
(62, 6, 'pending', 0, '2025-05-05 23:37:35'),
(63, 6, 'pending', 0, '2025-05-05 23:37:41'),
(65, 2, 'pending', 0, '2025-05-05 23:38:25'),
(66, 2, 'pending', 0, '2025-05-05 23:38:32'),
(68, 6, 'pending', 0, '2025-05-05 20:00:10'),
(76, 2, 'paid', 15, '2025-05-06 20:08:35'),
(78, 6, 'paid', 85, '2025-05-06 21:32:05'),
(79, 6, 'pending', 0, '2025-05-09 23:17:56');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `options` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `gift` tinyint(1) NOT NULL DEFAULT 0,
  `served` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `options`, `quantity`, `gift`, `served`) VALUES
(33, 27, 5, '1,3', 2, 0, 1),
(34, 27, 4, '1,4', 1, 0, 1),
(35, 28, 5, '1,3', 6, 0, 1),
(36, 28, 4, '2,3', 1, 0, 1),
(46, 36, 5, '1,3', 1, 0, 1),
(54, 43, 5, '1,3', 1, 0, 1),
(55, 43, 4, '1,3', 1, 0, 1),
(56, 43, 5, '1,3', 4, 0, 1),
(57, 44, 5, '1,3', 1, 0, 1),
(58, 47, 5, '1,3', 4, 0, 1),
(59, 47, 4, '1,3', 1, 0, 1),
(61, 49, 5, '1,3', 5, 0, 1),
(62, 50, 4, '1,3', 1, 0, 1),
(63, 51, 5, '1,3', 1, 0, 1),
(64, 52, 4, '1,3', 9, 0, 1),
(65, 53, 4, '1,3', 1, 0, 1),
(67, 55, 5, '1,3', 1, 0, 1),
(68, 55, 5, '1,3', 1, 0, 1),
(69, 55, 4, '1,3', 1, 0, 1),
(70, 55, 4, '1,3', 1, 0, 1),
(73, 57, 5, '1,3', 8, 0, 1),
(74, 58, 4, '1,3', 4, 0, 1),
(76, 60, 5, '1,3', 6, 0, 1),
(77, 60, 4, '1,3', 2, 0, 1),
(78, 61, 4, '1,3', 3, 0, 1),
(79, 62, 4, '1,3', 1, 0, 1),
(80, 62, 5, '1,3', 1, 0, 1),
(81, 63, 4, '1,3', 1, 0, 1),
(83, 65, 5, '1,3', 1, 0, 1),
(84, 65, 5, '1,3', 1, 0, 1),
(85, 66, 4, '1,3', 1, 0, 1),
(87, 68, 5, '1,3', 5, 0, 1),
(88, 68, 4, '1,3', 5, 0, 1),
(103, 76, 5, '1,3', 1, 0, 1),
(105, 78, 4, '2,3', 5, 0, 1),
(106, 79, 4, '1,4', 3, 0, 1),
(107, 79, 5, '1,5', 1, 0, 1),
(108, 79, 4, '1,4', 1, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `options` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `active` tinyint(4) DEFAULT 1,
  `image` varchar(255) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `price`, `options`, `description`, `active`, `image`) VALUES
(4, 'Cà phê sữa', '1', 17.00, '2', '', 1, ''),
(5, 'Cà phê đá', '1', 15.00, '2', '', 1, 'uploads/damaged_0000.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `id` int(11) NOT NULL,
  `table_name` varchar(255) NOT NULL,
  `table_category` int(11) NOT NULL,
  `table_desc` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `occupied` tinyint(1) NOT NULL DEFAULT 0,
  `current_order_id` int(11) DEFAULT NULL,
  `status` enum('empty','occupied','served') NOT NULL DEFAULT 'empty',
  `deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tables`
--

INSERT INTO `tables` (`id`, `table_name`, `table_category`, `table_desc`, `active`, `occupied`, `current_order_id`, `status`) VALUES
(2, '2', 4, '', 1, 1, NULL, 'occupied'),
(6, '1', 4, '', 1, 1, 79, 'occupied'),
(7, '3', 5, '', 1, 1, NULL, 'occupied');

-- --------------------------------------------------------

--
-- Table structure for table `table_categories`
--

CREATE TABLE `table_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `table_categories`
--

INSERT INTO `table_categories` (`id`, `name`) VALUES
(4, 'Ngoài sân'),
(5, 'Trong Nhà');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('waiter','admin','kitchen') NOT NULL,
  `user_category` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `user_category`, `image`, `created_at`) VALUES
(3, 'admin', '$2y$10$ush4YWILoz5Cae2uBpZOxOBOSjewy98gFNLcy4vBd4bcw8ctaJFpy', 'admin', '', '', '2025-04-23 13:28:11'),
(4, 'test_waiter', '$2y$10$8por6PynMfwASOV4gwLTvuQQeLMdfeqi04lWd44KeyKzpActeNeBy', 'waiter', '', '', '2025-04-23 14:31:29'),
(5, 'kitchen', '$2y$10$KPrj/up6x5r0aJKVVU4B9.YlPqiPstY6NhtJ5CKD0FIOEO/QmJjZq', 'kitchen', '', '', '2025-04-27 03:19:53');

-- --------------------------------------------------------

--
-- Table structure for table `user_categories`
--

CREATE TABLE `user_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `options`
--
ALTER TABLE `options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `table_id` (`table_id`);

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
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`table_category`),
  ADD KEY `fk_tables_current_order` (`current_order_id`);

--
-- Indexes for table `table_categories`
--
ALTER TABLE `table_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_categories`
--
ALTER TABLE `user_categories`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `options`
--
ALTER TABLE `options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `table_categories`
--
ALTER TABLE `table_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_categories`
--
ALTER TABLE `user_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `options`
--
ALTER TABLE `options`
  ADD CONSTRAINT `options_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `option_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `tables`
--
ALTER TABLE `tables`
  ADD CONSTRAINT `fk_tables_current_order` FOREIGN KEY (`current_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tables_ibfk_1` FOREIGN KEY (`table_category`) REFERENCES `table_categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
