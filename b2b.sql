-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Gazdă: localhost
-- Timp de generare: ian. 07, 2026 la 08:16 PM
-- Versiune server: 8.0.44
-- Versiune PHP: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Bază de date: `b2b`
--

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `addresses`
--

CREATE TABLE `addresses` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `type` enum('billing','shipping','office') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'shipping',
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Romania',
  `county` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `street` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default_billing` tinyint(1) NOT NULL DEFAULT '0',
  `is_default_shipping` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `agent_customer`
--

CREATE TABLE `agent_customer` (
  `id` bigint UNSIGNED NOT NULL,
  `agent_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `agent_daily_routes`
--

CREATE TABLE `agent_daily_routes` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `total_distance` double NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `agent_daily_routes`
--

INSERT INTO `agent_daily_routes` (`id`, `user_id`, `date`, `start_time`, `end_time`, `total_distance`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, '2026-01-07', '2026-01-07 18:12:21', NULL, 0, 'active', '2026-01-07 18:12:21', '2026-01-07 18:12:21');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `agent_routes`
--

CREATE TABLE `agent_routes` (
  `id` bigint UNSIGNED NOT NULL,
  `agent_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') COLLATE utf8mb4_unicode_ci NOT NULL,
  `week_type` enum('all','odd','even') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `attributes`
--

CREATE TABLE `attributes` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `is_filterable` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_comparable` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `attribute_category`
--

CREATE TABLE `attribute_category` (
  `attribute_id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `attribute_values`
--

CREATE TABLE `attribute_values` (
  `id` bigint UNSIGNED NOT NULL,
  `attribute_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED DEFAULT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `value` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` bigint UNSIGNED DEFAULT NULL,
  `changes` json DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `changes`, `meta`, `created_at`, `updated_at`) VALUES
(1, NULL, 'created', 'App\\Models\\Promotion', 1, '{\"id\": 1, \"name\": \"10% Discount MacBook\", \"slug\": \"promo-macbook-10\", \"type\": \"standard\", \"value\": 10, \"end_at\": \"2026-02-06 19:50:18\", \"status\": \"active\", \"start_at\": \"2026-01-06 19:50:18\", \"applies_to\": \"products\", \"created_at\": \"2026-01-07 19:50:18\", \"updated_at\": \"2026-01-07 19:50:18\", \"value_type\": \"percent\", \"customer_type\": \"both\"}', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Symfony\"}', '2026-01-07 17:50:18', '2026-01-07 17:50:18'),
(2, NULL, 'created', 'App\\Models\\Promotion', 1, '{\"id\": 1, \"name\": \"10% Discount MacBook\", \"slug\": \"promo-macbook-10\", \"type\": \"standard\", \"value\": 10, \"end_at\": \"2026-02-06 19:51:52\", \"status\": \"active\", \"start_at\": \"2026-01-06 19:51:52\", \"applies_to\": \"products\", \"created_at\": \"2026-01-07 19:51:52\", \"updated_at\": \"2026-01-07 19:51:52\", \"value_type\": \"percent\", \"customer_type\": \"both\"}', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Symfony\"}', '2026-01-07 17:51:52', '2026-01-07 17:51:52'),
(3, NULL, 'created', 'App\\Models\\Promotion', 2, '{\"id\": 2, \"name\": \"200 RON Discount Galaxy\", \"slug\": \"promo-galaxy-200\", \"type\": \"standard\", \"value\": 200, \"end_at\": \"2026-02-06 19:51:52\", \"status\": \"active\", \"start_at\": \"2026-01-06 19:51:52\", \"applies_to\": \"products\", \"created_at\": \"2026-01-07 19:51:52\", \"updated_at\": \"2026-01-07 19:51:52\", \"value_type\": \"fixed_amount\", \"customer_type\": \"both\"}', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Symfony\"}', '2026-01-07 17:51:52', '2026-01-07 17:51:52'),
(4, NULL, 'created', 'App\\Models\\Promotion', 3, '{\"id\": 3, \"name\": \"15% Off Accessories\", \"slug\": \"promo-acc-15\", \"type\": \"standard\", \"value\": 15, \"end_at\": \"2026-02-06 19:51:52\", \"status\": \"active\", \"start_at\": \"2026-01-06 19:51:52\", \"applies_to\": \"categories\", \"created_at\": \"2026-01-07 19:51:52\", \"updated_at\": \"2026-01-07 19:51:52\", \"value_type\": \"percent\", \"customer_type\": \"both\"}', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Symfony\"}', '2026-01-07 17:51:52', '2026-01-07 17:51:52'),
(5, NULL, 'created', 'App\\Models\\Promotion', 1, '{\"id\": 1, \"name\": \"10% Discount MacBook\", \"slug\": \"promo-macbook-10\", \"type\": \"standard\", \"value\": 10, \"end_at\": \"2026-02-06 19:52:11\", \"status\": \"active\", \"start_at\": \"2026-01-06 19:52:11\", \"applies_to\": \"products\", \"created_at\": \"2026-01-07 19:52:11\", \"updated_at\": \"2026-01-07 19:52:11\", \"value_type\": \"percent\", \"customer_type\": \"both\"}', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Symfony\"}', '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(6, NULL, 'created', 'App\\Models\\Promotion', 2, '{\"id\": 2, \"name\": \"200 RON Discount Galaxy\", \"slug\": \"promo-galaxy-200\", \"type\": \"standard\", \"value\": 200, \"end_at\": \"2026-02-06 19:52:11\", \"status\": \"active\", \"start_at\": \"2026-01-06 19:52:11\", \"applies_to\": \"products\", \"created_at\": \"2026-01-07 19:52:11\", \"updated_at\": \"2026-01-07 19:52:11\", \"value_type\": \"fixed_amount\", \"customer_type\": \"both\"}', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Symfony\"}', '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(7, NULL, 'created', 'App\\Models\\Promotion', 3, '{\"id\": 3, \"name\": \"15% Off Accessories\", \"slug\": \"promo-acc-15\", \"type\": \"standard\", \"value\": 15, \"end_at\": \"2026-02-06 19:52:11\", \"status\": \"active\", \"start_at\": \"2026-01-06 19:52:11\", \"applies_to\": \"categories\", \"created_at\": \"2026-01-07 19:52:11\", \"updated_at\": \"2026-01-07 19:52:11\", \"value_type\": \"percent\", \"customer_type\": \"both\"}', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Symfony\"}', '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(8, NULL, 'created', 'App\\Models\\Promotion', 4, '{\"id\": 4, \"name\": \"5% Off Apple Brand\", \"slug\": \"promo-apple-5\", \"type\": \"standard\", \"value\": 5, \"end_at\": \"2026-02-06 19:52:11\", \"status\": \"active\", \"start_at\": \"2026-01-06 19:52:11\", \"applies_to\": \"brands\", \"created_at\": \"2026-01-07 19:52:11\", \"updated_at\": \"2026-01-07 19:52:11\", \"value_type\": \"percent\", \"customer_type\": \"both\"}', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Symfony\"}', '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(9, NULL, 'created', 'App\\Models\\Promotion', 5, '{\"id\": 5, \"name\": \"100 RON Off Orders > 5000\", \"slug\": \"promo-cart-5000\", \"type\": \"standard\", \"value\": 100, \"end_at\": \"2026-02-06 19:52:11\", \"status\": \"active\", \"start_at\": \"2026-01-06 19:52:11\", \"applies_to\": \"all\", \"created_at\": \"2026-01-07 19:52:11\", \"updated_at\": \"2026-01-07 19:52:11\", \"value_type\": \"fixed_amount\", \"customer_type\": \"both\", \"min_cart_total\": 5000}', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Symfony\"}', '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(10, NULL, 'created', 'App\\Models\\Promotion', 6, '{\"id\": 6, \"name\": \"Buy 5 Cables Get 20% Off\", \"slug\": \"promo-cables-bulk\", \"type\": \"volume\", \"value\": 20, \"end_at\": \"2026-02-06 19:52:11\", \"status\": \"active\", \"start_at\": \"2026-01-06 19:52:11\", \"applies_to\": \"products\", \"created_at\": \"2026-01-07 19:52:11\", \"updated_at\": \"2026-01-07 19:52:11\", \"value_type\": \"percent\", \"customer_type\": \"both\", \"min_qty_per_product\": 5}', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Symfony\"}', '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(11, NULL, 'created', 'App\\Models\\Promotion', 7, '{\"id\": 7, \"name\": \"Free Mouse with Laptop\", \"slug\": \"promo-free-mouse\", \"type\": \"gift\", \"value\": 0, \"end_at\": \"2026-02-06 19:52:11\", \"status\": \"active\", \"settings\": \"\\\"{\\\\\\\"gift_product_id\\\\\\\":3,\\\\\\\"gift_qty\\\\\\\":1}\\\"\", \"start_at\": \"2026-01-06 19:52:11\", \"applies_to\": \"products\", \"created_at\": \"2026-01-07 19:52:11\", \"updated_at\": \"2026-01-07 19:52:11\", \"value_type\": \"fixed_amount\", \"customer_type\": \"both\", \"min_qty_per_product\": 1}', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Symfony\"}', '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(12, 4, 'updated', 'App\\Models\\Promotion', 7, '{\"end_at\": \"2026-02-06 00:00:00\", \"settings\": \"{\\\"gift_qty\\\":1,\\\"gift_product_id\\\":1}\", \"start_at\": \"2026-01-06 00:00:00\", \"updated_at\": \"2026-01-07 20:12:00\", \"min_qty_per_product\": 3}', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Trae/1.104.3 Chrome/138.0.7204.251 Electron/37.6.1 Safari/537.36\"}', '2026-01-07 18:12:00', '2026-01-07 18:12:00');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `blog_categories`
--

CREATE TABLE `blog_categories` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `blog_categories`
--

INSERT INTO `blog_categories` (`id`, `name`, `slug`, `description`, `is_published`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Noutăți', 'noutati', NULL, 1, 0, '2026-01-07 17:45:30', '2026-01-07 17:45:30');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` bigint UNSIGNED DEFAULT NULL,
  `author_user_id` bigint UNSIGNED DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `published_at` timestamp NULL DEFAULT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `title`, `slug`, `excerpt`, `content`, `image_path`, `category_id`, `author_user_id`, `is_published`, `published_at`, `meta_title`, `meta_description`, `created_at`, `updated_at`) VALUES
(1, 'Tendințe în construcții pentru 2026', 'tendinte-in-constructii-pentru-2026', 'Descoperă noile tehnologii și materiale care vor domina piața construcțiilor în acest an.', '<p>Industria construcțiilor este în continuă evoluție...</p>', 'https://placehold.co/800x450?text=Tendinte+2026', 1, NULL, 1, '2026-01-05 17:45:30', NULL, NULL, '2026-01-07 17:45:30', '2026-01-07 17:45:30'),
(2, 'Cum să alegi materialele potrivite pentru izolație', 'cum-sa-alegi-materialele-potrivite-pentru-izolatie', 'Ghid complet pentru alegerea celor mai eficiente materiale termoizolante.', '<p>Izolația termică este crucială pentru eficiența energetică...</p>', 'https://placehold.co/800x450?text=Izolatie', 1, NULL, 1, '2026-01-02 17:45:30', NULL, NULL, '2026-01-07 17:45:30', '2026-01-07 17:45:30'),
(3, 'Lansăm noua gamă de scule profesionale', 'lansam-noua-gama-de-scule-profesionale', 'Suntem mândri să anunțăm parteneriatul cu brandul X pentru scule de înaltă performanță.', '<p>Noua gamă de scule include...</p>', 'https://placehold.co/800x450?text=Scule+Profesionale', 1, NULL, 1, '2025-12-28 17:45:30', NULL, NULL, '2026-01-07 17:45:30', '2026-01-07 17:45:30');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `brands`
--

CREATE TABLE `brands` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`, `logo_path`, `description`, `is_published`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Apple', 'apple', NULL, NULL, 1, 0, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(2, 'Samsung', 'samsung', NULL, NULL, 1, 0, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(3, 'Logitech', 'logitech', NULL, NULL, 1, 0, '2026-01-07 17:52:11', '2026-01-07 17:52:11');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `brand_promotion`
--

CREATE TABLE `brand_promotion` (
  `promotion_id` bigint UNSIGNED NOT NULL,
  `brand_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `brand_promotion`
--

INSERT INTO `brand_promotion` (`promotion_id`, `brand_id`) VALUES
(4, 1);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `carts`
--

CREATE TABLE `carts` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `session_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `coupon_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `carts`
--

INSERT INTO `carts` (`id`, `customer_id`, `user_id`, `session_id`, `status`, `created_at`, `updated_at`, `coupon_id`) VALUES
(1, NULL, 2, NULL, 'active', '2026-01-07 18:12:07', '2026-01-07 18:12:07', NULL),
(2, 4, 2, NULL, 'active', '2026-01-07 18:12:52', '2026-01-07 18:12:52', NULL);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `cart_items`
--

CREATE TABLE `cart_items` (
  `id` bigint UNSIGNED NOT NULL,
  `cart_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `product_variant_id`, `quantity`, `unit_price`, `total`, `created_at`, `updated_at`) VALUES
(1, 2, 1, NULL, 3, 12000.00, 36000.00, '2026-01-07 18:13:06', '2026-01-07 18:16:20');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `cart_promotion`
--

CREATE TABLE `cart_promotion` (
  `id` bigint UNSIGNED NOT NULL,
  `cart_id` bigint UNSIGNED NOT NULL,
  `promotion_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `categories`
--

CREATE TABLE `categories` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_desktop` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_mobile` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `slug`, `parent_id`, `sort_order`, `is_published`, `image_path`, `banner_desktop`, `banner_mobile`, `meta_title`, `meta_description`, `created_at`, `updated_at`) VALUES
(1, 'Electronics', NULL, 'electronics', NULL, 0, 1, NULL, NULL, NULL, NULL, NULL, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(2, 'Laptops', NULL, 'laptops', 1, 0, 1, NULL, NULL, NULL, NULL, NULL, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(3, 'Accessories', NULL, 'accessories', 1, 0, 1, NULL, NULL, NULL, NULL, NULL, '2026-01-07 17:52:11', '2026-01-07 17:52:11');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `category_product`
--

CREATE TABLE `category_product` (
  `category_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `category_product`
--

INSERT INTO `category_product` (`category_id`, `product_id`) VALUES
(1, 1),
(2, 1);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `category_promotion`
--

CREATE TABLE `category_promotion` (
  `promotion_id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `category_promotion`
--

INSERT INTO `category_promotion` (`promotion_id`, `category_id`) VALUES
(3, 3);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `content_blocks`
--

CREATE TABLE `content_blocks` (
  `id` bigint UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `content_blocks`
--

INSERT INTO `content_blocks` (`id`, `key`, `group`, `type`, `content`, `title`, `meta`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'home_hero_title', 'home', 'text', 'Soluții B2B Premium pentru Afacerea Ta', 'Home Hero Title', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(2, 'home_hero_subtitle', 'home', 'text', 'Descoperă catalogul nostru complet de produse și servicii dedicate partenerilor.', 'Home Hero Subtitle', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(3, 'home_hero_cta_text', 'home', 'text', 'Vezi Catalogul', 'Home Hero CTA Text', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(4, 'home_hero_cta_link', 'home', 'text', '/categories', 'Home Hero CTA Link', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(5, 'home_features_title', 'home', 'text', 'De ce să alegi platforma noastră?', 'Home Features Title', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(6, 'home_features_list', 'home', 'json', '[{\"icon\":\"bi-truck\",\"title\":\"Livrare Rapid\\u0103\",\"description\":\"Expediere \\u00een 24h pentru produsele din stoc.\"},{\"icon\":\"bi-shield-check\",\"title\":\"Calitate Garantat\\u0103\",\"description\":\"Produse verificate \\u0219i certificate conform standardelor UE.\"},{\"icon\":\"bi-headset\",\"title\":\"Suport Dedicat\",\"description\":\"Consultan\\u021b\\u0103 tehnic\\u0103 specializat\\u0103 pentru parteneri.\"},{\"icon\":\"bi-wallet2\",\"title\":\"Pre\\u021buri Competitive\",\"description\":\"Oferte personalizate \\u0219i discount-uri de volum.\"}]', 'Home Features List', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(7, 'footer_about_text', 'footer', 'text', 'Suntem partenerul tău de încredere în distribuția de echipamente și soluții industriale. Calitate și profesionalism din 2010.', 'Footer About Text', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(8, 'footer_contact_address', 'footer', 'text', 'Strada Industriei nr. 15, București', 'Footer Contact Address', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(9, 'footer_contact_phone', 'footer', 'text', '+40 722 123 456', 'Footer Contact Phone', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(10, 'footer_contact_email', 'footer', 'text', 'contact@b2b-portal.ro', 'Footer Contact Email', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(11, 'footer_copyright', 'footer', 'text', '© 2026 B2B Portal. Toate drepturile rezervate.', 'Footer Copyright', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(12, 'footer_social_links', 'footer', 'json', '[{\"icon\":\"bi bi-facebook\",\"url\":\"https:\\/\\/facebook.com\",\"label\":\"Facebook\"},{\"icon\":\"bi bi-linkedin\",\"url\":\"https:\\/\\/linkedin.com\",\"label\":\"LinkedIn\"},{\"icon\":\"bi bi-instagram\",\"url\":\"https:\\/\\/instagram.com\",\"label\":\"Instagram\"}]', 'Footer Social Links', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(13, 'footer_column_1', 'footer', 'json', '{\"title\":\"Companie\",\"links\":[{\"text\":\"Despre Noi\",\"url\":\"\\/despre-noi\"},{\"text\":\"Cariere\",\"url\":\"\\/cariere\"},{\"text\":\"Sustenabilitate\",\"url\":\"\\/sustenabilitate\"},{\"text\":\"Blog\",\"url\":\"\\/blog\"}]}', 'Footer Column 1', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(14, 'footer_column_2', 'footer', 'json', '{\"title\":\"Suport\",\"links\":[{\"text\":\"Contact\",\"url\":\"\\/contact\"},{\"text\":\"\\u00centreb\\u0103ri Frecvente\",\"url\":\"\\/faq\"},{\"text\":\"Livrare \\u0219i Retur\",\"url\":\"\\/livrare-retur\"},{\"text\":\"Garan\\u021bii\",\"url\":\"\\/garantii\"}]}', 'Footer Column 2', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(15, 'home_testimonials_title', 'home', 'text', 'Ce spun partenerii noștri', 'Home Testimonials Title', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(16, 'home_testimonials_list', 'home', 'json', '[{\"name\":\"Alexandru Popescu\",\"company\":\"Construct Vest SRL\",\"text\":\"Colabor\\u0103m de 5 ani \\u0219i suntem extrem de mul\\u021bumi\\u021bi de promptitudinea livr\\u0103rilor.\",\"rating\":5},{\"name\":\"Maria Ionescu\",\"company\":\"Design Interior SA\",\"text\":\"Gama variat\\u0103 de produse ne ajut\\u0103 s\\u0103 satisfacem cerin\\u021bele clien\\u021bilor no\\u0219tri.\",\"rating\":5},{\"name\":\"Ionut Radu\",\"company\":\"Tech Solutions SRL\",\"text\":\"Suportul tehnic este excelent, ne-au ajutat s\\u0103 alegem solu\\u021biile potrivite.\",\"rating\":4}]', 'Home Testimonials List', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(17, 'footer_contact_schedule', 'footer', 'text', 'Luni - Vineri: 09:00 - 18:00', 'Footer Contact Schedule', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(18, 'home_categories_title', 'home', 'text', 'Categorii populare', 'Home Categories Title', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(19, 'home_promotions_title', 'home', 'text', 'Promoții active', 'Home Promotions Title', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(20, 'home_new_products_title', 'home', 'text', 'Produse noi', 'Home New Products Title', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(21, 'home_recommended_title', 'home', 'text', 'Produse recomandate', 'Home Recommended Title', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(22, 'home_brands_title', 'home', 'text', 'Branduri partenere', 'Home Brands Title', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(23, 'home_blog_title', 'home', 'text', 'Noutăți de pe blog', 'Home Blog Title', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(24, 'footer_newsletter_title', 'footer', 'text', 'Abonează-te la newsletter', 'Footer Newsletter Title', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(25, 'footer_newsletter_text', 'footer', 'text', 'Primește ultimele oferte și noutăți direct pe email.', 'Footer Newsletter Text', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(26, 'footer_newsletter_placeholder', 'footer', 'text', 'Adresa ta de email', 'Footer Newsletter Placeholder', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(27, 'footer_newsletter_button', 'footer', 'text', 'Abonează-te', 'Footer Newsletter Button', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(28, 'footer_social_title', 'footer', 'text', 'Social Media', 'Footer Social Title', NULL, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `contract_prices`
--

CREATE TABLE `contract_prices` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `customer_group_id` bigint UNSIGNED DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RON',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `coupons`
--

CREATE TABLE `coupons` (
  `id` bigint UNSIGNED NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount_type` enum('percent','fixed_cart','fixed_product') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL,
  `usage_limit` int DEFAULT NULL,
  `usage_limit_per_user` int DEFAULT NULL,
  `min_cart_value` decimal(12,2) DEFAULT NULL,
  `start_at` timestamp NULL DEFAULT NULL,
  `end_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_stackable` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `description`, `discount_type`, `discount_value`, `usage_limit`, `usage_limit_per_user`, `min_cart_value`, `start_at`, `end_at`, `is_active`, `is_stackable`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME10', NULL, 'percent', 10.00, NULL, NULL, NULL, '2026-01-06 17:52:11', '2026-02-07 17:52:11', 1, 0, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(2, 'EXTRA50', NULL, 'fixed_cart', 50.00, NULL, NULL, 500.00, '2026-01-06 17:52:11', '2026-02-07 17:52:11', 1, 1, '2026-01-07 17:52:11', '2026-01-07 17:52:11');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `customers`
--

CREATE TABLE `customers` (
  `id` bigint UNSIGNED NOT NULL,
  `type` enum('b2c','b2b') COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `legal_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cif` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reg_com` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iban` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_id` bigint UNSIGNED DEFAULT NULL,
  `agent_user_id` bigint UNSIGNED DEFAULT NULL,
  `sales_director_user_id` bigint UNSIGNED DEFAULT NULL,
  `payment_terms_days` int NOT NULL DEFAULT '0',
  `credit_limit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `allow_global_discount` tinyint(1) NOT NULL DEFAULT '0',
  `allow_line_discount` tinyint(1) NOT NULL DEFAULT '0',
  `current_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RON',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_partner` tinyint(1) NOT NULL DEFAULT '0',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `customers`
--

INSERT INTO `customers` (`id`, `type`, `name`, `legal_name`, `cif`, `reg_com`, `iban`, `email`, `phone`, `group_id`, `agent_user_id`, `sales_director_user_id`, `payment_terms_days`, `credit_limit`, `allow_global_discount`, `allow_line_discount`, `current_balance`, `currency`, `is_active`, `is_partner`, `latitude`, `longitude`, `created_at`, `updated_at`) VALUES
(1, 'b2c', 'John Doe Company', NULL, NULL, NULL, NULL, 'john@test.com', '0700000001', 1, NULL, NULL, 0, 0.00, 0, 0, 0.00, 'RON', 1, 0, NULL, NULL, '2026-01-07 17:52:10', '2026-01-07 17:52:10'),
(2, 'b2b', 'Jane Smith Ltd', NULL, 'RO123456', 'J40/123/2020', NULL, 'jane@test.com', '0700000002', 2, NULL, NULL, 0, 0.00, 1, 1, 0.00, 'RON', 1, 0, NULL, NULL, '2026-01-07 17:52:10', '2026-01-07 17:52:10'),
(3, 'b2b', 'Demo Customer 1', NULL, NULL, NULL, NULL, 'demo1@test.com', '0700000001', 1, 3, 1, 30, 50000.00, 0, 0, 0.00, 'RON', 1, 0, NULL, NULL, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(4, 'b2b', 'Demo Customer 2', NULL, NULL, NULL, NULL, 'demo2@test.com', '0700000002', 1, 2, 1, 30, 50000.00, 0, 0, 0.00, 'RON', 1, 0, NULL, NULL, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(5, 'b2b', 'Demo Customer 3', NULL, NULL, NULL, NULL, 'demo3@test.com', '0700000003', 1, 3, 1, 30, 50000.00, 0, 0, 2800.00, 'RON', 1, 0, NULL, NULL, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(6, 'b2b', 'Demo Customer 4', NULL, NULL, NULL, NULL, 'demo4@test.com', '0700000004', 1, 2, 1, 30, 50000.00, 0, 0, 0.00, 'RON', 1, 0, NULL, NULL, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(7, 'b2b', 'Demo Customer 5', NULL, NULL, NULL, NULL, 'demo5@test.com', '0700000005', 1, 3, 1, 30, 50000.00, 0, 0, 0.00, 'RON', 1, 0, NULL, NULL, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(8, 'b2b', 'Demo Customer 6', NULL, NULL, NULL, NULL, 'demo6@test.com', '0700000006', 1, 2, 1, 30, 50000.00, 0, 0, 3100.00, 'RON', 1, 0, NULL, NULL, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(9, 'b2b', 'Demo Customer 7', NULL, NULL, NULL, NULL, 'demo7@test.com', '0700000007', 1, 3, 1, 30, 50000.00, 0, 0, 0.00, 'RON', 1, 0, NULL, NULL, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(10, 'b2b', 'Demo Customer 8', NULL, NULL, NULL, NULL, 'demo8@test.com', '0700000008', 1, 2, 1, 30, 50000.00, 0, 0, 0.00, 'RON', 1, 0, NULL, NULL, '2026-01-07 17:52:11', '2026-01-07 17:52:11');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `customer_groups`
--

CREATE TABLE `customer_groups` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('b2b','b2c') COLLATE utf8mb4_unicode_ci NOT NULL,
  `default_discount_percent` decimal(8,2) NOT NULL DEFAULT '0.00',
  `default_payment_terms_days` int NOT NULL DEFAULT '0',
  `default_credit_limit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `customer_groups`
--

INSERT INTO `customer_groups` (`id`, `name`, `type`, `default_discount_percent`, `default_payment_terms_days`, `default_credit_limit`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Distribuitori B2B', 'b2b', 5.00, 0, 0.00, 1, '2026-01-07 17:52:10', '2026-01-07 17:52:10'),
(2, 'VIP Retail', 'b2c', 2.00, 0, 0.00, 1, '2026-01-07 17:52:10', '2026-01-07 17:52:10');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `customer_group_promotion`
--

CREATE TABLE `customer_group_promotion` (
  `promotion_id` bigint UNSIGNED NOT NULL,
  `customer_group_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `customer_promotion`
--

CREATE TABLE `customer_promotion` (
  `promotion_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `customer_visits`
--

CREATE TABLE `customer_visits` (
  `id` bigint UNSIGNED NOT NULL,
  `agent_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `status` enum('planned','in_progress','completed','missed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planned',
  `outcome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'The result of the visit (e.g. order_placed, no_interest, etc)',
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `end_latitude` decimal(10,8) DEFAULT NULL,
  `end_longitude` decimal(11,8) DEFAULT NULL,
  `distance_deviation` int DEFAULT NULL COMMENT 'Distance in meters from customer location at check-in',
  `is_off_site` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'True if check-in was far from customer location',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `customer_visits`
--

INSERT INTO `customer_visits` (`id`, `agent_id`, `customer_id`, `status`, `outcome`, `start_time`, `end_time`, `latitude`, `longitude`, `end_latitude`, `end_longitude`, `distance_deviation`, `is_off_site`, `notes`, `created_at`, `updated_at`) VALUES
(1, 2, 4, 'in_progress', NULL, '2026-01-07 18:12:26', NULL, 46.21354400, 27.66465400, NULL, NULL, 5804703, 1, NULL, '2026-01-07 18:12:26', '2026-01-07 18:12:55');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `discount_rules`
--

CREATE TABLE `discount_rules` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rule_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'approval_threshold',
  `target_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'global',
  `target_id` bigint UNSIGNED DEFAULT NULL,
  `limit_percent` decimal(5,2) NOT NULL,
  `apply_to_total` tinyint(1) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `erp_sync_logs`
--

CREATE TABLE `erp_sync_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direction` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payload` json DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `run_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `erp_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'invoice',
  `series` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'issued',
  `issue_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RON',
  `pdf_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `invoices`
--

INSERT INTO `invoices` (`id`, `customer_id`, `order_id`, `erp_id`, `type`, `series`, `number`, `status`, `issue_date`, `due_date`, `subtotal`, `tax_total`, `total`, `currency`, `pdf_url`, `meta`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'ERP-11715', 'fiscal', 'FACT', '84421', 'unpaid', '2025-11-09', '2025-11-10', 2608.09, 495.54, 3103.63, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(2, 1, NULL, 'ERP-31644', 'fiscal', 'FACT', '58450', 'unpaid', '2025-11-17', '2025-11-19', 4418.42, 839.50, 5257.92, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(3, 1, NULL, 'ERP-78962', 'fiscal', 'FACT', '59799', 'unpaid', '2025-12-03', '2026-01-31', 3181.24, 604.44, 3785.68, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(4, 1, NULL, 'ERP-39777', 'fiscal', 'FACT', '25533', 'overdue', '2025-11-20', '2025-12-28', 494.62, 93.98, 588.60, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(5, 2, NULL, 'ERP-72834', 'fiscal', 'FACT', '18906', 'unpaid', '2025-12-25', '2026-01-02', 1456.52, 276.74, 1733.26, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(6, 2, NULL, 'ERP-67418', 'fiscal', 'FACT', '38429', 'unpaid', '2025-12-28', '2026-01-08', 1423.41, 270.45, 1693.86, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(7, 2, NULL, 'ERP-14162', 'fiscal', 'FACT', '86472', 'unpaid', '2025-11-29', '2025-11-30', 1704.26, 323.81, 2028.07, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(8, 2, NULL, 'ERP-22418', 'fiscal', 'FACT', '6637', 'overdue', '2025-12-22', '2025-12-28', 4283.87, 813.94, 5097.81, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(9, 3, NULL, 'ERP-41623', 'fiscal', 'FACT', '27383', 'unpaid', '2025-11-12', '2026-01-06', 3127.06, 594.14, 3721.20, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(10, 3, NULL, 'ERP-80955', 'fiscal', 'FACT', '48243', 'unpaid', '2026-01-03', '2026-01-14', 3108.26, 590.57, 3698.83, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(11, 3, NULL, 'ERP-27128', 'fiscal', 'FACT', '8005', 'unpaid', '2025-12-16', '2026-01-28', 961.02, 182.59, 1143.61, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(12, 3, NULL, 'ERP-45089', 'fiscal', 'FACT', '84876', 'overdue', '2025-12-27', '2025-12-28', 3116.26, 592.09, 3708.35, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(13, 4, NULL, 'ERP-31417', 'fiscal', 'FACT', '70305', 'unpaid', '2025-12-27', '2025-12-29', 2624.13, 498.58, 3122.71, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(14, 4, NULL, 'ERP-73308', 'fiscal', 'FACT', '84127', 'unpaid', '2025-12-26', '2026-01-14', 2490.50, 473.20, 2963.70, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(15, 4, NULL, 'ERP-15203', 'fiscal', 'FACT', '8487', 'unpaid', '2025-12-02', '2026-01-19', 3853.19, 732.11, 4585.30, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(16, 4, NULL, 'ERP-41127', 'fiscal', 'FACT', '32522', 'overdue', '2025-11-30', '2025-12-28', 1324.21, 251.60, 1575.81, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(17, 5, NULL, 'ERP-25383', 'fiscal', 'FACT', '84685', 'unpaid', '2025-11-13', '2025-12-20', 1025.60, 194.86, 1220.46, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(18, 5, NULL, 'ERP-16557', 'fiscal', 'FACT', '48704', 'unpaid', '2025-12-20', '2026-01-18', 4217.80, 801.38, 5019.18, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(19, 5, NULL, 'ERP-70744', 'fiscal', 'FACT', '25960', 'unpaid', '2025-12-06', '2025-12-11', 2905.15, 551.98, 3457.13, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(20, 5, NULL, 'ERP-78727', 'fiscal', 'FACT', '28088', 'overdue', '2025-12-03', '2025-12-28', 116.94, 22.22, 139.16, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(21, 6, NULL, 'ERP-17631', 'fiscal', 'FACT', '92925', 'unpaid', '2026-01-06', '2026-01-26', 1690.41, 321.18, 2011.59, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(22, 6, NULL, 'ERP-31023', 'fiscal', 'FACT', '54113', 'unpaid', '2025-12-08', '2025-12-09', 2363.45, 449.06, 2812.51, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(23, 6, NULL, 'ERP-57661', 'fiscal', 'FACT', '23714', 'unpaid', '2025-12-05', '2025-12-28', 4511.82, 857.25, 5369.07, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(24, 6, NULL, 'ERP-74442', 'fiscal', 'FACT', '57055', 'overdue', '2025-12-11', '2025-12-28', 589.22, 111.95, 701.17, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(25, 7, NULL, 'ERP-87855', 'fiscal', 'FACT', '55296', 'unpaid', '2026-01-01', '2026-01-27', 1784.86, 339.12, 2123.98, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(26, 7, NULL, 'ERP-75996', 'fiscal', 'FACT', '98621', 'unpaid', '2025-11-22', '2025-11-29', 1187.29, 225.59, 1412.88, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(27, 7, NULL, 'ERP-79473', 'fiscal', 'FACT', '56711', 'unpaid', '2025-11-11', '2025-11-23', 4286.96, 814.52, 5101.48, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(28, 7, NULL, 'ERP-62551', 'fiscal', 'FACT', '25590', 'overdue', '2025-12-08', '2025-12-28', 2655.01, 504.45, 3159.46, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(29, 8, NULL, 'ERP-55834', 'fiscal', 'FACT', '37595', 'unpaid', '2025-11-20', '2026-01-04', 2476.06, 470.45, 2946.51, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(30, 8, NULL, 'ERP-80402', 'fiscal', 'FACT', '15302', 'unpaid', '2026-01-03', '2026-01-21', 2438.57, 463.33, 2901.90, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(31, 8, NULL, 'ERP-98882', 'fiscal', 'FACT', '21291', 'unpaid', '2025-11-09', '2025-12-31', 4166.19, 791.58, 4957.77, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(32, 8, NULL, 'ERP-41259', 'fiscal', 'FACT', '19444', 'overdue', '2025-12-10', '2025-12-28', 1437.31, 273.09, 1710.40, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(33, 9, NULL, 'ERP-45423', 'fiscal', 'FACT', '93021', 'unpaid', '2025-12-05', '2026-01-07', 1917.68, 364.36, 2282.04, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(34, 9, NULL, 'ERP-73437', 'fiscal', 'FACT', '70889', 'unpaid', '2025-12-19', '2026-01-23', 1643.17, 312.20, 1955.37, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(35, 9, NULL, 'ERP-86023', 'fiscal', 'FACT', '78850', 'unpaid', '2025-11-28', '2026-02-04', 4149.45, 788.40, 4937.85, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(36, 9, NULL, 'ERP-91441', 'fiscal', 'FACT', '92329', 'overdue', '2025-11-16', '2025-12-28', 483.26, 91.82, 575.08, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(37, 10, NULL, 'ERP-83954', 'fiscal', 'FACT', '46355', 'unpaid', '2025-12-31', '2026-01-01', 1505.07, 285.96, 1791.03, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(38, 10, NULL, 'ERP-37312', 'fiscal', 'FACT', '73178', 'unpaid', '2025-12-17', '2025-12-18', 1158.18, 220.05, 1378.23, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(39, 10, NULL, 'ERP-66592', 'fiscal', 'FACT', '73894', 'unpaid', '2025-11-16', '2026-01-03', 4367.27, 829.78, 5197.05, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29'),
(40, 10, NULL, 'ERP-20262', 'fiscal', 'FACT', '87055', 'overdue', '2025-11-21', '2025-12-28', 1593.80, 302.82, 1896.62, 'RON', NULL, NULL, '2026-01-07 17:45:29', '2026-01-07 17:45:29');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000001_create_cache_table', 1),
(2, '0001_01_01_000002_create_jobs_table', 1),
(3, '2025_01_01_000100_create_customer_groups_table', 1),
(4, '2025_01_01_000200_create_customers_table', 1),
(5, '2025_01_01_000300_create_users_table_custom', 1),
(6, '2025_01_01_000400_create_addresses_table', 1),
(7, '2025_01_01_000500_create_categories_table', 1),
(8, '2025_01_01_000600_create_brands_table', 1),
(9, '2025_01_01_000700_create_products_tables', 1),
(10, '2025_01_01_000800_create_cart_tables', 1),
(11, '2025_01_01_000850_create_shipping_tables', 1),
(12, '2025_01_01_000900_create_orders_tables', 1),
(13, '2025_01_01_001000_create_promotions_tables', 1),
(14, '2025_12_11_111146_create_personal_access_tokens_table', 1),
(15, '2025_12_11_112528_create_tickets_tables', 1),
(16, '2025_12_11_113024_create_sales_representatives_table', 1),
(17, '2025_12_11_113211_create_partner_requests_table', 1),
(18, '2025_12_11_113341_create_payments_table', 1),
(19, '2025_12_11_113443_create_blog_tables', 1),
(20, '2025_12_11_115702_create_recently_viewed_products_table', 1),
(21, '2025_12_11_115720_create_product_comparisons_table', 1),
(22, '2025_12_11_115730_create_order_templates_tables', 1),
(23, '2025_12_11_120545_create_notifications_table', 1),
(24, '2025_12_11_121016_create_quote_requests_tables', 1),
(25, '2025_12_11_121042_add_sales_links_to_customers_table', 1),
(26, '2025_12_11_121351_create_erp_sync_logs_table', 1),
(27, '2025_12_11_121500_create_shipments_table', 1),
(28, '2025_12_11_121929_create_roles_and_permissions_tables', 1),
(29, '2025_12_11_122225_create_audit_logs_table', 1),
(30, '2025_12_11_131138_add_cancellation_fields_to_orders_table', 1),
(31, '2025_12_11_131321_create_invoices_table', 1),
(32, '2025_12_11_131517_add_b2b_fields_to_users_table', 1),
(33, '2025_12_11_131550_add_approval_fields_to_orders_table', 1),
(34, '2025_12_11_132905_add_flags_to_products_table', 1),
(35, '2025_12_11_165414_add_slug_to_roles_table', 1),
(36, '2025_12_15_094116_add_price_to_products_table', 1),
(37, '2025_12_15_141740_create_product_documents_table', 1),
(38, '2025_12_31_074600_create_promotion_customer_group_table', 1),
(39, '2025_12_31_074832_create_promotion_customer_table', 1),
(40, '2025_12_31_081714_create_promotion_category_table', 1),
(41, '2025_12_31_082000_create_promotion_brand_table', 1),
(42, '2025_12_31_082500_create_promotion_product_table', 1),
(43, '2025_12_31_103157_create_payment_invoices_table', 1),
(44, '2025_12_31_103157_create_receipt_books_table', 1),
(45, '2025_12_31_103221_add_receipt_book_id_to_payments_table', 1),
(46, '2025_12_31_114141_add_is_active_to_customer_groups_table', 1),
(47, '2025_12_31_114755_create_contract_prices_table', 1),
(48, '2025_12_31_114755_create_coupons_table', 1),
(49, '2025_12_31_114756_update_promotions_structure', 1),
(50, '2025_12_31_115707_add_coupon_id_to_carts_table', 1),
(51, '2025_12_31_120000_make_customer_id_nullable_in_payments', 1),
(52, '2025_12_31_120054_add_missing_columns_to_promotions_table', 1),
(53, '2025_12_31_130000_add_bo_cec_fields_to_payments', 1),
(54, '2025_12_31_140000_update_products_table_comprehensive', 1),
(55, '2026_01_05_100715_update_hierarchy_and_add_routes_visits_tables', 1),
(56, '2026_01_05_105927_add_visit_id_to_orders_and_week_type_to_routes', 1),
(57, '2026_01_05_112606_add_outcome_to_customer_visits_table', 1),
(58, '2026_01_05_113201_create_sales_targets_table', 1),
(59, '2026_01_05_124851_create_agent_customer_table', 1),
(60, '2026_01_05_130827_add_location_fields_to_customers_and_visits', 1),
(61, '2026_01_05_132951_create_visit_location_logs_table', 1),
(62, '2026_01_05_133351_add_telemetry_fields_to_visit_location_logs_table', 1),
(63, '2026_01_05_133652_create_agent_tracking_tables', 1),
(64, '2026_01_05_140430_create_sales_target_items_table', 1),
(65, '2026_01_05_154605_create_offers_table', 1),
(66, '2026_01_05_154613_create_offer_items_table', 1),
(67, '2026_01_05_154621_create_offer_messages_table', 1),
(68, '2026_01_05_162627_create_settings_table', 1),
(69, '2026_01_05_170456_add_quote_request_id_to_offers_table', 1),
(70, '2026_01_05_193721_add_notification_preferences_to_users_table', 1),
(71, '2026_01_05_193952_create_product_stock_alerts_table', 1),
(72, '2026_01_06_000001_create_product_reviews_table', 1),
(73, '2026_01_06_093428_create_content_blocks_table', 1),
(74, '2026_01_06_094905_add_image_path_to_blog_posts_table', 1),
(75, '2026_01_06_100034_add_seo_fields_to_categories_table', 1),
(76, '2026_01_06_131650_add_customer_visit_id_to_offers_table', 1),
(77, '2026_01_07_085306_add_discount_flags_to_customers_and_payment_details_to_orders', 1),
(78, '2026_01_07_090839_add_internal_note_to_orders_table', 1),
(79, '2026_01_07_111258_add_applied_promotions_to_order_items_table', 1),
(80, '2026_01_07_111756_update_promotions_table_structure', 1),
(81, '2026_01_07_112040_create_promotion_tiers_table', 1),
(82, '2026_01_07_163001_create_discount_rules_table', 1),
(83, '2026_01_07_174500_remove_priority_columns', 1),
(84, '2026_01_07_181436_create_cart_promotion_table', 1);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint UNSIGNED NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `offers`
--

CREATE TABLE `offers` (
  `id` bigint UNSIGNED NOT NULL,
  `agent_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `customer_visit_id` bigint UNSIGNED DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `requires_director_approval` tinyint(1) NOT NULL DEFAULT '0',
  `valid_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `quote_request_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `offer_items`
--

CREATE TABLE `offer_items` (
  `id` bigint UNSIGNED NOT NULL,
  `offer_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `final_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `offer_messages`
--

CREATE TABLE `offer_messages` (
  `id` bigint UNSIGNED NOT NULL,
  `offer_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `orders`
--

CREATE TABLE `orders` (
  `id` bigint UNSIGNED NOT NULL,
  `order_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `placed_by_user_id` bigint UNSIGNED DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approval_status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `approved_by_user_id` bigint UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'b2c',
  `total_items` int NOT NULL DEFAULT '0',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `global_discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `discount_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `shipping_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RON',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_document` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `shipping_method_id` bigint UNSIGNED DEFAULT NULL,
  `billing_address_id` bigint UNSIGNED DEFAULT NULL,
  `shipping_address_id` bigint UNSIGNED DEFAULT NULL,
  `credit_blocked` tinyint(1) NOT NULL DEFAULT '0',
  `placed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auto_cancelled` tinyint(1) NOT NULL DEFAULT '0',
  `due_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `customer_visit_id` bigint UNSIGNED DEFAULT NULL,
  `internal_note` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED DEFAULT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `applied_promotions` json DEFAULT NULL COMMENT 'Snapshot of promotions applied to this item',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `order_templates`
--

CREATE TABLE `order_templates` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `order_template_items`
--

CREATE TABLE `order_template_items` (
  `id` bigint UNSIGNED NOT NULL,
  `order_template_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` decimal(15,3) NOT NULL DEFAULT '1.000',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `pages`
--

CREATE TABLE `pages` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `pages`
--

INSERT INTO `pages` (`id`, `title`, `slug`, `content`, `is_published`, `meta_title`, `meta_description`, `created_at`, `updated_at`) VALUES
(1, 'Despre Noi', 'despre-noi', '\n                    <h2 class=\"h4 mb-4\">Cine suntem</h2>\n                    <p>Suntem o companie dedicată furnizării de soluții complete pentru construcții și industrie. Cu o experiență de peste 15 ani pe piață, ne mândrim cu un portofoliu vast de produse și servicii adaptate nevoilor clienților noștri.</p>\n                    \n                    <h3 class=\"h5 mt-4 mb-3\">Misiunea Noastră</h3>\n                    <p>Misiunea noastră este să oferim calitate și inovație, contribuind la succesul proiectelor partenerilor noștri prin livrarea rapidă a materialelor necesare.</p>\n                    \n                    <h3 class=\"h5 mt-4 mb-3\">Valorile Noastre</h3>\n                    <ul class=\"list-unstyled\">\n                        <li class=\"mb-2\"><i class=\"bi bi-check-circle text-primary me-2\"></i><strong>Integritate:</strong> Ne respectăm promisiunile și acționăm etic.</li>\n                        <li class=\"mb-2\"><i class=\"bi bi-check-circle text-primary me-2\"></i><strong>Calitate:</strong> Nu facem compromisuri când vine vorba de produsele noastre.</li>\n                        <li class=\"mb-2\"><i class=\"bi bi-check-circle text-primary me-2\"></i><strong>Parteneriat:</strong> Construim relații de lungă durată cu clienții noștri.</li>\n                    </ul>\n                ', 1, 'Despre Noi - B2B Portal', 'Află mai multe despre compania noastră, misiunea și valorile care ne ghidează.', '2026-01-07 17:45:30', '2026-01-07 17:45:30'),
(2, 'Contact', 'contact', '\n                    <p class=\"mb-4\">Echipa noastră vă stă la dispoziție pentru orice întrebări sau solicitări. Nu ezitați să ne contactați folosind detaliile de mai jos.</p>\n                    \n                    <div class=\"row g-4\">\n                        <div class=\"col-md-6\">\n                            <div class=\"card h-100 border-0 shadow-sm\">\n                                <div class=\"card-body\">\n                                    <h5 class=\"card-title text-primary mb-3\"><i class=\"bi bi-building me-2\"></i>Sediul Central</h5>\n                                    <p class=\"card-text\">\n                                        Strada Industriei nr. 15<br>\n                                        Sector 3, București<br>\n                                        România\n                                    </p>\n                                </div>\n                            </div>\n                        </div>\n                        <div class=\"col-md-6\">\n                            <div class=\"card h-100 border-0 shadow-sm\">\n                                <div class=\"card-body\">\n                                    <h5 class=\"card-title text-primary mb-3\"><i class=\"bi bi-headset me-2\"></i>Suport Clienți</h5>\n                                    <p class=\"card-text\">\n                                        <strong>Telefon:</strong> +40 722 123 456<br>\n                                        <strong>Email:</strong> contact@b2b-portal.ro<br>\n                                        <strong>Program:</strong> Luni - Vineri, 09:00 - 18:00\n                                    </p>\n                                </div>\n                            </div>\n                        </div>\n                    </div>\n                ', 1, 'Contact - B2B Portal', 'Contactează-ne pentru oferte personalizate și suport tehnic.', '2026-01-07 17:45:30', '2026-01-07 17:45:30'),
(3, 'Termeni și Condiții', 'termeni-conditii', '\n                    <h2 class=\"h5 mb-3\">1. Introducere</h2>\n                    <p>Folosirea acestui site implică acceptarea termenilor și condițiilor de mai jos. Recomandăm citirea cu atenție a acestora.</p>\n                    \n                    <h2 class=\"h5 mt-4 mb-3\">2. Comenzi și Livrare</h2>\n                    <p>Comenzile plasate pe site sunt procesate în ordinea primirii. Termenul de livrare este de 24-48 ore pentru produsele din stoc.</p>\n                    \n                    <h2 class=\"h5 mt-4 mb-3\">3. Garanții și Retur</h2>\n                    <p>Toate produsele beneficiază de garanție conform legislației în vigoare. Returul produselor se poate face în termen de 14 zile conform politicii noastre de retur.</p>\n                ', 1, 'Termeni și Condiții - B2B Portal', 'Termenii și condițiile de utilizare a platformei B2B Portal.', '2026-01-07 17:45:30', '2026-01-07 17:45:30'),
(4, 'Politica de Confidențialitate (GDPR)', 'gdpr', '\n                    <p>Respectăm confidențialitatea datelor dumneavoastră și ne angajăm să le protejăm. Această politică explică modul în care colectăm și utilizăm datele personale.</p>\n                    \n                    <h3 class=\"h6 mt-3\">Colectarea datelor</h3>\n                    <p>Colectăm date necesare procesării comenzilor și îmbunătățirii experienței pe site (nume, adresă, email, telefon).</p>\n                ', 1, 'GDPR - B2B Portal', 'Politica noastră de confidențialitate și protecția datelor.', '2026-01-07 17:45:30', '2026-01-07 17:45:30'),
(5, 'Politica Cookies', 'cookies', '\n                    <p>Acest site folosește cookie-uri pentru a vă oferi o experiență mai bună de navigare și servicii adaptate intereselor dumneavoastră.</p>\n                ', 1, 'Politica Cookies - B2B Portal', 'Informații despre utilizarea cookie-urilor pe platforma noastră.', '2026-01-07 17:45:30', '2026-01-07 17:45:30'),
(6, 'Livrare și Retur', 'livrare-retur', '\n                    <h2 class=\"h5 mb-3\">Livrare</h2>\n                    <p>Livrarea se face prin curier rapid sau flotă proprie, în funcție de volumul și greutatea comenzii.</p>\n                    \n                    <h2 class=\"h5 mt-4 mb-3\">Retur</h2>\n                    <p>Puteți returna produsele în termen de 14 zile calendaristice, fără penalități și fără invocarea unui motiv.</p>\n                ', 1, 'Livrare și Retur - B2B Portal', 'Detalii despre metodele de livrare și politica de retur.', '2026-01-07 17:45:30', '2026-01-07 17:45:30'),
(7, 'Garanții', 'garantii', '\n                    <p>Oferim garanție comercială pentru toate produsele vândute, conform specificațiilor producătorului.</p>\n                ', 1, 'Garanții - B2B Portal', 'Informații despre garanția produselor.', '2026-01-07 17:45:30', '2026-01-07 17:45:30'),
(8, 'Cariere', 'cariere', '\n                    <p>Momentan nu avem posturi disponibile. Vă rugăm să reveniți.</p>\n                ', 1, 'Cariere - B2B Portal', 'Alătură-te echipei noastre.', '2026-01-07 17:45:30', '2026-01-07 17:45:30'),
(9, 'Sustenabilitate', 'sustenabilitate', '\n                    <p>Ne angajăm să reducem amprenta de carbon prin optimizarea logisticii și promovarea produselor eco-friendly.</p>\n                ', 1, 'Sustenabilitate - B2B Portal', 'Eforturile noastre pentru un viitor sustenabil.', '2026-01-07 17:45:30', '2026-01-07 17:45:30'),
(10, 'Întrebări Frecvente (FAQ)', 'faq', '\n                    <div class=\"accordion\" id=\"accordionFAQ\">\n                      <div class=\"accordion-item\">\n                        <h2 class=\"accordion-header\">\n                          <button class=\"accordion-button\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#collapseOne\">\n                            Cum pot deveni partener B2B?\n                          </button>\n                        </h2>\n                        <div id=\"collapseOne\" class=\"accordion-collapse collapse show\" data-bs-parent=\"#accordionFAQ\">\n                          <div class=\"accordion-body\">\n                            Completați formularul de înregistrare B2B și un reprezentant vă va contacta pentru validarea contului.\n                          </div>\n                        </div>\n                      </div>\n                      <div class=\"accordion-item\">\n                        <h2 class=\"accordion-header\">\n                          <button class=\"accordion-button collapsed\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#collapseTwo\">\n                            Care este comanda minimă?\n                          </button>\n                        </h2>\n                        <div id=\"collapseTwo\" class=\"accordion-collapse collapse\" data-bs-parent=\"#accordionFAQ\">\n                          <div class=\"accordion-body\">\n                            Nu impunem o comandă minimă valorică, dar pentru livrare gratuită comanda trebuie să depășească 500 RON.\n                          </div>\n                        </div>\n                      </div>\n                    </div>\n                ', 1, 'FAQ - B2B Portal', 'Răspunsuri la cele mai frecvente întrebări.', '2026-01-07 17:45:30', '2026-01-07 17:45:30');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `partner_requests`
--

CREATE TABLE `partner_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cif` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reg_com` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iban` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activity_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `assigned_agent_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `payments`
--

CREATE TABLE `payments` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `recorded_by_user_id` bigint UNSIGNED DEFAULT NULL,
  `receipt_book_id` bigint UNSIGNED DEFAULT NULL,
  `type` enum('chs','bo','cec','card','op') COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RON',
  `bank` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'confirmed',
  `payment_date` timestamp NULL DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `document_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `series` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `customer_visit_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `payment_invoices`
--

CREATE TABLE `payment_invoices` (
  `id` bigint UNSIGNED NOT NULL,
  `payment_id` bigint UNSIGNED NOT NULL,
  `invoice_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `code`, `module`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Vezi produse', 'products.view', 'products', NULL, '2026-01-07 17:45:27', '2026-01-07 17:45:27'),
(2, 'Gestionează produse', 'products.manage', 'products', NULL, '2026-01-07 17:45:27', '2026-01-07 17:45:27'),
(3, 'Vezi promoții', 'promotions.view', 'promotions', NULL, '2026-01-07 17:45:27', '2026-01-07 17:45:27'),
(4, 'Gestionează promoții', 'promotions.manage', 'promotions', NULL, '2026-01-07 17:45:27', '2026-01-07 17:45:27'),
(5, 'Vezi clienți', 'customers.view', 'customers', NULL, '2026-01-07 17:45:27', '2026-01-07 17:45:27'),
(6, 'Gestionează clienți', 'customers.manage', 'customers', NULL, '2026-01-07 17:45:27', '2026-01-07 17:45:27'),
(7, 'Vezi comenzi', 'orders.view', 'orders', NULL, '2026-01-07 17:45:27', '2026-01-07 17:45:27'),
(8, 'Gestionează comenzi', 'orders.manage', 'orders', NULL, '2026-01-07 17:45:27', '2026-01-07 17:45:27'),
(9, 'Încasări / plăți', 'payments.manage', 'payments', NULL, '2026-01-07 17:45:27', '2026-01-07 17:45:27'),
(10, 'Vezi audit log', 'audit.view', 'audit', NULL, '2026-01-07 17:45:27', '2026-01-07 17:45:27');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `permission_role`
--

CREATE TABLE `permission_role` (
  `role_id` bigint UNSIGNED NOT NULL,
  `permission_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `permission_role`
--

INSERT INTO `permission_role` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\User', 4, 'spa', 'caaffa7b9851fddb856d3d067774bb615f90febf21b27a9107b57270f4f68745', '[\"*\"]', '2026-01-07 18:14:41', NULL, '2026-01-07 17:51:12', '2026-01-07 18:14:41'),
(2, 'App\\Models\\User', 4, 'spa', '0c4e2ee99248d829384d4f5160a3dec8532475434cdd31bf7bf5efd64d20f5f2', '[\"*\"]', '2026-01-07 18:11:59', NULL, '2026-01-07 18:07:36', '2026-01-07 18:11:59'),
(3, 'App\\Models\\User', 2, 'spa', '10af44faed36c94c0dc9417797a9cc9a46af5e85fe0416f98a7b892ed37e3129', '[\"*\"]', '2026-01-07 18:16:21', NULL, '2026-01-07 18:12:05', '2026-01-07 18:16:21');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `products`
--

CREATE TABLE `products` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('simple','variant') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'simple',
  `internal_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `erp_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `short_description` text COLLATE utf8mb4_unicode_ci,
  `long_description` longtext COLLATE utf8mb4_unicode_ci,
  `key_benefits` json DEFAULT NULL,
  `technical_specs` json DEFAULT NULL,
  `main_category_id` bigint UNSIGNED NOT NULL,
  `brand_id` bigint UNSIGNED DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `status` enum('published','hidden') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hidden',
  `visibility` enum('public','logged_in','b2b') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `sort_order` int NOT NULL DEFAULT '0',
  `list_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `rrp_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `vat_rate` decimal(5,2) NOT NULL DEFAULT '19.00',
  `vat_included` tinyint(1) NOT NULL DEFAULT '0',
  `price_override` decimal(15,2) DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RON',
  `stock_status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_stock',
  `stock_qty` int NOT NULL DEFAULT '0',
  `min_stock_limit` int NOT NULL DEFAULT '0',
  `supplier_stock_qty` int NOT NULL DEFAULT '0',
  `lead_time_days` int NOT NULL DEFAULT '0',
  `is_new` tinyint(1) NOT NULL DEFAULT '0',
  `is_recommended` tinyint(1) NOT NULL DEFAULT '0',
  `is_on_sale` tinyint(1) NOT NULL DEFAULT '0',
  `is_promo` tinyint(1) NOT NULL DEFAULT '0',
  `is_best_seller` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `meta_keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `video_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allow_backorder` tinyint(1) NOT NULL DEFAULT '0',
  `overstock_policy` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'block' COMMENT 'block, warning',
  `estimated_delivery_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_of_measure` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'buc',
  `min_order_quantity` int NOT NULL DEFAULT '1',
  `order_quantity_step` int NOT NULL DEFAULT '1',
  `requires_quote` tinyint(1) NOT NULL DEFAULT '0',
  `erp_sync_status` enum('synced','pending','error') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `erp_last_sync_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `slug`, `type`, `internal_code`, `barcode`, `erp_id`, `short_description`, `long_description`, `key_benefits`, `technical_specs`, `main_category_id`, `brand_id`, `tags`, `status`, `visibility`, `sort_order`, `list_price`, `rrp_price`, `vat_rate`, `vat_included`, `price_override`, `currency`, `stock_status`, `stock_qty`, `min_stock_limit`, `supplier_stock_qty`, `lead_time_days`, `is_new`, `is_recommended`, `is_on_sale`, `is_promo`, `is_best_seller`, `created_at`, `updated_at`, `meta_title`, `meta_description`, `meta_keywords`, `video_url`, `allow_backorder`, `overstock_policy`, `estimated_delivery_text`, `unit_of_measure`, `min_order_quantity`, `order_quantity_step`, `requires_quote`, `erp_sync_status`, `erp_last_sync_at`) VALUES
(1, 'MacBook Pro 16', 0.00, 'macbook-pro-16', 'simple', 'MBP16', NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '[\"premium\", \"new\"]', 'published', 'public', 0, 12000.00, 0.00, 19.00, 0, NULL, 'RON', 'in_stock', 50, 0, 0, 0, 1, 0, 0, 0, 0, '2026-01-07 17:52:11', '2026-01-07 17:52:11', NULL, NULL, NULL, NULL, 0, 'block', NULL, 'buc', 1, 1, 0, 'pending', NULL),
(2, 'Samsung Galaxy S24', 0.00, 'samsung-galaxy-s24', 'simple', 'SGS24', NULL, NULL, NULL, NULL, NULL, NULL, 1, 2, '[\"promo\", \"bestseller\"]', 'published', 'public', 0, 4000.00, 0.00, 19.00, 0, NULL, 'RON', 'in_stock', 100, 0, 0, 0, 0, 0, 0, 1, 0, '2026-01-07 17:52:11', '2026-01-07 17:52:11', NULL, NULL, NULL, NULL, 0, 'block', NULL, 'buc', 1, 1, 0, 'pending', NULL),
(3, 'Logitech MX Master 3S', 0.00, 'logitech-mx-master-3s', 'simple', 'MXM3S', NULL, NULL, NULL, NULL, NULL, NULL, 3, 3, NULL, 'published', 'public', 0, 500.00, 0.00, 19.00, 0, NULL, 'RON', 'in_stock', 200, 0, 0, 0, 0, 0, 0, 0, 0, '2026-01-07 17:52:11', '2026-01-07 17:52:11', NULL, NULL, NULL, NULL, 0, 'block', NULL, 'buc', 1, 1, 0, 'pending', NULL),
(4, 'USB-C Cable', 0.00, 'usb-c-cable', 'simple', 'CABLE1', NULL, NULL, NULL, NULL, NULL, NULL, 3, 2, NULL, 'published', 'public', 0, 50.00, 0.00, 19.00, 0, NULL, 'RON', 'in_stock', 1000, 0, 0, 0, 0, 0, 0, 0, 0, '2026-01-07 17:52:11', '2026-01-07 17:52:11', NULL, NULL, NULL, NULL, 0, 'block', NULL, 'buc', 1, 1, 0, 'pending', NULL);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `product_comparisons`
--

CREATE TABLE `product_comparisons` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `session_key` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `product_documents`
--

CREATE TABLE `product_documents` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visibility` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `product_images`
--

CREATE TABLE `product_images` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `product_promotion`
--

CREATE TABLE `product_promotion` (
  `promotion_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `product_promotion`
--

INSERT INTO `product_promotion` (`promotion_id`, `product_id`) VALUES
(1, 1),
(2, 2),
(6, 4),
(7, 1);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `author_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `product_stock_alerts`
--

CREATE TABLE `product_stock_alerts` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `notified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `product_units`
--

CREATE TABLE `product_units` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversion_factor` decimal(10,4) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `price_calculation_mode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'per_unit',
  `specific_price` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `product_variants`
--

CREATE TABLE `product_variants` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sku` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `erp_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `list_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `price_override` decimal(15,2) DEFAULT NULL,
  `stock_qty` int NOT NULL DEFAULT '0',
  `stock_status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_stock',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `promotions`
--

CREATE TABLE `promotions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('standard','volume','bundle','shipping','special_price','gift') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard',
  `value_type` enum('percent','fixed_amount','fixed_price') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percent',
  `value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `short_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `hero_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_at` timestamp NULL DEFAULT NULL,
  `end_at` timestamp NULL DEFAULT NULL,
  `settings` json DEFAULT NULL,
  `conditions` json DEFAULT NULL,
  `status` enum('draft','active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `applies_to` enum('all','products','categories','brands') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `is_exclusive` tinyint(1) NOT NULL DEFAULT '0',
  `is_iterative` tinyint(1) NOT NULL DEFAULT '0',
  `stacking_type` enum('exclusive','iterative') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'iterative',
  `discount_percent` decimal(5,2) DEFAULT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL,
  `min_cart_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `min_qty_per_product` int NOT NULL DEFAULT '0',
  `customer_type` enum('b2b','b2c','both') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both',
  `logged_in_only` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `promotions`
--

INSERT INTO `promotions` (`id`, `name`, `slug`, `type`, `value_type`, `value`, `short_description`, `description`, `hero_image`, `banner_image`, `mobile_image`, `start_at`, `end_at`, `settings`, `conditions`, `status`, `applies_to`, `is_exclusive`, `is_iterative`, `stacking_type`, `discount_percent`, `discount_value`, `min_cart_total`, `min_qty_per_product`, `customer_type`, `logged_in_only`, `created_at`, `updated_at`) VALUES
(1, '10% Discount MacBook', 'promo-macbook-10', 'standard', 'percent', 10.00, NULL, NULL, NULL, NULL, NULL, '2026-01-06 17:52:11', '2026-02-06 17:52:11', NULL, NULL, 'active', 'products', 0, 0, 'iterative', NULL, NULL, 0.00, 0, 'both', 0, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(2, '200 RON Discount Galaxy', 'promo-galaxy-200', 'standard', 'fixed_amount', 200.00, NULL, NULL, NULL, NULL, NULL, '2026-01-06 17:52:11', '2026-02-06 17:52:11', NULL, NULL, 'active', 'products', 0, 0, 'iterative', NULL, NULL, 0.00, 0, 'both', 0, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(3, '15% Off Accessories', 'promo-acc-15', 'standard', 'percent', 15.00, NULL, NULL, NULL, NULL, NULL, '2026-01-06 17:52:11', '2026-02-06 17:52:11', NULL, NULL, 'active', 'categories', 0, 0, 'iterative', NULL, NULL, 0.00, 0, 'both', 0, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(4, '5% Off Apple Brand', 'promo-apple-5', 'standard', 'percent', 5.00, NULL, NULL, NULL, NULL, NULL, '2026-01-06 17:52:11', '2026-02-06 17:52:11', NULL, NULL, 'active', 'brands', 0, 0, 'iterative', NULL, NULL, 0.00, 0, 'both', 0, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(5, '100 RON Off Orders > 5000', 'promo-cart-5000', 'standard', 'fixed_amount', 100.00, NULL, NULL, NULL, NULL, NULL, '2026-01-06 17:52:11', '2026-02-06 17:52:11', NULL, NULL, 'active', 'all', 0, 0, 'iterative', NULL, NULL, 5000.00, 0, 'both', 0, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(6, 'Buy 5 Cables Get 20% Off', 'promo-cables-bulk', 'volume', 'percent', 20.00, NULL, NULL, NULL, NULL, NULL, '2026-01-06 17:52:11', '2026-02-06 17:52:11', NULL, NULL, 'active', 'products', 0, 0, 'iterative', NULL, NULL, 0.00, 5, 'both', 0, '2026-01-07 17:52:11', '2026-01-07 17:52:11'),
(7, 'Free Mouse with Laptop', 'promo-free-mouse', 'gift', 'fixed_amount', 0.00, NULL, NULL, NULL, NULL, NULL, '2026-01-05 22:00:00', '2026-02-05 22:00:00', '{\"gift_qty\": 1, \"gift_product_id\": 1}', NULL, 'active', 'products', 0, 0, 'iterative', NULL, NULL, 0.00, 3, 'both', 0, '2026-01-07 17:52:11', '2026-01-07 18:12:00');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `promotion_brand`
--

CREATE TABLE `promotion_brand` (
  `id` bigint UNSIGNED NOT NULL,
  `promotion_id` bigint UNSIGNED NOT NULL,
  `brand_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `promotion_category`
--

CREATE TABLE `promotion_category` (
  `id` bigint UNSIGNED NOT NULL,
  `promotion_id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `promotion_customer`
--

CREATE TABLE `promotion_customer` (
  `id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `promotion_customer_group`
--

CREATE TABLE `promotion_customer_group` (
  `id` bigint UNSIGNED NOT NULL,
  `promotion_id` bigint UNSIGNED NOT NULL,
  `customer_group_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `promotion_product`
--

CREATE TABLE `promotion_product` (
  `id` bigint UNSIGNED NOT NULL,
  `promotion_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `promotion_tiers`
--

CREATE TABLE `promotion_tiers` (
  `id` bigint UNSIGNED NOT NULL,
  `promotion_id` bigint UNSIGNED NOT NULL,
  `min_qty` int NOT NULL DEFAULT '1',
  `value` decimal(15,2) NOT NULL,
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `quote_requests`
--

CREATE TABLE `quote_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `created_by_user_id` bigint UNSIGNED NOT NULL,
  `assigned_agent_id` bigint UNSIGNED DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `source` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estimated_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `offered_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `valid_until` timestamp NULL DEFAULT NULL,
  `customer_notes` text COLLATE utf8mb4_unicode_ci,
  `internal_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `quote_request_items`
--

CREATE TABLE `quote_request_items` (
  `id` bigint UNSIGNED NOT NULL,
  `quote_request_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `list_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `requested_price` decimal(15,2) DEFAULT NULL,
  `offered_price` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `receipt_books`
--

CREATE TABLE `receipt_books` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL COMMENT 'Agent or Director assigned to this book',
  `series` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_number` int NOT NULL,
  `end_number` int NOT NULL,
  `current_number` int NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `receipt_books`
--

INSERT INTO `receipt_books` (`id`, `user_id`, `series`, `start_number`, `end_number`, `current_number`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'B2B', 101, 150, 101, 1, '2026-01-07 17:45:29', '2026-01-07 17:45:29');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `recently_viewed_products`
--

CREATE TABLE `recently_viewed_products` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `session_key` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `viewed_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `related_products`
--

CREATE TABLE `related_products` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `related_product_id` bigint UNSIGNED NOT NULL,
  `type` enum('similar','complementary') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'similar',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `roles`
--

INSERT INTO `roles` (`id`, `slug`, `name`, `code`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Administrator', 'admin', NULL, 0, '2026-01-07 17:45:27', '2026-01-07 17:45:27'),
(2, 'customer_b2c', 'Client B2C', 'customer_b2c', NULL, 0, '2026-01-07 17:45:27', '2026-01-07 17:45:27'),
(3, 'customer_b2b', 'Client B2B', 'customer_b2b', NULL, 0, '2026-01-07 17:45:27', '2026-01-07 17:45:27'),
(4, 'sales_agent', 'Agent vânzări', 'sales_agent', NULL, 0, '2026-01-07 17:45:27', '2026-01-07 17:45:27'),
(5, 'sales_director', 'Director vânzări', 'sales_director', NULL, 0, '2026-01-07 17:45:27', '2026-01-07 17:45:27'),
(6, 'operator', 'Operator birou', 'operator', NULL, 0, '2026-01-07 17:45:27', '2026-01-07 17:45:27'),
(7, 'marketer', 'Marketer', 'marketer', NULL, 0, '2026-01-07 17:45:27', '2026-01-07 17:45:27'),
(8, 'agent', 'Agent vânzări', 'agent', NULL, 1, '2026-01-07 17:45:27', '2026-01-07 17:45:27');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `role_user`
--

CREATE TABLE `role_user` (
  `role_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `role_user`
--

INSERT INTO `role_user` (`role_id`, `user_id`) VALUES
(5, 1),
(4, 2),
(4, 3),
(1, 4);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `route_points`
--

CREATE TABLE `route_points` (
  `id` bigint UNSIGNED NOT NULL,
  `agent_daily_route_id` bigint UNSIGNED NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `accuracy` double DEFAULT NULL,
  `speed` double DEFAULT NULL,
  `heading` double DEFAULT NULL,
  `recorded_at` timestamp NOT NULL,
  `battery_level` int DEFAULT NULL,
  `is_mocked` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `route_points`
--

INSERT INTO `route_points` (`id`, `agent_daily_route_id`, `latitude`, `longitude`, `accuracy`, `speed`, `heading`, `recorded_at`, `battery_level`, `is_mocked`, `created_at`, `updated_at`) VALUES
(1, 1, 46.21354400, 27.66465400, 500, NULL, NULL, '2026-01-07 18:12:26', NULL, 0, '2026-01-07 18:12:26', '2026-01-07 18:12:26'),
(2, 1, 46.21354400, 27.66465400, 500, NULL, NULL, '2026-01-07 18:12:38', NULL, 0, '2026-01-07 18:12:38', '2026-01-07 18:12:38'),
(3, 1, 46.21354400, 27.66465400, 500, NULL, NULL, '2026-01-07 18:13:54', NULL, 0, '2026-01-07 18:13:54', '2026-01-07 18:13:54');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `sales_representatives`
--

CREATE TABLE `sales_representatives` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `counties` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `sales_targets`
--

CREATE TABLE `sales_targets` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `year` int NOT NULL,
  `month` int NOT NULL,
  `target_sales_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `target_visits_count` int NOT NULL DEFAULT '0',
  `target_new_customers` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `sales_target_items`
--

CREATE TABLE `sales_target_items` (
  `id` bigint UNSIGNED NOT NULL,
  `sales_target_id` bigint UNSIGNED NOT NULL,
  `target_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` bigint UNSIGNED NOT NULL,
  `target_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `settings`
--

CREATE TABLE `settings` (
  `id` bigint UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `group`, `type`, `description`, `created_at`, `updated_at`) VALUES
(1, 'offer_discount_threshold_approval', '15', 'offers', 'integer', 'Discount percentage threshold above which director approval is required (15-20%).', '2026-01-07 17:45:18', '2026-01-07 17:45:18'),
(2, 'offer_discount_max', '20', 'offers', 'integer', 'Maximum allowed discount percentage for offers.', '2026-01-07 17:45:18', '2026-01-07 17:45:18');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `shipments`
--

CREATE TABLE `shipments` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `courier` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `awb_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'created',
  `raw_payload` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `shipping_methods`
--

CREATE TABLE `shipping_methods` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('courier','own_fleet','pickup') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'courier',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `shipping_rules`
--

CREATE TABLE `shipping_rules` (
  `id` bigint UNSIGNED NOT NULL,
  `shipping_method_id` bigint UNSIGNED NOT NULL,
  `min_weight` decimal(10,2) NOT NULL DEFAULT '0.00',
  `max_weight` decimal(10,2) NOT NULL DEFAULT '0.00',
  `min_value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `max_value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `region` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `county` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `free_over` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `tickets`
--

CREATE TABLE `tickets` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `created_by_user_id` bigint UNSIGNED DEFAULT NULL,
  `assigned_to_user_id` bigint UNSIGNED DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `priority` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `last_message_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `ticket_messages`
--

CREATE TABLE `ticket_messages` (
  `id` bigint UNSIGNED NOT NULL,
  `ticket_id` bigint UNSIGNED NOT NULL,
  `sender_user_id` bigint UNSIGNED DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notification_preferences` json DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requires_approval` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `director_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `users`
--

INSERT INTO `users` (`id`, `customer_id`, `first_name`, `last_name`, `email`, `phone`, `email_verified_at`, `password`, `is_active`, `notification_preferences`, `remember_token`, `company_role`, `requires_approval`, `created_at`, `updated_at`, `director_id`) VALUES
(1, NULL, 'Diana', 'Director', 'director@example.com', '0711111111', NULL, '$2y$12$VPrmCZ2bgwzvZvpghOCX0esu/AbjuY2fe1yDmj0uDYAEjbOd8CGs6', 1, NULL, NULL, NULL, 0, '2026-01-07 17:45:28', '2026-01-07 17:45:28', NULL),
(2, NULL, 'Alex', 'Agent', 'agent1@example.com', '0722222222', NULL, '$2y$12$A3GREVmVSQZwWAT9mvKEbue8Rw7/.IuVYkvb6DnQ.fRjbAl7pSArO', 1, NULL, NULL, NULL, 0, '2026-01-07 17:45:29', '2026-01-07 17:45:29', NULL),
(3, NULL, 'Bianca', 'Agent', 'agent2@example.com', '0733333333', NULL, '$2y$12$WB9n/dCfplK9e8QSztHe1OZ2lsi4RYFlk0DXI/BM4ydsBVa3sSA5K', 1, NULL, NULL, NULL, 0, '2026-01-07 17:45:29', '2026-01-07 17:45:29', NULL),
(4, NULL, 'Admin', 'System', 'cod.binar@gmail.com', '0700000000', NULL, '$2y$12$Iu2O7B0Sarj0Fb1vosXcm.6tEC9IRY.8KAMsucUMfPXtBv.7TI8Ua', 1, NULL, NULL, NULL, 0, '2026-01-07 17:50:17', '2026-01-07 17:50:17', NULL);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `visit_location_logs`
--

CREATE TABLE `visit_location_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_visit_id` bigint UNSIGNED NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `accuracy` double DEFAULT NULL,
  `battery_level` int DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `speed` double DEFAULT NULL,
  `heading` double DEFAULT NULL,
  `altitude` double DEFAULT NULL,
  `network_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_mocked` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `visit_location_logs`
--

INSERT INTO `visit_location_logs` (`id`, `customer_visit_id`, `latitude`, `longitude`, `accuracy`, `battery_level`, `provider`, `recorded_at`, `created_at`, `updated_at`, `speed`, `heading`, `altitude`, `network_type`, `is_mocked`) VALUES
(1, 1, 46.2135440, 27.6646540, 500, 100, 'browser', '2026-01-07 20:12:55', '2026-01-07 18:12:55', '2026-01-07 18:12:55', NULL, NULL, NULL, '4g', 0),
(2, 1, 46.2135440, 27.6646540, 500, 100, 'browser', '2026-01-07 20:13:54', '2026-01-07 18:13:54', '2026-01-07 18:13:54', NULL, NULL, NULL, '4g', 0),
(3, 1, 46.2135440, 27.6646540, 500, 100, 'browser', '2026-01-07 20:16:06', '2026-01-07 18:16:06', '2026-01-07 18:16:06', NULL, NULL, NULL, '4g', 0),
(4, 1, 46.2135440, 27.6646540, 500, 100, 'browser', '2026-01-07 20:16:21', '2026-01-07 18:16:21', '2026-01-07 18:16:21', NULL, NULL, NULL, '4g', 0);

--
-- Indexuri pentru tabele eliminate
--

--
-- Indexuri pentru tabele `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `addresses_customer_id_foreign` (`customer_id`);

--
-- Indexuri pentru tabele `agent_customer`
--
ALTER TABLE `agent_customer`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `agent_customer_agent_id_customer_id_unique` (`agent_id`,`customer_id`),
  ADD KEY `agent_customer_customer_id_foreign` (`customer_id`);

--
-- Indexuri pentru tabele `agent_daily_routes`
--
ALTER TABLE `agent_daily_routes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_daily_routes_user_id_date_index` (`user_id`,`date`);

--
-- Indexuri pentru tabele `agent_routes`
--
ALTER TABLE `agent_routes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `agent_routes_agent_id_customer_id_day_of_week_unique` (`agent_id`,`customer_id`,`day_of_week`),
  ADD KEY `agent_routes_customer_id_foreign` (`customer_id`);

--
-- Indexuri pentru tabele `attributes`
--
ALTER TABLE `attributes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attributes_slug_unique` (`slug`);

--
-- Indexuri pentru tabele `attribute_category`
--
ALTER TABLE `attribute_category`
  ADD PRIMARY KEY (`attribute_id`,`category_id`);

--
-- Indexuri pentru tabele `attribute_values`
--
ALTER TABLE `attribute_values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attribute_values_attribute_id_foreign` (`attribute_id`),
  ADD KEY `attribute_values_product_id_foreign` (`product_id`),
  ADD KEY `attribute_values_product_variant_id_foreign` (`product_variant_id`);

--
-- Indexuri pentru tabele `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  ADD KEY `audit_logs_action_index` (`action`),
  ADD KEY `audit_logs_user_id_foreign` (`user_id`);

--
-- Indexuri pentru tabele `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `blog_categories_slug_unique` (`slug`);

--
-- Indexuri pentru tabele `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `blog_posts_slug_unique` (`slug`),
  ADD KEY `blog_posts_category_id_foreign` (`category_id`),
  ADD KEY `blog_posts_author_user_id_foreign` (`author_user_id`);

--
-- Indexuri pentru tabele `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `brands_slug_unique` (`slug`);

--
-- Indexuri pentru tabele `brand_promotion`
--
ALTER TABLE `brand_promotion`
  ADD PRIMARY KEY (`promotion_id`,`brand_id`);

--
-- Indexuri pentru tabele `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexuri pentru tabele `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexuri pentru tabele `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `carts_customer_id_foreign` (`customer_id`),
  ADD KEY `carts_user_id_foreign` (`user_id`),
  ADD KEY `carts_coupon_id_foreign` (`coupon_id`);

--
-- Indexuri pentru tabele `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_items_cart_id_foreign` (`cart_id`),
  ADD KEY `cart_items_product_id_foreign` (`product_id`),
  ADD KEY `cart_items_product_variant_id_foreign` (`product_variant_id`);

--
-- Indexuri pentru tabele `cart_promotion`
--
ALTER TABLE `cart_promotion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cart_promotion_cart_id_promotion_id_unique` (`cart_id`,`promotion_id`),
  ADD KEY `cart_promotion_promotion_id_foreign` (`promotion_id`);

--
-- Indexuri pentru tabele `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`),
  ADD KEY `categories_parent_id_foreign` (`parent_id`);

--
-- Indexuri pentru tabele `category_product`
--
ALTER TABLE `category_product`
  ADD PRIMARY KEY (`category_id`,`product_id`);

--
-- Indexuri pentru tabele `category_promotion`
--
ALTER TABLE `category_promotion`
  ADD PRIMARY KEY (`promotion_id`,`category_id`);

--
-- Indexuri pentru tabele `content_blocks`
--
ALTER TABLE `content_blocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `content_blocks_key_unique` (`key`),
  ADD KEY `content_blocks_group_index` (`group`);

--
-- Indexuri pentru tabele `contract_prices`
--
ALTER TABLE `contract_prices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_prices_customer_id_foreign` (`customer_id`),
  ADD KEY `contract_prices_customer_group_id_foreign` (`customer_group_id`),
  ADD KEY `contract_prices_product_id_customer_id_index` (`product_id`,`customer_id`),
  ADD KEY `contract_prices_product_id_customer_group_id_index` (`product_id`,`customer_group_id`);

--
-- Indexuri pentru tabele `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `coupons_code_unique` (`code`);

--
-- Indexuri pentru tabele `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customers_group_id_foreign` (`group_id`),
  ADD KEY `customers_agent_user_id_foreign` (`agent_user_id`),
  ADD KEY `customers_sales_director_user_id_foreign` (`sales_director_user_id`);

--
-- Indexuri pentru tabele `customer_groups`
--
ALTER TABLE `customer_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexuri pentru tabele `customer_group_promotion`
--
ALTER TABLE `customer_group_promotion`
  ADD PRIMARY KEY (`promotion_id`,`customer_group_id`);

--
-- Indexuri pentru tabele `customer_promotion`
--
ALTER TABLE `customer_promotion`
  ADD PRIMARY KEY (`promotion_id`,`customer_id`);

--
-- Indexuri pentru tabele `customer_visits`
--
ALTER TABLE `customer_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_visits_agent_id_foreign` (`agent_id`),
  ADD KEY `customer_visits_customer_id_foreign` (`customer_id`);

--
-- Indexuri pentru tabele `discount_rules`
--
ALTER TABLE `discount_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexuri pentru tabele `erp_sync_logs`
--
ALTER TABLE `erp_sync_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `erp_sync_logs_entity_type_direction_status_index` (`entity_type`,`direction`,`status`);

--
-- Indexuri pentru tabele `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexuri pentru tabele `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoices_order_id_foreign` (`order_id`),
  ADD KEY `invoices_customer_id_type_status_index` (`customer_id`,`type`,`status`);

--
-- Indexuri pentru tabele `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexuri pentru tabele `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexuri pentru tabele `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexuri pentru tabele `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indexuri pentru tabele `offers`
--
ALTER TABLE `offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `offers_agent_id_foreign` (`agent_id`),
  ADD KEY `offers_customer_id_foreign` (`customer_id`),
  ADD KEY `offers_quote_request_id_foreign` (`quote_request_id`),
  ADD KEY `offers_customer_visit_id_foreign` (`customer_visit_id`);

--
-- Indexuri pentru tabele `offer_items`
--
ALTER TABLE `offer_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `offer_items_offer_id_foreign` (`offer_id`),
  ADD KEY `offer_items_product_id_foreign` (`product_id`);

--
-- Indexuri pentru tabele `offer_messages`
--
ALTER TABLE `offer_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `offer_messages_offer_id_foreign` (`offer_id`),
  ADD KEY `offer_messages_user_id_foreign` (`user_id`);

--
-- Indexuri pentru tabele `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_order_number_unique` (`order_number`),
  ADD KEY `orders_customer_id_foreign` (`customer_id`),
  ADD KEY `orders_placed_by_user_id_foreign` (`placed_by_user_id`),
  ADD KEY `orders_shipping_method_id_foreign` (`shipping_method_id`),
  ADD KEY `orders_billing_address_id_foreign` (`billing_address_id`),
  ADD KEY `orders_shipping_address_id_foreign` (`shipping_address_id`),
  ADD KEY `orders_approved_by_user_id_foreign` (`approved_by_user_id`),
  ADD KEY `orders_customer_visit_id_foreign` (`customer_visit_id`);

--
-- Indexuri pentru tabele `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_order_id_foreign` (`order_id`),
  ADD KEY `order_items_product_id_foreign` (`product_id`),
  ADD KEY `order_items_product_variant_id_foreign` (`product_variant_id`);

--
-- Indexuri pentru tabele `order_templates`
--
ALTER TABLE `order_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_templates_user_id_foreign` (`user_id`);

--
-- Indexuri pentru tabele `order_template_items`
--
ALTER TABLE `order_template_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_template_items_order_template_id_foreign` (`order_template_id`),
  ADD KEY `order_template_items_product_id_foreign` (`product_id`),
  ADD KEY `order_template_items_product_variant_id_foreign` (`product_variant_id`);

--
-- Indexuri pentru tabele `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pages_slug_unique` (`slug`);

--
-- Indexuri pentru tabele `partner_requests`
--
ALTER TABLE `partner_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partner_requests_assigned_agent_id_foreign` (`assigned_agent_id`);

--
-- Indexuri pentru tabele `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payments_customer_id_foreign` (`customer_id`),
  ADD KEY `payments_order_id_foreign` (`order_id`),
  ADD KEY `payments_recorded_by_user_id_foreign` (`recorded_by_user_id`),
  ADD KEY `payments_receipt_book_id_foreign` (`receipt_book_id`),
  ADD KEY `payments_customer_visit_id_foreign` (`customer_visit_id`);

--
-- Indexuri pentru tabele `payment_invoices`
--
ALTER TABLE `payment_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_invoices_payment_id_foreign` (`payment_id`),
  ADD KEY `payment_invoices_invoice_id_foreign` (`invoice_id`);

--
-- Indexuri pentru tabele `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_code_unique` (`code`);

--
-- Indexuri pentru tabele `permission_role`
--
ALTER TABLE `permission_role`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_role_permission_id_foreign` (`permission_id`);

--
-- Indexuri pentru tabele `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexuri pentru tabele `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_slug_unique` (`slug`),
  ADD UNIQUE KEY `products_internal_code_unique` (`internal_code`),
  ADD KEY `products_main_category_id_foreign` (`main_category_id`),
  ADD KEY `products_brand_id_foreign` (`brand_id`);

--
-- Indexuri pentru tabele `product_comparisons`
--
ALTER TABLE `product_comparisons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_comparisons_user_id_product_id_unique` (`user_id`,`product_id`),
  ADD KEY `product_comparisons_user_id_product_id_index` (`user_id`,`product_id`),
  ADD KEY `product_comparisons_session_key_product_id_index` (`session_key`,`product_id`),
  ADD KEY `product_comparisons_product_id_foreign` (`product_id`);

--
-- Indexuri pentru tabele `product_documents`
--
ALTER TABLE `product_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_documents_product_id_foreign` (`product_id`);

--
-- Indexuri pentru tabele `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_images_product_id_foreign` (`product_id`);

--
-- Indexuri pentru tabele `product_promotion`
--
ALTER TABLE `product_promotion`
  ADD PRIMARY KEY (`promotion_id`,`product_id`);

--
-- Indexuri pentru tabele `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_reviews_product_id_foreign` (`product_id`);

--
-- Indexuri pentru tabele `product_stock_alerts`
--
ALTER TABLE `product_stock_alerts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_prod_var_unique` (`user_id`,`product_id`,`product_variant_id`),
  ADD KEY `product_stock_alerts_product_id_foreign` (`product_id`),
  ADD KEY `product_stock_alerts_product_variant_id_foreign` (`product_variant_id`);

--
-- Indexuri pentru tabele `product_units`
--
ALTER TABLE `product_units`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_units_product_id_foreign` (`product_id`);

--
-- Indexuri pentru tabele `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_variants_product_id_foreign` (`product_id`);

--
-- Indexuri pentru tabele `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `promotions_slug_unique` (`slug`);

--
-- Indexuri pentru tabele `promotion_brand`
--
ALTER TABLE `promotion_brand`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `promotion_brand_promotion_id_brand_id_unique` (`promotion_id`,`brand_id`),
  ADD KEY `promotion_brand_brand_id_foreign` (`brand_id`);

--
-- Indexuri pentru tabele `promotion_category`
--
ALTER TABLE `promotion_category`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `promotion_category_promotion_id_category_id_unique` (`promotion_id`,`category_id`),
  ADD KEY `promotion_category_category_id_foreign` (`category_id`);

--
-- Indexuri pentru tabele `promotion_customer`
--
ALTER TABLE `promotion_customer`
  ADD PRIMARY KEY (`id`);

--
-- Indexuri pentru tabele `promotion_customer_group`
--
ALTER TABLE `promotion_customer_group`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `promotion_customer_group_promotion_id_customer_group_id_unique` (`promotion_id`,`customer_group_id`),
  ADD KEY `promotion_customer_group_customer_group_id_foreign` (`customer_group_id`);

--
-- Indexuri pentru tabele `promotion_product`
--
ALTER TABLE `promotion_product`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `promotion_product_promotion_id_product_id_unique` (`promotion_id`,`product_id`),
  ADD KEY `promotion_product_product_id_foreign` (`product_id`);

--
-- Indexuri pentru tabele `promotion_tiers`
--
ALTER TABLE `promotion_tiers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promotion_tiers_promotion_id_foreign` (`promotion_id`);

--
-- Indexuri pentru tabele `quote_requests`
--
ALTER TABLE `quote_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quote_requests_customer_id_foreign` (`customer_id`),
  ADD KEY `quote_requests_created_by_user_id_foreign` (`created_by_user_id`),
  ADD KEY `quote_requests_assigned_agent_id_foreign` (`assigned_agent_id`);

--
-- Indexuri pentru tabele `quote_request_items`
--
ALTER TABLE `quote_request_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quote_request_items_quote_request_id_foreign` (`quote_request_id`),
  ADD KEY `quote_request_items_product_id_foreign` (`product_id`),
  ADD KEY `quote_request_items_product_variant_id_foreign` (`product_variant_id`);

--
-- Indexuri pentru tabele `receipt_books`
--
ALTER TABLE `receipt_books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receipt_books_user_id_foreign` (`user_id`);

--
-- Indexuri pentru tabele `recently_viewed_products`
--
ALTER TABLE `recently_viewed_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `recently_viewed_products_user_id_product_id_unique` (`user_id`,`product_id`),
  ADD KEY `recently_viewed_products_user_id_viewed_at_index` (`user_id`,`viewed_at`),
  ADD KEY `recently_viewed_products_session_key_viewed_at_index` (`session_key`,`viewed_at`),
  ADD KEY `recently_viewed_products_product_id_foreign` (`product_id`);

--
-- Indexuri pentru tabele `related_products`
--
ALTER TABLE `related_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `related_products_product_id_foreign` (`product_id`),
  ADD KEY `related_products_related_product_id_foreign` (`related_product_id`);

--
-- Indexuri pentru tabele `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_code_unique` (`code`),
  ADD UNIQUE KEY `roles_slug_unique` (`slug`);

--
-- Indexuri pentru tabele `role_user`
--
ALTER TABLE `role_user`
  ADD PRIMARY KEY (`role_id`,`user_id`),
  ADD KEY `role_user_user_id_foreign` (`user_id`);

--
-- Indexuri pentru tabele `route_points`
--
ALTER TABLE `route_points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `route_points_agent_daily_route_id_index` (`agent_daily_route_id`);

--
-- Indexuri pentru tabele `sales_representatives`
--
ALTER TABLE `sales_representatives`
  ADD PRIMARY KEY (`id`);

--
-- Indexuri pentru tabele `sales_targets`
--
ALTER TABLE `sales_targets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sales_targets_user_id_year_month_unique` (`user_id`,`year`,`month`);

--
-- Indexuri pentru tabele `sales_target_items`
--
ALTER TABLE `sales_target_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_sales_target_item` (`sales_target_id`,`target_type`,`target_id`);

--
-- Indexuri pentru tabele `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Indexuri pentru tabele `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipments_order_id_foreign` (`order_id`),
  ADD KEY `shipments_courier_awb_number_index` (`courier`,`awb_number`);

--
-- Indexuri pentru tabele `shipping_methods`
--
ALTER TABLE `shipping_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shipping_methods_code_unique` (`code`);

--
-- Indexuri pentru tabele `shipping_rules`
--
ALTER TABLE `shipping_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipping_rules_shipping_method_id_foreign` (`shipping_method_id`);

--
-- Indexuri pentru tabele `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tickets_customer_id_foreign` (`customer_id`),
  ADD KEY `tickets_created_by_user_id_foreign` (`created_by_user_id`),
  ADD KEY `tickets_assigned_to_user_id_foreign` (`assigned_to_user_id`);

--
-- Indexuri pentru tabele `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_messages_ticket_id_foreign` (`ticket_id`),
  ADD KEY `ticket_messages_sender_user_id_foreign` (`sender_user_id`);

--
-- Indexuri pentru tabele `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_customer_id_foreign` (`customer_id`),
  ADD KEY `users_director_id_foreign` (`director_id`);

--
-- Indexuri pentru tabele `visit_location_logs`
--
ALTER TABLE `visit_location_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visit_location_logs_customer_visit_id_foreign` (`customer_visit_id`);

--
-- AUTO_INCREMENT pentru tabele eliminate
--

--
-- AUTO_INCREMENT pentru tabele `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `agent_customer`
--
ALTER TABLE `agent_customer`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `agent_daily_routes`
--
ALTER TABLE `agent_daily_routes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pentru tabele `agent_routes`
--
ALTER TABLE `agent_routes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `attributes`
--
ALTER TABLE `attributes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `attribute_values`
--
ALTER TABLE `attribute_values`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pentru tabele `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pentru tabele `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pentru tabele `brands`
--
ALTER TABLE `brands`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pentru tabele `carts`
--
ALTER TABLE `carts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pentru tabele `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pentru tabele `cart_promotion`
--
ALTER TABLE `cart_promotion`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pentru tabele `content_blocks`
--
ALTER TABLE `content_blocks`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pentru tabele `contract_prices`
--
ALTER TABLE `contract_prices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pentru tabele `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pentru tabele `customer_groups`
--
ALTER TABLE `customer_groups`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pentru tabele `customer_visits`
--
ALTER TABLE `customer_visits`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pentru tabele `discount_rules`
--
ALTER TABLE `discount_rules`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `erp_sync_logs`
--
ALTER TABLE `erp_sync_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT pentru tabele `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT pentru tabele `offers`
--
ALTER TABLE `offers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `offer_items`
--
ALTER TABLE `offer_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `offer_messages`
--
ALTER TABLE `offer_messages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `order_templates`
--
ALTER TABLE `order_templates`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `order_template_items`
--
ALTER TABLE `order_template_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `pages`
--
ALTER TABLE `pages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pentru tabele `partner_requests`
--
ALTER TABLE `partner_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `payment_invoices`
--
ALTER TABLE `payment_invoices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pentru tabele `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pentru tabele `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pentru tabele `product_comparisons`
--
ALTER TABLE `product_comparisons`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `product_documents`
--
ALTER TABLE `product_documents`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `product_stock_alerts`
--
ALTER TABLE `product_stock_alerts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `product_units`
--
ALTER TABLE `product_units`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pentru tabele `promotion_brand`
--
ALTER TABLE `promotion_brand`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `promotion_category`
--
ALTER TABLE `promotion_category`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `promotion_customer`
--
ALTER TABLE `promotion_customer`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `promotion_customer_group`
--
ALTER TABLE `promotion_customer_group`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `promotion_product`
--
ALTER TABLE `promotion_product`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `promotion_tiers`
--
ALTER TABLE `promotion_tiers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `quote_requests`
--
ALTER TABLE `quote_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `quote_request_items`
--
ALTER TABLE `quote_request_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `receipt_books`
--
ALTER TABLE `receipt_books`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pentru tabele `recently_viewed_products`
--
ALTER TABLE `recently_viewed_products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `related_products`
--
ALTER TABLE `related_products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pentru tabele `route_points`
--
ALTER TABLE `route_points`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pentru tabele `sales_representatives`
--
ALTER TABLE `sales_representatives`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `sales_targets`
--
ALTER TABLE `sales_targets`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `sales_target_items`
--
ALTER TABLE `sales_target_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pentru tabele `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `shipping_methods`
--
ALTER TABLE `shipping_methods`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `shipping_rules`
--
ALTER TABLE `shipping_rules`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `ticket_messages`
--
ALTER TABLE `ticket_messages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pentru tabele `visit_location_logs`
--
ALTER TABLE `visit_location_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constrângeri pentru tabele eliminate
--

--
-- Constrângeri pentru tabele `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `agent_customer`
--
ALTER TABLE `agent_customer`
  ADD CONSTRAINT `agent_customer_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agent_customer_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `agent_daily_routes`
--
ALTER TABLE `agent_daily_routes`
  ADD CONSTRAINT `agent_daily_routes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `agent_routes`
--
ALTER TABLE `agent_routes`
  ADD CONSTRAINT `agent_routes_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agent_routes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `attribute_values`
--
ALTER TABLE `attribute_values`
  ADD CONSTRAINT `attribute_values_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attribute_values_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attribute_values_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD CONSTRAINT `blog_posts_author_user_id_foreign` FOREIGN KEY (`author_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `blog_posts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `carts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `cart_promotion`
--
ALTER TABLE `cart_promotion`
  ADD CONSTRAINT `cart_promotion_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_promotion_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `contract_prices`
--
ALTER TABLE `contract_prices`
  ADD CONSTRAINT `contract_prices_customer_group_id_foreign` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contract_prices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contract_prices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_agent_user_id_foreign` FOREIGN KEY (`agent_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customers_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `customer_groups` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customers_sales_director_user_id_foreign` FOREIGN KEY (`sales_director_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `customer_visits`
--
ALTER TABLE `customer_visits`
  ADD CONSTRAINT `customer_visits_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_visits_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoices_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `offers`
--
ALTER TABLE `offers`
  ADD CONSTRAINT `offers_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `offers_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `offers_customer_visit_id_foreign` FOREIGN KEY (`customer_visit_id`) REFERENCES `customer_visits` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `offers_quote_request_id_foreign` FOREIGN KEY (`quote_request_id`) REFERENCES `quote_requests` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `offer_items`
--
ALTER TABLE `offer_items`
  ADD CONSTRAINT `offer_items_offer_id_foreign` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `offer_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `offer_messages`
--
ALTER TABLE `offer_messages`
  ADD CONSTRAINT `offer_messages_offer_id_foreign` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `offer_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_approved_by_user_id_foreign` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_billing_address_id_foreign` FOREIGN KEY (`billing_address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_customer_visit_id_foreign` FOREIGN KEY (`customer_visit_id`) REFERENCES `customer_visits` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_placed_by_user_id_foreign` FOREIGN KEY (`placed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_shipping_address_id_foreign` FOREIGN KEY (`shipping_address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_shipping_method_id_foreign` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `order_templates`
--
ALTER TABLE `order_templates`
  ADD CONSTRAINT `order_templates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `order_template_items`
--
ALTER TABLE `order_template_items`
  ADD CONSTRAINT `order_template_items_order_template_id_foreign` FOREIGN KEY (`order_template_id`) REFERENCES `order_templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_template_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_template_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `partner_requests`
--
ALTER TABLE `partner_requests`
  ADD CONSTRAINT `partner_requests_assigned_agent_id_foreign` FOREIGN KEY (`assigned_agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_customer_visit_id_foreign` FOREIGN KEY (`customer_visit_id`) REFERENCES `customer_visits` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_receipt_book_id_foreign` FOREIGN KEY (`receipt_book_id`) REFERENCES `receipt_books` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_recorded_by_user_id_foreign` FOREIGN KEY (`recorded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `payment_invoices`
--
ALTER TABLE `payment_invoices`
  ADD CONSTRAINT `payment_invoices_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_invoices_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `permission_role`
--
ALTER TABLE `permission_role`
  ADD CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_main_category_id_foreign` FOREIGN KEY (`main_category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `product_comparisons`
--
ALTER TABLE `product_comparisons`
  ADD CONSTRAINT `product_comparisons_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_comparisons_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `product_documents`
--
ALTER TABLE `product_documents`
  ADD CONSTRAINT `product_documents_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `product_stock_alerts`
--
ALTER TABLE `product_stock_alerts`
  ADD CONSTRAINT `product_stock_alerts_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_stock_alerts_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_stock_alerts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `product_units`
--
ALTER TABLE `product_units`
  ADD CONSTRAINT `product_units_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `promotion_brand`
--
ALTER TABLE `promotion_brand`
  ADD CONSTRAINT `promotion_brand_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_brand_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `promotion_category`
--
ALTER TABLE `promotion_category`
  ADD CONSTRAINT `promotion_category_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_category_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `promotion_customer_group`
--
ALTER TABLE `promotion_customer_group`
  ADD CONSTRAINT `promotion_customer_group_customer_group_id_foreign` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_customer_group_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `promotion_product`
--
ALTER TABLE `promotion_product`
  ADD CONSTRAINT `promotion_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_product_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `promotion_tiers`
--
ALTER TABLE `promotion_tiers`
  ADD CONSTRAINT `promotion_tiers_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `quote_requests`
--
ALTER TABLE `quote_requests`
  ADD CONSTRAINT `quote_requests_assigned_agent_id_foreign` FOREIGN KEY (`assigned_agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `quote_requests_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quote_requests_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `quote_request_items`
--
ALTER TABLE `quote_request_items`
  ADD CONSTRAINT `quote_request_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quote_request_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `quote_request_items_quote_request_id_foreign` FOREIGN KEY (`quote_request_id`) REFERENCES `quote_requests` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `receipt_books`
--
ALTER TABLE `receipt_books`
  ADD CONSTRAINT `receipt_books_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `recently_viewed_products`
--
ALTER TABLE `recently_viewed_products`
  ADD CONSTRAINT `recently_viewed_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recently_viewed_products_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `related_products`
--
ALTER TABLE `related_products`
  ADD CONSTRAINT `related_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `related_products_related_product_id_foreign` FOREIGN KEY (`related_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `role_user`
--
ALTER TABLE `role_user`
  ADD CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `route_points`
--
ALTER TABLE `route_points`
  ADD CONSTRAINT `route_points_agent_daily_route_id_foreign` FOREIGN KEY (`agent_daily_route_id`) REFERENCES `agent_daily_routes` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `sales_targets`
--
ALTER TABLE `sales_targets`
  ADD CONSTRAINT `sales_targets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `sales_target_items`
--
ALTER TABLE `sales_target_items`
  ADD CONSTRAINT `sales_target_items_sales_target_id_foreign` FOREIGN KEY (`sales_target_id`) REFERENCES `sales_targets` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `shipments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `shipping_rules`
--
ALTER TABLE `shipping_rules`
  ADD CONSTRAINT `shipping_rules_shipping_method_id_foreign` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_assigned_to_user_id_foreign` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tickets_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tickets_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD CONSTRAINT `ticket_messages_sender_user_id_foreign` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ticket_messages_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;

--
-- Constrângeri pentru tabele `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_director_id_foreign` FOREIGN KEY (`director_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constrângeri pentru tabele `visit_location_logs`
--
ALTER TABLE `visit_location_logs`
  ADD CONSTRAINT `visit_location_logs_customer_visit_id_foreign` FOREIGN KEY (`customer_visit_id`) REFERENCES `customer_visits` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
