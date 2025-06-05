-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 05, 2025 at 03:05 PM
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
  `deleted` tinyint(1) NOT NULL,
  `type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `options`
--

INSERT INTO `options` (`id`, `label`, `deleted`, `type_id`) VALUES
(1, 'Đá', 0, 1),
(2, 'Nóng', 0, 1),
(3, 'Ngọt', 0, 2),
(4, 'Đắng', 0, 2),
(5, 'Bình Thường', 0, 2),
(6, 'a', 1, 2),
(7, 'b', 1, 2),
(8, 'c', 1, 2),
(9, 'a', 1, 2),
(10, 'b', 1, 2),
(11, 'c', 1, 2),
(12, 'd', 1, 2),
(13, 'e', 1, 2),
(14, 'f', 1, 2),
(15, 'g', 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `status` enum('pending','served','paid','deleted') NOT NULL DEFAULT 'pending',
  `paid_amount` decimal(10,0) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `method` enum('cash','qr') NOT NULL DEFAULT 'cash',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `charged_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `table_id`, `status`, `paid_amount`, `created_at`, `method`, `created_by`, `charged_at`, `deleted`) VALUES
(27, 2, 'served', 0, '2025-05-05 20:55:52', 'cash', NULL, '0000-00-00 00:00:00', 0),
(28, 2, 'served', 0, '2025-05-05 20:57:26', 'cash', NULL, '0000-00-00 00:00:00', 0),
(36, 2, 'served', 0, '2025-05-05 18:55:19', 'cash', NULL, '0000-00-00 00:00:00', 0),
(471, 6, 'paid', 25, '2025-06-03 16:54:15', 'cash', 4, '2025-06-03 16:54:19', 0),
(472, 2, 'deleted', 0, '2025-06-03 17:04:44', 'cash', 4, '2025-06-03 17:04:44', 0),
(473, 2, 'paid', 10, '2025-06-03 17:05:03', 'cash', 4, '2025-06-03 19:41:52', 0),
(474, 6, 'deleted', 0, '2025-06-03 19:34:22', 'cash', 4, '2025-06-03 19:34:22', 0),
(475, 9, 'deleted', 0, '2025-06-03 19:34:37', 'cash', 4, '2025-06-03 19:34:37', 0),
(476, 7, 'paid', 32, '2025-06-03 19:34:45', 'cash', 4, '2025-06-03 19:35:03', 0),
(477, 7, 'deleted', 0, '2025-06-03 19:35:16', 'cash', 4, '2025-06-03 19:35:16', 0),
(478, 9, 'deleted', 0, '2025-06-03 19:36:18', 'cash', 4, '2025-06-03 19:36:18', 0),
(479, 9, 'paid', 32, '2025-06-03 19:36:35', 'cash', 4, '2025-06-03 19:41:56', 0),
(480, 6, 'deleted', 0, '2025-06-03 19:36:55', 'cash', 4, '2025-06-03 19:36:55', 0),
(481, 6, 'deleted', 0, '2025-06-03 19:37:16', 'cash', 4, '2025-06-03 19:37:16', 0),
(482, 6, 'deleted', 0, '2025-06-03 19:37:27', 'cash', 4, '2025-06-03 19:37:27', 0),
(483, 6, 'deleted', 0, '2025-06-03 19:38:20', 'cash', 4, '2025-06-03 19:38:20', 0),
(484, 6, 'deleted', 0, '2025-06-03 19:41:07', 'cash', 4, '2025-06-03 19:41:07', 0),
(485, 6, 'paid', 32, '2025-06-03 19:41:42', 'cash', 4, '2025-06-03 19:41:54', 0),
(486, 9, 'paid', 32, '2025-06-03 19:42:00', 'cash', 4, '2025-06-03 19:54:20', 0),
(487, 6, 'paid', 32, '2025-06-03 19:42:08', 'cash', 4, '2025-06-03 19:54:44', 0),
(488, 6, 'paid', 25, '2025-06-03 19:55:36', 'cash', 4, '2025-06-03 19:55:49', 0),
(489, 9, 'paid', 32, '2025-06-03 19:59:41', 'cash', 4, '2025-06-03 19:59:46', 0),
(490, 7, 'paid', 32, '2025-06-03 20:01:30', 'cash', 4, '2025-06-03 20:01:43', 0),
(491, 6, 'paid', 32, '2025-06-03 20:05:44', 'cash', 4, '2025-06-03 20:06:12', 0),
(492, 6, 'paid', 32, '2025-06-03 20:07:15', 'cash', 4, '2025-06-03 20:07:22', 0),
(493, 6, 'paid', 32, '2025-06-03 20:08:59', 'cash', 4, '2025-06-03 20:09:08', 0),
(494, 6, 'paid', 32, '2025-06-04 19:50:07', 'cash', 4, '2025-06-05 15:41:36', 0),
(495, 2, 'paid', 32, '2025-06-04 19:52:50', 'cash', 4, '2025-06-04 19:57:18', 0),
(496, 9, 'paid', 25, '2025-06-04 19:53:37', 'cash', 4, '2025-06-05 15:41:44', 0);

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
  `served` tinyint(1) NOT NULL DEFAULT 0,
  `deleted` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `options`, `quantity`, `gift`, `served`, `deleted`) VALUES
