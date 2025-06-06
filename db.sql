-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 06, 2025 at 05:55 AM
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
-- Table structure for table `daily_reports`
--

CREATE TABLE `daily_reports` (
  `id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_report_items`
--

CREATE TABLE `daily_report_items` (
  `report_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `revenue` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ingredients`
--

CREATE TABLE `ingredients` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ingredients`
--

INSERT INTO `ingredients` (`id`, `name`, `category`, `unit_id`, `quantity`, `image`, `deleted`) VALUES
(1, 'Sữa Đặc', 1, 1, 5.00, '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `ingredient_categories`
--

CREATE TABLE `ingredient_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ingredient_categories`
--

INSERT INTO `ingredient_categories` (`id`, `name`) VALUES
(1, 'Diary');

-- --------------------------------------------------------

--
-- Table structure for table `ingredient_logs`
--

CREATE TABLE `ingredient_logs` (
  `id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `change_amount` decimal(10,2) NOT NULL,
  `before_qty` decimal(10,2) NOT NULL,
  `after_qty` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ingredient_logs`
--

INSERT INTO `ingredient_logs` (`id`, `ingredient_id`, `user_id`, `change_amount`, `before_qty`, `after_qty`, `created_at`) VALUES
(1, 1, 5, 2.00, 7.00, 5.00, '2025-06-05 11:33:30');

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
(15, 'g', 1, 2),
(16, 'Đá', 1, 1),
(17, 'Đá', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `option_categories`
--

CREATE TABLE `option_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `option_categories`
--

INSERT INTO `option_categories` (`id`, `name`) VALUES
(1, 'Nhiệt độ'),
(2, 'Đường');

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
(43, 7, 'served', 0, '2025-05-05 19:32:39', 'cash', NULL, '0000-00-00 00:00:00', 0),
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
(502, 6, 'deleted', 0, '2025-06-06 10:39:10', 'cash', 4, '2025-06-06 10:39:10', 0),
(503, 6, 'deleted', 0, '2025-06-06 10:39:24', 'cash', 4, '2025-06-06 10:39:24', 0),
(504, 2, 'deleted', 0, '2025-06-06 10:42:34', 'cash', 4, '2025-06-06 10:42:34', 0),
(505, 2, 'deleted', 0, '2025-06-06 10:42:51', 'cash', 4, '2025-06-06 10:42:51', 0),
(506, 6, 'deleted', 0, '2025-06-06 10:43:03', 'cash', 4, '2025-06-06 10:43:03', 0),
(507, 6, 'paid', 30, '2025-06-06 10:53:21', 'cash', 4, '2025-06-06 10:53:33', 0);

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
(666, 459, 7, '[\"2\"]', 1, 0, 1, 0),
(667, 460, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(668, 460, 7, '[\"2\"]', 1, 0, 1, 0),
(669, 461, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(670, 461, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(671, 461, 7, '[\"2\"]', 1, 0, 1, 0),
(672, 462, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(673, 462, 7, '[\"2\"]', 1, 0, 1, 0),
(674, 463, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(675, 463, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(676, 463, 7, '[\"2\"]', 1, 0, 1, 0),
(677, 464, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(678, 464, 7, '[\"2\"]', 1, 0, 1, 0),
(679, 465, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(680, 465, 7, '[\"2\"]', 1, 0, 1, 0),
(681, 466, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(682, 466, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(683, 467, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(684, 467, 7, '[\"2\"]', 1, 0, 1, 0),
(685, 468, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(686, 468, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(687, 469, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(688, 469, 7, '[\"2\"]', 1, 0, 1, 0),
(689, 470, 5, '[\"2\",\"5\"]', 4, 0, 1, 0),
(690, 470, 7, '[\"2\"]', 1, 0, 1, 0),
(691, 470, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(692, 470, 4, '[\"2\",\"3\"]', 1, 0, 1, 0),
(693, 471, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(694, 471, 7, '[\"2\"]', 1, 0, 1, 0),
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
(749, 499, 7, '[\"2\"]', 1, 0, 1, 0),
(750, 499, 5, '[\"2\",\"3\"]', 1, 0, 1, 0),
(751, 500, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(752, 500, 7, '[\"2\"]', 1, 0, 1, 0),
(753, 501, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(754, 501, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(755, 502, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(756, 502, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(757, 503, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(758, 504, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(759, 504, 4, '[\"2\",\"5\"]', 2, 0, 1, 0),
(760, 505, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(761, 505, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(762, 506, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(763, 506, 4, '[\"2\",\"5\"]', 1, 0, 1, 0),
(764, 507, 5, '[\"2\",\"5\"]', 1, 0, 1, 0),
(765, 507, 5, '[\"2\",\"5\"]', 1, 0, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

CREATE TABLE `payment_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `name`) VALUES
(1, 'Cà phê'),
(2, 'Trà');

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
(2, '2', 4, '', 1, 1, NULL, 'occupied', 0),
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
-- Table structure for table `unit_options`
--

CREATE TABLE `unit_options` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `unit_options`
--

INSERT INTO `unit_options` (`id`, `name`) VALUES
(1, 'hộp'),
(2, 'kg');

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
-- Indexes for table `daily_reports`
--
ALTER TABLE `daily_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_date` (`report_date`);

--
-- Indexes for table `daily_report_items`
--
ALTER TABLE `daily_report_items`
  ADD PRIMARY KEY (`report_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `ingredient_categories`
--
ALTER TABLE `ingredient_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ingredient_logs`
--
ALTER TABLE `ingredient_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ingredient_id` (`ingredient_id`),
  ADD KEY `user_id` (`user_id`);

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
-- Indexes for table `option_categories`
--
ALTER TABLE `option_categories`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
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
-- Indexes for table `unit_options`
--
ALTER TABLE `unit_options`
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
-- AUTO_INCREMENT for table `daily_reports`
--
ALTER TABLE `daily_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ingredient_categories`
--
ALTER TABLE `ingredient_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ingredient_logs`
--
ALTER TABLE `ingredient_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `options`
--
ALTER TABLE `options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `option_categories`
--
ALTER TABLE `option_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=508;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=766;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- AUTO_INCREMENT for table `unit_options`
--
ALTER TABLE `unit_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- Constraints for table `daily_report_items`
--
ALTER TABLE `daily_report_items`
  ADD CONSTRAINT `daily_report_items_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `daily_reports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `daily_report_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD CONSTRAINT `ingredients_ibfk_1` FOREIGN KEY (`category`) REFERENCES `ingredient_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ingredients_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `unit_options` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ingredient_logs`
--
ALTER TABLE `ingredient_logs`
  ADD CONSTRAINT `ingredient_logs_ibfk_1` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`),
  ADD CONSTRAINT `ingredient_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

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
