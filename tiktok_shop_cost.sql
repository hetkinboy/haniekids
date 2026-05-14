-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th5 14, 2026 lúc 12:53 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `tiktok_shop_cost`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `migrations`
--

INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES
(1, '2026-05-14-000001', 'App\\Database\\Migrations\\CreateMvpTables', 'default', 'App', 1778728132, 1),
(2, '2026-05-14-000002', 'App\\Database\\Migrations\\CreateAuthTables', 'default', 'App', 1778728665, 2),
(3, '2026-05-14-000003', 'App\\Database\\Migrations\\CreateTiktokSettlementTables', 'default', 'App', 1778741006, 3),
(4, '2026-05-14-000004', 'App\\Database\\Migrations\\AddMainSkuToProducts', 'default', 'App', 1778749412, 4),
(5, '2026-05-14-000005', 'App\\Database\\Migrations\\CreateTiktokIntegrationTables', 'default', 'App', 1778753157, 5),
(6, '2026-05-14-000006', 'App\\Database\\Migrations\\AddTiktokSkuImportFields', 'default', 'App', 1778755254, 6);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `operating_costs`
--

CREATE TABLE `operating_costs` (
  `id` int(10) UNSIGNED NOT NULL,
  `cost_date` date NOT NULL,
  `cost_type` varchar(50) NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `allocation_type` varchar(50) NOT NULL DEFAULT 'manual',
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_code` varchar(80) NOT NULL,
  `platform` varchar(50) NOT NULL DEFAULT 'tiktok',
  `tiktok_order_id` varchar(120) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `order_date` datetime NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `gross_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `platform_fee` decimal(14,2) NOT NULL DEFAULT 0.00,
  `transaction_fee` decimal(14,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` decimal(14,2) NOT NULL DEFAULT 0.00,
  `cod_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `net_revenue` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_profit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `stock_deducted` tinyint(1) NOT NULL DEFAULT 0,
  `stock_returned` tinyint(1) NOT NULL DEFAULT 0,
  `return_fee` decimal(14,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `sku_id` int(10) UNSIGNED NOT NULL,
  `sku_code` varchar(120) NOT NULL,
  `sku_display_name` varchar(255) NOT NULL,
  `size_option_id` int(10) UNSIGNED NOT NULL,
  `size_name` varchar(120) NOT NULL,
  `combo_option_id` int(10) UNSIGNED NOT NULL,
  `combo_name` varchar(120) NOT NULL,
  `combo_quantity` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `stock_quantity_deducted` int(10) UNSIGNED NOT NULL,
  `sale_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `cost_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_sale` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `allocated_fee` decimal(14,2) NOT NULL DEFAULT 0.00,
  `profit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT 'api',
  `last_used_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `user_id`, `token_hash`, `name`, `last_used_at`, `expires_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'd40d7a8707ccc20b7213487748886999545afd81bf06c32ed06aff8e1a5d7e90', 'api', '2026-05-14 03:18:36', '2026-06-13 03:18:36', '2026-05-14 03:18:36', '2026-05-14 03:18:36', NULL),
(2, 2, 'b4a7bb18d2fb41afcbb9f5cac8b579a82c6c0dc9da0debf7fa26daac8f923f50', 'api', '2026-05-14 03:18:36', '2026-06-13 03:18:36', '2026-05-14 03:18:36', '2026-05-14 03:18:36', NULL),
(3, 1, '47cfa81db4aa2fd366a2c3cb2f395245471a1b37fd3c84f7a28626449307f889', 'api', '2026-05-14 03:24:41', '2026-06-13 03:24:40', '2026-05-14 03:24:40', '2026-05-14 03:24:41', NULL),
(4, 2, '741998012a963445671c6d3230cbfdb642744714030877f804e8acfbec7d3286', 'api', '2026-05-14 03:24:41', '2026-06-13 03:24:41', '2026-05-14 03:24:41', '2026-05-14 03:24:41', NULL),
(5, 1, 'edd34b849dd9672acd6847d4461efcca394956a50b57285ef4d5326ba141a617', 'api', '2026-05-14 03:37:18', '2026-06-13 03:37:18', '2026-05-14 03:37:18', '2026-05-14 03:37:18', NULL),
(6, 1, 'c68c361bedc006ccc00d88e3928119baed83cd558ecaf7f28b7fb7b7b1bd6594', 'api', '2026-05-14 03:38:00', '2026-06-13 03:37:59', '2026-05-14 03:37:59', '2026-05-14 03:38:00', NULL),
(7, 1, 'dc216649485e304c9ad117565f38347bf142b9d9fe92f7f5f5362225b823c3a5', 'api', '2026-05-14 03:39:47', '2026-06-13 03:39:47', '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(8, 1, '0a35e22511a9fd10cd53cbcd355eebe58b2f796e7f2ec50ab40fd247a84fff41', 'api', '2026-05-14 03:45:43', '2026-06-13 03:45:42', '2026-05-14 03:45:42', '2026-05-14 03:45:43', NULL),
(9, 1, '604104480be8cce1ee58ec9f04e36a9aba623c935fe63e2fdbed684d673dd0e7', 'api', '2026-05-14 04:04:46', '2026-06-13 04:04:09', '2026-05-14 04:04:09', '2026-05-14 04:04:46', NULL),
(10, 1, '9c9abcd3f00a8e57e561a980ab3b23581a84f9ed1cac2995fc134be1202dd318', 'api', '2026-05-14 06:49:41', '2026-06-13 05:46:12', '2026-05-14 05:46:12', '2026-05-14 06:49:41', NULL),
(11, 1, '9eb75b405e43721feba46b59f80888c48229c94bcb1b59c114ee6582d6f27b6c', 'api', '2026-05-14 10:51:12', '2026-06-13 08:15:03', '2026-05-14 08:15:03', '2026-05-14 10:51:12', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_code` varchar(80) NOT NULL,
  `main_sku` varchar(120) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(120) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `product_code`, `main_sku`, `name`, `category`, `description`, `image_url`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'COTTON-101836', NULL, 'Bo cotton test', 'kids', 'Smoke test', NULL, 'active', '2026-05-14 03:18:36', '2026-05-14 09:46:39', '2026-05-14 09:46:39'),
(2, 'STOCK-102441', NULL, 'San pham kho test', 'test', 'Smoke test product', NULL, 'active', '2026-05-14 03:24:41', '2026-05-14 09:46:38', '2026-05-14 09:46:38'),
(3, 'SKU103718', NULL, 'Bo cotton sku test', 'kids', NULL, NULL, 'active', '2026-05-14 03:37:18', '2026-05-14 09:46:36', '2026-05-14 09:46:36'),
(4, 'SKU103759', NULL, 'Bo cotton sku test', 'kids', NULL, NULL, 'active', '2026-05-14 03:37:59', '2026-05-14 09:46:35', '2026-05-14 09:46:35'),
(5, 'SKU103947', NULL, 'Bo cotton variant smoke', 'kids', NULL, NULL, 'active', '2026-05-14 03:39:47', '2026-05-14 09:46:34', '2026-05-14 09:46:34'),
(6, 'STK104542', NULL, 'Bo cotton stock smoke', 'kids', NULL, NULL, 'active', '2026-05-14 03:45:42', '2026-05-14 09:46:32', '2026-05-14 09:46:32'),
(7, 'AT-01', NULL, 'Đồ tole Nam ba lỗ', 'KIds', '', NULL, 'active', '2026-05-14 05:52:24', '2026-05-14 09:46:45', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_skus`
--

CREATE TABLE `product_skus` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `sku_code` varchar(120) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `size_option_id` int(10) UNSIGNED NOT NULL,
  `combo_option_id` int(10) UNSIGNED NOT NULL,
  `combo_quantity` int(10) UNSIGNED NOT NULL,
  `suggested_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `cost_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `sale_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tiktok_sku_id` varchar(120) DEFAULT NULL,
  `is_sellable` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `product_skus`
--

INSERT INTO `product_skus` (`id`, `product_id`, `sku_code`, `display_name`, `size_option_id`, `combo_option_id`, `combo_quantity`, `suggested_cost`, `cost_price`, `sale_price`, `tiktok_sku_id`, `is_sellable`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 4, 'SKU103759-S5-C1', 'Size 5 - Combo 1', 1, 4, 1, 38000.00, 120000.00, 179000.00, 'TT_SKU_123', 1, 1, '2026-05-14 03:38:00', '2026-05-14 03:38:00', NULL),
(2, 4, 'SKU103759-S5-C3', 'Size 5 - Combo 3', 1, 5, 3, 114000.00, 114000.00, 0.00, NULL, 1, 1, '2026-05-14 03:38:00', '2026-05-14 03:38:00', NULL),
(3, 4, 'SKU103759-S5-C5', 'Size 5 - Combo 5', 1, 6, 5, 190000.00, 190000.00, 0.00, NULL, 1, 1, '2026-05-14 03:38:00', '2026-05-14 03:38:00', NULL),
(4, 4, 'SKU103759-S6-C1', 'Size 6 - Combo 1', 2, 4, 1, 38000.00, 38000.00, 0.00, NULL, 1, 1, '2026-05-14 03:38:00', '2026-05-14 03:38:00', NULL),
(5, 4, 'SKU103759-S6-C3', 'Size 6 - Combo 3', 2, 5, 3, 114000.00, 114000.00, 0.00, NULL, 1, 1, '2026-05-14 03:38:00', '2026-05-14 03:38:00', NULL),
(6, 4, 'SKU103759-S6-C5', 'Size 6 - Combo 5', 2, 6, 5, 190000.00, 190000.00, 0.00, NULL, 1, 1, '2026-05-14 03:38:00', '2026-05-14 03:38:00', NULL),
(7, 4, 'SKU103759-S7-C1', 'Size 7 - Combo 1', 3, 4, 1, 38000.00, 38000.00, 0.00, NULL, 1, 1, '2026-05-14 03:38:00', '2026-05-14 03:38:00', NULL),
(8, 4, 'SKU103759-S7-C3', 'Size 7 - Combo 3', 3, 5, 3, 114000.00, 114000.00, 0.00, NULL, 1, 1, '2026-05-14 03:38:00', '2026-05-14 03:38:00', NULL),
(9, 4, 'SKU103759-S7-C5', 'Size 7 - Combo 5', 3, 6, 5, 190000.00, 190000.00, 0.00, NULL, 1, 1, '2026-05-14 03:38:00', '2026-05-14 03:38:00', NULL),
(10, 5, 'SKU103947-S5-C1', 'Size 5 - Combo 1', 7, 10, 1, 38000.00, 38000.00, 0.00, NULL, 1, 1, '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(11, 5, 'SKU103947-S5-C3', 'Size 5 - Combo 3', 7, 11, 3, 114000.00, 114000.00, 0.00, NULL, 1, 1, '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(12, 5, 'SKU103947-S5-C5', 'Size 5 - Combo 5', 7, 12, 5, 190000.00, 190000.00, 0.00, NULL, 1, 1, '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(13, 5, 'SKU103947-S6-C1', 'Size 6 - Combo 1', 8, 10, 1, 38000.00, 38000.00, 0.00, NULL, 1, 1, '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(14, 5, 'SKU103947-S6-C3', 'Size 6 - Combo 3', 8, 11, 3, 114000.00, 114000.00, 0.00, NULL, 1, 1, '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(15, 5, 'SKU103947-S6-C5', 'Size 6 - Combo 5', 8, 12, 5, 190000.00, 190000.00, 0.00, NULL, 1, 1, '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(16, 5, 'SKU103947-S7-C1', 'Size 7 - Combo 1', 9, 10, 1, 38000.00, 38000.00, 0.00, NULL, 1, 1, '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(17, 5, 'SKU103947-S7-C3', 'Size 7 - Combo 3', 9, 11, 3, 114000.00, 114000.00, 0.00, NULL, 1, 1, '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(18, 5, 'SKU103947-S7-C5', 'Size 7 - Combo 5', 9, 12, 5, 190000.00, 190000.00, 0.00, NULL, 1, 1, '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(19, 7, 'AT-01-2bo-5', 'Size 5 - 2', 16, 18, 2, 48000.00, 74000.00, 0.00, '', 1, 1, '2026-05-14 05:54:18', '2026-05-14 09:55:08', NULL),
(20, 7, 'AT-01-3bo-5', 'Size 5 - 3', 16, 22, 3, 72000.00, 111000.00, 0.00, NULL, 1, 1, '2026-05-14 05:54:18', '2026-05-14 09:55:08', NULL),
(21, 7, 'AT-01-5bo-5', 'Size 5 - 5', 16, 23, 5, 120000.00, 185000.00, 0.00, NULL, 1, 1, '2026-05-14 05:54:18', '2026-05-14 09:55:08', NULL),
(22, 7, 'AT-01-2bo-6', 'Size 6 - 2', 19, 18, 2, 48000.00, 74000.00, 0.00, NULL, 1, 1, '2026-05-14 05:54:18', '2026-05-14 09:55:08', NULL),
(23, 7, 'AT-01-3bo-6', 'Size 6 - 3', 19, 22, 3, 72000.00, 111000.00, 0.00, NULL, 1, 1, '2026-05-14 05:54:18', '2026-05-14 09:55:08', NULL),
(24, 7, 'AT-01-5bo-6', 'Size 6 - 5', 19, 23, 5, 120000.00, 185000.00, 0.00, NULL, 1, 1, '2026-05-14 05:54:18', '2026-05-14 09:55:08', NULL),
(25, 7, 'AT-01-2bo-7', 'Size 7 - 2', 24, 18, 2, 48000.00, 74000.00, 0.00, NULL, 1, 1, '2026-05-14 08:17:03', '2026-05-14 09:55:08', NULL),
(26, 7, 'AT-01-3bo-7', 'Size 7 - 3', 24, 22, 3, 72000.00, 111000.00, 0.00, NULL, 1, 1, '2026-05-14 08:17:03', '2026-05-14 09:55:08', NULL),
(27, 7, 'AT-01-5bo-7', 'Size 7 - 5', 24, 23, 5, 120000.00, 185000.00, 0.00, NULL, 1, 1, '2026-05-14 08:17:03', '2026-05-14 09:55:08', NULL),
(28, 7, 'AT-01-2bo-8', 'Size 8 - 2', 25, 18, 2, 48000.00, 48000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(29, 7, 'AT-01-3bo-8', 'Size 8 - 3', 25, 22, 3, 72000.00, 72000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(30, 7, 'AT-01-5bo-8', 'Size 8 - 5', 25, 23, 5, 120000.00, 120000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(31, 7, 'AT-01-2bo-9', 'Size 9 - 2', 26, 18, 2, 52000.00, 52000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(32, 7, 'AT-01-3bo-9', 'Size 9 - 3', 26, 22, 3, 78000.00, 78000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(33, 7, 'AT-01-5bo-9', 'Size 9 - 5', 26, 23, 5, 130000.00, 130000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(34, 7, 'AT-01-2bo-10', 'Size 10 - 2', 27, 18, 2, 52000.00, 52000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(35, 7, 'AT-01-3bo-10', 'Size 10 - 3', 27, 22, 3, 78000.00, 78000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(36, 7, 'AT-01-5bo-10', 'Size 10 - 5', 27, 23, 5, 130000.00, 130000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(37, 7, 'AT-01-2bo-11', 'Size 11 - 2', 28, 18, 2, 52000.00, 52000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(38, 7, 'AT-01-3bo-11', 'Size 11 - 3', 28, 22, 3, 78000.00, 78000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(39, 7, 'AT-01-5bo-11', 'Size 11 - 5', 28, 23, 5, 130000.00, 130000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(40, 7, 'AT-01-2bo-12', 'Size 12 - 2', 29, 18, 2, 52000.00, 52000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(41, 7, 'AT-01-3bo-12', 'Size 12 - 3', 29, 22, 3, 78000.00, 78000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(42, 7, 'AT-01-5bo-12', 'Size 12 - 5', 29, 23, 5, 130000.00, 130000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(43, 7, 'AT-01-2bo-13', 'Size 13 - 2', 30, 18, 2, 56000.00, 56000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(44, 7, 'AT-01-3bo-13', 'Size 13 - 3', 30, 22, 3, 84000.00, 84000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(45, 7, 'AT-01-5bo-13', 'Size 13 - 5', 30, 23, 5, 140000.00, 140000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(46, 7, 'AT-01-2bo-14', 'Size 14 - 2', 31, 18, 2, 56000.00, 56000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(47, 7, 'AT-01-3bo-14', 'Size 14 - 3', 31, 22, 3, 84000.00, 84000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(48, 7, 'AT-01-5bo-14', 'Size 14 - 5', 31, 23, 5, 140000.00, 140000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(49, 7, 'AT-01-2bo-15', 'Size 15 - 2', 32, 18, 2, 56000.00, 56000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(50, 7, 'AT-01-3bo-15', 'Size 15 - 3', 32, 22, 3, 84000.00, 84000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(51, 7, 'AT-01-5bo-15', 'Size 15 - 5', 32, 23, 5, 140000.00, 140000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(52, 7, 'AT-01-2bo-16', 'Size 16 - 2', 33, 18, 2, 56000.00, 56000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(53, 7, 'AT-01-3bo-16', 'Size 16 - 3', 33, 22, 3, 84000.00, 84000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(54, 7, 'AT-01-5bo-16', 'Size 16 - 5', 33, 23, 5, 140000.00, 140000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(55, 7, 'AT-01-2bo-S', 'Size S - 2', 34, 18, 2, 74000.00, 74000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(56, 7, 'AT-01-3bo-S', 'Size S - 3', 34, 22, 3, 111000.00, 111000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(57, 7, 'AT-01-5bo-S', 'Size S - 5', 34, 23, 5, 185000.00, 185000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(58, 7, 'AT-01-2bo-M', 'Size M - 2', 35, 18, 2, 74000.00, 74000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(59, 7, 'AT-01-3bo-M', 'Size M - 3', 35, 22, 3, 111000.00, 111000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(60, 7, 'AT-01-5bo-M', 'Size M - 5', 35, 23, 5, 185000.00, 185000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(61, 7, 'AT-01-2bo-L', 'Size L - 2', 36, 18, 2, 74000.00, 74000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(62, 7, 'AT-01-3bo-L', 'Size L - 3', 36, 22, 3, 111000.00, 111000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(63, 7, 'AT-01-5bo-L', 'Size L - 5', 36, 23, 5, 185000.00, 185000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(64, 7, 'AT-01-2bo-XL', 'Size XL - 2', 37, 18, 2, 74000.00, 74000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(65, 7, 'AT-01-3bo-XL', 'Size XL - 3', 37, 22, 3, 111000.00, 111000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(66, 7, 'AT-01-5bo-XL', 'Size XL - 5', 37, 23, 5, 185000.00, 185000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(67, 7, 'AT-01-2bo-XXL', 'Size XXL - 2', 38, 18, 2, 74000.00, 74000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(68, 7, 'AT-01-3bo-XXL', 'Size XXL - 3', 38, 22, 3, 111000.00, 111000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(69, 7, 'AT-01-5bo-XXL', 'Size XXL - 5', 38, 23, 5, 185000.00, 185000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(70, 7, 'AT-01-2bo-3XL', 'Size 3XL - 2', 39, 18, 2, 90000.00, 90000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(71, 7, 'AT-01-3bo-3XL', 'Size 3XL - 3', 39, 22, 3, 135000.00, 135000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(72, 7, 'AT-01-5bo-3XL', 'Size 3XL - 5', 39, 23, 5, 225000.00, 225000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(73, 7, 'AT-01-2bo-4XL', 'Size 4XL - 2', 40, 18, 2, 100000.00, 100000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(74, 7, 'AT-01-3bo-4XL', 'Size 4XL - 3', 40, 22, 3, 150000.00, 150000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL),
(75, 7, 'AT-01-5bo-4XL', 'Size 4XL - 5', 40, 23, 5, 250000.00, 250000.00, 0.00, NULL, 1, 1, '2026-05-14 09:55:08', '2026-05-14 09:55:08', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `purchase_imports`
--

CREATE TABLE `purchase_imports` (
  `id` int(10) UNSIGNED NOT NULL,
  `import_code` varchar(80) NOT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `import_date` date NOT NULL,
  `total_quantity` int(11) NOT NULL DEFAULT 0,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `purchase_imports`
--

INSERT INTO `purchase_imports` (`id`, `import_code`, `supplier_name`, `import_date`, `total_quantity`, `total_amount`, `note`, `created_at`, `updated_at`) VALUES
(1, 'IMP-STK104542', 'NCC Test', '2026-05-14', 10, 380000.00, 'Smoke import', '2026-05-14 03:45:43', '2026-05-14 03:45:43'),
(2, 'IMP-20260514-062251', 't', '2026-05-14', 5, 5.00, '', '2026-05-14 06:22:51', '2026-05-14 06:22:51'),
(3, 'IMP-20260514-091545', '', '2026-05-14', 60, 1440000.00, '', '2026-05-14 09:15:45', '2026-05-14 09:15:45'),
(4, 'IMP-20260514-104929', '', '2026-05-14', 10, 240000.00, '', '2026-05-14 10:49:29', '2026-05-14 10:49:29');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `purchase_import_items`
--

CREATE TABLE `purchase_import_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `purchase_import_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `size_option_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `unit_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `purchase_import_items`
--

INSERT INTO `purchase_import_items` (`id`, `purchase_import_id`, `product_id`, `size_option_id`, `quantity`, `unit_cost`, `total_cost`, `created_at`, `updated_at`) VALUES
(1, 1, 6, 13, 10, 38000.00, 380000.00, '2026-05-14 03:45:43', '2026-05-14 03:45:43'),
(2, 2, 7, 16, 5, 1.00, 5.00, '2026-05-14 06:22:51', '2026-05-14 06:22:51'),
(3, 3, 7, 16, 20, 24000.00, 480000.00, '2026-05-14 09:15:45', '2026-05-14 09:15:45'),
(4, 3, 7, 19, 20, 24000.00, 480000.00, '2026-05-14 09:15:45', '2026-05-14 09:15:45'),
(5, 3, 7, 24, 20, 24000.00, 480000.00, '2026-05-14 09:15:45', '2026-05-14 09:15:45'),
(6, 4, 7, 16, 10, 24000.00, 240000.00, '2026-05-14 10:49:29', '2026-05-14 10:49:29');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `roles`
--

INSERT INTO `roles` (`id`, `name`, `label`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Admin', '2026-05-14 03:17:45', '2026-05-14 03:17:45'),
(2, 'member', 'Nhan vien', '2026-05-14 03:17:45', '2026-05-14 03:17:45');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `settlements`
--

CREATE TABLE `settlements` (
  `id` int(10) UNSIGNED NOT NULL,
  `settlement_code` varchar(120) NOT NULL,
  `period_from` date NOT NULL,
  `period_to` date NOT NULL,
  `platform` varchar(50) NOT NULL DEFAULT 'tiktok',
  `total_gross` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_fee` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_settled` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_difference` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `settlement_items`
--

CREATE TABLE `settlement_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `settlement_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `order_code` varchar(120) NOT NULL,
  `gross_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `platform_fee` decimal(14,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` decimal(14,2) NOT NULL DEFAULT 0.00,
  `settled_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `expected_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `difference_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `stock_by_size`
--

CREATE TABLE `stock_by_size` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `size_option_id` int(10) UNSIGNED NOT NULL,
  `quantity_on_hand` int(11) NOT NULL DEFAULT 0,
  `quantity_reserved` int(11) NOT NULL DEFAULT 0,
  `quantity_available` int(11) NOT NULL DEFAULT 0,
  `avg_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `stock_by_size`
--

INSERT INTO `stock_by_size` (`id`, `product_id`, `size_option_id`, `quantity_on_hand`, `quantity_reserved`, `quantity_available`, `avg_cost`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 0, 0, 0, 38000.00, '2026-05-14 03:37:59', '2026-05-14 03:37:59'),
(2, 4, 2, 0, 0, 0, 38000.00, '2026-05-14 03:37:59', '2026-05-14 03:37:59'),
(3, 4, 3, 0, 0, 0, 38000.00, '2026-05-14 03:37:59', '2026-05-14 03:37:59'),
(4, 5, 7, 0, 0, 0, 38000.00, '2026-05-14 03:39:47', '2026-05-14 03:39:47'),
(5, 5, 8, 0, 0, 0, 38000.00, '2026-05-14 03:39:47', '2026-05-14 03:39:47'),
(6, 5, 9, 0, 0, 0, 38000.00, '2026-05-14 03:39:47', '2026-05-14 03:39:47'),
(7, 6, 13, 7, 0, 7, 38000.00, '2026-05-14 03:45:43', '2026-05-14 03:45:43'),
(8, 6, 14, 0, 0, 0, 38000.00, '2026-05-14 04:04:33', '2026-05-14 04:04:33'),
(10, 7, 16, 35, 0, 35, 20571.57, '2026-05-14 05:53:07', '2026-05-14 10:49:29'),
(11, 7, 19, 20, 0, 20, 24000.00, '2026-05-14 05:53:30', '2026-05-14 09:15:45'),
(12, 7, 24, 20, 0, 20, 24000.00, '2026-05-14 08:16:57', '2026-05-14 09:15:45'),
(13, 7, 25, 0, 0, 0, 24000.00, '2026-05-14 09:46:54', '2026-05-14 09:51:55'),
(14, 7, 26, 0, 0, 0, 26000.00, '2026-05-14 09:52:06', '2026-05-14 09:52:06'),
(15, 7, 27, 0, 0, 0, 26000.00, '2026-05-14 09:52:11', '2026-05-14 09:52:11'),
(16, 7, 28, 0, 0, 0, 26000.00, '2026-05-14 09:52:16', '2026-05-14 09:52:25'),
(17, 7, 29, 0, 0, 0, 26000.00, '2026-05-14 09:52:54', '2026-05-14 09:52:54'),
(18, 7, 30, 0, 0, 0, 28000.00, '2026-05-14 09:53:04', '2026-05-14 09:53:04'),
(19, 7, 31, 0, 0, 0, 28000.00, '2026-05-14 09:53:09', '2026-05-14 09:53:09'),
(20, 7, 32, 0, 0, 0, 28000.00, '2026-05-14 09:53:19', '2026-05-14 09:53:19'),
(21, 7, 33, 0, 0, 0, 28000.00, '2026-05-14 09:53:24', '2026-05-14 09:53:24'),
(22, 7, 34, 0, 0, 0, 37000.00, '2026-05-14 09:53:38', '2026-05-14 09:53:38'),
(23, 7, 35, 0, 0, 0, 37000.00, '2026-05-14 09:53:46', '2026-05-14 09:53:46'),
(24, 7, 36, 0, 0, 0, 37000.00, '2026-05-14 09:53:59', '2026-05-14 09:53:59'),
(25, 7, 37, 0, 0, 0, 37000.00, '2026-05-14 09:54:10', '2026-05-14 09:54:10'),
(26, 7, 38, 0, 0, 0, 37000.00, '2026-05-14 09:54:19', '2026-05-14 09:54:19'),
(27, 7, 39, 0, 0, 0, 45000.00, '2026-05-14 09:54:33', '2026-05-14 09:54:33'),
(28, 7, 40, 0, 0, 0, 50000.00, '2026-05-14 09:54:52', '2026-05-14 09:54:52');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `size_option_id` int(10) UNSIGNED NOT NULL,
  `movement_type` varchar(30) NOT NULL,
  `quantity` int(11) NOT NULL,
  `quantity_before` int(11) NOT NULL DEFAULT 0,
  `quantity_after` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) UNSIGNED DEFAULT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `order_item_id` int(10) UNSIGNED DEFAULT NULL,
  `purchase_import_id` int(10) UNSIGNED DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `size_option_id`, `movement_type`, `quantity`, `quantity_before`, `quantity_after`, `unit_cost`, `reference_type`, `reference_id`, `order_id`, `order_item_id`, `purchase_import_id`, `note`, `created_at`, `updated_at`) VALUES
(1, 6, 13, 'import', 10, 0, 10, 38000.00, 'purchase_import', 1, NULL, NULL, 1, 'Import item #1', '2026-05-14 03:45:43', '2026-05-14 03:45:43'),
(2, 6, 13, 'adjustment', -3, 10, 7, 0.00, 'stock_adjustment', NULL, NULL, NULL, NULL, 'Smoke decrease', '2026-05-14 03:45:43', '2026-05-14 03:45:43'),
(3, 7, 16, 'import', 5, 0, 5, 1.00, 'purchase_import', 2, NULL, NULL, 2, 'Import item #2', '2026-05-14 06:22:51', '2026-05-14 06:22:51'),
(4, 7, 16, 'import', 20, 5, 25, 24000.00, 'purchase_import', 3, NULL, NULL, 3, 'Import item #3', '2026-05-14 09:15:45', '2026-05-14 09:15:45'),
(5, 7, 19, 'import', 20, 0, 20, 24000.00, 'purchase_import', 3, NULL, NULL, 3, 'Import item #4', '2026-05-14 09:15:45', '2026-05-14 09:15:45'),
(6, 7, 24, 'import', 20, 0, 20, 24000.00, 'purchase_import', 3, NULL, NULL, 3, 'Import item #5', '2026-05-14 09:15:45', '2026-05-14 09:15:45'),
(7, 7, 16, 'import', 10, 25, 35, 24000.00, 'purchase_import', 4, NULL, NULL, 4, 'Import item #6', '2026-05-14 10:49:29', '2026-05-14 10:49:29');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tiktok_products`
--

CREATE TABLE `tiktok_products` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `tiktok_product_id` varchar(120) NOT NULL,
  `name` varchar(255) NOT NULL,
  `shop_name` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tiktok_products`
--

INSERT INTO `tiktok_products` (`id`, `product_id`, `tiktok_product_id`, `name`, `shop_name`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 7, 't', 'Đồ tole bé trai 3 lỗ', 'Đồ tole bé trai 3 lỗ', 'active', '2026-05-14 09:16:34', '2026-05-14 10:45:44', '2026-05-14 10:45:44'),
(2, 6, '2', '2', NULL, 'active', '2026-05-14 09:18:47', '2026-05-14 09:46:26', '2026-05-14 09:46:26'),
(3, 7, '1730254802916641626', 'Hanie Kids Combo 2-3-5 Bộ Đồ Bộ Tole Lanh Bé Trai Ba Lỗ Màu Ngẫu Nhiên Dễ Thương #quanao #balo Chất Liệu Vải Lanh Mềm Mại Thoáng Mát Size 5-38kg', NULL, 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tiktok_shop_connections`
--

CREATE TABLE `tiktok_shop_connections` (
  `id` int(10) UNSIGNED NOT NULL,
  `shop_name` varchar(255) DEFAULT NULL,
  `shop_id` varchar(120) DEFAULT NULL,
  `shop_cipher` varchar(255) NOT NULL,
  `app_key` varchar(120) NOT NULL,
  `app_secret` varchar(255) NOT NULL,
  `base_url` varchar(255) NOT NULL DEFAULT 'https://open-api.tiktokglobalshop.com',
  `auth_base_url` varchar(255) NOT NULL DEFAULT 'https://auth.tiktok-shops.com',
  `access_token` text DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `access_token_expires_at` datetime DEFAULT NULL,
  `refresh_token_expires_at` datetime DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `last_synced_at` datetime DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tiktok_skus`
--

CREATE TABLE `tiktok_skus` (
  `id` int(10) UNSIGNED NOT NULL,
  `tiktok_product_id` int(10) UNSIGNED NOT NULL,
  `product_sku_id` int(10) UNSIGNED DEFAULT NULL,
  `tiktok_sku_id` varchar(120) NOT NULL,
  `seller_sku` varchar(120) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `tiktok_price` decimal(14,2) DEFAULT 0.00,
  `tiktok_inventory_quantity` int(11) DEFAULT 0,
  `tiktok_warehouse_id` varchar(120) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tiktok_skus`
--

INSERT INTO `tiktok_skus` (`id`, `tiktok_product_id`, `product_sku_id`, `tiktok_sku_id`, `seller_sku`, `name`, `tiktok_price`, `tiktok_inventory_quantity`, `tiktok_warehouse_id`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 19, 't', NULL, 'test', 0.00, 0, NULL, 'active', '2026-05-14 09:18:40', '2026-05-14 10:42:16', '2026-05-14 10:42:16'),
(2, 3, 19, '1732827297944995674', 'AT-01-2bo-5', 'AT-01-2bo-5', 98000.00, 8, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(3, 3, 20, '1732827297945061210', 'AT-01-3bo-5', 'AT-01-3bo-5', 128000.00, 5, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(4, 3, 21, '1732827297945126746', 'AT-01-5bo-5', 'AT-01-5bo-5', 195000.00, 3, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(5, 3, 22, '1732827297945192282', 'AT-01-2bo-6', 'AT-01-2bo-6', 105000.00, 9, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(6, 3, 23, '1732827297945257818', 'AT-01-3bo-6', 'AT-01-3bo-6', 131000.00, 6, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(7, 3, 24, '1732827297945323354', 'AT-01-5bo-6', 'AT-01-5bo-6', 197000.00, 3, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(8, 3, 25, '1732827350995863386', 'AT-01-2bo-7', 'AT-01-2bo-7', 110000.00, 8, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(9, 3, 26, '1732827350995928922', 'AT-01-3bo-7', 'AT-01-3bo-7', 133000.00, 5, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(10, 3, 27, '1732827350995994458', 'AT-01-5bo-7', 'AT-01-5bo-7', 200000.00, 3, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(11, 3, 28, '1732827350996059994', 'AT-01-2bo-8', 'AT-01-2bo-8', 115000.00, 7, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(12, 3, 29, '1732827350996125530', 'AT-01-3bo-8', 'AT-01-3bo-8', 135000.00, 5, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(13, 3, 30, '1732827350996191066', 'AT-01-5bo-8', 'AT-01-5bo-8', 205000.00, 3, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(14, 3, 31, '1732827350996256602', 'AT-01-2bo-9', 'AT-01-2bo-9', 118000.00, 7, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(15, 3, 32, '1732827350996322138', 'AT-01-3bo-9', 'AT-01-3bo-9', 137000.00, 5, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(16, 3, 33, '1732827350996387674', 'AT-01-5bo-9', 'AT-01-5bo-9', 210000.00, 3, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(17, 3, 34, '1732827350996453210', 'AT-01-2bo-10', 'AT-01-2bo-10', 121000.00, 1, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(18, 3, 35, '1732827350996518746', 'AT-01-3bo-10', 'AT-01-3bo-10', 140000.00, 1, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(19, 3, 36, '1732827350996584282', 'AT-01-5bo-10', 'AT-01-5bo-10', 215000.00, 0, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(20, 3, 37, '1732827350996649818', 'AT-01-2bo-11', 'AT-01-2bo-11', 124000.00, 6, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(21, 3, 38, '1732827350996715354', 'AT-01-3bo-11', 'AT-01-3bo-11', 144000.00, 4, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(22, 3, 39, '1732827350996780890', 'AT-01-5bo-11', 'AT-01-5bo-11', 220000.00, 2, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(23, 3, 40, '1732827350996846426', 'AT-01-2bo-12', 'AT-01-2bo-12', 127000.00, 3, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(24, 3, 41, '1732827350996911962', 'AT-01-3bo-12', 'AT-01-3bo-12', 147000.00, 2, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(25, 3, 42, '1732827350996977498', 'AT-01-5bo-12', 'AT-01-5bo-12', 225000.00, 1, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(26, 3, 43, '1732827350997043034', 'AT-01-2bo-13', 'AT-01-2bo-13', 130000.00, 6, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(27, 3, 44, '1732827350997108570', 'AT-01-3bo-13', 'AT-01-3bo-13', 150000.00, 4, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(28, 3, 45, '1732827350997174106', 'AT-01-5bo-13', 'AT-01-5bo-13', 230000.00, 2, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(29, 3, 46, '1732827350997239642', 'AT-01-2bo-14', 'AT-01-2bo-14', 133000.00, 5, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(30, 3, 47, '1732827350997305178', 'AT-01-3bo-14', 'AT-01-3bo-14', 153000.00, 3, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(31, 3, 48, '1732827350997370714', 'AT-01-5bo-14', 'AT-01-5bo-14', 235000.00, 2, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(32, 3, 49, '1732827350997436250', 'AT-01-2bo-15', 'AT-01-2bo-15', 136000.00, 8, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(33, 3, 50, '1732827350997501786', 'AT-01-3bo-15', 'AT-01-3bo-15', 156000.00, 5, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(34, 3, 51, '1732827350997567322', 'AT-01-5bo-15', 'AT-01-5bo-15', 240000.00, 3, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(35, 3, 52, '1732827492646815578', 'AT-01-2bo-16', 'AT-01-2bo-16', 139000.00, 11, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(36, 3, 53, '1732827492646881114', 'AT-01-3bo-16', 'AT-01-3bo-16', 159000.00, 7, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(37, 3, 54, '1732827492646946650', 'AT-01-5bo-16', 'AT-01-5bo-16', 250000.00, 4, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(38, 3, 55, '1732827350997829466', 'AT-01-2bo-S', 'AT-01-2bo-S', 155000.00, 9, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(39, 3, 56, '1732827350997895002', 'AT-01-3bo-S', 'AT-01-3bo-S', 180000.00, 6, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(40, 3, 57, '1732827350997960538', 'AT-01-5bo-S', 'AT-01-5bo-S', 285000.00, 3, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(41, 3, 58, '1732827350998026074', 'AT-01-2bo-M', 'AT-01-2bo-M', 160000.00, 6, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(42, 3, 59, '1732827350998091610', 'AT-01-3bo-M', 'AT-01-3bo-M', 185000.00, 4, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(43, 3, 60, '1732827350998157146', 'AT-01-5bo-M', 'AT-01-5bo-M', 290000.00, 2, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(44, 3, 61, '1732827350998222682', 'AT-01-2bo-L', 'AT-01-2bo-L', 165000.00, 4, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(45, 3, 62, '1732827350998288218', 'AT-01-3bo-L', 'AT-01-3bo-L', 190000.00, 3, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(46, 3, 63, '1732827350998353754', 'AT-01-5bo-L', 'AT-01-5bo-L', 295000.00, 1, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(47, 3, 64, '1732827350998419290', 'AT-01-2bo-XL', 'AT-01-2bo-XL', 170000.00, 2, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(48, 3, 65, '1732827350998484826', 'AT-01-3bo-XL', 'AT-01-3bo-XL', 195000.00, 1, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(49, 3, 66, '1732827350998550362', 'AT-01-5bo-XL', 'AT-01-5bo-XL', 300000.00, 1, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(50, 3, 67, '1732827350998615898', 'AT-01-2bo-XXL', 'AT-01-2bo-XXL', 175000.00, 9, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(51, 3, 68, '1732827350998681434', 'AT-01-3bo-XXL', 'AT-01-3bo-XXL', 200000.00, 6, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(52, 3, 69, '1732827350998746970', 'AT-01-5bo-XXL', 'AT-01-5bo-XXL', 310000.00, 3, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(53, 3, 70, '1735569775142602586', 'AT-01-2bo-3XL', 'AT-01-2bo-3XL', 180000.00, 2, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(54, 3, 71, '1735569775142668122', 'AT-01-3bo-3XL', 'AT-01-3bo-3XL', 230000.00, 1, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(55, 3, 72, '1735569775142733658', 'AT-01-5bo-3XL', 'AT-01-5bo-3XL', 360000.00, 0, '7359440537135859461', 'active', '2026-05-14 10:44:22', '2026-05-14 10:44:22', NULL),
(56, 3, 73, '1735569775142799194', 'AT-01-2bo-4XL', 'AT-01-2bo-4XL', 190000.00, 5, '7359440537135859461', 'active', '2026-05-14 10:44:23', '2026-05-14 10:44:23', NULL),
(57, 3, 74, '1735569775142864730', 'AT-01-3bo-4XL', 'AT-01-3bo-4XL', 265000.00, 3, '7359440537135859461', 'active', '2026-05-14 10:44:23', '2026-05-14 10:44:23', NULL),
(58, 3, 75, '1735569775142930266', 'AT-01-5bo-4XL', 'AT-01-5bo-4XL', 410000.00, 2, '7359440537135859461', 'active', '2026-05-14 10:44:23', '2026-05-14 10:44:23', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tiktok_webhook_events`
--

CREATE TABLE `tiktok_webhook_events` (
  `id` int(10) UNSIGNED NOT NULL,
  `connection_id` int(10) UNSIGNED DEFAULT NULL,
  `event_type` varchar(120) DEFAULT NULL,
  `order_id` varchar(120) DEFAULT NULL,
  `order_status` varchar(120) DEFAULT NULL,
  `payload_json` longtext NOT NULL,
  `process_status` varchar(30) NOT NULL DEFAULT 'received',
  `error_message` text DEFAULT NULL,
  `received_at` datetime NOT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `password_hash`, `status`, `last_login_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Admin', 'admin@example.com', '$2y$10$UHD3fSELpyBr8rr7TinIBOy3wxib9xtfDILbDc7.BSmV66jzefLw.', 'active', '2026-05-14 08:15:03', '2026-05-14 03:17:45', '2026-05-14 08:15:03', NULL),
(2, 2, 'Nhan vien', 'staff@example.com', '$2y$10$hi4/RrfaGfXf2oaJo85jguC.KmNA7O1v.8jX2l8g/i9xKFHNHZk5C', 'active', '2026-05-14 03:24:41', '2026-05-14 03:17:45', '2026-05-14 03:24:41', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `variant_groups`
--

CREATE TABLE `variant_groups` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `type` varchar(20) NOT NULL,
  `is_stock_group` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `variant_groups`
--

INSERT INTO `variant_groups` (`id`, `product_id`, `name`, `type`, `is_stock_group`, `sort_order`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 4, 'Size', 'text', 1, 1, 'active', '2026-05-14 03:37:59', '2026-05-14 03:37:59', NULL),
(2, 4, 'Combo', 'combo', 0, 2, 'active', '2026-05-14 03:37:59', '2026-05-14 03:37:59', NULL),
(3, 5, 'Size', 'text', 1, 1, 'active', '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(4, 5, 'Combo', 'combo', 0, 2, 'active', '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(5, 6, 'Size', 'text', 1, 1, 'active', '2026-05-14 03:45:43', '2026-05-14 03:45:43', NULL),
(6, 7, 'Size', 'text', 1, 1, 'active', '2026-05-14 05:52:29', '2026-05-14 05:52:29', NULL),
(7, 7, 'Combo', 'combo', 0, 2, 'active', '2026-05-14 05:52:35', '2026-05-14 05:52:35', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `variant_options`
--

CREATE TABLE `variant_options` (
  `id` int(10) UNSIGNED NOT NULL,
  `variant_group_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `option_code` varchar(80) DEFAULT NULL,
  `base_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `combo_quantity` int(10) UNSIGNED DEFAULT NULL,
  `default_sellable` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `variant_options`
--

INSERT INTO `variant_options` (`id`, `variant_group_id`, `name`, `option_code`, `base_cost`, `combo_quantity`, `default_sellable`, `sort_order`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, '5', '5', 38000.00, NULL, 1, 5, 'active', '2026-05-14 03:37:59', '2026-05-14 03:37:59', NULL),
(2, 1, '6', '6', 38000.00, NULL, 1, 6, 'active', '2026-05-14 03:37:59', '2026-05-14 03:37:59', NULL),
(3, 1, '7', '7', 38000.00, NULL, 1, 7, 'active', '2026-05-14 03:37:59', '2026-05-14 03:37:59', NULL),
(4, 2, 'Combo 1', NULL, 0.00, 1, 1, 1, 'active', '2026-05-14 03:38:00', '2026-05-14 03:38:00', NULL),
(5, 2, 'Combo 3', NULL, 0.00, 3, 1, 3, 'active', '2026-05-14 03:38:00', '2026-05-14 03:38:00', NULL),
(6, 2, 'Combo 5', NULL, 0.00, 5, 1, 5, 'active', '2026-05-14 03:38:00', '2026-05-14 03:38:00', NULL),
(7, 3, '5', '5', 38000.00, NULL, 1, 5, 'active', '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(8, 3, '6', '6', 38000.00, NULL, 1, 6, 'active', '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(9, 3, '7', '7', 38000.00, NULL, 1, 7, 'active', '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(10, 4, 'Combo 1', NULL, 0.00, 1, 1, 1, 'active', '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(11, 4, 'Combo 3', NULL, 0.00, 3, 1, 3, 'active', '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(12, 4, 'Combo 5', NULL, 0.00, 5, 1, 5, 'active', '2026-05-14 03:39:47', '2026-05-14 03:39:47', NULL),
(13, 5, '5', '5', 38000.00, NULL, 1, 1, 'active', '2026-05-14 03:45:43', '2026-05-14 03:45:43', NULL),
(14, 5, '6', '6', 38000.00, NULL, 1, 1, 'active', '2026-05-14 04:04:33', '2026-05-14 04:04:33', NULL),
(15, 6, '5', '5', 0.00, NULL, 1, 1, 'active', '2026-05-14 05:52:54', '2026-05-14 05:53:01', '2026-05-14 05:53:01'),
(16, 6, '5', '5', 24000.00, NULL, 1, 1, 'active', '2026-05-14 05:53:07', '2026-05-14 09:51:33', NULL),
(17, 7, '2', NULL, 0.00, 1, 1, 1, 'active', '2026-05-14 05:53:09', '2026-05-14 05:53:18', '2026-05-14 05:53:18'),
(18, 7, '2', NULL, 0.00, 2, 1, 1, 'active', '2026-05-14 05:53:21', '2026-05-14 05:53:21', NULL),
(19, 6, '6', '6', 24000.00, NULL, 1, 2, 'active', '2026-05-14 05:53:30', '2026-05-14 09:51:38', NULL),
(20, 7, '3', NULL, 0.00, 3, 1, 1, 'active', '2026-05-14 05:53:37', '2026-05-14 05:53:45', '2026-05-14 05:53:45'),
(21, 7, '5', NULL, 0.00, 5, 1, 1, 'active', '2026-05-14 05:53:39', '2026-05-14 05:53:46', '2026-05-14 05:53:46'),
(22, 7, '3', NULL, 0.00, 3, 1, 2, 'active', '2026-05-14 05:53:49', '2026-05-14 05:53:49', NULL),
(23, 7, '5', NULL, 0.00, 5, 1, 3, 'active', '2026-05-14 05:53:54', '2026-05-14 05:53:54', NULL),
(24, 6, '7', '7', 24000.00, NULL, 1, 3, 'active', '2026-05-14 08:16:57', '2026-05-14 09:51:51', NULL),
(25, 6, '8', '8', 24000.00, NULL, 1, 3, 'active', '2026-05-14 09:46:54', '2026-05-14 09:51:55', NULL),
(26, 6, '9', '9', 26000.00, NULL, 1, 4, 'active', '2026-05-14 09:52:06', '2026-05-14 09:52:06', NULL),
(27, 6, '10', '10', 26000.00, NULL, 1, 5, 'active', '2026-05-14 09:52:11', '2026-05-14 09:52:11', NULL),
(28, 6, '11', '11', 26000.00, NULL, 1, 6, 'active', '2026-05-14 09:52:16', '2026-05-14 09:52:25', NULL),
(29, 6, '12', '12', 26000.00, NULL, 1, 7, 'active', '2026-05-14 09:52:54', '2026-05-14 09:52:54', NULL),
(30, 6, '13', '13', 28000.00, NULL, 1, 8, 'active', '2026-05-14 09:53:04', '2026-05-14 09:53:04', NULL),
(31, 6, '14', '14', 28000.00, NULL, 1, 9, 'active', '2026-05-14 09:53:09', '2026-05-14 09:53:09', NULL),
(32, 6, '15', '15', 28000.00, NULL, 1, 10, 'active', '2026-05-14 09:53:19', '2026-05-14 09:53:19', NULL),
(33, 6, '16', '16', 28000.00, NULL, 1, 11, 'active', '2026-05-14 09:53:24', '2026-05-14 09:53:24', NULL),
(34, 6, 'S', 'S', 37000.00, NULL, 1, 12, 'active', '2026-05-14 09:53:38', '2026-05-14 09:53:38', NULL),
(35, 6, 'M', 'M', 37000.00, NULL, 1, 13, 'active', '2026-05-14 09:53:46', '2026-05-14 09:53:46', NULL),
(36, 6, 'L', 'L', 37000.00, NULL, 1, 14, 'active', '2026-05-14 09:53:59', '2026-05-14 09:53:59', NULL),
(37, 6, 'XL', 'XL', 37000.00, NULL, 1, 15, 'active', '2026-05-14 09:54:10', '2026-05-14 09:54:10', NULL),
(38, 6, 'XXL', 'XXL', 37000.00, NULL, 1, 16, 'active', '2026-05-14 09:54:19', '2026-05-14 09:54:19', NULL),
(39, 6, '3XL', '3XL', 45000.00, NULL, 1, 17, 'active', '2026-05-14 09:54:33', '2026-05-14 09:54:33', NULL),
(40, 6, '4XL', '4XL', 50000.00, NULL, 1, 18, 'active', '2026-05-14 09:54:52', '2026-05-14 09:54:52', NULL);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `operating_costs`
--
ALTER TABLE `operating_costs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `operating_costs_order_id_foreign` (`order_id`),
  ADD KEY `cost_date` (`cost_date`),
  ADD KEY `cost_type` (`cost_type`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `tiktok_order_id` (`tiktok_order_id`),
  ADD KEY `order_date` (`order_date`),
  ADD KEY `status` (`status`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_combo_option_id_foreign` (`combo_option_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `sku_id` (`sku_id`),
  ADD KEY `size_option_id` (`size_option_id`);

--
-- Chỉ mục cho bảng `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_hash` (`token_hash`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code` (`product_code`),
  ADD KEY `status` (`status`),
  ADD KEY `products_main_sku_index` (`main_sku`);

--
-- Chỉ mục cho bảng `product_skus`
--
ALTER TABLE `product_skus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku_code` (`sku_code`),
  ADD UNIQUE KEY `product_sku_variant_unique` (`product_id`,`size_option_id`,`combo_option_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `size_option_id` (`size_option_id`),
  ADD KEY `combo_option_id` (`combo_option_id`);

--
-- Chỉ mục cho bảng `purchase_imports`
--
ALTER TABLE `purchase_imports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `import_code` (`import_code`),
  ADD KEY `import_date` (`import_date`);

--
-- Chỉ mục cho bảng `purchase_import_items`
--
ALTER TABLE `purchase_import_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_import_id` (`purchase_import_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `size_option_id` (`size_option_id`);

--
-- Chỉ mục cho bảng `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Chỉ mục cho bảng `settlements`
--
ALTER TABLE `settlements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settlement_code` (`settlement_code`),
  ADD KEY `period_from_period_to` (`period_from`,`period_to`);

--
-- Chỉ mục cho bảng `settlement_items`
--
ALTER TABLE `settlement_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `settlement_id` (`settlement_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `order_code` (`order_code`);

--
-- Chỉ mục cho bảng `stock_by_size`
--
ALTER TABLE `stock_by_size`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_by_size_unique` (`product_id`,`size_option_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `size_option_id` (`size_option_id`);

--
-- Chỉ mục cho bảng `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_movements_order_id_foreign` (`order_id`),
  ADD KEY `stock_movements_order_item_id_foreign` (`order_item_id`),
  ADD KEY `stock_movements_purchase_import_id_foreign` (`purchase_import_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `size_option_id` (`size_option_id`),
  ADD KEY `movement_type` (`movement_type`),
  ADD KEY `reference_type_reference_id` (`reference_type`,`reference_id`);

--
-- Chỉ mục cho bảng `tiktok_products`
--
ALTER TABLE `tiktok_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tiktok_product_id` (`tiktok_product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `tiktok_shop_connections`
--
ALTER TABLE `tiktok_shop_connections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`),
  ADD KEY `shop_cipher` (`shop_cipher`);

--
-- Chỉ mục cho bảng `tiktok_skus`
--
ALTER TABLE `tiktok_skus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tiktok_sku_id` (`tiktok_sku_id`),
  ADD KEY `tiktok_product_id` (`tiktok_product_id`),
  ADD KEY `product_sku_id` (`product_sku_id`);

--
-- Chỉ mục cho bảng `tiktok_webhook_events`
--
ALTER TABLE `tiktok_webhook_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `connection_id` (`connection_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `process_status` (`process_status`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `status` (`status`);

--
-- Chỉ mục cho bảng `variant_groups`
--
ALTER TABLE `variant_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `product_id_type` (`product_id`,`type`);

--
-- Chỉ mục cho bảng `variant_options`
--
ALTER TABLE `variant_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `variant_group_id` (`variant_group_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `operating_costs`
--
ALTER TABLE `operating_costs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `product_skus`
--
ALTER TABLE `product_skus`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT cho bảng `purchase_imports`
--
ALTER TABLE `purchase_imports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `purchase_import_items`
--
ALTER TABLE `purchase_import_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `settlements`
--
ALTER TABLE `settlements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `settlement_items`
--
ALTER TABLE `settlement_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `stock_by_size`
--
ALTER TABLE `stock_by_size`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT cho bảng `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `tiktok_products`
--
ALTER TABLE `tiktok_products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `tiktok_shop_connections`
--
ALTER TABLE `tiktok_shop_connections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `tiktok_skus`
--
ALTER TABLE `tiktok_skus`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT cho bảng `tiktok_webhook_events`
--
ALTER TABLE `tiktok_webhook_events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `variant_groups`
--
ALTER TABLE `variant_groups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `variant_options`
--
ALTER TABLE `variant_options`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `operating_costs`
--
ALTER TABLE `operating_costs`
  ADD CONSTRAINT `operating_costs_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `operating_costs_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Các ràng buộc cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_combo_option_id_foreign` FOREIGN KEY (`combo_option_id`) REFERENCES `variant_options` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_size_option_id_foreign` FOREIGN KEY (`size_option_id`) REFERENCES `variant_options` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_sku_id_foreign` FOREIGN KEY (`sku_id`) REFERENCES `product_skus` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD CONSTRAINT `personal_access_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `product_skus`
--
ALTER TABLE `product_skus`
  ADD CONSTRAINT `product_skus_combo_option_id_foreign` FOREIGN KEY (`combo_option_id`) REFERENCES `variant_options` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `product_skus_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `product_skus_size_option_id_foreign` FOREIGN KEY (`size_option_id`) REFERENCES `variant_options` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `purchase_import_items`
--
ALTER TABLE `purchase_import_items`
  ADD CONSTRAINT `purchase_import_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `purchase_import_items_purchase_import_id_foreign` FOREIGN KEY (`purchase_import_id`) REFERENCES `purchase_imports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `purchase_import_items_size_option_id_foreign` FOREIGN KEY (`size_option_id`) REFERENCES `variant_options` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `settlement_items`
--
ALTER TABLE `settlement_items`
  ADD CONSTRAINT `settlement_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `settlement_items_settlement_id_foreign` FOREIGN KEY (`settlement_id`) REFERENCES `settlements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `stock_by_size`
--
ALTER TABLE `stock_by_size`
  ADD CONSTRAINT `stock_by_size_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_by_size_size_option_id_foreign` FOREIGN KEY (`size_option_id`) REFERENCES `variant_options` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `stock_movements_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `stock_movements_purchase_import_id_foreign` FOREIGN KEY (`purchase_import_id`) REFERENCES `purchase_imports` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `stock_movements_size_option_id_foreign` FOREIGN KEY (`size_option_id`) REFERENCES `variant_options` (`id`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `tiktok_products`
--
ALTER TABLE `tiktok_products`
  ADD CONSTRAINT `tiktok_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Các ràng buộc cho bảng `tiktok_skus`
--
ALTER TABLE `tiktok_skus`
  ADD CONSTRAINT `tiktok_skus_product_sku_id_foreign` FOREIGN KEY (`product_sku_id`) REFERENCES `product_skus` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `tiktok_skus_tiktok_product_id_foreign` FOREIGN KEY (`tiktok_product_id`) REFERENCES `tiktok_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `tiktok_webhook_events`
--
ALTER TABLE `tiktok_webhook_events`
  ADD CONSTRAINT `tiktok_webhook_events_connection_id_foreign` FOREIGN KEY (`connection_id`) REFERENCES `tiktok_shop_connections` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Các ràng buộc cho bảng `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Các ràng buộc cho bảng `variant_groups`
--
ALTER TABLE `variant_groups`
  ADD CONSTRAINT `variant_groups_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `variant_options`
--
ALTER TABLE `variant_options`
  ADD CONSTRAINT `variant_options_variant_group_id_foreign` FOREIGN KEY (`variant_group_id`) REFERENCES `variant_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