(33, 27, 5, '1,3', 2, 0, 1, 0),
(34, 27, 4, '1,4', 1, 0, 1, 0),
(35, 28, 5, '1,3', 6, 0, 1, 0),
(36, 28, 4, '2,3', 1, 0, 1, 0),
(46, 36, 5, '1,3', 1, 0, 1, 0),
(54, 43, 5, '1,3', 1, 0, 1, 0),
(55, 43, 4, '1,3', 1, 0, 1, 0),
(56, 43, 5, '1,3', 4, 0, 1, 0),
(57, 44, 5, '1,3', 1, 0, 1, 0),
(58, 47, 5, '1,3', 4, 0, 1, 0),
(695, 472, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(696, 473, 7, '[\"2\"]', 1, 0, 1, 0),
(697, 474, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(698, 474, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(699, 474, 7, '[\"2\"]', 1, 0, 1, 0),
(700, 475, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(701, 475, 7, '[\"2\"]', 1, 0, 1, 0),
(702, 476, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(703, 476, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(704, 477, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(705, 477, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(706, 478, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(707, 478, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(708, 479, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(709, 479, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(710, 480, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(711, 480, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(712, 481, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(713, 481, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(714, 482, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(715, 483, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(716, 484, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(717, 484, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(718, 485, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(719, 485, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(720, 486, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(721, 486, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(722, 487, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(723, 487, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(724, 488, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(725, 488, 7, '[\"2\"]', 1, 0, 1, 0),
(726, 489, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(727, 489, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(728, 490, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(729, 490, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(730, 491, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(731, 491, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(732, 492, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(733, 492, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(734, 493, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(735, 493, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(736, 494, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(737, 494, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(738, 495, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(739, 495, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(740, 496, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(741, 496, 7, '[\"2\"]', 1, 0, 1, 0);

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
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(4) DEFAULT 1,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `price`, `options`, `description`, `deleted`, `active`, `image`) VALUES
(4, 'Cà phê sữa', '1', 17.00, '2', '', 0, 1, 'uploads/Nội dung đoạn văn bản của bạn.png'),
(5, 'Cà phê đá ', '1', 15.00, '2', '', 0, 1, 'uploads/images-removebg-preview.png'),
(7, 'cf', '1', 10.00, '', '', 0, 1, ''),
(8, 'bac siu', '1', 10.00, '', '', 1, 1, ''),
(11, 'Trà Đào', '2', 25.00, '', '', 0, 1, '');

-- --------------------------------------------------------

--
-- Table structure for table `product_options`
--

CREATE TABLE `product_options` (
  `product_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_options`
--

INSERT INTO `product_options` (`product_id`, `option_id`) VALUES
(4, 1),
(4, 2),
(4, 3),
(4, 4),
(4, 5),
(5, 1),
(5, 2),
(5, 3),
(5, 4),
(5, 5),
(7, 1),
(7, 2),
(11, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `id` int(11) NOT NULL,
  `table_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci NOT NULL,
  `table_category` int(11) NOT NULL,
  `table_desc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `occupied` tinyint(1) NOT NULL DEFAULT 0,
  `current_order_id` int(11) DEFAULT NULL,
  `status` enum('empty','occupied','served') NOT NULL DEFAULT 'empty',
  `deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tables`
--

INSERT INTO `tables` (`id`, `table_name`, `table_category`, `table_desc`, `active`, `occupied`, `current_order_id`, `status`, `deleted`) VALUES
(2, '2', 4, '', 1, 1, NULL, '', 0),
(6, '1', 4, '', 1, 1, NULL, '', 0),
(7, '3', 5, '', 1, 1, NULL, '', 0),
(9, '4', 5, '', 1, 0, NULL, '', 0);

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
(3, 'admin', '$2y$10$jmiQ0twCIkkgzIDtzPRAXu2RL5cAQAl86uk5D1ybyhRtE5PzDd5l2', 'admin', '', '', '2025-04-23 13:28:11'),
(4, 'test_waiter', '$2y$10$QZpyGGAPdrzXcHfxMsdl9OxvrOp6fUdNWjPRGyxig5tiD.NCzh90G', 'waiter', '', '', '2025-04-23 14:31:29'),
(5, 'kitchen', '$2y$10$XDT0Th6QPV2VFCX88GSiuuQ78ORvn4Qm7ARUpzfcEKYn4PRFLUgu.', 'kitchen', '', '', '2025-04-27 03:19:53');

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
  ADD KEY `table_id` (`table_id`),
  ADD KEY `fk_orders_created_by` (`created_by`);

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
-- Indexes for table `product_options`
--
ALTER TABLE `product_options`
  ADD PRIMARY KEY (`product_id`,`option_id`),
  ADD KEY `option_id` (`option_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=497;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=742;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  ADD CONSTRAINT `fk_orders_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `product_options`
--
ALTER TABLE `product_options`
  ADD CONSTRAINT `fk_prodopt_option` FOREIGN KEY (`option_id`) REFERENCES `options` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prodopt_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

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
