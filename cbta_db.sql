-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 03-08-2026 a las 21:38:58
-- Versión del servidor: 8.0.30
-- Versión de PHP: 8.3.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `cbta_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administration_routes`
--

CREATE TABLE `administration_routes` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `administration_routes`
--

INSERT INTO `administration_routes` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'INTRAVENOSA', '2026-05-19 10:28:41', '2026-05-19 10:28:41'),
(2, 'INTRAMUSCULAR', '2026-05-19 10:28:41', '2026-05-19 10:28:41'),
(3, 'SUBCUTANEA', '2026-05-19 10:28:41', '2026-05-19 10:28:41'),
(4, 'OCULAR', '2026-05-19 10:28:41', '2026-05-19 10:28:41'),
(5, 'INTRATECAL', '2026-05-19 10:28:41', '2026-05-19 10:28:41'),
(6, 'ORAL', '2026-05-19 21:46:49', '2026-05-19 21:46:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administration_route_medicine_catalog`
--

CREATE TABLE `administration_route_medicine_catalog` (
  `id` bigint UNSIGNED NOT NULL,
  `administration_route_id` bigint UNSIGNED NOT NULL,
  `medicine_catalog_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `administration_route_medicine_catalog`
--

INSERT INTO `administration_route_medicine_catalog` (`id`, `administration_route_id`, `medicine_catalog_id`, `created_at`, `updated_at`) VALUES
(4, 1, 2, NULL, NULL),
(5, 1, 7, NULL, NULL),
(6, 1, 1, NULL, NULL),
(7, 1, 4, NULL, NULL),
(8, 3, 4, NULL, NULL),
(9, 1, 5, NULL, NULL),
(10, 1, 6, NULL, NULL),
(11, 1, 8, NULL, NULL),
(12, 1, 9, NULL, NULL),
(13, 1, 10, NULL, NULL),
(14, 1, 11, NULL, NULL),
(15, 3, 11, NULL, NULL),
(16, 1, 12, NULL, NULL),
(17, 1, 13, NULL, NULL),
(18, 1, 14, NULL, NULL),
(19, 1, 15, NULL, NULL),
(20, 1, 16, NULL, NULL),
(21, 5, 17, NULL, NULL),
(22, 1, 17, NULL, NULL),
(23, 1, 18, NULL, NULL),
(24, 1, 19, NULL, NULL),
(25, 1, 20, NULL, NULL),
(26, 1, 21, NULL, NULL),
(27, 1, 22, NULL, NULL),
(28, 1, 23, NULL, NULL),
(29, 1, 24, NULL, NULL),
(30, 1, 25, NULL, NULL),
(31, 5, 26, NULL, NULL),
(32, 1, 26, NULL, NULL),
(33, 1, 27, NULL, NULL),
(34, 1, 28, NULL, NULL),
(35, 1, 29, NULL, NULL),
(36, 1, 30, NULL, NULL),
(37, 1, 31, NULL, NULL),
(38, 1, 32, NULL, NULL),
(39, 1, 33, NULL, NULL),
(40, 1, 34, NULL, NULL),
(41, 1, 35, NULL, NULL),
(42, 2, 36, NULL, NULL),
(43, 1, 36, NULL, NULL),
(44, 1, 37, NULL, NULL),
(45, 1, 38, NULL, NULL),
(46, 1, 39, NULL, NULL),
(47, 1, 40, NULL, NULL),
(48, 1, 41, NULL, NULL),
(49, 1, 42, NULL, NULL),
(50, 1, 43, NULL, NULL),
(51, 1, 44, NULL, NULL),
(52, 1, 45, NULL, NULL),
(53, 1, 46, NULL, NULL),
(54, 1, 47, NULL, NULL),
(55, 1, 48, NULL, NULL),
(56, 1, 49, NULL, NULL),
(57, 1, 50, NULL, NULL),
(58, 1, 51, NULL, NULL),
(59, 1, 53, NULL, NULL),
(60, 2, 54, NULL, NULL),
(61, 5, 54, NULL, NULL),
(62, 1, 54, NULL, NULL),
(63, 1, 55, NULL, NULL),
(64, 1, 56, NULL, NULL),
(65, 1, 57, NULL, NULL),
(66, 1, 58, NULL, NULL),
(67, 2, 59, NULL, NULL),
(68, 1, 59, NULL, NULL),
(69, 1, 60, NULL, NULL),
(70, 1, 61, NULL, NULL),
(71, 1, 62, NULL, NULL),
(72, 1, 63, NULL, NULL),
(73, 4, 63, NULL, NULL),
(74, 1, 64, NULL, NULL),
(75, 1, 66, NULL, NULL),
(76, 1, 65, NULL, NULL),
(77, 1, 67, NULL, NULL),
(78, 1, 68, NULL, NULL),
(79, 1, 69, NULL, NULL),
(80, 1, 70, NULL, NULL),
(81, 1, 71, NULL, NULL),
(82, 5, 38, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Aminoácidos', NULL, NULL),
(2, 'Carbohidratos', NULL, NULL),
(3, 'Lípidos', NULL, NULL),
(4, 'Electrolitos', NULL, NULL),
(5, 'Aditivos', NULL, NULL),
(6, 'Bolsa Eva', NULL, NULL),
(7, 'Otra', NULL, NULL),
(8, 'Cloruro de Sodio 0.9%', NULL, NULL),
(10, 'Set de Infusión', NULL, NULL),
(11, 'Servicio de Preparación', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` bigint UNSIGNED NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `razon_social` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rfc` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `razon_social`, `rfc`, `telefono`, `created_at`, `updated_at`) VALUES
(1, 'Centro Biotecnologico de Terapias Avanzadas', 'CBTA', NULL, NULL, '2026-05-29 04:54:01', '2026-05-29 04:54:01'),
(2, 'OPERADORA DE HOSPITALES ANGELES S.A. DE C.V.', 'OHA051017KE7', NULL, NULL, '2026-05-29 05:13:22', '2026-05-29 05:13:22'),
(3, 'YUGASSO', 'YSC110704UH9', NULL, NULL, '2026-06-24 19:52:32', '2026-06-24 19:52:32'),
(4, 'CENTRO DE MEZCLAS PRODIFEM CDMX', 'PRO170214P96', NULL, NULL, '2026-07-03 16:52:21', '2026-07-03 16:52:21'),
(5, 'IMSS Bienestar Estado de Mexico', 'A', NULL, NULL, '2026-07-28 19:30:34', '2026-07-28 19:30:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente_hospital`
--

CREATE TABLE `cliente_hospital` (
  `id` bigint UNSIGNED NOT NULL,
  `cliente_id` bigint UNSIGNED NOT NULL,
  `hospital_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cliente_hospital`
--

INSERT INTO `cliente_hospital` (`id`, `cliente_id`, `hospital_id`, `created_at`, `updated_at`) VALUES
(2, 3, 47, NULL, NULL),
(3, 2, 2, NULL, NULL),
(4, 4, 48, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `diluents`
--

CREATE TABLE `diluents` (
  `id` bigint UNSIGNED NOT NULL,
  `denominacion_generica` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `diluents`
--

INSERT INTO `diluents` (`id`, `denominacion_generica`, `created_at`, `updated_at`) VALUES
(1, 'CLORURO DE SODIO 0.9%', '2026-05-19 20:06:55', '2026-05-20 22:55:10'),
(3, 'SOLUCION HARTMANN', '2026-05-20 22:54:53', '2026-05-20 22:54:53'),
(4, 'GLUCOSA 5%', '2026-05-20 22:58:37', '2026-05-20 22:58:37'),
(5, 'AGUA INYECTABLE', '2026-05-20 23:59:06', '2026-05-20 23:59:17'),
(6, 'DILUYENTE PROPIO', '2026-05-29 20:50:20', '2026-05-29 20:50:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `diluent_medicine_catalog`
--

CREATE TABLE `diluent_medicine_catalog` (
  `id` bigint UNSIGNED NOT NULL,
  `diluent_id` bigint UNSIGNED NOT NULL,
  `medicine_catalog_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `diluent_medicine_catalog`
--

INSERT INTO `diluent_medicine_catalog` (`id`, `diluent_id`, `medicine_catalog_id`, `created_at`, `updated_at`) VALUES
(2, 1, 2, NULL, NULL),
(3, 1, 7, NULL, NULL),
(4, 1, 1, NULL, NULL),
(5, 4, 1, NULL, NULL),
(6, 1, 4, NULL, NULL),
(7, 1, 5, NULL, NULL),
(8, 1, 6, NULL, NULL),
(9, 1, 8, NULL, NULL),
(10, 4, 8, NULL, NULL),
(11, 1, 9, NULL, NULL),
(12, 1, 10, NULL, NULL),
(13, 4, 10, NULL, NULL),
(14, 1, 11, NULL, NULL),
(15, 4, 12, NULL, NULL),
(16, 1, 13, NULL, NULL),
(17, 1, 14, NULL, NULL),
(18, 4, 14, NULL, NULL),
(19, 1, 15, NULL, NULL),
(20, 4, 15, NULL, NULL),
(21, 4, 16, NULL, NULL),
(22, 1, 17, NULL, NULL),
(23, 4, 17, NULL, NULL),
(24, 1, 18, NULL, NULL),
(25, 1, 19, NULL, NULL),
(26, 4, 19, NULL, NULL),
(27, 1, 20, NULL, NULL),
(28, 4, 20, NULL, NULL),
(29, 1, 21, NULL, NULL),
(30, 1, 22, NULL, NULL),
(31, 4, 22, NULL, NULL),
(32, 1, 23, NULL, NULL),
(33, 4, 23, NULL, NULL),
(34, 1, 24, NULL, NULL),
(35, 1, 25, NULL, NULL),
(36, 1, 26, NULL, NULL),
(37, 4, 26, NULL, NULL),
(38, 3, 27, NULL, NULL),
(39, 1, 28, NULL, NULL),
(40, 4, 28, NULL, NULL),
(41, 1, 29, NULL, NULL),
(42, 4, 29, NULL, NULL),
(43, 1, 30, NULL, NULL),
(44, 4, 30, NULL, NULL),
(45, 1, 31, NULL, NULL),
(46, 1, 32, NULL, NULL),
(47, 5, 4, NULL, NULL),
(48, 1, 33, NULL, NULL),
(49, 4, 33, NULL, NULL),
(50, 1, 34, NULL, NULL),
(51, 4, 34, NULL, NULL),
(52, 1, 35, NULL, NULL),
(53, 1, 36, NULL, NULL),
(54, 4, 36, NULL, NULL),
(55, 1, 37, NULL, NULL),
(56, 4, 37, NULL, NULL),
(57, 1, 38, NULL, NULL),
(58, 4, 38, NULL, NULL),
(59, 1, 39, NULL, NULL),
(60, 4, 39, NULL, NULL),
(61, 1, 40, NULL, NULL),
(62, 1, 41, NULL, NULL),
(63, 4, 41, NULL, NULL),
(64, 1, 42, NULL, NULL),
(65, 4, 43, NULL, NULL),
(66, 1, 44, NULL, NULL),
(67, 4, 44, NULL, NULL),
(68, 1, 45, NULL, NULL),
(69, 4, 45, NULL, NULL),
(70, 1, 46, NULL, NULL),
(71, 1, 47, NULL, NULL),
(72, 4, 47, NULL, NULL),
(73, 1, 48, NULL, NULL),
(74, 1, 49, NULL, NULL),
(75, 1, 50, NULL, NULL),
(76, 4, 50, NULL, NULL),
(77, 1, 51, NULL, NULL),
(78, 4, 51, NULL, NULL),
(79, 1, 52, NULL, NULL),
(80, 4, 52, NULL, NULL),
(81, 3, 52, NULL, NULL),
(82, 1, 53, NULL, NULL),
(83, 4, 53, NULL, NULL),
(84, 1, 54, NULL, NULL),
(85, 4, 54, NULL, NULL),
(86, 1, 55, NULL, NULL),
(87, 4, 55, NULL, NULL),
(88, 1, 56, NULL, NULL),
(89, 4, 56, NULL, NULL),
(90, 1, 57, NULL, NULL),
(91, 1, 58, NULL, NULL),
(92, 5, 59, NULL, NULL),
(93, 1, 59, NULL, NULL),
(94, 1, 60, NULL, NULL),
(95, 4, 60, NULL, NULL),
(96, 1, 61, NULL, NULL),
(97, 1, 62, NULL, NULL),
(98, 4, 62, NULL, NULL),
(99, 5, 63, NULL, NULL),
(100, 1, 63, NULL, NULL),
(101, 1, 64, NULL, NULL),
(102, 1, 65, NULL, NULL),
(103, 4, 65, NULL, NULL),
(104, 1, 66, NULL, NULL),
(105, 1, 67, NULL, NULL),
(106, 1, 68, NULL, NULL),
(107, 1, 69, NULL, NULL),
(108, 1, 70, NULL, NULL),
(109, 1, 71, NULL, NULL),
(110, 5, 38, NULL, NULL),
(111, 6, 38, NULL, NULL),
(112, 5, 17, NULL, NULL),
(113, 6, 17, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `diluent_presentations`
--

CREATE TABLE `diluent_presentations` (
  `id` bigint UNSIGNED NOT NULL,
  `diluent_id` bigint UNSIGNED NOT NULL,
  `laboratory_id` bigint UNSIGNED DEFAULT NULL,
  `presentacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `volume_ml` decimal(10,2) NOT NULL,
  `denominacion_comercial` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fabricante` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lote` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caducidad` date DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `stock_inicial` decimal(12,2) NOT NULL DEFAULT '0.00',
  `stock_actual` decimal(12,2) NOT NULL DEFAULT '0.00',
  `stock_reservado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `diluent_presentations`
--

INSERT INTO `diluent_presentations` (`id`, `diluent_id`, `laboratory_id`, `presentacion`, `volume_ml`, `denominacion_comercial`, `fabricante`, `lote`, `caducidad`, `fecha_ingreso`, `stock_inicial`, `stock_actual`, `stock_reservado`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 1, 1, 'Cloruro de sodio 250 mL', 250.00, 'CLORUROSODICA ALPHA', 'FRESENIUS', '73F5B0293', '2027-02-28', '2026-07-23', 100.00, 97.00, 0.00, 1, '2026-05-20 03:48:20', '2026-07-24 11:59:37'),
(4, 1, 1, 'Cloruro de sodio 500 ml', 500.00, 'CLORUROSODICA ALPHA', 'FRESENIUS', '73F5D0373', '2027-04-30', '2026-07-23', 100.00, 99.00, 0.00, 1, '2026-05-20 22:51:31', '2026-07-24 11:57:11'),
(5, 1, NULL, 'Cloruro de sodio 0.9% 100ml', 100.00, 'CLORUROSODICA ALPHA', 'FRESENIUS', '73F5F0082', '2027-06-30', '2026-07-23', 100.00, 100.00, 0.00, 1, '2026-05-20 22:52:32', '2026-07-24 11:55:31'),
(6, 1, NULL, 'Cloruro de sodio 0.9% 50ml', 50.00, 'CLORUROSODICA ALPHA', 'FRESENIUS', '73F5G0102', '2027-07-30', '2026-07-24', 100.00, 100.00, 0.00, 1, '2026-05-20 22:53:11', '2026-07-24 11:55:53'),
(7, 1, NULL, 'Cloruro de sodio 0.9% 1000 ml', 1000.00, 'CLORUROSODICA ALPHA', 'FRESENIUS', '73F5E0503', '2027-05-30', '2026-07-23', 100.00, 100.00, 0.00, 1, '2026-05-20 22:54:22', '2026-07-24 11:55:21'),
(8, 3, NULL, 'Solución Hartmann 250 ml', 250.00, 'HARTMANN ALPHA', NULL, '73H4H0653', '2027-02-28', NULL, 100.00, 100.00, 0.00, 1, '2026-05-20 22:58:10', '2026-07-30 12:01:11'),
(9, 4, NULL, 'Glucosa 5% 500ml', 500.00, 'DEXTRALPHA', 'FRESENIUS', '73G5B0063', '2027-02-28', '2026-07-24', 100.00, 100.00, 0.00, 1, '2026-05-20 22:59:20', '2026-07-24 12:09:28'),
(10, 4, NULL, 'Glucosa 5% 1000ml', 1000.00, 'DEXTRALPHA', 'FRESENIUS', '73G4L0313', '2026-11-30', '2026-07-24', 100.00, 100.00, 0.00, 1, '2026-05-20 22:59:58', '2026-07-24 12:09:39'),
(11, 4, NULL, 'Glucosa 5% 250ml', 250.00, 'DEXTRALPHA', 'FRESENIUS', '73G5K0123', '2027-10-30', '2026-07-24', 100.00, 100.00, 0.00, 1, '2026-05-20 23:00:42', '2026-07-24 12:09:04'),
(12, 4, NULL, 'Glucosa 5% 100ml', 100.00, 'DEXTRALPHA', 'FRESENIUS', '73G5B0022', '2027-02-28', '2026-07-24', 100.00, 100.00, 0.00, 1, '2026-05-20 23:01:56', '2026-07-24 12:09:17'),
(13, 5, NULL, 'Agua inyectable 500ml', 500.00, 'Agua inyectable', NULL, '73A5I0203', '2027-09-30', NULL, 0.00, 0.00, 0.00, 1, '2026-05-20 23:59:55', '2026-05-20 23:59:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `diluent_stock_movements`
--

CREATE TABLE `diluent_stock_movements` (
  `id` bigint UNSIGNED NOT NULL,
  `diluent_presentation_id` bigint UNSIGNED NOT NULL,
  `laboratory_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `movement_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `stock_actual_before` decimal(12,2) NOT NULL DEFAULT '0.00',
  `stock_actual_after` decimal(12,2) NOT NULL DEFAULT '0.00',
  `reference_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint UNSIGNED DEFAULT NULL,
  `notes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `diluent_stock_movements`
--

INSERT INTO `diluent_stock_movements` (`id`, `diluent_presentation_id`, `laboratory_id`, `user_id`, `movement_type`, `quantity`, `stock_actual_before`, `stock_actual_after`, `reference_type`, `reference_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 4, 'ajuste_entrada', 100.00, 0.00, 100.00, 'diluent_presentation', 3, 'Ajuste manual de inventario de diluyente.', '2026-07-23 13:41:25', '2026-07-23 13:41:25'),
(2, 4, 1, 4, 'ajuste_entrada', 100.00, 0.00, 100.00, 'diluent_presentation', 4, 'Ajuste manual de inventario de diluyente.', '2026-07-23 13:41:44', '2026-07-23 13:41:44'),
(3, 7, NULL, 4, 'ajuste_entrada', 100.00, 0.00, 100.00, 'diluent_presentation', 7, 'Ajuste manual de inventario de diluyente.', '2026-07-24 11:55:21', '2026-07-24 11:55:21'),
(4, 5, NULL, 4, 'ajuste_entrada', 100.00, 0.00, 100.00, 'diluent_presentation', 5, 'Ajuste manual de inventario de diluyente.', '2026-07-24 11:55:31', '2026-07-24 11:55:31'),
(5, 6, NULL, 4, 'ajuste_entrada', 100.00, 0.00, 100.00, 'diluent_presentation', 6, 'Ajuste manual de inventario de diluyente.', '2026-07-24 11:55:53', '2026-07-24 11:55:53'),
(6, 3, 1, 4, 'salida', 1.00, 100.00, 99.00, 'mezcla', 28, 'Consumo de diluyente por aprobacion de mezcla.', '2026-07-24 11:56:46', '2026-07-24 11:56:46'),
(7, 4, 1, 4, 'salida', 1.00, 100.00, 99.00, 'mezcla', 29, 'Consumo de diluyente por aprobacion de mezcla.', '2026-07-24 11:57:11', '2026-07-24 11:57:11'),
(8, 3, 1, 4, 'salida', 1.00, 99.00, 98.00, 'mezcla', 19, 'Consumo de diluyente por aprobacion de mezcla.', '2026-07-24 11:59:13', '2026-07-24 11:59:13'),
(9, 3, 1, 4, 'salida', 1.00, 98.00, 97.00, 'mezcla', 20, 'Consumo de diluyente por aprobacion de mezcla.', '2026-07-24 11:59:37', '2026-07-24 11:59:37'),
(10, 11, NULL, 4, 'ajuste_entrada', 100.00, 0.00, 100.00, 'diluent_presentation', 11, 'Ajuste manual de inventario de diluyente.', '2026-07-24 12:09:04', '2026-07-24 12:09:04'),
(11, 12, NULL, 4, 'ajuste_entrada', 100.00, 0.00, 100.00, 'diluent_presentation', 12, 'Ajuste manual de inventario de diluyente.', '2026-07-24 12:09:17', '2026-07-24 12:09:17'),
(12, 9, NULL, 4, 'ajuste_entrada', 100.00, 0.00, 100.00, 'diluent_presentation', 9, 'Ajuste manual de inventario de diluyente.', '2026-07-24 12:09:28', '2026-07-24 12:09:28'),
(13, 10, NULL, 4, 'ajuste_entrada', 100.00, 0.00, 100.00, 'diluent_presentation', 10, 'Ajuste manual de inventario de diluyente.', '2026-07-24 12:09:39', '2026-07-24 12:09:39'),
(14, 8, NULL, 8, 'ajuste_entrada', 100.00, 0.00, 100.00, 'diluent_presentation', 8, 'Ajuste manual de inventario de diluyente.', '2026-07-30 12:01:11', '2026-07-30 12:01:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `distributors`
--

CREATE TABLE `distributors` (
  `id` bigint UNSIGNED NOT NULL,
  `medicine_list_id` bigint UNSIGNED NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `logo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hospitals`
--

CREATE TABLE `hospitals` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `adress` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `laboratory_id` bigint UNSIGNED DEFAULT NULL,
  `nutri_medicine_list_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `onco_medicine_list_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `hospitals`
--

INSERT INTO `hospitals` (`id`, `name`, `adress`, `is_active`, `laboratory_id`, `nutri_medicine_list_id`, `created_at`, `updated_at`, `onco_medicine_list_id`) VALUES
(1, 'CBTA', 'San Francisco 516, Col. Del Valle Centro, Benito Juárez, 03100, CDMX', 1, 1, 1, '2024-02-22 06:40:35', '2026-06-06 20:04:57', 1),
(2, 'ANGELES METROPOLITANO', 'Tlacotalpan 59, Centro Urbano Pdte. Juárez, Roma Sur, Cuauhtémoc, 06760 Cuauhtémoc, CDMX', 1, 1, 2, '2024-02-22 06:42:55', '2026-05-29 22:30:06', 1),
(3, 'HOSPITAL DE PRUEBA', 'San Francisco 524, Col del Valle Centro, Benito Juárez, 03100 Ciudad de México, CDMX', 1, NULL, NULL, '2024-07-02 08:38:55', '2024-12-07 18:24:21', NULL),
(4, 'ANGELES ACOXPA', 'Calz Acoxpa 430, Coapa, Ex-Hacienda Coapa, Tlalpan, 14308 Ciudad de México, CDMX', 1, NULL, NULL, '2024-07-02 12:13:01', '2024-07-02 12:13:01', NULL),
(5, 'ANGELES PEDREGAL', 'Camino Sta. Teresa 1055-S, Heroes de Padierna, Héroes de Padierna, La Magdalena Contreras, 10700 Ciudad de México, CDMX', 1, NULL, NULL, '2024-12-05 15:18:22', '2024-12-05 15:22:14', NULL),
(6, 'ANGELES UNIVERSIDAD', 'Av. Universidad 1080, Xoco, Benito Juárez, 03330 Ciudad de México, CDMX', 1, NULL, NULL, '2024-12-05 15:21:36', '2024-12-05 15:23:51', NULL),
(7, 'ANGELES LINDAVISTA', 'Riobamba 639 Col, Magdalena de las Salinas, Gustavo A. Madero, 07760 Ciudad de México, CDMX', 1, NULL, NULL, '2024-12-05 15:25:45', '2024-12-05 15:25:45', NULL),
(8, 'ANGELES MOCEL', 'C. Gobernador Gregorio V. Gelati 29, San Miguel Chapultepec I Secc, Miguel Hidalgo, 11850 Ciudad de México, CDMX', 1, NULL, NULL, '2024-12-05 15:26:09', '2024-12-05 15:26:09', NULL),
(9, 'ANGELES LOMAS', 'Manzana 018, Hacienda de las Palmas, 52763 Jesús del Monte, Méx.', 1, NULL, NULL, '2024-12-05 15:27:16', '2024-12-05 15:27:16', NULL),
(10, 'ANGELES SANTA MONICA', 'Temístocles 210, Polanco, Polanco IV Secc, Miguel Hidalgo, 11560 Ciudad de México, CDMX', 1, NULL, NULL, '2024-12-05 16:04:51', '2024-12-05 16:04:51', NULL),
(11, 'ANGELES CLINICA LONDRES', 'Dgo. 50, Roma, Cuauhtémoc, 06700 Ciudad de México, CDMX', 1, NULL, NULL, '2024-12-05 16:08:39', '2024-12-05 16:08:39', NULL),
(12, 'MITANIPHARMA', 'Manzana 029, 50230 San Nicolás Tolentino, Méx.', 1, NULL, NULL, '2024-12-05 16:12:24', '2024-12-05 16:12:24', NULL),
(13, 'Centro Médico \"Lic. Adolfo López Mateos\"', 'Av San Juan s/n, Delegación San Lorenzo Tepaltitlán I, Delegación San Lorenzo Tepaltitlán, 50010 Santa Cruz Atzcapotzaltongo, Méx', 1, NULL, NULL, '2024-12-11 17:07:31', '2024-12-11 17:07:31', NULL),
(14, 'Hospital Materno Perinatal \"Mónica Pretelini Sáenz\"', 'Avenida Paseo Tollocan, Poniente 201, Universidad, 50010 Toluca de Lerdo, Méx.', 1, NULL, NULL, '2024-12-11 17:55:33', '2024-12-11 17:55:33', NULL),
(15, 'Hospital Materno Infantil San José del Rincón \"José Ma. Morelos y Pavon\"', 'Victoria El Oro, Col. Santa Cruz del Rincón. C.P. 50660', 1, NULL, NULL, '2024-12-11 17:56:14', '2024-12-11 17:56:14', NULL),
(16, 'Hospital Materno Infantil \"Guadalupe Victoria\", Atizapan de Zaragoza', 'Ejército Mexicano Lote 11, Lomas de las Torres, 52918 Cdad. López Mateos, Méx.', 1, NULL, NULL, '2024-12-11 17:56:44', '2024-12-11 17:56:44', NULL),
(17, 'Hospital Materno Infantil Chalco \"Josefa Ortiz de Domínguez', 'Jazmín 10, Santa maria, 56623 Santa Catarina Ayotzingo, Méx.', 1, NULL, NULL, '2024-12-11 17:58:11', '2024-12-11 17:58:11', NULL),
(18, 'Hospital Materno Infantil \"Vicente Guerrero\" Chimalhuacán', 'Emiliano Zapata s/n, Transportistas, 56363 Chimalhuacán, Méx.', 1, NULL, NULL, '2024-12-11 17:58:46', '2024-12-11 17:58:46', NULL),
(19, 'Hospital Materno Infantil Los Reyes La Paz', 'KM 23.5, S/N, México 136, Col. La Magdalena Atlicpac, 56525 La Magdalena Atlicpac, Méx', 1, NULL, NULL, '2024-12-11 18:00:01', '2024-12-11 18:00:01', NULL),
(20, 'Hospital General \"Dr Nicolas San Juan\"', 'Calle Doctor Nicolás San Juan, De La Magdalena, Delegación San Lorenzo Tepatitlán, 50010 Santa Cruz Atzcapotzaltongo, Méx.', 1, NULL, NULL, '2024-12-11 18:01:11', '2024-12-11 18:01:11', NULL),
(21, 'Hospital General Atlacomulco', 'Jorge Jiménez Cantú Centro S/N, Las Mercedes, 50455 Atlacomulco de Fabela, Méx.', 1, NULL, NULL, '2024-12-11 18:01:49', '2024-12-11 18:01:49', NULL),
(22, 'Hospital General San Felipe del Progreso', 'Calle Insurgentes S/N, Insurgentes 126, Héroes de la Independencia, 50640 San Felipe del Progreso, Méx.', 1, NULL, NULL, '2024-12-11 18:02:48', '2024-12-11 18:02:48', NULL),
(23, 'Hospital General Ixtlahuaca \"Valentin Gómez Farias\"', 'Carretera Ixtlahuaca-Jiquipilco km. 1 Manzana 069, San Pedro, 50740 San Pedro la Cabecera, Méx.', 1, NULL, NULL, '2024-12-11 18:04:17', '2024-12-11 18:04:17', NULL),
(24, 'Hospital General Jilotepec', 'Av Reforma s/n, Vista Hermosa, 54246 Jilotepec de Molina Enríquez, Méx', 1, NULL, NULL, '2024-12-11 18:04:48', '2024-12-11 18:04:48', NULL),
(25, 'Hospital General Valle de Bravo', 'Fray Gregorio Jiménez de La Cuenca Manzana 064, San Antonio, 51200 Valle de Bravo, Méx.', 1, NULL, NULL, '2024-12-11 18:05:17', '2024-12-11 18:05:17', NULL),
(26, 'Hospital General Ixtapan de la Sal', 'Boulevar Turístico Ixtapan De La Sal-Tonatico E N/A, 51900 Ixtapan de la Sal, Méx.', 1, NULL, NULL, '2024-12-11 18:06:39', '2024-12-11 18:06:39', NULL),
(27, 'Hospital General Tejupilco \"Miguel Hidalgo y Costilla\"', 'Carretera Tejupilco Luviano km 1,, Tejupilco, 51406 Hidalgo, Méx.', 1, NULL, NULL, '2024-12-11 18:07:05', '2024-12-11 18:07:05', NULL),
(28, 'Hospital General Tenancingo', 'Dr. Genaro Díaz Manon, La Trinidad, 52436 El Salitre, Méx', 1, NULL, NULL, '2024-12-11 18:07:29', '2024-12-11 18:07:29', NULL),
(29, 'Hospital General \"José Ma. Rodríguez\" Ecatepec', 'Leona Vicario 109, Valle de Anáhuac, 55200 Ecatepec de Morelos, Méx.', 1, NULL, NULL, '2024-12-11 18:07:55', '2024-12-11 18:07:55', NULL),
(30, 'Hospital General \"Las Americas\" Ecatepec', 'Avenida Simón Bolívar 1, Manzana 10, 55076 Ecatepec de Morelos, Méx', 1, NULL, NULL, '2024-12-11 18:08:30', '2024-12-11 18:08:30', NULL),
(31, 'Hospital General \"Maximiliano Ruiz Castañeda\" Naucalpan', 'Ferrocarril De Acámbaro S/N, San Bartolo, 53000 Naucalpan de Juárez, Méx.', 1, NULL, NULL, '2024-12-11 18:09:47', '2024-12-11 18:09:47', NULL),
(32, 'Hospital General Axapusco', 'Avenida Licenciado Benito Juárez CARRETERA MÉXICO TULANCINGO, 55940 Axapusco, Méx.', 1, NULL, NULL, '2024-12-11 18:10:22', '2024-12-11 18:10:22', NULL),
(33, 'Hospital General Chalco', 'Av Cuauhtémoc s/n, Ejidal, 56604 Chalco de Díaz Covarrubias, Méx.', 1, NULL, NULL, '2024-12-11 18:11:00', '2024-12-11 18:11:00', NULL),
(34, 'Hospital General \"Dr Salvador González Herrejon\", Atizapán', 'Boulevard Adolfo López Mateos El Potrero, 52987 Cdad. López Mateos, Méx.', 1, NULL, NULL, '2024-12-11 18:11:39', '2024-12-11 18:11:39', NULL),
(35, 'Hospital General \"Dr. Gustavo Baz Prada\" Nezahualcoyotl', 'Av. Bordo de Xochiaca Manzana 001, 57300 Nezahualcóyotl, TAMPS', 1, NULL, NULL, '2024-12-11 18:12:05', '2024-12-11 18:12:05', NULL),
(36, 'Hospital General La Perla, Nezahualcóyotl', 'C. Escondida 63, La Perla, 57830 Cdad. Nezahualcóyotl, Méx.', 1, NULL, NULL, '2024-12-11 18:12:29', '2024-12-28 23:11:14', NULL),
(37, 'Hospital General \"José Vicente Villada\" Cuautitlán', 'Alfonso Reyes Manzana 001, Paseos de Sta María, 54800 Cuautitlán, Méx.', 1, NULL, NULL, '2024-12-11 18:12:58', '2024-12-11 18:12:58', NULL),
(38, 'Hospital General \"Dr. Fernando Quiróz Gutiérrez\", Xico', 'Av. del Mazo s/n, San Miguel, 56600 Valle de Chalco Solidaridad, Méx.', 1, NULL, NULL, '2024-12-11 18:13:22', '2024-12-11 18:13:22', NULL),
(39, 'Hospital General Chimalhuacan', 'Av. del Peñon S/N, Saraperos, 56356 Chimalhuacán, Méx.', 1, NULL, NULL, '2024-12-11 18:14:38', '2024-12-11 18:14:38', NULL),
(40, 'Hospital General Texcoco \"Guadalupe Victoria\"', 'Manzana 023, San Juanito, 56120 Texcoco de Mora, Méx', 1, NULL, NULL, '2024-12-11 18:15:01', '2024-12-11 18:15:01', NULL),
(41, 'Hospital General \" Valentin Gómez Farias\", Amecameca', 'C. Laura Méndez de Cuenca 7, 56766 La Colonia, Méx.', 1, NULL, NULL, '2024-12-11 18:15:24', '2024-12-11 18:15:24', NULL),
(42, 'Hospital Municipal Xonacatlán \"Vicente Guerrero\", Bicentenario', 'Benito Juárez SN, Centro, 52060 Xonacatlán, Méx.', 1, NULL, NULL, '2024-12-11 18:16:15', '2024-12-11 18:16:15', NULL),
(43, 'Hospital Municipal Hueypoxtla', 'Manzana 031, Centro, 55670 Hueypoxtla, Méx.', 1, NULL, NULL, '2024-12-11 18:16:54', '2024-12-11 18:16:54', NULL),
(44, 'Hospital Municipal Ixtapaluca \"Leoana Vicario\"', 'Paseo de Los Volcanes S/N, Unidad San Buenaventura, 56530 San Buenaventura, Méx.', 1, NULL, NULL, '2024-12-11 18:17:23', '2024-12-28 23:13:30', NULL),
(45, 'test', 'testt', 1, NULL, NULL, '2025-05-17 13:43:22', '2025-05-17 13:43:22', NULL),
(46, 'Hospital Angeles Metropolitano', 'Calle Tlacotalpan 59, 06760 Ciudad de México, Ciudad de México', 0, 1, 1, '2026-05-29 05:14:05', '2026-06-24 20:44:47', NULL),
(47, 'HOSPITAL SAN DIEGO', 'Av. San Diego 1203, zona 1, Delicias, 62330 Cuernavaca, Mor.', 1, 1, 2, '2026-06-24 19:56:01', '2026-06-24 19:56:01', NULL),
(48, 'CENTRO DE MEZCLAS PRODIFEM CDMX', 'San Francisco 524, int C, Col del Valle Nte, Benito Juárez, 03103 Ciudad de México, CDMX', 1, 1, 2, '2026-07-03 16:57:36', '2026-07-03 16:57:36', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `infusors`
--

CREATE TABLE `infusors` (
  `id` bigint UNSIGNED NOT NULL,
  `nombre_generico` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre_comercial` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lote` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caducidad` date DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inputs`
--

CREATE TABLE `inputs` (
  `id` bigint UNSIGNED NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `tipo_input` enum('adulto','niño','ambos') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden_enum` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `category_id` bigint UNSIGNED DEFAULT NULL,
  `mult` decimal(8,3) NOT NULL DEFAULT '1.000',
  `div` decimal(10,5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inputs`
--

INSERT INTO `inputs` (`id`, `description`, `unidad`, `is_active`, `tipo_input`, `orden_enum`, `created_at`, `updated_at`, `category_id`, `mult`, `div`) VALUES
(4, 'Aminoácidos Estándar al 10%', 'g/Kg', 1, 'adulto', 1, NULL, NULL, 1, 100.000, 10.00000),
(5, 'Aminoácidos pediátricos 10%', 'g/Kg', 1, 'niño', 2, NULL, NULL, 1, 100.000, 10.00000),
(6, 'Aminoácidos al 8% de cadena ramificada', 'g/Kg', 1, 'ambos', 3, NULL, NULL, 1, 100.000, 8.00000),
(7, 'Aminoácidos para Nefrópatas 5.4%', 'g/Kg', 1, 'ambos', 4, NULL, NULL, 1, 100.000, 5.40000),
(8, 'Solución Glucosada al 50%', 'g/Kg', 1, 'ambos', 6, NULL, NULL, 2, 100.000, 50.00000),
(9, 'Lípidos de Cadena Media y Larga al 20%', 'g/Kg', 1, 'ambos', 8, NULL, NULL, 3, 100.000, 20.00000),
(10, 'Lípidos de Cadena Media con Acidos Grasos Omega 3 al 20%', 'g/Kg', 1, 'ambos', 10, NULL, NULL, 3, 100.000, 20.00000),
(11, 'Cloruro de Sodio al 17.7% (3 mEq/mL) ', 'mEq/Kg', 1, 'ambos', 11, NULL, NULL, 4, 1.000, 3.00000),
(12, 'Acetato de Sodio (4 mEq/mL)', 'mEq/Kg', 1, 'ambos', 12, NULL, NULL, 4, 1.000, 4.00000),
(13, 'Fosfato de Sodio (4 mEq/mL)', 'mEq/Kg', 0, 'ambos', 13, NULL, NULL, 4, 1.000, 4.00000),
(14, 'Sulfato de Magnesio (0.81 mEq/mL)', 'mEq/Kg', 1, 'ambos', 14, NULL, NULL, 4, 1.000, 0.81000),
(15, 'Cloruro de Potasio (4 mEq/mL)', 'mEq/Kg', 1, 'ambos', 15, NULL, NULL, 4, 1.000, 4.00000),
(16, 'Acetato de Potasio (2 mEq/mL)', 'mEq/Kg', 1, 'ambos', 16, NULL, NULL, 4, 1.000, 2.00000),
(17, 'Fosfato de Potasio (2 mEq/mL)', 'mEq/Kg', 1, 'ambos', 17, NULL, NULL, 4, 1.000, 2.00000),
(18, 'Gluconato de Calcio (0.465 mEq/mL)', 'mEq/Kg', 1, 'ambos', 18, NULL, NULL, 4, 1.000, 0.46500),
(19, 'Emulsión de Aceite de Pescado al 10% (AG Omega 3)', 'mL', 1, 'ambos', 19, NULL, NULL, 5, 1.000, 1.00000),
(20, 'Albúmina 25% (0.25 g/mL)', 'g', 1, 'ambos', 20, NULL, NULL, 5, 100.000, 25.00000),
(21, 'Albúmina 20% (0.20 g/mL)', 'g', 1, 'ambos', 21, NULL, '2026-07-21 16:44:02', 5, 100.000, 20.00000),
(22, 'Glutamina 20%', 'g', 1, 'ambos', 22, NULL, NULL, 5, 100.000, 20.00000),
(23, 'Cromo  (4 mcg/mL)', 'mcg', 1, 'ambos', 23, NULL, NULL, 5, 1.000, 4.00000),
(24, 'Heparina (1000 UI/mL)', 'UI', 1, 'ambos', 24, NULL, NULL, 5, 1.000, 1000.00000),
(25, 'L-Carnitina (200 mg/mL)', 'mg', 1, 'ambos', 25, NULL, NULL, 5, 1.000, 200.00000),
(26, 'Insulina (100 UI/mL)', 'UI', 1, 'ambos', 26, NULL, NULL, 5, 1.000, 100.00000),
(27, 'Manganeso (100 mcg/mL)', 'mcg', 0, 'ambos', 27, NULL, NULL, 5, 1.000, 100.00000),
(28, 'Multivitaminico', 'mL', 1, 'ambos', 28, NULL, NULL, 5, 1.000, 1.00000),
(29, 'Oligoelementos', 'mL', 1, 'ambos', 30, NULL, NULL, 5, 1.000, 1.00000),
(30, 'Ácido Folínico (12.5 mg/mL)', 'mg', 1, 'ambos', 32, NULL, NULL, 5, 1.000, 12.50000),
(31, 'Selenio (40 mcg/mL)', 'mcg', 0, 'ambos', 33, NULL, NULL, 5, 1.000, 40.00000),
(32, 'Vitamina C (100 mg/mL)', 'mg', 1, 'ambos', 34, NULL, NULL, 5, 1.000, 100.00000),
(33, 'Vitamina K (10 mg/mL)', 'mg', 1, 'ambos', 35, NULL, NULL, 5, 1.000, 10.00000),
(34, 'Zinc (1 mg/mL)', 'mg', 1, 'ambos', 36, NULL, NULL, 5, 1.000, 1.00000),
(35, 'L-Cisteina (50mg/mL)', 'mg', 1, 'ambos', 37, NULL, NULL, 5, 1.000, 50.00000),
(36, 'Cloruro de Sodio 0.9%', 'mL', 0, 'ambos', 5, NULL, NULL, 8, 1.000, 1.00000),
(37, 'Agua Inyectable', 'mL', 1, 'ambos', 38, NULL, NULL, 7, 1.000, 1.00000),
(38, 'Bolsa EVA (3000 mL)', 'mL', 1, 'ambos', 39, NULL, NULL, 6, 1.000, 1.00000),
(39, 'Bolsa EVA (500 mL)', 'mL', 1, 'ambos', 40, NULL, NULL, 6, 1.000, 1.00000),
(40, 'Set de Infusión', 'ud', 1, 'ambos', 43, NULL, NULL, 10, 1.000, 1.00000),
(41, 'Preparación para NPT', 'serv', 1, 'ambos', 44, NULL, NULL, 11, 1.000, 1.00000),
(42, 'Bolsa EVA (250 mL)', 'mL', 1, 'ambos', 41, NULL, NULL, 6, 1.000, 1.00000),
(43, 'Bolsa EVA (1000 mL)', 'mL', 1, 'ambos', 42, NULL, NULL, 6, 1.000, 1.00000),
(44, 'Multivitaminico Pediátrico', 'mL', 1, 'niño', 29, NULL, NULL, 5, 1.000, 1.00000),
(45, 'SMOF 10%', 'g/Kg', 0, 'ambos', 9, NULL, NULL, 3, 100.000, 10.00000),
(46, 'MCT/LCT 10%', 'g/Kg', 0, 'ambos', 7, NULL, NULL, 3, 100.000, 10.00000),
(47, 'Oligoelementos Tracefusin', 'mL', 0, 'ambos', 31, NULL, NULL, 5, 1.000, 1.00000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inspeccion_mezclas`
--

CREATE TABLE `inspeccion_mezclas` (
  `id` bigint UNSIGNED NOT NULL,
  `mezcla_id` bigint UNSIGNED NOT NULL,
  `es_limpia` tinyint(1) NOT NULL DEFAULT '0',
  `es_libre` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_inspeccion` date NOT NULL,
  `hora_inspeccion` time NOT NULL,
  `tipo_contenedor` enum('Frasco','Bolsa','Jeringa','Infusor') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_contenedor_otro` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `esta_rotulado` tinyint(1) NOT NULL DEFAULT '0',
  `numero_lote` tinyint(1) NOT NULL DEFAULT '0',
  `medicamento` tinyint(1) NOT NULL DEFAULT '0',
  `dosis_volumen_total` tinyint(1) NOT NULL DEFAULT '0',
  `volumen_medicamento` tinyint(1) NOT NULL DEFAULT '0',
  `rubrica_preparador` tinyint(1) NOT NULL DEFAULT '0',
  `sello_seguridad` tinyint(1) NOT NULL DEFAULT '0',
  `presenta_grietas` tinyint(1) NOT NULL DEFAULT '0',
  `presenta_fugas` tinyint(1) NOT NULL DEFAULT '0',
  `esta_roto` tinyint(1) NOT NULL DEFAULT '0',
  `coloracion_apropiada` tinyint(1) NOT NULL DEFAULT '0',
  `contenido_homogeneo` tinyint(1) NOT NULL DEFAULT '0',
  `presenta_particulas` tinyint(1) NOT NULL DEFAULT '0',
  `presenta_turbidez` tinyint(1) NOT NULL DEFAULT '0',
  `volumen_correcto` tinyint(1) NOT NULL DEFAULT '0',
  `aprueba_contenido` tinyint(1) NOT NULL DEFAULT '0',
  `aprueba_contenedor` tinyint(1) NOT NULL DEFAULT '0',
  `dosis_volumen` decimal(8,2) NOT NULL DEFAULT '0.00',
  `peso_mezcla` decimal(8,2) NOT NULL DEFAULT '0.00',
  `mezcla_aprobada` tinyint(1) NOT NULL DEFAULT '0',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reviso_nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aprobo_nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `preparo_nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `libero_nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inspeccion_mezclas`
--

INSERT INTO `inspeccion_mezclas` (`id`, `mezcla_id`, `es_limpia`, `es_libre`, `fecha_inspeccion`, `hora_inspeccion`, `tipo_contenedor`, `tipo_contenedor_otro`, `esta_rotulado`, `numero_lote`, `medicamento`, `dosis_volumen_total`, `volumen_medicamento`, `rubrica_preparador`, `sello_seguridad`, `presenta_grietas`, `presenta_fugas`, `esta_roto`, `coloracion_apropiada`, `contenido_homogeneo`, `presenta_particulas`, `presenta_turbidez`, `volumen_correcto`, `aprueba_contenido`, `aprueba_contenedor`, `dosis_volumen`, `peso_mezcla`, `mezcla_aprobada`, `observaciones`, `reviso_nombre`, `aprobo_nombre`, `preparo_nombre`, `libero_nombre`, `created_at`, `updated_at`) VALUES
(1, 1, 0, 0, '2026-05-28', '17:45:07', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.00, 0.00, 0, NULL, '', 'Gabriela', 'Gabriela', NULL, '2026-05-29 05:45:07', '2026-05-29 05:51:10'),
(2, 2, 1, 1, '2026-06-24', '14:25:03', 'Frasco', NULL, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 1, 0, 0, 1, 1, 1, 0.00, 0.00, 1, 'N.A.', 'Gabriela', 'Gabriela', 'Gabriela', NULL, '2026-06-04 19:50:36', '2026-06-24 20:25:03'),
(3, 3, 1, 1, '2026-06-24', '13:21:50', 'Frasco', NULL, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 1, 0, 0, 1, 1, 1, 6.00, 0.00, 1, 'N.A.', 'Gabriela', 'Gabriela', 'Cristian David', NULL, '2026-06-24 23:58:30', '2026-06-24 19:21:50'),
(4, 4, 0, 0, '2026-07-21', '10:58:57', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.00, 0.00, 0, NULL, '', 'Gaby', NULL, NULL, '2026-07-21 16:58:57', '2026-07-21 16:58:57'),
(5, 6, 0, 0, '2026-07-21', '11:03:14', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.00, 0.00, 0, NULL, '', 'Gaby', 'GCORTES', NULL, '2026-07-21 17:03:14', '2026-07-28 19:14:43'),
(6, 8, 1, 1, '2026-07-21', '12:49:28', 'Frasco', NULL, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 1, 0, 0, 1, 1, 1, 40.00, 220.00, 1, 'N.A.', 'HCARBAJAL', 'GCORTES', 'GCORTES', NULL, '2026-07-21 18:05:20', '2026-07-21 18:49:28'),
(7, 5, 0, 0, '2026-07-21', '16:27:45', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.00, 0.00, 0, NULL, '', 'GCORTES', NULL, NULL, '2026-07-21 22:27:45', '2026-07-21 22:27:45'),
(8, 28, 0, 0, '2026-07-24', '05:56:46', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.00, 0.00, 0, NULL, '', 'GCORTES', NULL, NULL, '2026-07-24 11:56:46', '2026-07-24 11:56:46'),
(9, 29, 0, 0, '2026-07-24', '05:57:11', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.00, 0.00, 0, NULL, '', 'GCORTES', NULL, NULL, '2026-07-24 11:57:11', '2026-07-24 11:57:11'),
(10, 19, 0, 0, '2026-07-24', '05:59:13', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.00, 0.00, 0, NULL, '', 'GCORTES', NULL, NULL, '2026-07-24 11:59:13', '2026-07-24 11:59:13'),
(11, 20, 0, 0, '2026-07-24', '05:59:37', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.00, 0.00, 0, NULL, '', 'GCORTES', NULL, NULL, '2026-07-24 11:59:37', '2026-07-24 11:59:37'),
(12, 36, 1, 1, '2026-08-01', '09:14:40', NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 1, 0, 0, 1, 1, 1, 50.00, 275.00, 1, 'N.A.', 'GCORTES', 'GCORTES', 'GCORTES', NULL, '2026-08-01 15:13:50', '2026-08-01 15:14:40'),
(13, 37, 1, 1, '2026-08-01', '14:23:07', 'Frasco', NULL, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 1, 0, 0, 1, 1, 1, 70.00, 750.00, 1, 'N.A.', 'GCORTES', 'GCORTES', 'PVAZQUEZ', NULL, '2026-08-01 15:53:49', '2026-08-01 20:23:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inspeccion_nutricionales`
--

CREATE TABLE `inspeccion_nutricionales` (
  `id` bigint UNSIGNED NOT NULL,
  `solicitud_id` bigint UNSIGNED NOT NULL,
  `es_limpia` tinyint(1) NOT NULL DEFAULT '0',
  `es_libre` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_inspeccion` date NOT NULL,
  `hora_inspeccion` time NOT NULL,
  `tipo_contenedor` enum('Frasco','Bolsa','Jeringa','Infusor') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_contenedor_otro` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `esta_rotulado` tinyint(1) NOT NULL DEFAULT '0',
  `numero_lote` tinyint(1) NOT NULL DEFAULT '0',
  `medicamento` tinyint(1) NOT NULL DEFAULT '0',
  `dosis_volumen_total` tinyint(1) NOT NULL DEFAULT '0',
  `volumen_medicamento` tinyint(1) NOT NULL DEFAULT '0',
  `rubrica_preparador` tinyint(1) NOT NULL DEFAULT '0',
  `sello_seguridad` tinyint(1) NOT NULL DEFAULT '0',
  `presenta_grietas` tinyint(1) NOT NULL DEFAULT '0',
  `presenta_fugas` tinyint(1) NOT NULL DEFAULT '0',
  `esta_roto` tinyint(1) NOT NULL DEFAULT '0',
  `coloracion_apropiada` tinyint(1) NOT NULL DEFAULT '0',
  `contenido_homogeneo` tinyint(1) NOT NULL DEFAULT '0',
  `presenta_particulas` tinyint(1) NOT NULL DEFAULT '0',
  `presenta_turbidez` tinyint(1) NOT NULL DEFAULT '0',
  `volumen_correcto` tinyint(1) NOT NULL DEFAULT '0',
  `aprueba_contenido` tinyint(1) NOT NULL DEFAULT '0',
  `aprueba_contenedor` tinyint(1) NOT NULL DEFAULT '0',
  `dosis_volumen` decimal(8,2) NOT NULL DEFAULT '0.00',
  `peso_mezcla` decimal(8,2) NOT NULL DEFAULT '0.00',
  `mezcla_aprobada` tinyint(1) NOT NULL DEFAULT '0',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reviso_nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aprobo_nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preparo_nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `libero_nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inspeccion_nutricionales`
--

INSERT INTO `inspeccion_nutricionales` (`id`, `solicitud_id`, `es_limpia`, `es_libre`, `fecha_inspeccion`, `hora_inspeccion`, `tipo_contenedor`, `tipo_contenedor_otro`, `esta_rotulado`, `numero_lote`, `medicamento`, `dosis_volumen_total`, `volumen_medicamento`, `rubrica_preparador`, `sello_seguridad`, `presenta_grietas`, `presenta_fugas`, `esta_roto`, `coloracion_apropiada`, `contenido_homogeneo`, `presenta_particulas`, `presenta_turbidez`, `volumen_correcto`, `aprueba_contenido`, `aprueba_contenedor`, `dosis_volumen`, `peso_mezcla`, `mezcla_aprobada`, `observaciones`, `reviso_nombre`, `aprobo_nombre`, `preparo_nombre`, `libero_nombre`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, '2026-05-18', '23:02:55', 'Frasco', NULL, 0, 1, 1, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 200.00, 200.00, 0, 'N.A.', 'Itzel Azucena', 'Itzel Azucena', 'Itzel Azucena', 'Itzel Azucena', '2026-05-19 11:01:53', '2026-05-19 11:03:52'),
(2, 2, 1, 1, '2026-05-19', '08:49:32', 'Frasco', NULL, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 0, 0, 1, 1, 1, 200.00, 200.00, 1, 'N.A.', 'Itzel Azucena', 'Itzel Azucena', 'Itzel Azucena', 'Itzel Azucena', '2026-05-19 20:31:31', '2026-05-19 20:50:21'),
(3, 3, 0, 0, '2026-06-24', '16:19:13', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.00, 0.00, 0, NULL, NULL, 'Gabriela', 'Cristian David', NULL, '2026-06-24 22:19:13', '2026-06-30 19:34:13'),
(4, 4, 1, 1, '2026-06-30', '13:35:05', NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 0, 0, 1, 1, 1, 1498.00, 1800.00, 1, 'N.A.', 'Gabriela', 'Gabriela', 'Cristian David', 'GCORTES', '2026-06-30 19:19:00', '2026-07-22 15:48:10'),
(5, 5, 1, 1, '2026-07-02', '17:13:57', NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 0, 0, 1, 1, 1, 185.00, 200.00, 1, 'N.A.', 'Gabriela', 'Gabriela', 'HANNIA RUBI', 'GCORTES', '2026-07-02 22:48:00', '2026-07-22 15:48:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `institution_billings`
--

CREATE TABLE `institution_billings` (
  `id` bigint UNSIGNED NOT NULL,
  `institucion_id` bigint UNSIGNED NOT NULL,
  `hospital_id` bigint UNSIGNED DEFAULT NULL,
  `origen_tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origen_id` bigint UNSIGNED NOT NULL,
  `precio_total` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conciliable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `folio_factura_uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `folio_interno` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_facturacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estatus_facturacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_carta_factura` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_carta_factura` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `laboratories`
--

CREATE TABLE `laboratories` (
  `id` bigint UNSIGNED NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `laboratories`
--

INSERT INTO `laboratories` (`id`, `nombre`, `estado`, `direccion`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'CDMX', 'CDMX', 'San Francisco 516', 1, '2026-05-19 10:33:04', '2026-05-19 10:33:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicines`
--

CREATE TABLE `medicines` (
  `id` bigint UNSIGNED NOT NULL,
  `denominacion_comercial` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `denominacion_generica` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio_ml` double NOT NULL,
  `presentacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `presentacion_ml` double DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `osmolaridad` double DEFAULT NULL,
  `category_id` bigint UNSIGNED NOT NULL,
  `input_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medicines`
--

INSERT INTO `medicines` (`id`, `denominacion_comercial`, `denominacion_generica`, `precio_ml`, `presentacion`, `presentacion_ml`, `is_active`, `osmolaridad`, `category_id`, `input_id`, `created_at`, `updated_at`) VALUES
(1, 'LEVAMIN NORMO 10%.', 'AMINOACIDOS ESTANDAR AL 10%', 0.1, 'FRASCO 500ML', 500, 1, 1021, 1, 4, '2024-03-14 16:29:02', '2026-05-19 06:30:40'),
(2, 'PRIMENE', 'AMINOACIDOS PEDIATRICOS 10%', 6.88, 'FRASCO 250ML', 250, 1, 1021, 1, 5, '2024-03-14 16:33:20', '2026-05-19 06:38:07'),
(3, 'LEVAMI NORMO', 'AMINOACIDOS CRISTALINOS AL 8.5%', 0.1, 'FRASCO 500ML', 500, 1, 60, 1, 6, '2024-03-14 16:38:52', '2026-03-08 02:09:17'),
(4, 'LEVAMIN NEP', 'AMINOACIDOS PARA NEFROPATAS 5.4%', 0.1, 'FRASCO 250ML', 250, 1, 60, 1, 7, '2024-03-14 16:44:04', '2025-10-03 05:06:24'),
(5, 'SOLUCION DX-50 PISA', 'SOLUCION GLUCOSADA AL 50%', 2.08, 'FRASCO 500ML', 500, 1, 2525, 2, 8, '2024-03-14 16:44:48', '2026-05-19 06:36:53'),
(6, 'LIPOVENOES', 'LIPIDOS DE CADENA MEDIA Y LARGA 20%', 15.65, 'FRASCO 500ML', 500, 1, 380, 3, 9, '2024-03-14 16:45:46', '2026-05-19 06:41:21'),
(7, 'SMOF LIPID', 'EMULSION LIPIDICA 20% DE ACEITE DE SOJA, TRIGLICERIDOS DE CADENA MEDIA, ACEITE DE OLIVA Y DE ACEITE DE PESCADO', 20.87, 'FRASCO 500ML', 500, 1, 6, 3, 10, '2024-03-14 16:49:08', '2026-05-19 06:46:21'),
(8, 'SOLUCION CS-C 17.7%', 'CLORURO DE SODIO AL 17.7% (3mEq/mL)', 5.21, 'FRASCO 50ML', 50, 1, 5370, 4, 11, '2024-03-14 16:49:59', '2026-05-19 06:50:09'),
(9, 'SOLUCION AC-S', 'ACETATO DE SODIO (4mEq/mL)', 9.39, 'FRASCO 50ML', 50, 1, 20, 4, 12, '2024-03-14 16:51:06', '2026-05-19 06:50:57'),
(10, 'SOLUCION AC-S', 'FOSFATO DE SODIO (4mEq/mL)', 9.5, 'FRASCO 50ML', 50, 1, 7, 4, 13, '2024-03-14 16:52:10', '2026-05-19 06:51:39'),
(11, 'MAGNEFUSIN', 'SULFATO DE MAGNESIO (0.81mEq/mL)', 7.3, 'AMP 10ML', 10, 1, 1620, 4, 14, '2024-03-14 16:53:02', '2026-05-19 06:52:21'),
(12, 'KELEFUSIN', 'CLORURO DE POTASIO (4mEq/mL)', 13.56, 'AMP 5ML', 5, 1, 5369, 4, 15, '2024-03-14 16:53:32', '2026-05-19 06:53:04'),
(13, 'CEPOSIL', 'ACETATO DE POTASIO (2mEq/mL)', 9.39, 'FRASCO 20', 20, 1, 50, 4, 16, '2024-03-14 16:54:42', '2026-05-19 06:57:14'),
(14, 'FP-20', 'FOSFATO DE POTASIO (2mEq/mL)', 8.34, 'FRASCO 50ML', 50, 1, 2667, 4, 17, '2024-03-14 16:55:12', '2026-05-19 07:00:26'),
(15, 'SOLUCION GC', 'GLUCONATO DE CALCIO (0.465mEq/mL)', 7.3, 'AMP 10ML', 10, 1, 697.07, 4, 18, '2024-03-14 16:55:56', '2026-05-19 07:02:38'),
(16, 'FRESOMEGA', 'ACIDOS GRACOS OMEGA 3', 43.82, 'FCO AMP 100ML', 100, 1, 273, 5, 19, '2024-03-14 16:56:55', '2026-03-04 05:54:04'),
(17, 'OCTALBIN', 'ALBUMINA 25% (0.25g/ml)', 125.22, 'FCO AMP 50ML', 50, 1, 10, 5, 20, '2024-03-14 16:57:40', '2026-04-08 05:34:03'),
(18, 'ALBUMINA HUMANA GRIFOLS', 'ALBUMINA 0.2g/ml', 125.22, 'FCO AMP 50ML', 50, 1, 19, 5, 21, '2024-03-14 16:58:37', '2026-04-08 10:23:14'),
(19, 'DIPEPTIVEN', 'GLUTAMINA 20%', 109.56, 'FCO AMP 50ML', 50, 1, 921, 5, 22, '2024-03-14 16:59:37', '2026-05-19 07:10:31'),
(20, 'CROMIFUSIN', 'CROMO (4mcg/mL)', 1, 'FCO AMP 10ML', 10, 1, 15, 5, 23, '2024-03-14 17:00:08', '2026-05-19 07:22:01'),
(21, 'INHEPAR', 'HEPARINA (1000UI/mL)', 67.82, 'FCO AMP 10ML', 10, 1, 78, 5, 24, '2024-03-14 17:00:58', '2026-03-04 06:07:52'),
(22, 'EFE-CARN', 'L-CARNITINA (200mg/mL)', 29.21, 'AMP 5ML', 5, 1, 56, 5, 25, '2024-03-14 17:01:31', '2026-05-19 07:11:26'),
(23, 'INSULEX R', 'INSULINA (100UI/mL)', 118.1, 'FCO AMP 10ML', 10, 1, 90, 5, 26, '2024-03-14 17:02:05', '2025-04-30 06:39:35'),
(24, 'MN-FUSIN', 'MANGANESO (100mcg/mL)', 1, 'FCO AMP 10ML', 10, 1, 96, 5, 27, '2024-03-14 17:02:34', '2025-10-03 05:19:00'),
(25, 'VITAFUSIN', 'MULTIVITAMINICO ADULTO', 234.78, 'FCO AMP 5ML', 5, 1, 60, 5, 28, '2024-03-14 17:09:25', '2026-04-13 16:15:57'),
(26, 'Tracefusin', 'OLIGOELEMENTOS', 50.08, 'AMP 20ML', 10, 1, 5, 5, 29, '2024-03-14 17:09:50', '2026-05-19 07:13:53'),
(27, 'INNEFOL', 'ACIDO FOLINICO (12.5 mg/mL)', 553.05, 'FCO AMP 4ML', 4, 1, 41, 5, 30, '2024-03-14 17:10:32', '2026-05-19 07:14:41'),
(28, 'SELEFUSIN', 'SELENIO 40mcg/ml', 105, 'FCO AMP 10ML', 10, 1, 63, 5, 31, '2024-03-14 17:11:01', '2024-03-14 17:11:01'),
(29, 'INFALET', 'VITAMINA C (100mg/mL)', 27.13, 'AMP 10ML', 10, 1, 50, 5, 32, '2024-03-14 17:11:35', '2026-05-19 07:15:14'),
(30, 'UNOKAVI', 'VITAMINA K (10mg/mL)', 424.7, 'AMP 1ML', 1, 1, 63, 5, 33, '2024-03-14 17:12:25', '2026-04-08 05:40:12'),
(31, 'ZINC-FUSIN', 'ZINC (1mg/mL)', 109.56, 'FCO AMP 10ML', 10, 1, 85, 5, 34, '2024-03-14 17:13:00', '2026-05-19 07:16:52'),
(32, 'FIXCANAT', 'L-CISTEINA (50mg/mL)', 91.82, 'FCO AMP 10ML', 10, 1, 20, 5, 35, '2024-03-14 17:13:33', '2026-05-19 07:19:21'),
(33, 'CLORUROSÓDICA ALPHA', 'CLORURO DE SODIO 0.9%', 0.1, 'FRASCO 100ML', 100, 1, 6, 8, 36, '2024-03-14 17:15:52', '2025-08-08 04:39:09'),
(34, 'FRESENIUS', 'AGUA INYECTABLE', 1.56, 'FRASCO 500ML', 500, 1, 21, 7, 37, '2024-04-09 11:43:55', '2026-05-19 07:23:25'),
(35, 'Industrias plásticas Médicas', 'BOLSA EVA 3000 ML', 0, 'BOLSA 3000ML', 3000, 1, 0, 6, 38, '2024-04-09 11:55:18', '2026-05-19 07:26:37'),
(36, 'Industrias plásticas Médicas', 'BOLSA EVA 500 ML', 0, 'BOLSA 500ML', 500, 1, 0, 6, 39, '2024-04-09 11:59:45', '2026-05-19 07:30:40'),
(37, 'OPTIMA', 'SET DE INFUSIÓN', 300, 'UNIDAD', 1, 1, 0, 10, 40, '2024-04-09 16:38:48', '2024-04-09 16:38:48'),
(38, 'SERVICIO DE PREPARACIÓN', 'SERVICIO DE MEZCLADO', 521.75, 'serv', 0, 1, 0, 11, 41, '2024-04-23 05:05:36', '2026-03-04 06:10:58'),
(39, 'Industrias plásticas Médicas', 'BOLSA EVA 250 ML', 0, 'BOLSA 250ML', 250, 1, 0, 6, 42, '2024-12-13 06:38:37', '2026-05-19 07:31:21'),
(40, 'Industrias plásticas Médicas', 'BOLSA EVA 1000 ML', 0, 'BOLSA 1000ML', 1000, 1, 0, 6, 43, '2024-12-13 06:39:42', '2025-09-23 08:47:09'),
(41, 'VITAFUSIN PED', 'MULTIVITAMINICO PEDIÁTRICO', 234.78, 'FCO AMP 5ML', 5, 1, 60, 5, 44, '2024-12-24 19:10:07', '2026-05-19 07:34:02'),
(42, 'LIPIDOS 10% SMOF', 'SMOF LIPID 10%', 9, 'FRASCO 500ML', 500, 1, 6, 3, 45, '2024-12-24 19:11:35', '2025-10-02 05:32:30'),
(43, 'LIPIDOS 10% MCT/LTC', 'LIPOFUNDIN 10%', 8.32, 'FRASCO 500ML', 500, 1, 8, 3, 46, '2024-12-24 19:13:11', '2026-03-08 02:13:35'),
(44, 'DESACTIVADO', 'OLIGOELEMENTOS', 50.08, 'FRASCO 20ML', 20, 1, 5, 5, 47, '2024-12-24 19:16:19', '2026-03-04 06:10:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicines_catalog`
--

CREATE TABLE `medicines_catalog` (
  `id` bigint UNSIGNED NOT NULL,
  `denominacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `requires_infusor` tinyint(1) NOT NULL DEFAULT '0',
  `state` tinyint(1) NOT NULL DEFAULT '1',
  `conc_min` decimal(8,2) DEFAULT NULL,
  `conc_max` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medicines_catalog`
--

INSERT INTO `medicines_catalog` (`id`, `denominacion`, `requires_infusor`, `state`, `conc_min`, `conc_max`, `created_at`, `updated_at`) VALUES
(1, 'Acido Folinico', 0, 1, 0.10, 10.00, '2026-04-16 04:08:53', '2026-07-21 15:48:15'),
(2, 'Acido Zoledronico', 0, 1, 0.04, 0.05, '2026-04-16 04:29:01', '2026-04-16 04:29:01'),
(3, 'Alemtuzumab', 0, 1, NULL, NULL, '2026-04-16 05:45:20', '2026-04-16 05:45:20'),
(4, 'Azacitidina', 0, 1, 25.00, 10.00, '2026-04-16 06:59:24', '2026-05-20 23:46:12'),
(5, 'Bleomicina', 0, 1, 15.00, 3.00, '2026-04-16 07:04:10', '2026-04-16 07:04:10'),
(6, 'Blinatumumab', 0, 1, NULL, NULL, '2026-04-16 07:07:27', '2026-04-16 07:07:27'),
(7, 'Atezolizumab', 0, 0, 3.20, 16.80, '2026-05-20 23:30:32', '2026-06-06 21:54:38'),
(8, 'Bendamustina', 0, 1, 1.85, 5.60, '2026-05-20 23:34:04', '2026-05-20 23:34:04'),
(9, 'Bevacizumab', 0, 1, 1.40, 16.40, '2026-05-20 23:35:29', '2026-05-20 23:35:29'),
(10, 'Brentuximab', 0, 1, 0.40, 1.80, '2026-05-20 23:36:19', '2026-05-20 23:45:15'),
(11, 'Bortezomib', 0, 1, 1.00, 2.50, '2026-05-20 23:37:44', '2026-05-20 23:37:44'),
(12, 'Carboplatino', 0, 1, 0.50, 4.00, '2026-05-20 23:44:46', '2026-05-20 23:44:46'),
(13, 'Belimumab', 0, 1, 2.00, 4.00, '2026-05-20 23:46:50', '2026-05-20 23:46:50'),
(14, 'Busulfano', 0, 0, 0.50, 0.50, '2026-05-20 23:47:15', '2026-06-17 01:39:26'),
(15, 'Carmustina', 0, 1, 0.10, 1.00, '2026-05-20 23:47:58', '2026-05-20 23:47:58'),
(16, 'Carfilzomib', 0, 1, 0.60, 1.20, '2026-05-20 23:49:05', '2026-05-20 23:49:05'),
(17, 'Citarabina', 0, 1, 0.10, 32.00, '2026-05-20 23:49:58', '2026-05-20 23:49:58'),
(18, 'Carboximaltosa ferrica', 0, 1, 2.00, 3.00, '2026-05-20 23:51:19', '2026-05-20 23:51:19'),
(19, 'Ciclofosfamida', 0, 1, 1.00, 40.00, '2026-05-20 23:52:19', '2026-07-23 12:15:02'),
(20, 'Cisplatino', 0, 1, 0.05, 2.00, '2026-05-20 23:52:42', '2026-05-20 23:52:42'),
(21, 'Cetuximab', 0, 1, 4.00, 4.00, '2026-05-20 23:53:06', '2026-05-20 23:53:06'),
(22, 'Daunorubicina', 0, 1, 0.02, 0.10, '2026-05-20 23:53:32', '2026-05-20 23:53:32'),
(23, 'Dacarbazina', 0, 1, 1.40, 3.00, '2026-05-20 23:54:15', '2026-05-20 23:54:15'),
(24, 'Daratumumab', 0, 1, NULL, NULL, '2026-05-20 23:55:00', '2026-05-20 23:55:00'),
(25, 'Daratumumab', 0, 0, NULL, NULL, '2026-05-20 23:55:00', '2026-05-30 03:29:25'),
(26, 'Dexametasona', 0, 1, NULL, NULL, '2026-05-20 23:55:36', '2026-05-20 23:55:36'),
(27, 'Dexrazoxano', 0, 1, 1.30, 3.00, '2026-05-20 23:56:20', '2026-05-20 23:56:20'),
(28, 'Doxorubicina', 0, 1, 0.01, 2.00, '2026-05-20 23:56:54', '2026-05-20 23:56:54'),
(29, 'Docetaxel', 0, 1, 0.00, 0.74, '2026-05-20 23:57:44', '2026-05-20 23:57:44'),
(30, 'Etoposido', 0, 1, 0.20, 0.40, '2026-05-20 23:58:03', '2026-05-20 23:58:03'),
(31, 'Fosaprepitant', 0, 1, 1.00, 1.00, '2026-05-20 23:58:31', '2026-05-20 23:58:31'),
(32, 'Gemcitabina', 0, 1, 0.10, 10.00, '2026-05-20 23:58:53', '2026-05-20 23:58:53'),
(33, 'Idarrubicina', 0, 1, 0.01, 0.10, '2026-05-21 00:04:56', '2026-05-21 00:04:56'),
(34, 'Ifosfamida', 0, 1, 0.60, 20.00, '2026-05-21 00:05:21', '2026-05-21 00:05:21'),
(35, 'Infliximab', 0, 1, NULL, NULL, '2026-05-21 00:06:33', '2026-05-21 00:06:33'),
(36, 'L- Asparaginasa', 0, 1, NULL, NULL, '2026-05-21 00:07:54', '2026-05-21 00:07:54'),
(37, 'Mitoxantrona', 0, 1, 0.02, 0.50, '2026-05-30 03:15:00', '2026-05-30 03:15:00'),
(38, 'Metotrexato', 0, 1, 0.22, 24.00, '2026-05-30 03:16:05', '2026-05-30 03:16:05'),
(39, 'Mesna', 0, 1, 1.00, 20.00, '2026-05-30 03:18:12', '2026-05-30 03:18:21'),
(40, 'Nab_paclitaxel', 0, 1, 2.00, 10.00, '2026-05-30 03:18:50', '2026-05-30 03:18:50'),
(41, 'Nivolumab', 0, 1, 1.00, 10.00, '2026-05-30 03:19:18', '2026-05-30 03:19:18'),
(42, 'Obinutuzumab', 0, 1, 0.40, 20.00, '2026-05-30 03:19:52', '2026-05-30 03:19:52'),
(43, 'Oxaliplatino', 0, 1, 0.20, 2.00, '2026-05-30 03:20:55', '2026-05-30 03:20:55'),
(44, 'Paclitaxel', 0, 1, 0.30, 1.20, '2026-05-30 03:21:32', '2026-05-30 03:21:32'),
(45, 'Pembrolizumab', 0, 1, 1.00, 10.00, '2026-05-30 03:22:03', '2026-05-30 03:22:03'),
(46, 'Ramucirumab', 0, 0, NULL, NULL, '2026-05-30 03:22:42', '2026-06-24 23:40:43'),
(47, 'Rituximab', 0, 1, 1.00, 4.00, '2026-05-30 03:23:08', '2026-05-30 03:23:08'),
(48, 'Traztuzumab', 0, 1, 1.00, 8.00, '2026-05-30 03:23:45', '2026-05-30 03:23:45'),
(49, 'Tocilizumab', 0, 1, 1.00, 8.00, '2026-05-30 03:24:10', '2026-05-30 03:24:10'),
(50, 'Vinblastina', 0, 1, 0.01, 0.20, '2026-05-30 03:24:59', '2026-05-30 03:24:59'),
(51, 'Epirrubicina', 0, 1, 0.50, 2.00, '2026-05-30 03:35:04', '2026-05-30 03:37:18'),
(52, 'Enfortumab vedotin', 0, 1, 0.30, 4.00, '2026-05-30 03:36:54', '2026-05-30 03:36:54'),
(53, 'Fluorouracilo', 1, 1, 1.00, 10.00, '2026-05-30 03:38:33', '2026-05-30 03:38:33'),
(54, 'Hidrocortisona', 0, 1, NULL, NULL, '2026-05-30 03:40:08', '2026-05-30 03:40:08'),
(55, 'Irinotecan', 0, 1, 0.40, 2.80, '2026-05-30 03:41:08', '2026-05-30 03:41:08'),
(56, 'Isatuximab', 0, 1, NULL, NULL, '2026-05-30 03:41:50', '2026-05-30 03:41:50'),
(57, 'Ocrelizumab', 0, 1, 0.00, 1.20, '2026-05-30 03:42:38', '2026-05-30 03:42:38'),
(58, 'Panitumumab', 0, 1, NULL, NULL, '2026-05-30 03:43:05', '2026-05-30 03:43:05'),
(59, 'Pegaspargasa', 0, 1, 0.00, 0.10, '2026-05-30 03:44:11', '2026-05-30 03:44:52'),
(60, 'Polatuzumab', 0, 1, 0.72, 2.70, '2026-05-30 03:46:02', '2026-05-30 03:46:02'),
(61, 'Ramucirumab', 0, 1, 0.40, 4.00, '2026-05-30 03:46:50', '2026-05-30 03:46:50'),
(62, 'Trabectedina', 0, 1, NULL, NULL, '2026-05-30 03:47:52', '2026-05-30 03:47:52'),
(63, 'Trabectedina', 0, 0, 5.00, 6.00, '2026-06-02 07:27:52', '2026-06-02 07:28:07'),
(64, 'Pemetrexed', 0, 1, 25.00, 25.00, '2026-06-19 01:19:56', '2026-06-19 01:19:56'),
(65, 'Vincristina', 0, 1, 0.01, 2.00, '2026-06-25 15:41:39', '2026-06-25 15:41:39'),
(66, 'Pertuzumab', 0, 1, 1.59, 3.00, '2026-06-25 15:42:54', '2026-06-25 15:43:36'),
(67, 'Palonosetron', 0, 1, NULL, NULL, '2026-06-25 15:44:30', '2026-06-25 15:44:30'),
(68, 'Pertuzumab/Trastuzumab', 0, 1, 1.00, 1.00, '2026-06-25 16:21:07', '2026-06-25 16:21:07'),
(69, 'Ipilumumab', 0, 1, 1.00, 4.00, '2026-06-25 19:33:43', '2026-06-25 19:33:43'),
(70, 'Sol. estabilizadora para Blinatumumab', 0, 1, NULL, NULL, '2026-07-03 16:39:28', '2026-07-03 16:39:28'),
(71, 'Mitomicina', 0, 1, NULL, NULL, '2026-07-21 16:36:04', '2026-07-21 16:36:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicine_batches`
--

CREATE TABLE `medicine_batches` (
  `id` bigint UNSIGNED NOT NULL,
  `laboratory_id` bigint UNSIGNED NOT NULL,
  `medicine_presentation_id` bigint UNSIGNED NOT NULL,
  `lote` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `caducidad` date NOT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `stock_inicial` int NOT NULL DEFAULT '0',
  `stock_actual` int NOT NULL DEFAULT '0',
  `stock_reservado` int NOT NULL DEFAULT '0',
  `costo_unitario` decimal(12,4) DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medicine_batches`
--

INSERT INTO `medicine_batches` (`id`, `laboratory_id`, `medicine_presentation_id`, `lote`, `caducidad`, `fecha_ingreso`, `stock_inicial`, `stock_actual`, `stock_reservado`, `costo_unitario`, `is_current`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'C25T119', '2027-10-31', '2026-02-05', 59, 40, 0, NULL, 1, 1, '2026-05-20 23:04:04', '2026-07-14 14:38:20'),
(2, 1, 9, '250358', '2028-07-31', '2026-05-28', 8, 5, 0, NULL, 1, 1, '2026-05-29 05:40:25', '2026-06-04 19:50:36'),
(3, 1, 10, '250177', '2027-05-31', '2026-05-28', 6, 2, 0, NULL, 1, 1, '2026-05-29 05:41:02', '2026-06-04 19:50:36'),
(4, 1, 11, '1178679', '2026-06-26', '2026-06-04', 6, 6, 0, NULL, 1, 1, '2026-05-29 05:41:39', '2026-06-04 19:33:00'),
(5, 1, 44, '0260048A', '2027-08-09', '2026-06-24', 50, 48, 0, NULL, 1, 1, '2026-06-24 23:22:14', '2026-06-24 23:58:30'),
(6, 1, 2, '2400176A', '2027-07-31', '2025-02-06', 15, 15, 0, NULL, 1, 1, '2026-07-12 16:05:22', '2026-07-12 16:05:22'),
(7, 1, 4, '5FE018A', '2027-06-30', '2026-07-12', 2, 2, 0, NULL, 1, 1, '2026-07-12 16:14:02', '2026-07-12 16:14:02'),
(8, 1, 19, '4JE018A', '2026-09-30', '2026-07-12', 1, 1, 0, NULL, 1, 1, '2026-07-12 16:21:07', '2026-07-12 16:21:07'),
(9, 1, 19, '5LE025A', '2027-11-30', '2026-07-12', 9, 9, 0, NULL, 1, 1, '2026-07-12 16:22:06', '2026-07-12 16:22:06'),
(10, 1, 24, 'M2600369', '2027-12-31', '0026-03-26', 15, 15, 0, NULL, 1, 1, '2026-07-12 16:32:59', '2026-07-12 16:32:59'),
(11, 1, 23, 'M2508644', '2027-09-30', '2026-02-11', 30, 30, 0, NULL, 1, 1, '2026-07-12 16:34:49', '2026-07-12 16:34:49'),
(12, 1, 23, 'M2409197', '2026-09-30', '2026-06-30', 5, 5, 0, NULL, 1, 1, '2026-07-12 16:36:43', '2026-07-12 16:36:43'),
(13, 1, 23, 'M2503778', '2027-03-31', '2026-07-12', 17, 17, 0, NULL, 1, 1, '2026-07-12 16:37:45', '2026-07-12 16:37:45'),
(14, 1, 59, '06999', '2028-01-31', '2026-06-25', 50, 48, 0, NULL, 1, 1, '2026-07-12 16:39:58', '2026-07-24 11:59:37'),
(15, 1, 61, '07111', '2028-03-31', '2026-06-25', 30, 30, 0, NULL, 1, 1, '2026-07-12 16:42:16', '2026-07-12 16:42:16'),
(16, 1, 61, '06809', '2027-10-31', '2026-07-12', 20, 18, 0, NULL, 1, 1, '2026-07-12 16:45:19', '2026-08-01 15:13:50'),
(17, 1, 34, '0250233A', '2027-06-13', '2026-07-12', 2, 2, 0, NULL, 1, 1, '2026-07-12 16:58:21', '2026-07-12 16:58:21'),
(18, 1, 32, 'P260025', '2028-12-31', '2026-03-26', 53, 53, 0, NULL, 1, 1, '2026-07-12 17:02:17', '2026-07-12 17:02:17'),
(19, 1, 38, '06265', '2027-03-31', '2025-07-25', 30, 30, 0, NULL, 1, 1, '2026-07-12 17:13:52', '2026-07-12 17:13:52'),
(20, 1, 42, '1L24130', '2026-09-30', '2026-07-12', 41, 41, 0, NULL, 1, 1, '2026-07-12 17:23:21', '2026-07-12 17:23:21'),
(21, 1, 80, 'M2502307', '2027-02-28', '2027-02-28', 30, 28, 0, NULL, 1, 1, '2026-07-12 17:33:36', '2026-07-21 17:03:14'),
(22, 1, 145, 'Z25N002', '2027-05-01', '2026-04-23', 50, 50, 0, NULL, 1, 1, '2026-07-12 17:52:41', '2026-07-12 17:52:41'),
(23, 1, 145, 'Z24N007', '2026-10-01', '2026-04-23', 50, 50, 0, NULL, 1, 1, '2026-07-12 17:53:56', '2026-07-12 17:53:56'),
(24, 1, 142, '06324', '2027-04-01', '0026-01-22', 50, 50, 0, NULL, 1, 1, '2026-07-12 18:07:29', '2026-07-12 18:07:29'),
(25, 1, 117, 'M2600433', '2027-09-30', '2026-07-12', 55, 55, 0, NULL, 1, 1, '2026-07-12 18:19:18', '2026-07-12 18:19:18'),
(26, 1, 115, '1L24192', '2025-12-31', '2026-03-18', 10, 10, 0, NULL, 1, 1, '2026-07-12 18:23:15', '2026-07-12 18:23:15'),
(27, 1, 90, 'M2504717', '2027-04-30', '2026-06-30', 30, 30, 0, NULL, 1, 1, '2026-07-12 18:28:42', '2026-07-12 18:28:42'),
(28, 1, 101, '1L25315', '2027-11-30', '2026-03-18', 30, 30, 0, NULL, 1, 1, '2026-07-12 18:31:11', '2026-07-12 18:31:11'),
(29, 1, 53, '6BE007A', '2028-02-29', '2026-06-19', 2, 2, 0, NULL, 1, 1, '2026-07-12 18:53:11', '2026-07-12 18:53:11'),
(30, 1, 37, '1L25072', '2027-04-30', '2025-08-10', 57, 56, 0, NULL, 1, 1, '2026-07-13 15:00:10', '2026-07-24 11:59:13'),
(31, 1, 46, '1L25239', '2027-10-31', '2026-07-13', 11, 11, 0, NULL, 1, 1, '2026-07-13 15:07:23', '2026-07-13 15:07:23'),
(32, 1, 56, '2L25037', '2027-02-28', '2026-07-13', 27, 27, 0, NULL, 1, 1, '2026-07-13 15:24:33', '2026-07-13 15:24:33'),
(33, 1, 64, '06497', '2027-06-30', '2026-07-13', 138, 138, 0, NULL, 1, 1, '2026-07-13 21:19:21', '2026-07-13 21:19:21'),
(34, 1, 68, '1L25110', '2027-06-30', '2026-07-13', 98, 90, 0, NULL, 1, 1, '2026-07-13 21:25:26', '2026-07-30 12:05:06'),
(35, 1, 67, 'M2600334', '2027-06-30', '2026-07-13', 127, 127, 0, NULL, 1, 1, '2026-07-13 21:27:22', '2026-07-13 21:27:22'),
(36, 1, 151, '4JE001A', '2026-09-30', '2026-07-13', 25, 25, 0, NULL, 1, 1, '2026-07-13 21:32:11', '2026-07-13 21:32:11'),
(37, 1, 144, 'M2503636', '2027-03-31', '2026-02-16', 15, 15, 0, NULL, 1, 1, '2026-07-14 13:27:44', '2026-07-14 13:27:44'),
(38, 1, 121, 'B24G791', '2026-08-31', '2026-07-14', 42, 42, 0, NULL, 1, 1, '2026-07-14 13:33:36', '2026-07-14 13:33:36'),
(39, 1, 121, 'B25J616', '2027-06-30', '2026-07-14', 35, 35, 0, NULL, 1, 1, '2026-07-14 13:34:48', '2026-07-14 13:34:48'),
(40, 1, 108, '5AE037A', '2027-01-31', '2026-07-14', 2, 2, 0, NULL, 1, 1, '2026-07-14 13:46:49', '2026-07-14 13:46:49'),
(41, 1, 105, '06527', '2027-07-31', '2026-02-13', 41, 37, 0, NULL, 1, 1, '2026-07-14 13:58:13', '2026-07-24 16:15:46'),
(42, 1, 106, '06212', '2027-02-28', '2026-02-13', 44, 43, 0, NULL, 1, 1, '2026-07-14 14:00:21', '2026-07-24 16:15:46'),
(43, 1, 101, '1L25148', '2027-07-31', '2025-08-10', 4, 4, 0, NULL, 1, 1, '2026-07-14 14:19:37', '2026-07-14 14:19:37'),
(44, 1, 36, '6EE002A', '2028-05-31', '2026-07-02', 14, 14, 0, NULL, 1, 1, '2026-07-14 14:22:44', '2026-07-14 14:24:53'),
(45, 1, 7, '25CD007', '2027-02-20', '2026-01-12', 31, 31, 0, NULL, 1, 1, '2026-07-14 14:33:21', '2026-07-14 14:33:21'),
(46, 1, 1, 'C25T120', '2027-10-31', '2026-05-15', 50, 50, 0, NULL, 1, 1, '2026-07-14 14:36:59', '2026-07-14 14:36:59'),
(47, 1, 49, 'P2502488', '2027-02-28', '2026-01-16', 38, 36, 0, NULL, 1, 1, '2026-07-14 14:48:00', '2026-07-24 11:56:46'),
(48, 1, 5, '06394', '2027-05-31', '2025-09-22', 18, 18, 0, NULL, 1, 1, '2026-07-15 14:15:30', '2026-07-15 14:15:30'),
(49, 1, 13, 'F225G034', '2027-06-30', '2026-05-14', 6, 6, 0, NULL, 1, 1, '2026-07-15 14:25:50', '2026-07-15 14:27:44'),
(50, 1, 124, '1L25006', '2027-01-31', '2025-09-22', 24, 24, 0, NULL, 1, 1, '2026-07-15 14:33:00', '2026-07-15 14:33:00'),
(51, 1, 6, '1205315', '2028-02-29', '2026-07-09', 3, 3, 0, NULL, 1, 1, '2026-07-15 14:38:22', '2026-07-15 14:38:22'),
(52, 1, 39, 'QES0F01', '2027-04-30', '2026-06-11', 3, 3, 0, NULL, 1, 1, '2026-07-15 14:41:55', '2026-07-15 14:42:53'),
(53, 1, 40, 'QCS5M13', '2028-02-29', '2026-06-11', 2, 2, 0, NULL, 1, 1, '2026-07-15 14:44:27', '2026-07-15 14:44:27'),
(54, 1, 51, '4K070AB', '2027-10-31', '2026-05-06', 1, 1, 0, NULL, 1, 1, '2026-07-15 14:52:43', '2026-07-15 14:52:43'),
(55, 1, 112, 'ACY9355', '2027-11-30', '2026-01-27', 1, 1, 0, NULL, 1, 1, '2026-07-15 14:57:00', '2026-07-15 14:57:00'),
(56, 1, 112, 'ACY5607', '2027-07-30', '2026-05-07', 4, 4, 0, NULL, 1, 1, '2026-07-15 14:58:04', '2026-07-15 14:59:23'),
(57, 1, 113, 'ACV0038', '2027-04-30', '2025-12-12', 1, 1, 0, NULL, 1, 1, '2026-07-15 15:05:54', '2026-07-15 15:05:54'),
(58, 1, 113, 'ACT5591', '2027-04-30', '2025-12-17', 1, 1, 0, NULL, 1, 1, '2026-07-15 15:07:23', '2026-07-15 15:07:23'),
(59, 1, 113, 'ACY5616', '2027-04-30', '2025-12-30', 1, 1, 0, NULL, 1, 1, '2026-07-15 15:08:54', '2026-07-15 15:08:54'),
(60, 1, 35, 'A002642', '2027-08-26', '2026-07-09', 2, 2, 0, NULL, 1, 1, '2026-07-15 16:03:00', '2026-07-15 16:03:00'),
(61, 1, 111, 'H0262B29', '2027-06-12', '2026-07-15', 1, 1, 0, NULL, 1, 1, '2026-07-15 16:08:53', '2026-07-15 16:08:53'),
(62, 1, 153, '0240258A', '2026-08-05', '2026-07-16', 7, 7, 0, NULL, 1, 1, '2026-07-16 14:57:34', '2026-07-16 14:57:34'),
(63, 1, 154, 'X5KCI074A', '2027-10-31', '2026-07-16', 25, 25, 0, NULL, 1, 1, '2026-07-16 15:07:00', '2026-07-16 15:07:00'),
(64, 1, 155, '1L25292', '2027-11-30', '2026-07-16', 30, 30, 0, NULL, 1, 1, '2026-07-16 15:22:50', '2026-07-16 15:22:50'),
(65, 1, 118, '5FE021A', '2027-06-30', '2026-07-16', 16, 16, 0, NULL, 1, 1, '2026-07-16 15:35:53', '2026-07-16 15:35:53'),
(66, 1, 28, 'G029MN', '2028-03-31', '2024-09-18', 9, 9, 0, NULL, 1, 1, '2026-07-16 15:52:15', '2026-07-16 15:52:15'),
(67, 1, 28, 'G02FS9', '2028-07-31', '2026-07-16', 50, 50, 0, NULL, 1, 1, '2026-07-16 15:53:31', '2026-07-16 15:53:31'),
(68, 1, 156, 'MT8951', '2027-06-30', '2026-05-08', 11, 9, 0, NULL, 1, 1, '2026-07-16 16:03:08', '2026-08-01 15:53:49'),
(69, 1, 157, 'MC1299', '2026-11-30', '2026-05-06', 17, 16, 0, NULL, 1, 1, '2026-07-16 16:04:25', '2026-08-01 15:53:49'),
(70, 1, 126, 'H8048B09', '2027-06-14', '2026-07-20', 1, 0, 0, NULL, 1, 0, '2026-07-20 15:45:42', '2026-07-21 16:58:57'),
(71, 1, 110, 'H7953B24', '2027-04-04', '2026-07-20', 2, 2, 0, NULL, 1, 1, '2026-07-20 15:48:09', '2026-07-20 15:48:09'),
(72, 1, 88, '4238Q', '2027-09-28', '2026-07-20', 2, 2, 0, NULL, 1, 1, '2026-07-20 15:49:11', '2026-07-20 15:49:12'),
(73, 1, 58, 'B3568', '2027-01-17', '2026-07-20', 1, 1, 0, NULL, 1, 1, '2026-07-20 15:50:39', '2026-07-20 15:50:39'),
(74, 1, 89, '1189134', '2027-12-31', '2026-07-20', 4, 4, 0, NULL, 1, 1, '2026-07-20 15:51:18', '2026-07-20 15:51:18'),
(75, 1, 55, 'B2824B20', '2026-07-19', '2026-07-20', 1, 1, 0, NULL, 1, 1, '2026-07-20 15:52:59', '2026-07-20 15:52:59'),
(76, 1, 55, 'B3608B25', '2026-12-12', '2026-07-20', 1, 1, 0, NULL, 1, 1, '2026-07-20 15:53:57', '2026-07-20 15:53:57'),
(77, 1, 55, 'B3611B12', '2027-01-13', '2026-07-20', 1, 1, 0, NULL, 1, 1, '2026-07-20 15:54:26', '2026-07-20 15:54:26'),
(78, 1, 71, 'B4025B07', '2027-08-18', '2026-07-20', 3, 3, 0, NULL, 1, 1, '2026-07-20 15:55:21', '2026-07-20 15:55:21'),
(79, 1, 158, '2L25130', '2027-06-30', '2026-07-20', 2, 2, 0, NULL, 1, 1, '2026-07-20 18:31:16', '2026-07-20 18:31:16'),
(80, 1, 159, '06159', '2027-01-31', '2026-07-20', 40, 40, 0, NULL, 1, 1, '2026-07-20 19:11:44', '2026-07-20 19:11:44'),
(81, 1, 160, '5B3C233', '2030-06-30', '2026-07-20', 4, 4, 0, NULL, 1, 1, '2026-07-20 19:32:21', '2026-07-20 19:32:21'),
(82, 1, 161, 'MW4564', '2029-01-31', '2026-07-20', 2, 1, 0, NULL, 1, 1, '2026-07-20 20:18:23', '2026-07-21 22:27:45'),
(83, 1, 162, 'EHV0206', '2026-08-31', '2026-07-20', 4, 4, 0, NULL, 1, 1, '2026-07-20 20:27:18', '2026-07-20 20:27:18'),
(84, 1, 21, '12911421', '2028-12-31', '2026-07-20', 5, 5, 0, NULL, 1, 1, '2026-07-20 20:30:43', '2026-07-20 20:30:43'),
(85, 1, 163, '3F226A', '2026-08-31', '2026-07-20', 2, 2, 0, NULL, 1, 1, '2026-07-20 20:37:14', '2026-07-20 20:37:14'),
(86, 1, 164, '5FE021A.', '2027-06-30', '2026-07-22', 5, 5, 0, NULL, 1, 1, '2026-07-22 16:02:26', '2026-07-22 16:02:26'),
(87, 1, 126, '7613326005760', '2027-03-31', '2026-08-01', 2, 2, 0, NULL, 1, 1, '2026-08-01 16:05:44', '2026-08-01 16:05:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicine_batch_movements`
--

CREATE TABLE `medicine_batch_movements` (
  `id` bigint UNSIGNED NOT NULL,
  `medicine_batch_id` bigint UNSIGNED NOT NULL,
  `laboratory_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `movement_type` enum('entrada','salida','ajuste_positivo','ajuste_negativo','reserva','liberacion_reserva','merma','cancelacion_salida') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `stock_actual_before` int NOT NULL DEFAULT '0',
  `stock_actual_after` int NOT NULL DEFAULT '0',
  `stock_reservado_before` int NOT NULL DEFAULT '0',
  `stock_reservado_after` int NOT NULL DEFAULT '0',
  `reference_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint UNSIGNED DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medicine_batch_movements`
--

INSERT INTO `medicine_batch_movements` (`id`, `medicine_batch_id`, `laboratory_id`, `user_id`, `movement_type`, `quantity`, `stock_actual_before`, `stock_actual_after`, `stock_reservado_before`, `stock_reservado_after`, `reference_type`, `reference_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 4, 'entrada', 8, 0, 8, 0, 0, 'IngresoInventarioOncologico', 2, 'Ingreso de inventario oncológico', '2026-05-29 05:40:25', '2026-05-29 05:40:25'),
(2, 3, 1, 4, 'entrada', 6, 0, 6, 0, 0, 'IngresoInventarioOncologico', 3, 'Ingreso de inventario oncológico', '2026-05-29 05:41:02', '2026-05-29 05:41:02'),
(3, 4, 1, 4, 'entrada', 1, 0, 1, 0, 0, 'IngresoInventarioOncologico', 4, 'Ingreso de inventario oncológico', '2026-05-29 05:41:39', '2026-05-29 05:41:39'),
(4, 3, 1, 4, 'salida', 2, 6, 4, 0, 0, 'mezcla', 1, 'Consumo de inventario por edición de mezcla.', '2026-05-29 05:45:07', '2026-05-29 05:45:07'),
(5, 2, 1, 4, 'salida', 2, 8, 6, 0, 0, 'mezcla', 1, 'Consumo de inventario por edición de mezcla.', '2026-05-29 05:45:07', '2026-05-29 05:45:07'),
(6, 3, 1, 4, 'cancelacion_salida', 2, 4, 6, 0, 0, 'mezcla', 1, 'Devolución de inventario por edición de mezcla.', '2026-05-29 05:56:10', '2026-05-29 05:56:10'),
(7, 2, 1, 4, 'cancelacion_salida', 2, 6, 8, 0, 0, 'mezcla', 1, 'Devolución de inventario por edición de mezcla.', '2026-05-29 05:56:10', '2026-05-29 05:56:10'),
(8, 3, 1, 4, 'salida', 2, 6, 4, 0, 0, 'mezcla', 1, 'Consumo de inventario por edición de mezcla.', '2026-05-29 05:56:10', '2026-05-29 05:56:10'),
(9, 2, 1, 4, 'salida', 2, 8, 6, 0, 0, 'mezcla', 1, 'Consumo de inventario por edición de mezcla.', '2026-05-29 05:56:10', '2026-05-29 05:56:10'),
(10, 3, 1, 4, 'cancelacion_salida', 2, 4, 6, 0, 0, 'mezcla', 1, 'Devolución de inventario por edición de mezcla.', '2026-05-29 06:02:35', '2026-05-29 06:02:35'),
(11, 2, 1, 4, 'cancelacion_salida', 2, 6, 8, 0, 0, 'mezcla', 1, 'Devolución de inventario por edición de mezcla.', '2026-05-29 06:02:35', '2026-05-29 06:02:35'),
(12, 3, 1, 4, 'salida', 2, 6, 4, 0, 0, 'mezcla', 1, 'Consumo de inventario por edición de mezcla.', '2026-05-29 06:02:35', '2026-05-29 06:02:35'),
(13, 2, 1, 4, 'salida', 2, 8, 6, 0, 0, 'mezcla', 1, 'Consumo de inventario por edición de mezcla.', '2026-05-29 06:02:35', '2026-05-29 06:02:35'),
(14, 4, 1, 4, 'entrada', 5, 1, 6, 0, 0, 'IngresoInventarioOncologico', 4, 'Ingreso de inventario oncológico', '2026-06-04 19:33:00', '2026-06-04 19:33:00'),
(15, 3, 1, 4, 'salida', 2, 4, 2, 0, 0, 'mezcla', 2, 'Consumo de inventario por edición de mezcla.', '2026-06-04 19:50:36', '2026-06-04 19:50:36'),
(16, 2, 1, 4, 'salida', 1, 6, 5, 0, 0, 'mezcla', 2, 'Consumo de inventario por edición de mezcla.', '2026-06-04 19:50:36', '2026-06-04 19:50:36'),
(17, 5, 1, 4, 'entrada', 50, 0, 50, 0, 0, 'IngresoInventarioOncologico', 5, 'Ingreso de inventario oncológico', '2026-06-24 23:22:14', '2026-06-24 23:22:14'),
(18, 5, 1, 4, 'salida', 2, 50, 48, 0, 0, 'mezcla', 3, 'Consumo de inventario por edición de mezcla.', '2026-06-24 23:58:30', '2026-06-24 23:58:30'),
(19, 6, 1, 8, 'entrada', 15, 0, 15, 0, 0, 'IngresoInventarioOncologico', 6, 'Ingreso de inventario oncológico', '2026-07-12 16:05:22', '2026-07-12 16:05:22'),
(20, 7, 1, 8, 'entrada', 2, 0, 2, 0, 0, 'IngresoInventarioOncologico', 7, 'Ingreso de inventario oncológico', '2026-07-12 16:14:02', '2026-07-12 16:14:02'),
(21, 8, 1, 8, 'entrada', 1, 0, 1, 0, 0, 'IngresoInventarioOncologico', 8, 'Ingreso de inventario oncológico', '2026-07-12 16:21:07', '2026-07-12 16:21:07'),
(22, 9, 1, 8, 'entrada', 9, 0, 9, 0, 0, 'IngresoInventarioOncologico', 9, 'Ingreso de inventario oncológico', '2026-07-12 16:22:06', '2026-07-12 16:22:06'),
(23, 10, 1, 8, 'entrada', 15, 0, 15, 0, 0, 'IngresoInventarioOncologico', 10, 'Ingreso de inventario oncológico', '2026-07-12 16:32:59', '2026-07-12 16:32:59'),
(24, 11, 1, 8, 'entrada', 30, 0, 30, 0, 0, 'IngresoInventarioOncologico', 11, 'Ingreso de inventario oncológico', '2026-07-12 16:34:49', '2026-07-12 16:34:49'),
(25, 12, 1, 8, 'entrada', 5, 0, 5, 0, 0, 'IngresoInventarioOncologico', 12, 'Ingreso de inventario oncológico', '2026-07-12 16:36:43', '2026-07-12 16:36:43'),
(26, 13, 1, 8, 'entrada', 17, 0, 17, 0, 0, 'IngresoInventarioOncologico', 13, 'Ingreso de inventario oncológico', '2026-07-12 16:37:45', '2026-07-12 16:37:45'),
(27, 14, 1, 8, 'entrada', 50, 0, 50, 0, 0, 'IngresoInventarioOncologico', 14, 'Ingreso de inventario oncológico', '2026-07-12 16:39:58', '2026-07-12 16:39:58'),
(28, 15, 1, 8, 'entrada', 30, 0, 30, 0, 0, 'IngresoInventarioOncologico', 15, 'Ingreso de inventario oncológico', '2026-07-12 16:42:16', '2026-07-12 16:42:16'),
(29, 16, 1, 8, 'entrada', 20, 0, 20, 0, 0, 'IngresoInventarioOncologico', 16, 'Ingreso de inventario oncológico', '2026-07-12 16:45:19', '2026-07-12 16:45:19'),
(30, 17, 1, 8, 'entrada', 2, 0, 2, 0, 0, 'IngresoInventarioOncologico', 17, 'Ingreso de inventario oncológico', '2026-07-12 16:58:21', '2026-07-12 16:58:21'),
(31, 18, 1, 8, 'entrada', 53, 0, 53, 0, 0, 'IngresoInventarioOncologico', 18, 'Ingreso de inventario oncológico', '2026-07-12 17:02:17', '2026-07-12 17:02:17'),
(32, 19, 1, 8, 'entrada', 30, 0, 30, 0, 0, 'IngresoInventarioOncologico', 19, 'Ingreso de inventario oncológico', '2026-07-12 17:13:52', '2026-07-12 17:13:52'),
(33, 20, 1, 8, 'entrada', 41, 0, 41, 0, 0, 'IngresoInventarioOncologico', 20, 'Ingreso de inventario oncológico', '2026-07-12 17:23:21', '2026-07-12 17:23:21'),
(34, 21, 1, 8, 'entrada', 30, 0, 30, 0, 0, 'IngresoInventarioOncologico', 21, 'Ingreso de inventario oncológico', '2026-07-12 17:33:36', '2026-07-12 17:33:36'),
(35, 22, 1, 8, 'entrada', 50, 0, 50, 0, 0, 'IngresoInventarioOncologico', 22, 'Ingreso de inventario oncológico', '2026-07-12 17:52:41', '2026-07-12 17:52:41'),
(36, 23, 1, 8, 'entrada', 50, 0, 50, 0, 0, 'IngresoInventarioOncologico', 23, 'Ingreso de inventario oncológico', '2026-07-12 17:53:56', '2026-07-12 17:53:56'),
(37, 24, 1, 8, 'entrada', 50, 0, 50, 0, 0, 'IngresoInventarioOncologico', 24, 'Ingreso de inventario oncológico', '2026-07-12 18:07:29', '2026-07-12 18:07:29'),
(38, 25, 1, 8, 'entrada', 55, 0, 55, 0, 0, 'IngresoInventarioOncologico', 25, 'Ingreso de inventario oncológico', '2026-07-12 18:19:18', '2026-07-12 18:19:18'),
(39, 26, 1, 8, 'entrada', 10, 0, 10, 0, 0, 'IngresoInventarioOncologico', 26, 'Ingreso de inventario oncológico', '2026-07-12 18:23:15', '2026-07-12 18:23:15'),
(40, 27, 1, 8, 'entrada', 30, 0, 30, 0, 0, 'IngresoInventarioOncologico', 27, 'Ingreso de inventario oncológico', '2026-07-12 18:28:42', '2026-07-12 18:28:42'),
(41, 28, 1, 8, 'entrada', 30, 0, 30, 0, 0, 'IngresoInventarioOncologico', 28, 'Ingreso de inventario oncológico', '2026-07-12 18:31:11', '2026-07-12 18:31:11'),
(42, 29, 1, 8, 'entrada', 2, 0, 2, 0, 0, 'IngresoInventarioOncologico', 29, 'Ingreso de inventario oncológico', '2026-07-12 18:53:11', '2026-07-12 18:53:11'),
(43, 30, 1, 8, 'entrada', 17, 0, 17, 0, 0, 'IngresoInventarioOncologico', 30, 'Ingreso de inventario oncológico', '2026-07-13 15:00:10', '2026-07-13 15:00:10'),
(44, 30, 1, 8, 'entrada', 40, 17, 57, 0, 0, 'IngresoInventarioOncologico', 30, 'Ingreso de inventario oncológico', '2026-07-13 15:01:25', '2026-07-13 15:01:25'),
(45, 31, 1, 8, 'entrada', 11, 0, 11, 0, 0, 'IngresoInventarioOncologico', 31, 'Ingreso de inventario oncológico', '2026-07-13 15:07:23', '2026-07-13 15:07:23'),
(46, 32, 1, 8, 'entrada', 27, 0, 27, 0, 0, 'IngresoInventarioOncologico', 32, 'Ingreso de inventario oncológico', '2026-07-13 15:24:33', '2026-07-13 15:24:33'),
(47, 33, 1, 8, 'entrada', 138, 0, 138, 0, 0, 'IngresoInventarioOncologico', 33, 'Ingreso de inventario oncológico', '2026-07-13 21:19:21', '2026-07-13 21:19:21'),
(48, 34, 1, 8, 'entrada', 98, 0, 98, 0, 0, 'IngresoInventarioOncologico', 34, 'Ingreso de inventario oncológico', '2026-07-13 21:25:26', '2026-07-13 21:25:26'),
(49, 35, 1, 8, 'entrada', 127, 0, 127, 0, 0, 'IngresoInventarioOncologico', 35, 'Ingreso de inventario oncológico', '2026-07-13 21:27:22', '2026-07-13 21:27:22'),
(50, 36, 1, 8, 'entrada', 25, 0, 25, 0, 0, 'IngresoInventarioOncologico', 36, 'Ingreso de inventario oncológico', '2026-07-13 21:32:11', '2026-07-13 21:32:11'),
(51, 37, 1, 8, 'entrada', 15, 0, 15, 0, 0, 'IngresoInventarioOncologico', 37, 'Ingreso de inventario oncológico', '2026-07-14 13:27:44', '2026-07-14 13:27:44'),
(52, 38, 1, 8, 'entrada', 42, 0, 42, 0, 0, 'IngresoInventarioOncologico', 38, 'Ingreso de inventario oncológico', '2026-07-14 13:33:36', '2026-07-14 13:33:36'),
(53, 39, 1, 8, 'entrada', 35, 0, 35, 0, 0, 'IngresoInventarioOncologico', 39, 'Ingreso de inventario oncológico', '2026-07-14 13:34:48', '2026-07-14 13:34:48'),
(54, 40, 1, 8, 'entrada', 2, 0, 2, 0, 0, 'IngresoInventarioOncologico', 40, 'Ingreso de inventario oncológico', '2026-07-14 13:46:49', '2026-07-14 13:46:49'),
(55, 41, 1, 8, 'entrada', 41, 0, 41, 0, 0, 'IngresoInventarioOncologico', 41, 'Ingreso de inventario oncológico', '2026-07-14 13:58:13', '2026-07-14 13:58:13'),
(56, 42, 1, 8, 'entrada', 44, 0, 44, 0, 0, 'IngresoInventarioOncologico', 42, 'Ingreso de inventario oncológico', '2026-07-14 14:00:21', '2026-07-14 14:00:21'),
(57, 43, 1, 8, 'entrada', 4, 0, 4, 0, 0, 'IngresoInventarioOncologico', 43, 'Ingreso de inventario oncológico', '2026-07-14 14:19:37', '2026-07-14 14:19:37'),
(58, 44, 1, 8, 'entrada', 1, 0, 1, 0, 0, 'IngresoInventarioOncologico', 44, 'Ingreso de inventario oncológico', '2026-07-14 14:22:44', '2026-07-14 14:22:44'),
(59, 44, 1, 8, 'entrada', 3, 1, 4, 0, 0, 'IngresoInventarioOncologico', 44, 'Ingreso de inventario oncológico', '2026-07-14 14:23:46', '2026-07-14 14:23:46'),
(60, 44, 1, 8, 'entrada', 10, 4, 14, 0, 0, 'IngresoInventarioOncologico', 44, 'Ingreso de inventario oncológico', '2026-07-14 14:24:53', '2026-07-14 14:24:53'),
(61, 45, 1, 8, 'entrada', 31, 0, 31, 0, 0, 'IngresoInventarioOncologico', 45, 'Ingreso de inventario oncológico', '2026-07-14 14:33:21', '2026-07-14 14:33:21'),
(62, 46, 1, 8, 'entrada', 50, 0, 50, 0, 0, 'IngresoInventarioOncologico', 46, 'Ingreso de inventario oncológico', '2026-07-14 14:36:59', '2026-07-14 14:36:59'),
(63, 1, 1, 8, 'entrada', 40, 0, 40, 0, 0, 'IngresoInventarioOncologico', 1, 'Ingreso de inventario oncológico', '2026-07-14 14:38:20', '2026-07-14 14:38:20'),
(64, 47, 1, 8, 'entrada', 38, 0, 38, 0, 0, 'IngresoInventarioOncologico', 47, 'Ingreso de inventario oncológico', '2026-07-14 14:48:00', '2026-07-14 14:48:00'),
(65, 48, 1, 8, 'entrada', 18, 0, 18, 0, 0, 'IngresoInventarioOncologico', 48, 'Ingreso de inventario oncológico', '2026-07-15 14:15:30', '2026-07-15 14:15:30'),
(66, 49, 1, 8, 'entrada', 2, 0, 2, 0, 0, 'IngresoInventarioOncologico', 49, 'Ingreso de inventario oncológico', '2026-07-15 14:25:50', '2026-07-15 14:25:50'),
(67, 49, 1, 8, 'entrada', 4, 2, 6, 0, 0, 'IngresoInventarioOncologico', 49, 'Ingreso de inventario oncológico', '2026-07-15 14:27:44', '2026-07-15 14:27:44'),
(68, 50, 1, 8, 'entrada', 24, 0, 24, 0, 0, 'IngresoInventarioOncologico', 50, 'Ingreso de inventario oncológico', '2026-07-15 14:33:00', '2026-07-15 14:33:00'),
(69, 51, 1, 8, 'entrada', 3, 0, 3, 0, 0, 'IngresoInventarioOncologico', 51, 'Ingreso de inventario oncológico', '2026-07-15 14:38:22', '2026-07-15 14:38:22'),
(70, 52, 1, 8, 'entrada', 1, 0, 1, 0, 0, 'IngresoInventarioOncologico', 52, 'Ingreso de inventario oncológico', '2026-07-15 14:41:55', '2026-07-15 14:41:55'),
(71, 52, 1, 8, 'entrada', 2, 1, 3, 0, 0, 'IngresoInventarioOncologico', 52, 'Ingreso de inventario oncológico', '2026-07-15 14:42:53', '2026-07-15 14:42:53'),
(72, 53, 1, 8, 'entrada', 2, 0, 2, 0, 0, 'IngresoInventarioOncologico', 53, 'Ingreso de inventario oncológico', '2026-07-15 14:44:27', '2026-07-15 14:44:27'),
(73, 54, 1, 8, 'entrada', 1, 0, 1, 0, 0, 'IngresoInventarioOncologico', 54, 'Ingreso de inventario oncológico', '2026-07-15 14:52:43', '2026-07-15 14:52:43'),
(74, 55, 1, 8, 'entrada', 1, 0, 1, 0, 0, 'IngresoInventarioOncologico', 55, 'Ingreso de inventario oncológico', '2026-07-15 14:57:00', '2026-07-15 14:57:00'),
(75, 56, 1, 8, 'entrada', 1, 0, 1, 0, 0, 'IngresoInventarioOncologico', 56, 'Ingreso de inventario oncológico', '2026-07-15 14:58:04', '2026-07-15 14:58:04'),
(76, 56, 1, 8, 'entrada', 3, 1, 4, 0, 0, 'IngresoInventarioOncologico', 56, 'Ingreso de inventario oncológico', '2026-07-15 14:59:23', '2026-07-15 14:59:23'),
(77, 57, 1, 8, 'entrada', 1, 0, 1, 0, 0, 'IngresoInventarioOncologico', 57, 'Ingreso de inventario oncológico', '2026-07-15 15:05:54', '2026-07-15 15:05:54'),
(78, 58, 1, 8, 'entrada', 1, 0, 1, 0, 0, 'IngresoInventarioOncologico', 58, 'Ingreso de inventario oncológico', '2026-07-15 15:07:23', '2026-07-15 15:07:23'),
(79, 59, 1, 8, 'entrada', 1, 0, 1, 0, 0, 'IngresoInventarioOncologico', 59, 'Ingreso de inventario oncológico', '2026-07-15 15:08:54', '2026-07-15 15:08:54'),
(80, 60, 1, 8, 'entrada', 2, 0, 2, 0, 0, 'IngresoInventarioOncologico', 60, 'Ingreso de inventario oncológico', '2026-07-15 16:03:00', '2026-07-15 16:03:00'),
(81, 61, 1, 8, 'entrada', 1, 0, 1, 0, 0, 'IngresoInventarioOncologico', 61, 'Ingreso de inventario oncológico', '2026-07-15 16:08:53', '2026-07-15 16:08:53'),
(82, 62, 1, 8, 'entrada', 7, 0, 7, 0, 0, 'IngresoInventarioOncologico', 62, 'Ingreso de inventario oncológico', '2026-07-16 14:57:34', '2026-07-16 14:57:34'),
(83, 63, 1, 8, 'entrada', 25, 0, 25, 0, 0, 'IngresoInventarioOncologico', 63, 'Ingreso de inventario oncológico', '2026-07-16 15:07:00', '2026-07-16 15:07:00'),
(84, 64, 1, 8, 'entrada', 30, 0, 30, 0, 0, 'IngresoInventarioOncologico', 64, 'Ingreso de inventario oncológico', '2026-07-16 15:22:50', '2026-07-16 15:22:50'),
(85, 65, 1, 8, 'entrada', 16, 0, 16, 0, 0, 'IngresoInventarioOncologico', 65, 'Ingreso de inventario oncológico', '2026-07-16 15:35:53', '2026-07-16 15:35:53'),
(86, 66, 1, 8, 'entrada', 9, 0, 9, 0, 0, 'IngresoInventarioOncologico', 66, 'Ingreso de inventario oncológico', '2026-07-16 15:52:15', '2026-07-16 15:52:15'),
(87, 67, 1, 8, 'entrada', 50, 0, 50, 0, 0, 'IngresoInventarioOncologico', 67, 'Ingreso de inventario oncológico', '2026-07-16 15:53:31', '2026-07-16 15:53:31'),
(88, 68, 1, 8, 'entrada', 11, 0, 11, 0, 0, 'IngresoInventarioOncologico', 68, 'Ingreso de inventario oncológico', '2026-07-16 16:03:08', '2026-07-16 16:03:08'),
(89, 69, 1, 8, 'entrada', 2, 0, 2, 0, 0, 'IngresoInventarioOncologico', 69, 'Ingreso de inventario oncológico', '2026-07-16 16:04:25', '2026-07-16 16:04:25'),
(90, 69, 1, 8, 'entrada', 15, 2, 17, 0, 0, 'IngresoInventarioOncologico', 69, 'Ingreso de inventario oncológico', '2026-07-16 16:06:32', '2026-07-16 16:06:32'),
(91, 70, 1, 8, 'entrada', 1, 0, 1, 0, 0, 'IngresoInventarioOncologico', 70, 'Ingreso de inventario oncológico', '2026-07-20 15:45:42', '2026-07-20 15:45:42'),
(92, 71, 1, 8, 'entrada', 2, 0, 2, 0, 0, 'IngresoInventarioOncologico', 71, 'Ingreso de inventario oncológico', '2026-07-20 15:48:09', '2026-07-20 15:48:09'),
(93, 72, 1, 8, 'entrada', 1, 0, 1, 0, 0, 'IngresoInventarioOncologico', 72, 'Ingreso de inventario oncológico', '2026-07-20 15:49:11', '2026-07-20 15:49:11'),
(94, 72, 1, 8, 'entrada', 1, 1, 2, 0, 0, 'IngresoInventarioOncologico', 72, 'Ingreso de inventario oncológico', '2026-07-20 15:49:12', '2026-07-20 15:49:12'),
(95, 73, 1, 8, 'entrada', 1, 0, 1, 0, 0, 'IngresoInventarioOncologico', 73, 'Ingreso de inventario oncológico', '2026-07-20 15:50:39', '2026-07-20 15:50:39'),
(96, 74, 1, 8, 'entrada', 4, 0, 4, 0, 0, 'IngresoInventarioOncologico', 74, 'Ingreso de inventario oncológico', '2026-07-20 15:51:18', '2026-07-20 15:51:18'),
(97, 75, 1, 8, 'entrada', 1, 0, 1, 0, 0, 'IngresoInventarioOncologico', 75, 'Ingreso de inventario oncológico', '2026-07-20 15:52:59', '2026-07-20 15:52:59'),
(98, 76, 1, 8, 'entrada', 1, 0, 1, 0, 0, 'IngresoInventarioOncologico', 76, 'Ingreso de inventario oncológico', '2026-07-20 15:53:57', '2026-07-20 15:53:57'),
(99, 77, 1, 8, 'entrada', 1, 0, 1, 0, 0, 'IngresoInventarioOncologico', 77, 'Ingreso de inventario oncológico', '2026-07-20 15:54:26', '2026-07-20 15:54:26'),
(100, 78, 1, 8, 'entrada', 3, 0, 3, 0, 0, 'IngresoInventarioOncologico', 78, 'Ingreso de inventario oncológico', '2026-07-20 15:55:21', '2026-07-20 15:55:21'),
(101, 79, 1, 8, 'entrada', 2, 0, 2, 0, 0, 'IngresoInventarioOncologico', 79, 'Ingreso de inventario oncológico', '2026-07-20 18:31:16', '2026-07-20 18:31:16'),
(102, 80, 1, 8, 'entrada', 40, 0, 40, 0, 0, 'IngresoInventarioOncologico', 80, 'Ingreso de inventario oncológico', '2026-07-20 19:11:44', '2026-07-20 19:11:44'),
(103, 81, 1, 8, 'entrada', 4, 0, 4, 0, 0, 'IngresoInventarioOncologico', 81, 'Ingreso de inventario oncológico', '2026-07-20 19:32:21', '2026-07-20 19:32:21'),
(104, 82, 1, 8, 'entrada', 2, 0, 2, 0, 0, 'IngresoInventarioOncologico', 82, 'Ingreso de inventario oncológico', '2026-07-20 20:18:23', '2026-07-20 20:18:23'),
(105, 83, 1, 8, 'entrada', 4, 0, 4, 0, 0, 'IngresoInventarioOncologico', 83, 'Ingreso de inventario oncológico', '2026-07-20 20:27:18', '2026-07-20 20:27:18'),
(106, 84, 1, 8, 'entrada', 5, 0, 5, 0, 0, 'IngresoInventarioOncologico', 84, 'Ingreso de inventario oncológico', '2026-07-20 20:30:43', '2026-07-20 20:30:43'),
(107, 85, 1, 8, 'entrada', 2, 0, 2, 0, 0, 'IngresoInventarioOncologico', 85, 'Ingreso de inventario oncológico', '2026-07-20 20:37:14', '2026-07-20 20:37:14'),
(108, 70, 1, 4, 'salida', 1, 1, 0, 0, 0, 'mezcla', 4, 'Consumo de inventario por edición de mezcla.', '2026-07-21 16:58:57', '2026-07-21 16:58:57'),
(109, 21, 1, 4, 'salida', 2, 30, 28, 0, 0, 'mezcla', 6, 'Consumo de inventario por edición de mezcla.', '2026-07-21 17:03:14', '2026-07-21 17:03:14'),
(110, 41, 1, 4, 'salida', 2, 41, 39, 0, 0, 'mezcla', 8, 'Consumo de inventario por edición de mezcla.', '2026-07-21 18:05:20', '2026-07-21 18:05:20'),
(111, 82, 1, 4, 'salida', 1, 2, 1, 0, 0, 'mezcla', 5, 'Consumo de inventario por edición de mezcla.', '2026-07-21 22:27:45', '2026-07-21 22:27:45'),
(112, 86, 1, 8, 'entrada', 5, 0, 5, 0, 0, 'IngresoInventarioOncologico', 86, 'Ingreso de inventario oncológico', '2026-07-22 16:02:26', '2026-07-22 16:02:26'),
(113, 47, 1, 4, 'salida', 2, 38, 36, 0, 0, 'mezcla', 28, 'Consumo de inventario por edición de mezcla.', '2026-07-24 11:56:46', '2026-07-24 11:56:46'),
(114, 16, 1, 4, 'salida', 1, 20, 19, 0, 0, 'mezcla', 29, 'Consumo de inventario por edición de mezcla.', '2026-07-24 11:57:11', '2026-07-24 11:57:11'),
(115, 30, 1, 4, 'salida', 1, 57, 56, 0, 0, 'mezcla', 19, 'Consumo de inventario por edición de mezcla.', '2026-07-24 11:59:13', '2026-07-24 11:59:13'),
(116, 14, 1, 4, 'salida', 2, 50, 48, 0, 0, 'mezcla', 20, 'Consumo de inventario por edición de mezcla.', '2026-07-24 11:59:37', '2026-07-24 11:59:37'),
(117, 41, 1, 7, 'salida', 2, 39, 37, 0, 0, 'mezcla', 30, 'Consumo de inventario por edición de mezcla.', '2026-07-24 16:15:46', '2026-07-24 16:15:46'),
(118, 42, 1, 7, 'salida', 1, 44, 43, 0, 0, 'mezcla', 30, 'Consumo de inventario por edición de mezcla.', '2026-07-24 16:15:46', '2026-07-24 16:15:46'),
(119, 34, 1, 8, 'salida', 8, 98, 90, 0, 0, 'mezcla', 35, 'Consumo de inventario por edición de mezcla.', '2026-07-30 12:05:06', '2026-07-30 12:05:06'),
(120, 16, 1, 8, 'salida', 1, 19, 18, 0, 0, 'mezcla', 36, 'Consumo de inventario por edición de mezcla.', '2026-07-30 12:12:16', '2026-07-30 12:12:16'),
(121, 16, 1, 4, 'cancelacion_salida', 1, 18, 19, 0, 0, 'mezcla', 36, 'Devolución de inventario por edición de mezcla.', '2026-08-01 15:13:50', '2026-08-01 15:13:50'),
(122, 16, 1, 4, 'salida', 1, 19, 18, 0, 0, 'mezcla', 36, 'Consumo de inventario por edición de mezcla.', '2026-08-01 15:13:50', '2026-08-01 15:13:50'),
(123, 69, 1, 4, 'salida', 1, 17, 16, 0, 0, 'mezcla', 37, 'Consumo de inventario por edición de mezcla.', '2026-08-01 15:53:49', '2026-08-01 15:53:49'),
(124, 68, 1, 4, 'salida', 2, 11, 9, 0, 0, 'mezcla', 37, 'Consumo de inventario por edición de mezcla.', '2026-08-01 15:53:49', '2026-08-01 15:53:49'),
(125, 87, 1, 4, 'entrada', 2, 0, 2, 0, 0, 'IngresoInventarioOncologico', 87, 'Ingreso de inventario oncológico', '2026-08-01 16:05:44', '2026-08-01 16:05:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicine_laboratory_stocks`
--

CREATE TABLE `medicine_laboratory_stocks` (
  `id` bigint UNSIGNED NOT NULL,
  `nutrition_medicine_presentation_id` bigint UNSIGNED NOT NULL,
  `laboratory_id` bigint UNSIGNED NOT NULL,
  `frascos_iniciales` decimal(12,2) NOT NULL DEFAULT '0.00',
  `frascos_actuales` decimal(12,2) NOT NULL DEFAULT '0.00',
  `stock_ml_inicial` decimal(12,2) NOT NULL DEFAULT '0.00',
  `stock_ml_actual` decimal(12,2) NOT NULL DEFAULT '0.00',
  `lote` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `caducidad` date NOT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `numero_factura` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medicine_laboratory_stocks`
--

INSERT INTO `medicine_laboratory_stocks` (`id`, `nutrition_medicine_presentation_id`, `laboratory_id`, `frascos_iniciales`, `frascos_actuales`, `stock_ml_inicial`, `stock_ml_actual`, `lote`, `caducidad`, `fecha_ingreso`, `numero_factura`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 65.00, 65.00, 16250.00, 16250.00, 'LT24K2083', '2027-10-30', '2026-07-07', 'EF-11350', 1, '2026-07-07 16:12:02', '2026-07-07 16:12:02'),
(2, 16, 1, 13.00, 13.00, 1300.00, 1300.00, '16UE3845', '2026-11-30', '2026-07-07', '5252341748', 1, '2026-07-07 16:21:41', '2026-07-07 16:21:41'),
(3, 19, 1, 7.00, 7.00, 350.00, 350.00, '16TK2142', '2026-09-30', '2026-07-07', '5252344856', 1, '2026-07-07 16:59:18', '2026-07-07 16:59:18'),
(4, 19, 1, 18.00, 18.00, 900.00, 900.00, '16TU15018', '2027-08-30', '2026-07-07', '5252347805', 1, '2026-07-07 17:01:48', '2026-07-07 17:01:48'),
(5, 3, 1, 64.00, 64.00, 32000.00, 32000.00, 'C25T8012', '2027-10-30', '2026-07-07', 'FDS 51514', 1, '2026-07-07 18:46:16', '2026-07-07 18:46:16'),
(6, 34, 1, 46.00, 46.00, 23000.00, 23000.00, '73A5I0203', '2027-07-30', '2026-07-07', '5252349819', 1, '2026-07-07 18:48:31', '2026-07-07 18:48:31'),
(7, 6, 1, 55.00, 55.00, 27500.00, 27500.00, '16TI1849', '2026-09-30', '2026-07-07', 'na', 1, '2026-07-07 19:00:41', '2026-07-07 19:00:41'),
(8, 7, 1, 20.00, 20.00, 10000.00, 10000.00, '16UF4217', '2027-06-30', '2026-07-07', '5252347805', 1, '2026-07-07 19:03:42', '2026-07-07 19:03:42'),
(9, 1, 1, 22.00, 22.00, 11000.00, 11000.00, 'C25G018', '2027-08-30', '2026-07-07', 'FDS1526', 1, '2026-07-07 19:05:46', '2026-07-07 19:05:46'),
(10, 22, 1, 34.00, 34.00, 170.00, 170.00, 'B24D208', '2026-12-30', '2026-07-07', 'FC01535559', 1, '2026-07-07 19:07:35', '2026-07-07 19:07:35'),
(11, 22, 1, 212.00, 212.00, 1060.00, 1060.00, 'B25Y310', '2027-05-01', '2026-07-07', 'FC01535559', 1, '2026-07-07 19:09:10', '2026-07-07 19:09:10'),
(12, 22, 1, 99.00, 99.00, 495.00, 495.00, 'B25F203', '2027-02-28', '2026-07-07', 'FC01535559', 1, '2026-07-07 19:10:04', '2026-07-07 19:10:04'),
(13, 22, 1, 10.00, 10.00, 50.00, 50.00, 'B25U209', '2027-07-31', '2026-07-07', 'FC01535559', 1, '2026-07-07 19:10:44', '2026-07-07 19:10:44'),
(14, 22, 1, 24.00, 24.00, 120.00, 120.00, 'B25J212', '2027-06-30', '2026-07-07', 'FC01535559', 1, '2026-07-07 19:12:04', '2026-07-07 19:12:04'),
(15, 29, 1, 74.00, 74.00, 740.00, 740.00, 'B25J315', '2027-01-06', '2026-07-07', 'FCO1534826', 1, '2026-07-07 19:34:28', '2026-07-07 19:34:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicine_lists`
--

CREATE TABLE `medicine_lists` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `active_brands` tinyint(1) NOT NULL DEFAULT '1',
  `charge_by` enum('mg','frasco') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mg',
  `show_label_lot_expiry` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medicine_lists`
--

INSERT INTO `medicine_lists` (`id`, `name`, `description`, `active_brands`, `charge_by`, `show_label_lot_expiry`, `created_at`, `updated_at`) VALUES
(1, 'ANGELES METROPOLITANO', NULL, 1, 'frasco', 0, '2026-05-29 05:21:39', '2026-06-24 23:34:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicine_list_presentation`
--

CREATE TABLE `medicine_list_presentation` (
  `id` bigint UNSIGNED NOT NULL,
  `medicine_list_id` bigint UNSIGNED NOT NULL,
  `medicine_presentation_id` bigint UNSIGNED NOT NULL,
  `charge_by` enum('mg','frasco') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `precio_mg_override` decimal(10,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medicine_list_presentation`
--

INSERT INTO `medicine_list_presentation` (`id`, `medicine_list_id`, `medicine_presentation_id`, `charge_by`, `precio`, `precio_mg_override`, `created_at`, `updated_at`) VALUES
(4, 1, 35, 'frasco', 199000.00, NULL, '2026-06-19 01:11:09', '2026-08-01 16:07:43'),
(5, 1, 23, 'frasco', 9000.00, NULL, '2026-06-19 01:25:17', '2026-08-01 16:07:43'),
(6, 1, 24, 'frasco', 2995.00, NULL, '2026-06-19 01:25:17', '2026-08-01 16:07:43'),
(7, 1, 36, 'frasco', 12000.00, NULL, '2026-06-19 01:25:17', '2026-08-01 16:07:43'),
(8, 1, 44, 'frasco', 12420.00, NULL, '2026-06-24 23:33:44', '2026-08-01 16:07:43'),
(10, 1, 80, 'frasco', 12420.00, NULL, '2026-06-24 23:33:44', '2026-08-01 16:07:43'),
(12, 1, 59, 'frasco', 2990.00, NULL, '2026-06-24 23:37:06', '2026-08-01 16:07:43'),
(13, 1, 61, 'frasco', 10980.00, NULL, '2026-06-24 23:37:06', '2026-08-01 16:07:43'),
(15, 1, 49, 'frasco', 4995.00, NULL, '2026-06-24 23:37:55', '2026-08-01 16:07:43'),
(17, 1, 7, 'frasco', 2950.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(18, 1, 1, 'frasco', 2950.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(19, 1, 2, 'frasco', 6300.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(23, 1, 4, 'frasco', 32400.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(25, 1, 13, 'frasco', 20000.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(27, 1, 10, 'frasco', 15290.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(28, 1, 9, 'frasco', 57390.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(29, 1, 6, 'frasco', 76000.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(30, 1, 5, 'frasco', 4850.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(31, 1, 19, 'frasco', 18055.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(34, 1, 28, 'frasco', 20000.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(35, 1, 154, 'frasco', 2995.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(37, 1, 34, 'frasco', 2000.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(38, 1, 32, 'frasco', 2995.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(39, 1, 37, 'frasco', 3800.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(40, 1, 38, 'frasco', 5990.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(41, 1, 40, 'frasco', 22500.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(42, 1, 39, 'frasco', 87750.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(44, 1, 42, 'frasco', 6020.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(45, 1, 21, 'frasco', 168750.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(50, 1, 51, 'frasco', 53516.25, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(51, 1, 53, 'frasco', 4450.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(52, 1, 56, 'frasco', 4450.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(54, 1, 64, 'frasco', 3990.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(56, 1, 68, 'frasco', 1995.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(60, 1, 151, 'frasco', 6800.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(63, 1, 145, 'frasco', 1000.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(68, 1, 142, 'frasco', 4490.00, NULL, '2026-07-20 14:38:42', '2026-08-01 16:07:43'),
(80, 1, 155, 'frasco', 2200.00, NULL, '2026-07-20 14:48:37', '2026-08-01 16:07:43'),
(83, 1, 112, 'frasco', 108000.00, NULL, '2026-07-20 14:48:37', '2026-08-01 16:07:43'),
(84, 1, 113, 'frasco', 35000.00, NULL, '2026-07-20 14:48:37', '2026-08-01 16:07:43'),
(85, 1, 111, 'frasco', 248000.00, NULL, '2026-07-20 14:48:37', '2026-08-01 16:07:43'),
(86, 1, 110, 'frasco', 483750.00, NULL, '2026-07-20 14:48:37', '2026-08-01 16:07:43'),
(87, 1, 105, 'frasco', 9230.00, NULL, '2026-07-20 14:48:37', '2026-08-01 16:07:43'),
(88, 1, 106, 'frasco', 4490.00, NULL, '2026-07-20 14:48:37', '2026-08-01 16:07:43'),
(95, 1, 161, 'frasco', 90000.00, NULL, '2026-07-21 22:27:05', '2026-08-01 16:07:43'),
(96, 1, 118, 'frasco', 4490.00, NULL, '2026-07-22 17:49:30', '2026-08-01 16:07:43'),
(97, 1, 157, 'frasco', 53158.75, NULL, '2026-08-01 15:39:49', '2026-08-01 16:07:43'),
(98, 1, 156, 'frasco', 10632.00, NULL, '2026-08-01 15:39:49', '2026-08-01 16:07:43'),
(99, 1, 126, 'frasco', 202865.00, NULL, '2026-08-01 16:07:43', '2026-08-01 16:07:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicine_medicine_lists`
--

CREATE TABLE `medicine_medicine_lists` (
  `medicine_list_id` bigint UNSIGNED NOT NULL,
  `medicine_id` bigint UNSIGNED NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `precio_mg_override` decimal(12,4) DEFAULT NULL,
  `charge_by` enum('mg','frasco') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicine_oncos`
--

CREATE TABLE `medicine_oncos` (
  `id` bigint UNSIGNED NOT NULL,
  `catalog_id` bigint UNSIGNED NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `precio_mg` decimal(12,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medicine_oncos`
--

INSERT INTO `medicine_oncos` (`id`, `catalog_id`, `precio`, `precio_mg`, `created_at`, `updated_at`) VALUES
(1, 9, 0.00, NULL, '2026-05-29 05:33:32', '2026-05-29 05:33:32'),
(2, 29, 0.00, NULL, '2026-06-24 23:40:27', '2026-06-24 23:40:27'),
(3, 66, 0.00, NULL, '2026-07-21 15:12:12', '2026-07-21 15:12:12'),
(4, 48, 0.00, NULL, '2026-07-21 15:12:12', '2026-07-21 15:12:12'),
(5, 12, 0.00, NULL, '2026-07-21 15:12:12', '2026-07-21 15:12:12'),
(6, 43, 0.00, NULL, '2026-07-21 15:17:57', '2026-07-21 15:17:57'),
(7, 19, 0.00, NULL, '2026-07-21 15:22:27', '2026-07-21 15:22:27'),
(11, 1, 0.00, NULL, '2026-07-21 16:06:51', '2026-07-21 16:06:51'),
(12, 53, 0.00, NULL, '2026-07-21 16:06:51', '2026-07-21 16:06:51'),
(13, 17, 0.00, NULL, '2026-07-22 15:58:11', '2026-07-22 15:58:11'),
(14, 38, 0.00, NULL, '2026-07-22 22:46:21', '2026-07-22 22:46:21'),
(18, 28, 0.00, NULL, '2026-07-23 12:15:16', '2026-07-23 12:15:16'),
(19, 47, 0.00, NULL, '2026-08-01 15:46:58', '2026-08-01 15:46:58'),
(20, 45, 0.00, NULL, '2026-08-01 20:54:26', '2026-08-01 20:54:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicine_presentations`
--

CREATE TABLE `medicine_presentations` (
  `id` bigint UNSIGNED NOT NULL,
  `catalog_id` bigint UNSIGNED NOT NULL,
  `presentacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fabricante` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contenido_valor` decimal(10,2) NOT NULL,
  `contenido_unidad` enum('mg','g','ml','UI','smg') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mg',
  `cantidad_medicamento` decimal(10,2) DEFAULT NULL,
  `volumen_diluyente` decimal(10,2) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `virtual_stock` int DEFAULT NULL,
  `precio_frasco` decimal(12,4) DEFAULT NULL,
  `legend` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `forma_reconstitucion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `temp_min_c` smallint UNSIGNED DEFAULT NULL,
  `temp_max_c` smallint UNSIGNED DEFAULT NULL,
  `stability_hours` smallint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medicine_presentations`
--

INSERT INTO `medicine_presentations` (`id`, `catalog_id`, `presentacion`, `marca`, `fabricante`, `contenido_valor`, `contenido_unidad`, `cantidad_medicamento`, `volumen_diluyente`, `is_available`, `virtual_stock`, `precio_frasco`, `legend`, `forma_reconstitucion`, `temp_min_c`, `temp_max_c`, `stability_hours`, `created_at`, `updated_at`) VALUES
(1, 1, 'Frasco ampula 50mg', 'Innefol', NULL, 50.00, 'mg', 50.00, 4.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'no requiere reconstitución', 2, 8, 48, '2026-04-16 04:19:56', '2026-06-06 22:15:34'),
(2, 2, 'Fco amp 4mg/5mL', 'LEZOMIV', 'Synthon', 4.00, 'mg', 4.00, 5.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'no requiere reconstitución', 2, 8, 48, '2026-04-16 05:09:39', '2026-06-06 22:16:14'),
(3, 4, 'Frasco ampula 100mg/4ml', 'VIDAZA', 'CELEGENE', 100.00, 'mg', 100.00, 4.00, 1, NULL, NULL, 'Estable a T° 2-8°C 22h , estable a T°Amb x 90min . Agitar antes de usar. Suspensión. Proteger de la luz', 'Subcutaneo (IM): Se reconstituye con agua inyectable fría, agitar vigorosamente para homogenizar la suspensión.  Intravenoso: Reconstituir cada vial con 10 ml de agua inyectable, agitar vigorosamente hasta que todos los sólidos se disuelvan.', 2, 8, 8, '2026-04-16 07:00:39', '2026-06-06 22:41:31'),
(4, 4, 'Frasco ampula 100mg', 'DESOXIUL', 'ULSATECH', 100.00, 'mg', 100.00, 4.00, 1, NULL, NULL, 'Estable a T° 2-8°C 22h , estable a T°Amb x 90min . Agitar antes de usar. Suspensión. Proteger de la luz', 'Subcutaneo (IM): Se reconstituye con agua inyectable fría, agitar vigorosamente para homogenizar la suspensión.  Intravenoso: Reconstituir cada vial con 10 ml de agua inyectable, agitar vigorosamente hasta que todos los sólidos se disuelvan.', 2, 8, 8, '2026-04-16 07:03:11', '2026-06-06 22:41:25'),
(5, 5, 'Frasco ampula 15UI', 'NOVOMEXAN', 'KEMEX', 15.00, 'UI', 15.00, 5.00, 1, NULL, NULL, 'Proteger de la luz.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         Conservar en refrigeracion (2-8°C)', NULL, 2, 8, 48, '2026-04-16 07:06:13', '2026-06-06 22:49:47'),
(6, 6, 'Fco 35mcg', 'BLYNCITO', 'AMGEN', 25.00, 'smg', 500.00, 10.00, 1, NULL, NULL, '10 días a temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar 5.5 mL de solución estabilizadora al diluyente. Agregar 3 ml de agua estéril al fco  dirigiendo el agua a lo largo de las paredes del vial y no directamente sobre el polvo liofilizado. Agitar suavemente el contenido para evitar el exceso de espuma.  Mezclar suavemente evitando la formación de espuma.', 8, 20, 24, '2026-05-20 03:34:57', '2026-06-06 22:54:24'),
(7, 1, 'Ampolleta 50mg/4ml', 'FOSIDAD', NULL, 50.00, 'mg', 50.00, 4.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'no requiere reconstitución', 2, 8, 48, '2026-05-20 23:09:11', '2026-06-06 22:15:23'),
(8, 2, 'Fco amp 4mg/5mL', 'OXFON', 'ACCORD', 4.00, 'mg', 4.00, 5.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'no requiere reconstitución', 2, 8, 48, '2026-05-20 23:15:35', '2026-06-06 22:16:37'),
(9, 9, 'Fco amp 400mg/16mL', 'Effivia', 'LIOMONT', 400.00, 'mg', 400.00, 16.00, 1, NULL, NULL, 'Proteger de la Luz.\r\nMantener en refrigeración de 2° a 8° C.', 'sin reconstitución', 2, 8, 48, '2026-05-29 05:19:33', '2026-07-21 17:05:50'),
(10, 9, 'Fco amp 100mg/4mL', 'Effivia', 'Liomont', 100.00, 'mg', 100.00, 16.00, 1, NULL, NULL, 'Proteger de la luz.\r\nMantener en refrigeración de 2° a 8°', 'sin reconstitución', 2, 8, 48, '2026-05-29 05:20:27', '2026-07-21 17:05:38'),
(11, 9, 'Fco amp 100mg/4mL', 'Avastin', 'Roche', 100.00, 'mg', 100.00, 16.00, 1, NULL, NULL, 'Proteger de la luz.\r\nMantener en refrigeración 2° a 8 °C', 'sin reconstitución', 2, 8, 48, '2026-05-29 05:37:43', '2026-07-21 17:05:18'),
(12, 3, 'Fco amp 1200mg/20ml', 'TECENTRIQ', 'ROCHE', 1200.00, 'mg', 1200.00, 20.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C ,\r\nProtegido de la luz', 'no requiere reconstitución', 2, 8, 48, '2026-06-06 21:54:26', '2026-06-06 21:54:26'),
(13, 8, 'Fco amp 100mg/4mL', 'GLINDEKA', 'TEVA', 100.00, 'mg', 100.00, 4.00, 1, NULL, NULL, '24h a Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'no requiere reconstitución', 2, 8, 24, '2026-06-06 22:11:04', '2026-06-06 22:11:04'),
(14, 8, 'Fco amp 25mg', 'RIBOBUSTAN', 'JANSSEN', 25.00, 'mg', 24.00, 5.00, 1, NULL, NULL, 'Reconstituir con 5 ml de agua inyectable y agitar (5-10min) por rotación hasta homogeneizar.', NULL, 2, 8, 24, '2026-06-06 22:14:39', '2026-06-06 22:14:39'),
(15, 4, 'Fco amp 100mg/4mL', 'TINGALU', 'HETERO', 100.00, 'mg', 100.00, 4.00, 1, NULL, NULL, 'Estable a T° 2-8°C 22h , estable a T°Amb x 90min . Agitar antes de usar. Suspensión. Proteger de la luz', 'Subcutaneo (IM): Se reconstituye con agua inyectable fría, agitar vigorosamente para homogenizar la suspensión.  Intravenoso: Reconstituir cada vial con 10 ml de agua inyectable, agitar vigorosamente hasta que todos los sólidos se disuelvan.', 2, 8, 22, '2026-06-06 22:41:13', '2026-06-06 22:41:13'),
(16, 9, 'Fco amp 100mg/4mL', 'Mvasi', 'AMGEN', 100.00, 'mg', 100.00, 4.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C . Protegido de la luz', 'no requiere reconstitución', 2, 8, 48, '2026-06-06 22:44:30', '2026-07-21 17:06:00'),
(17, 9, 'Fco amp 400mg/164mL', 'Mvasi', 'AMGEN', 400.00, 'mg', 400.00, 16.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C. Protegido de la luz', 'no requiere reconstitución', 2, 8, 48, '2026-06-06 22:45:42', '2026-07-21 17:06:18'),
(18, 9, 'Fco amp 400mg/16mL', 'Avastin', 'ROCHE', 400.00, 'mg', 400.00, 16.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'no requiere reconstitución', 2, 8, 48, '2026-06-06 22:47:07', '2026-07-21 17:05:29'),
(19, 11, 'Fco amp 3.5mg', 'BEMONCAZ', 'ULSATECH', 3.50, 'mg', 3.50, 1.40, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Para la administracion IV reconstituir con 3.5 mL de CS al 0.9% para obtener una concentración de 1 mg/mL.  Para administración SC reconstituir con 1.4 mL de CS al 0.9% para obtener una concentración de 2.5 mg/mL.', 2, 8, 48, '2026-06-06 22:57:38', '2026-06-06 22:57:38'),
(20, 11, 'Fco amp 3.5mg', 'VELCADE', 'JANSSEN', 3.50, 'mg', 3.50, 1.40, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Para la administracion IV reconstituir con 3.5 mL de CS al 0.9% para obtener una concentración de 1 mg/mL.  Para administración SC reconstituir con 1.4 mL de CS al 0.9% para obtener una concentración de 2.5 mg/mL.', 2, 8, 48, '2026-06-17 00:46:53', '2026-06-17 00:46:53'),
(21, 10, 'Fco amp 50mg', 'ADCETRIS', 'TAKEDA', 50.00, 'mg', 50.00, 10.50, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C , 24hrs\r\nProtegido de la luz', 'Cada vial de 50 mg de brentuximab debe reconstituirse con 10,5 ml de agua inyectable para alcanzar una concentración de 5 mg/ml, dirija el flujo del líquido hacia la pared del vial (no directo al polvo) y agite suavemente hasta disolver. No agite bruscamente.', 2, 8, 24, '2026-06-17 01:35:44', '2026-06-17 01:35:44'),
(22, 14, 'Fco amp 60mg', 'Busilvex', 'PIERRE FABRE', 60.00, 'mg', 60.00, 10.00, 1, NULL, NULL, '12 h Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'debe diluirse siempre añadiendo el fármaco sobre el diluyente (y no al revés), utilizando jeringas que no sean de policarbonato', 2, 8, 12, '2026-06-17 01:39:12', '2026-06-17 01:39:12'),
(23, 12, 'Fco amp 450mg', 'Nuvaplast', 'ACCORD FARMA', 450.00, 'mg', 450.00, 45.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'no requiere reconstitución', 2, 8, 48, '2026-06-17 01:42:48', '2026-07-21 17:07:03'),
(24, 12, 'Fco amp 150mg', 'Nuvaplast', 'ACCORD FARMA', 150.00, 'mg', 150.00, 15.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'no requiere reconstitución', 2, 8, 48, '2026-06-17 01:43:37', '2026-07-21 17:06:57'),
(25, 18, 'Fco amp 500mg/10ml', 'Renegy', 'TAKEDA', 500.00, 'mg', 500.00, 10.00, 1, NULL, NULL, 'Temperatura ambiente / Protegido de la luz', 'no requiere reconstitución', 20, 35, 24, '2026-06-17 01:46:11', '2026-06-17 01:46:11'),
(26, 12, 'Fco amp 450', 'Placart', 'ULSATECH', 450.00, 'mg', 450.00, 45.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'no requiere reconstitución', 2, 8, 48, '2026-06-17 01:47:25', '2026-06-17 01:47:25'),
(27, 16, 'Fco amp 60mg', 'Kyprolis', 'AMGEN', 60.00, 'mg', 60.00, 30.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C, 24hrs\r\nProtegido de la luz', 'Inyecte lentamente 29 ml de agua estéril inyectable en el vial. Dirija el flujo hacia la pared interior del vial para evitar la formación de espuma.. Invierta y agite el vial suavemente durante aproximadamente 1 minuto hasta que el polvo se disuelva por completo. No lo agite bruscamente.', 2, 8, 24, '2026-06-17 01:50:19', '2026-06-17 01:50:56'),
(28, 21, 'Fco amp 100mg/20ml', 'Erbitux', 'MERCK', 100.00, 'mg', 100.00, 20.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C .\r\nProtegido de la luz', 'no requiere reconstitución', 2, 8, 48, '2026-06-17 01:55:30', '2026-06-17 01:55:30'),
(29, 19, 'Fco amp 500mg', 'Cyata', 'ACCORD FARMA', 500.00, 'mg', 500.00, 25.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Se debe reconstituir el vial utilizando Agua para Inyectables o solución de Cloruro de Sodio al 0.9%', 2, 8, 48, '2026-06-17 02:00:29', '2026-06-17 02:00:29'),
(30, 19, 'Fco amp 500mg', 'Mexcikem', 'KEMEX', 500.00, 'mg', 500.00, 25.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agrega Agua estéril o solución de Cloruro de Sodio al 0.9% por las paredes, Agite el frasco hasta que el polvo se disuelva por completo y la solución quede transparente, libre de partículas.', 2, 8, 48, '2026-06-17 02:03:04', '2026-07-21 17:08:09'),
(32, 20, 'Fco amp 50mg/50ml', 'Accocit', 'ACCORD FARMA', 50.00, 'mg', 50.00, 50.00, 1, NULL, NULL, 'Temperatura ambiente / Protegido de la luz', 'no requiere reconstitución', 15, 35, 48, '2026-06-17 02:08:35', '2026-06-17 02:08:35'),
(33, 20, 'Fco amp 10mg/10ml', 'Accocit', 'ACCORD FARMA', 10.00, 'mg', 10.00, 10.00, 1, NULL, NULL, 'Temperatura ambiente / Protegido de la luz', 'no requiere reconstitución', 15, 35, 48, '2026-06-17 02:09:09', '2026-06-17 02:09:09'),
(34, 20, 'Fco amp 10mg/10ml', 'Vishal', 'HETERO', 10.00, 'mg', 10.00, 10.00, 1, NULL, NULL, 'Temperatura ambiente / Protegido de la luz', 'no requiere reconstitución', 15, 35, 48, '2026-06-17 02:10:13', '2026-06-17 02:10:13'),
(35, 45, 'Fco amp 100mg/4ml', 'Keytruda', 'MSD', 100.00, 'mg', 100.00, 4.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'no requiere reconstitución', 2, 8, 48, '2026-06-19 01:09:29', '2026-06-19 01:09:29'),
(36, 64, 'Fco amp 500mg', 'Fampor', 'ULSATECH', 500.00, 'mg', 500.00, 20.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'añada 20 ml de solución de cloruro de sodio 0.9%, gite el vial suavemente hasta que el polvo se haya disuelto por completo. La solución resultante debe ser transparente e incolora.', 2, 8, 24, '2026-06-19 01:22:35', '2026-06-19 01:22:35'),
(37, 17, 'Fco amp 500mg', 'Zuphacit', 'Zurich', 500.00, 'mg', 500.00, 10.00, 1, NULL, NULL, 'Temperatura ambiente / Protegido de la luz', 'no requiere reconstitución', 2, 8, 48, '2026-06-19 23:49:39', '2026-06-19 23:49:39'),
(38, 23, 'Fco amp 200mg', 'Onecobax', 'KEMEX', 200.00, 'mg', 200.00, 20.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Añadir 19.7 ml de agua estéril para inyección en el vial de 200 mg.  Se debe agitar suavemente hasta disolver el polvo. La solución debe ser transparente y de color amarillo pálido. Si adquiere un color rosado, indica degradación por exposición a la luz y debe desecharse.', 2, 8, 48, '2026-06-20 00:31:02', '2026-06-20 00:31:02'),
(39, 24, 'Fco amp 400mg/20ml', 'Darzalex', 'JANSSEN', 400.00, 'mg', 400.00, 20.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'sin reconstitución', 2, 8, 48, '2026-06-20 01:20:10', '2026-06-20 01:21:29'),
(40, 24, 'fco amp 100mg/5ml', 'Darzalex', 'JANSSEN', 100.00, 'mg', 100.00, 5.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'sin reconstitución', 2, 8, 48, '2026-06-20 01:21:05', '2026-06-20 01:21:05'),
(41, 24, 'Fco amp 1800mg/15ml', 'Faspro', 'JANSSEN', 1800.00, 'mg', 1800.00, 15.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'jeringa precargada', 2, 8, 24, '2026-06-20 01:23:25', '2026-06-20 01:23:25'),
(42, 22, 'Fco amp 20mg', 'Zuleb', 'ZURICH', 20.00, 'mg', 20.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'e añade agua estéril para inyectables a cada vial. Se agita suavemente hasta la disolución completa del polvo.', 2, 8, 48, '2026-06-20 01:25:53', '2026-06-20 01:25:53'),
(43, 26, 'Fco amp 8mg', 'Dexametasona', 'PISA', 8.00, 'mg', 8.00, 2.00, 1, NULL, NULL, 'Temperatura ambiente / Protegido de la luz', 'Se añade 2ml de agua inyectable estéril y se agita suavemente.', 2, 8, 24, '2026-06-20 01:28:41', '2026-06-20 01:28:55'),
(44, 29, 'Fco amp 80 mg/ 4 mL', 'Bindu', 'HETERO', 80.00, 'mg', 80.00, 4.00, 1, NULL, NULL, 'Temperatura ambiente, 6hrs / Protegido de la luz', 'sin reconstitución', 25, 25, 6, '2026-06-24 21:27:59', '2026-06-24 21:27:59'),
(45, 27, 'Fco amp 500mg', 'Dexpexel', 'ULSATECH', 500.00, 'mg', 500.00, 25.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C , 4hrs\r\nProtegido de la luz', 'Disolver el contenido del frasco ámpula (500 mg) en exactamente 25 ml de agua estéril, agitar suavemente hasta disolver por completo.', 2, 8, 4, '2026-06-24 21:54:38', '2026-06-24 21:54:38'),
(46, 27, 'Fco amp 500mg', 'Zucordex', 'ZURICH', 500.00, 'mg', 500.00, 25.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C , 4hrs\r\nProtegido de la luz', 'Inyecte 25 ml de agua inyectable en el frasco ámpula, agite suavemente hasta disolver el liofilizado.', 2, 8, 4, '2026-06-24 21:54:38', '2026-06-24 21:54:38'),
(47, 50, 'Fco amp', 'Blestinib', 'ZURICH PHARMA', 10.00, 'mg', 10.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Disolver el contenido del frasco ámpula agregando 10 mL del diluyente incluido en la presentación,  agitar suavemente hasta lograr una solución completamente transparente y libre de partículas.', 2, 8, 48, '2026-06-24 22:20:29', '2026-06-24 22:20:29'),
(48, 50, 'Fco amp', 'Dyranovyl', 'ULSA TECH', 10.00, 'mg', 10.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar 10 mL del diluyente incluido directamente en el frasco ámpula, se agita de forma suave y circular hasta que el polvo se disuelva por completo, obteniendo una solución totalmente clara, transparente y sin partículas.', 2, 8, 48, '2026-06-24 22:20:29', '2026-06-24 22:20:29'),
(49, 28, 'Fco amp 50mg/25mL', 'Zodox', 'ACCORD FARMA', 50.00, 'mg', 50.00, 25.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 22:33:21', '2026-06-24 22:33:21'),
(50, 28, 'Fco amp 50mg', 'Zuclodox', 'ZURICH', 50.00, 'mg', 50.00, 25.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Inyecte 25 ml de agua inyectable  o cloruro de sodio 0.9% en el frasco ámpula, agite suavemente hasta disolver el liofilizado.', 2, 8, 48, '2026-06-24 22:33:21', '2026-06-24 22:33:21'),
(51, 52, 'Fco amp 30mg', 'Padcev', 'ASOFARMA', 30.00, 'mg', 30.00, 3.30, 1, NULL, NULL, '15 h Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Añada agua estéril, gire el vial lentamente hasta que el contenido se disuelva por completo, deje reposar la solución al menos 1 minuto para que desaparezcan las burbujas. NO AGITAR', 2, 8, 15, '2026-06-24 22:39:46', '2026-06-24 22:39:46'),
(52, 52, 'Fco amp 20mg', 'Padcev', 'ASOFARMA', 20.00, 'mg', 20.00, 2.30, 1, NULL, NULL, '15 h Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Añada agua estéril, gire el vial lentamente hasta que el contenido se disuelva por completo, deje reposar la solución al menos 1 minuto para que desaparezcan las burbujas. NO AGITAR', 2, 8, 16, '2026-06-24 22:39:46', '2026-06-24 22:39:46'),
(53, 51, 'Fco amp 50mg', 'Papluf', 'ULSATECH', 50.00, 'mg', 50.00, 25.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agrega 25 mL de diluyente, gite suavemente el frasco hasta que el polvo se disuelva por completo y la solución quede transparente, libre de partículas.', 2, 8, 48, '2026-06-24 22:53:25', '2026-06-24 22:53:25'),
(54, 48, 'Fco amp', 'Enhertu', 'ASTRA ZENECA', 100.00, 'mg', 100.00, 5.00, 0, NULL, NULL, '24h. Temperatura de refrigeración 2-8°C. Protegido de la luz', 'Agregar lentamente 5 mL de agua estéril directamente en el frasco dirigiendo el líquido hacia la pared del frasco, se gira el frasco con suavidad para disolver el polvo por completo sin agitar bruscamente para evitar la formación de espuma.', 2, 8, 24, '2026-06-24 22:54:12', '2026-07-21 19:13:46'),
(55, 48, 'Fco amp', 'Kadcyla 100', 'ROCHE', 100.00, 'mg', 100.00, 5.00, 0, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz.', 'Agregar lentamente 5 mL de agua estéril dirigiendo el líquido hacia la pared del frasco, se gira el frasco con suavidad en círculos hasta que el polvo se disuelva por completo. No se debe agitar bruscamente para evitar la formación de espuma.', 2, 8, 48, '2026-06-24 22:56:03', '2026-07-21 19:13:25'),
(56, 51, 'Fco amp 50mg', 'Zuclebin', 'ZURICH', 50.00, 'mg', 50.00, 25.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agrega 25 mL de diluyente, gite suavemente el frasco hasta que el polvo se disuelva por completo y la solución quede transparente, libre de partículas.', 2, 8, 48, '2026-06-24 22:56:16', '2026-07-13 15:25:47'),
(57, 48, 'Fco amp', 'Kadcyla 160', 'ROCHE', 160.00, 'mg', 160.00, 8.00, 0, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar lentamente 8 mL de agua estéril dirigiendo el líquido hacia la pared del frasco, se gira el frasco con suavidad en círculos hasta que el polvo se disuelva por completo. No se debe agitar bruscamente para evitar la formación de espuma.', 2, 8, 48, '2026-06-24 22:57:45', '2026-07-21 19:13:32'),
(58, 48, 'Fco amp', 'Herceptin', 'ROCHE', 440.00, 'mg', 440.00, 20.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar lentamente 20 mL del diluyente propio en el frasco, apuntando el líquido hacia el polvo liofilizado, mover el frasco con un balanceo suave y circular para disolverlo por completo. No se debe agitar bruscamente.', 2, 8, 48, '2026-06-24 23:00:40', '2026-06-24 23:00:40'),
(59, 19, 'Fco amp 200mg', 'Mexcikem', 'NOVAG', 200.00, 'mg', 200.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agrega Agua estéril o solución de Cloruro de Sodio al 0.9% por las paredes, Agite el frasco hasta que el polvo se disuelva por completo y la solución quede transparente, libre de partículas.', 2, 8, 48, '2026-06-24 23:01:07', '2026-07-21 17:08:01'),
(60, 48, 'Fco amp', 'Herceptin SC', 'ROCHE', 600.00, 'mg', 600.00, 5.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 23:01:51', '2026-06-24 23:01:51'),
(61, 19, 'Fco amp 1000mg', 'Mexcikem', 'NOVAG', 1000.00, 'mg', 1000.00, 50.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agrega Agua estéril o solución de Cloruro de Sodio al 0.9% por las paredes, Agite el frasco hasta que el polvo se disuelva por completo y la solución quede transparente, libre de partículas.', 2, 8, 48, '2026-06-24 23:03:38', '2026-07-21 17:07:53'),
(62, 48, 'Fco amp', 'Herzuma', 'CELLTRION', 440.00, 'mg', 440.00, 20.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar lentamente 20 mL de agua estéril o el diluyente proporcionado en el frasco ámpula, dirigiendo el líquido hacia la pared interna del vial, se rota el frasco con un movimiento circular y suave hasta disolver por completo el polvo, evitando agitar bruscamente para que no se forme espuma.', 2, 8, 48, '2026-06-24 23:04:12', '2026-06-24 23:04:12'),
(63, 30, 'Fco amp 100mg/5ml', 'Cavep', 'ACCORD FARMA', 100.00, 'mg', 100.00, 5.00, 1, NULL, NULL, 'Temperatura ambiente / Protegido de la luz', 'Desplazar el contenido del vial lentamente sin generar burbujas', 15, 25, 48, '2026-06-24 23:06:14', '2026-06-24 23:06:14'),
(64, 30, 'Fco amp 100mg/5ml', 'Eptoken', 'KEMEX', 100.00, 'mg', 100.00, 5.00, 1, NULL, NULL, 'Temperatura ambiente / Protegido de la luz', 'Desplazar el contenido del vial lentamente sin generar burbujas', 15, 25, 48, '2026-06-24 23:08:39', '2026-06-24 23:08:39'),
(65, 30, 'Fco amp 100mg/5ml', 'Tosuben', 'PISA', 100.00, 'mg', 100.00, 5.00, 1, NULL, NULL, 'Temperatura ambiente / Protegido de la luz', 'Desplazar el contenido del vial lentamente sin generar burbujas', 2, 8, 48, '2026-06-24 23:08:39', '2026-06-24 23:08:39'),
(66, 62, 'Fco', 'Yondelis', 'RAFFO', 1.00, 'mg', 1.00, 20.00, 1, NULL, NULL, '24h Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar 20 mL de agua estéril directamente en el frasco ámpula, dirigiendo el líquido hacia la pared interna del vial, e rota el frasco con un movimiento circular y suave hasta disolver por completo el polvo.', 2, 8, 24, '2026-06-24 23:11:52', '2026-06-24 23:11:52'),
(67, 53, 'Fco amp 250mg/5ml', 'Acoflut', 'ACCORD FARMA', 250.00, 'mg', 250.00, 5.00, 1, NULL, NULL, '\"Temperatura de refrigeración 2-8°C \r\nProtegido de la luz\"', 'no requiere reconstitución', 2, 8, 48, '2026-06-24 23:16:07', '2026-06-24 23:16:07'),
(68, 53, 'Fco amp 250mg/10ml', 'Fuoavil', 'ZURICH', 250.00, 'mg', 250.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'no requiere reconstitución', 2, 8, 72, '2026-06-24 23:16:07', '2026-06-24 23:16:07'),
(69, 53, 'Fco amp 250mg/10ml', 'Ulsacil', 'ULSATECH', 250.00, 'mg', 250.00, 10.00, 1, NULL, NULL, 'Temperatura ambiente / Protegido de la luz', 'No requiere reconstitución', 15, 25, 48, '2026-06-24 23:16:07', '2026-06-24 23:16:07'),
(70, 49, 'Fco amp', 'Roactemra 80', 'ROCHE', 80.00, 'mg', 80.00, 4.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 23:17:09', '2026-06-24 23:17:09'),
(71, 49, 'Fco amp', 'Roactemra 200', 'ROCHE', 200.00, 'mg', 200.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 23:17:09', '2026-06-24 23:17:09'),
(72, 49, 'Fco amp', 'Roactemra 400', 'ROCHE', 400.00, 'mg', 400.00, 20.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 23:17:09', '2026-06-24 23:17:09'),
(73, 47, 'Fco amp', 'Arasamila 100', 'PROBIOMED', 100.00, 'mg', 100.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 23:28:37', '2026-06-24 23:28:37'),
(74, 47, 'Fco amp', 'Arasamila 500', 'PROBIOMED', 500.00, 'mg', 500.00, 50.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 23:28:37', '2026-06-24 23:28:37'),
(75, 47, 'Fco amp', 'Blitzimia 100', 'CELLTRION', 100.00, 'mg', 100.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 23:28:37', '2026-06-24 23:28:37'),
(76, 47, 'Fco amp', 'Blitzimia 500', 'CELLTRION', 500.00, 'mg', 500.00, 50.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 23:28:37', '2026-06-24 23:28:37'),
(77, 47, 'Fco amp', 'Mabthera', 'ROCHE', 500.00, 'mg', 500.00, 50.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 23:28:37', '2026-06-24 23:28:37'),
(78, 29, 'Fco amp 80mg', 'Miocerkel', 'ULSATECH', 80.00, 'mg', 80.00, 6.00, 1, NULL, NULL, 'Temperatura ambiente, 6hrs / Protegido de la luz', 'Reconstituir con diluyente propia, deslinzado por las paredes y agitar suavemente. NO AGUITAR VIGOROSO', 15, 25, 6, '2026-06-24 23:30:52', '2026-06-24 23:30:52'),
(79, 29, 'Fco amp 20mg', 'Miocerkel', 'ULSATECH', 20.00, 'mg', 20.00, 4.00, 1, NULL, NULL, 'Temperatura ambiente, 6hrs / Protegido de la luz', 'Reconstituir con diluyente propia, deslinzado por las paredes y agitar suavemente. NO AGUITAR VIGOROSO', 15, 25, 6, '2026-06-24 23:30:52', '2026-07-21 17:09:42'),
(80, 29, 'Fco amp 80mg/4ml', 'Taxanit', 'ACCORD', 80.00, 'mg', 80.00, 4.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Ingresar el medicamento lentamente evitando la formación de espuma. Girar mezcla suavemente', 2, 8, 48, '2026-06-24 23:30:52', '2026-06-24 23:30:52'),
(81, 61, 'Fco amp', 'Cyramza 100', 'LILLY', 100.00, 'mg', 100.00, 10.00, 1, NULL, NULL, '24 h Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 24, '2026-06-24 23:35:34', '2026-06-24 23:35:34'),
(82, 61, 'Fco amp', 'Cyramza 500', 'LILLY', 500.00, 'mg', 500.00, 50.00, 1, NULL, NULL, '24 h Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 24, '2026-06-24 23:35:34', '2026-06-24 23:35:34'),
(83, 60, 'Fco amp', 'Polivy', 'ROCHE', 140.00, 'mg', 140.00, 7.20, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar lentamente 7.2 mL de agua estéril en el vial 140 mg, dirigiendo el líquido hacia la pared interna del vial, se rota el frasco con un movimiento circular y suave hasta disolver por completo el polvo, evitando agitar bruscamente para que no se forme espuma.', 2, 8, 48, '2026-06-24 23:48:20', '2026-06-24 23:48:20'),
(84, 31, 'Fco amp 150mg', 'Emend', 'MSD', 150.00, 'mg', 150.00, 5.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Reconstituir con 5mL CS 0.9-%  . Ingrese la solución lentamente evitando la formación de espuma. Girar mezcla suavemente. No agitar.', 2, 8, 48, '2026-06-25 00:05:19', '2026-06-25 00:05:19'),
(85, 64, 'Fco amp', 'Bemetad', 'HETERO', 500.00, 'mg', 500.00, 20.00, 1, NULL, NULL, 'Temperatura ambiente 24 h\r\nProtegido de la luz', 'Agregar 20 mL de solución de cloruro de sodio al 0.9% en el vial de 500 mg dirigiendo el líquido hacia la pared interna del vial. Se rota el frasco con un movimiento circular y suave hasta disolver por completo el polvo.', 2, 8, 24, '2026-06-25 00:10:29', '2026-06-25 00:10:29'),
(86, 64, 'Fco amp', 'Emped', 'ACCORD FARMA', 500.00, 'mg', 500.00, 20.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar 20 mL de solución de cloruro de sodio al 0.9% en el vial de 500 mg dirigiendo el líquido hacia la pared interna del vial. Se rota el frasco con un movimiento circular y suave hasta disolver por completo el polvo.', 2, 8, 48, '2026-06-25 00:10:29', '2026-06-25 00:10:29'),
(87, 59, 'Fco amp', 'Oncaspar 3750', 'SERVIER', 3750.00, 'UI', 3750.00, 5.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 19:25:53', '2026-06-24 19:25:53'),
(88, 59, 'Fco amp', 'Oncaspar 750', 'SERVIER', 750.00, 'UI', 750.00, 1.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 19:25:53', '2026-06-24 19:25:53'),
(89, 58, 'Fco amp', 'Vectibix', 'AMGEN', 100.00, 'mg', 100.00, 5.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 19:31:43', '2026-06-24 19:31:43'),
(90, 44, 'Fco amp', 'Acoexcel 300', 'ACCORD FARMA', 300.00, 'mg', 300.00, 50.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', NULL, NULL, NULL, 48, '2026-06-24 20:26:37', '2026-06-24 20:26:37'),
(91, 44, 'Fco amp', 'Acoexcel 30', 'ACCORD FARMA', 30.00, 'mg', 30.00, 5.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', NULL, NULL, NULL, 48, '2026-06-24 20:26:37', '2026-06-24 20:26:37'),
(92, 44, 'Fco amp', 'Daburex 300', 'FRESENIUS KABI', 300.00, 'mg', 300.00, 50.00, 1, NULL, NULL, 'Temperatura ambiente 27 H \r\nProtegido de la luz', NULL, NULL, NULL, 27, '2026-06-24 20:26:37', '2026-06-24 20:26:37'),
(93, 44, 'Fco amp', 'Daburex 30', 'FRESENIUS KABI', 30.00, 'mg', 30.00, 5.00, 1, NULL, NULL, 'Temperatura ambiente / 27h\r\nProtegido de la luz', NULL, NULL, NULL, 27, '2026-06-24 20:26:37', '2026-06-24 20:26:37'),
(94, 44, 'Fco amp', 'Nimaril', 'HETERO', 30.00, 'mg', 30.00, 5.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', NULL, NULL, NULL, 48, '2026-06-24 20:26:37', '2026-06-24 20:26:37'),
(95, 44, 'Fco amp', 'Petexel 260', 'ZYDUS', 260.00, 'mg', 260.00, 43.30, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', NULL, NULL, NULL, 48, '2026-06-24 20:26:37', '2026-06-24 20:26:37'),
(96, 44, 'Fco amp', 'Petexel 30', 'ZYDUS', 30.00, 'mg', 30.00, 5.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', NULL, NULL, NULL, 48, '2026-06-24 20:26:37', '2026-06-24 20:26:37'),
(97, 44, 'Fco amp', 'Sirapeh 300', 'ULSA TECH', 300.00, 'mg', 300.00, 50.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', NULL, NULL, NULL, 48, '2026-06-24 20:26:37', '2026-06-24 20:26:37'),
(98, 44, 'Fco amp', 'Sirapeh 30', 'ULSA TECH', 30.00, 'mg', 30.00, 5.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', NULL, NULL, NULL, 48, '2026-06-24 20:26:37', '2026-06-24 20:26:37'),
(99, 44, 'Fco amp', 'Taxol', 'BRISTOL-MYERS', 30.00, 'mg', 30.00, 5.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', NULL, NULL, NULL, 48, '2026-06-24 20:26:37', '2026-06-24 20:26:37'),
(100, 44, 'Fco amp', 'Zetataxo', 'GLENMARK', 300.00, 'mg', 300.00, 5.00, 1, NULL, NULL, 'Temperatura ambiente 24 h\r\nProtegido de la luz', NULL, NULL, NULL, 24, '2026-06-24 20:26:37', '2026-06-24 20:26:37'),
(101, 44, 'Fco amp', 'Zuricxel 300', 'ZURICH PHARMA', 300.00, 'mg', 300.00, 50.00, 1, NULL, NULL, 'Temperatura ambiente\r\nProtegido de la luz', NULL, NULL, NULL, 48, '2026-06-24 20:26:37', '2026-06-24 20:26:37'),
(102, 44, 'Fco amp', 'Zuricxel 30', 'ZURICH PHARMA', 30.00, 'mg', 30.00, 5.00, 1, NULL, NULL, 'Temperatura ambiente\r\nProtegido de la luz', NULL, NULL, NULL, 48, '2026-06-24 20:26:37', '2026-06-24 20:26:37'),
(103, 43, 'Fco amp 100mg/ml', 'Eloxatin', 'SANOFI', 100.00, 'mg', 100.00, 20.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 20:53:27', '2026-07-21 17:24:35'),
(104, 43, 'Fco amp 50mg/ml', 'Eloxatin', 'SANOFI', 50.00, 'mg', 50.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 20:53:27', '2026-07-21 17:24:10'),
(105, 43, 'Fco amp 100mg/ml', 'Kemoxa', 'KEMEX', 100.00, 'mg', 100.00, 20.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar 20 mL de agua estéril o solución de glucosa al 5% en el vial de 100 mg, se debe disolver por completo agitando suavemente de forma circular. Una vez reconstituido, se debe diluir de inmediato únicamente en solución de glucosa al 5%.', 2, 8, 48, '2026-06-24 20:53:27', '2026-07-21 18:44:15'),
(106, 43, 'Fco amp 50mg/ml', 'Kemoxa', 'KEMEX', 50.00, 'mg', 50.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar 10 mL de agua estéril o solución de glucosa al 5% en el vial de 100 mg, se debe disolver por completo agitando suavemente de forma circular. Una vez reconstituido, se debe diluir de inmediato únicamente en solución de glucosa al 5%.', 2, 8, 48, '2026-06-24 20:53:27', '2026-07-21 18:44:22'),
(107, 43, 'Fco amp 100mg/ml', 'Recoplat', 'ACCORD FARMA', 100.00, 'mg', 100.00, 20.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', NULL, NULL, NULL, 48, '2026-06-24 20:53:27', '2026-07-21 17:26:37'),
(108, 43, 'Fco amp 100mg/ml', 'Tiboquir', 'ULTRA LABORATORIOS', 100.00, 'mg', 100.00, 20.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 20:53:27', '2026-07-21 17:25:32'),
(109, 43, 'Fco amp 50mg/ml', 'Tiboquir', 'ULTRA LABORATORIOS', 50.00, 'mg', 50.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 20:53:27', '2026-07-21 17:25:24'),
(110, 57, 'Fco amp', 'Ocrevus', 'ROCHE', 300.00, 'mg', 300.00, 10.00, 1, NULL, NULL, '24h Temperatura de refrigeración 2-8°C\r\nProtegido de la luz', NULL, 2, 8, 24, '2026-06-24 20:56:24', '2026-06-24 20:56:24'),
(111, 42, 'Fco amp', 'Gazyva', 'ROCHE', 1000.00, 'mg', 1000.00, 40.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 21:02:37', '2026-06-24 21:02:37'),
(112, 41, 'Fco amp 100mg/ml', 'Opdivo', 'BRISTOL-MYERS', 100.00, 'mg', 100.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 21:06:46', '2026-07-21 17:10:47'),
(113, 41, 'Fco amp 40mg/ml', 'Opdivo', 'BRISTOL-MYERS', 40.00, 'mg', 40.00, 4.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 21:06:46', '2026-07-21 17:10:35'),
(114, 40, 'Fco amp', 'Abraxus', 'CELGENE', 100.00, 'mg', 100.00, 20.00, 1, NULL, NULL, '8h Temperatura ambiente\r\n Protegido de la luz', 'Agregar lentamente 20 mL de solución de cloruro de sodio al 0.9% en el vial de 100 mg dirigiéndolo hacia la pared interna, dejar reposar 2 minutos y disolver por completo rotando suavemente en círculos.', NULL, NULL, 8, '2026-06-24 21:12:41', '2026-06-24 21:12:41'),
(115, 37, 'Fco amp', 'Tonaxten', 'ZURICH PHARMA', 20.00, 'mg', 20.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 21:14:09', '2026-06-24 21:14:09'),
(116, 38, 'Fco amp', 'Fresexate', 'FRESENIUS KABI', 500.00, 'mg', 500.00, 20.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-24 21:26:15', '2026-06-24 21:26:15'),
(117, 38, 'Fco amp', 'Traxacord', 'ACCORD FARMA', 500.00, 'mg', 500.00, 5.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', NULL, NULL, NULL, 48, '2026-06-24 21:26:15', '2026-06-24 21:26:15'),
(118, 38, 'Fco amp', 'Ulmextral', 'ULSA TECH', 500.00, 'mg', 500.00, 20.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', 'Agregar 20 mL de solución de cloruro de sodio al 0.9% en el vial de 500 mg, se debe disolver por completo agitando suavemente de forma circular.', NULL, NULL, 48, '2026-06-24 21:26:15', '2026-06-24 21:26:15'),
(119, 38, 'Fco amp', 'Zumotrex', 'ULSA TECH', 500.00, 'mg', 500.00, 20.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', 'Agregar 20 mL de solución de cloruro de sodio al 0.9% en el vial de 500 mg , se debe disolver por completo agitando suavemente de forma circular.', NULL, NULL, 48, '2026-06-24 21:26:15', '2026-06-24 21:26:15'),
(120, 39, 'Fco amp', 'Mecav', 'VITALIS', 400.00, 'mg', 400.00, 4.00, 1, NULL, NULL, '24h Temperatura de refrigeración 2-8°C\r\nProtegido de la luz', NULL, 2, 8, 24, '2026-06-24 21:36:08', '2026-06-24 21:36:08'),
(121, 39, 'Fco amp', 'Novacarel', 'PISA', 400.00, 'mg', 400.00, 4.00, 1, NULL, NULL, 'Temperatura Ambiente\r\nProtegido de la luz', NULL, NULL, NULL, 48, '2026-06-24 21:36:08', '2026-06-24 21:36:08'),
(122, 39, 'Fco amp', 'Uromes', 'SANFER', 400.00, 'mg', 400.00, 4.00, 1, NULL, NULL, 'Temperatura ambiente\r\nProtegido de la luz', NULL, NULL, NULL, 48, '2026-06-24 21:36:08', '2026-06-24 21:36:08'),
(123, 65, 'Fco amp', 'Nefixol', 'ULSA TECH', 1.00, 'mg', 1.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar  10 mL de agua estéril  o solución salina al 0.9% en el vial de 1 mg, se debe disolver por completo agitando suavemente de forma circular. Una vez reconstituido, se debe diluir de inmediato en solución de cloruro de sodio al 0.9% o solución de glucosa al 5% para su infusión intravenosa.', 2, 8, 48, '2026-06-25 16:13:17', '2026-06-25 16:13:17'),
(124, 65, 'Fco amp', 'Sutivin', 'ZURICH PHARMA', 1.00, 'mg', 1.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar 10 mL de solución de cloruro de sodio al 0.9% o agua estéril en el vial de 1 mg, se debe disolver por completo agitando suavemente de forma circular.', 2, 8, 48, '2026-06-25 16:13:17', '2026-06-25 16:13:17'),
(125, 65, 'Fco amp', 'Vinlon1', 'CELON LABS', 1.00, 'mg', 1.00, 1.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar 1 mL de cloruro de sodio al 0.9% o agua inyectable estéril suavemente por las paredes del frasco y se rota el vial entre las manos sin agitar bruscamente.', 2, 8, 48, '2026-06-25 16:13:17', '2026-06-25 16:13:17'),
(126, 66, 'Fco amp', 'Perjeta', 'ROCHE', 420.00, 'mg', 420.00, 14.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-25 16:19:17', '2026-06-25 16:19:17'),
(127, 68, 'Fco amp', 'Phesgo 1200/600', 'ROCHE', 1200.00, 'mg', 600.00, 15.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-25 16:34:09', '2026-06-25 16:34:09'),
(128, 68, 'Fco amp', 'Phesgo 600/600', 'ROCHE', 600.00, 'mg', 600.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-25 16:34:09', '2026-06-25 16:34:09'),
(129, 36, 'Fco amp', 'Leunase', 'SANFER', 10000.00, 'UI', 10000.00, 2.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar 2 mL de agua estéril o solución de cloruro de sodio al 0.9% en el vial de 10,000 UI, disolver haciendo girar el frasco suavemente de forma circular, nunca agitar vigorosamente.', 2, 8, 48, '2026-06-25 17:43:06', '2026-06-25 17:43:06'),
(130, 36, 'Fco amp', 'Sprectila', 'SANFER', 10000.00, 'mg', 10000.00, 4.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Verter 3,7 ml de agua para inyectables con suavidad sobre la pared interna del vial con una jeringa para inyección (no verter directamente o sobre el polvo). Disolver el contenido con inversiones suaves (no agitar para evitar la aparición de espuma).', 2, 8, 48, '2026-06-25 17:43:06', '2026-06-25 17:43:06'),
(131, 56, 'Fco amp', 'Sarclisa 100', 'SANOFI', 100.00, 'mg', 100.00, 5.00, 1, NULL, NULL, '24h Temperatura de refrigeración 2-8°C\r\nProtegido de la luz', NULL, 2, 8, 24, '2026-06-25 17:48:53', '2026-06-25 17:48:53'),
(132, 56, 'Fco amp', 'Sarclisa 500', 'SANOFI', 500.00, 'mg', 500.00, 25.00, 1, NULL, NULL, '24h Temperatura de refrigeración 2-8°C\r\nProtegido de la luz', NULL, 2, 8, 24, '2026-06-25 17:48:53', '2026-06-25 17:48:53'),
(133, 55, 'Fco amp', 'Badix', 'HETERO', 100.00, 'mg', 100.00, 5.00, 1, NULL, NULL, '24h TA en CC, Protegido de la luz.\r\n 48h Temp 2-8°C en Dx', NULL, NULL, NULL, NULL, '2026-06-25 18:01:31', '2026-06-25 18:01:31'),
(134, 55, 'Fco amp', 'Camptosar', 'PFIZER', 100.00, 'mg', 100.00, 5.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-25 18:01:31', '2026-06-25 18:01:31'),
(135, 55, 'Fco amp', 'Colizactive', 'GLENMARK', 100.00, 'mg', 100.00, 5.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-25 18:01:31', '2026-06-25 18:01:31'),
(136, 55, 'Fco amp', 'Daritex_A', 'FRESENIUS KABI', 100.00, 'mg', 100.00, 5.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-25 18:01:31', '2026-06-25 18:01:31'),
(137, 55, 'Fco amp', 'Iraplax', 'ACCORD FARMA', 100.00, 'mg', 100.00, 5.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-06-25 18:01:31', '2026-06-25 18:01:31'),
(138, 35, 'Fco amp', 'Remicade', 'JANSSEN', 100.00, 'mg', 100.00, 10.00, 1, NULL, NULL, '24 h Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar 10 mL de agua estéril, se debe dirigir el chorro del diluyente suavemente por la pared de vidrio del vial , se debe hacer girar el frasco con suavidad de forma circular durante unos minutos para disolverlo por completo; no se debe agitar con fuerza.', 2, 8, 24, '2026-06-25 18:11:24', '2026-06-25 18:11:24'),
(139, 35, 'Fco amp', 'Ixifi', 'PFIZER', 100.00, 'mg', 100.00, 10.00, 1, NULL, NULL, '24 h Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar 10 mL de agua estéril, se debe dirigir el chorro del diluyente suavemente por la pared de vidrio del vial , se debe hacer girar el frasco con suavidad de forma circular durante unos minutos para disolverlo por completo; no se debe agitar con fuerza.', 2, 8, 48, '2026-06-25 18:11:24', '2026-06-25 18:11:24'),
(140, 34, 'Fco amp', 'Alquifos', 'ULSA TECH', 1000.00, 'mg', 1000.00, 25.00, 1, NULL, NULL, '24h Temperatura de refrigeración 2-8°C\r\nProtegido de la luz', 'Agregar 25 mL de agua para estéril para disolver el liofilizado.', 2, 8, 24, '2026-06-25 18:44:00', '2026-06-25 18:44:00'),
(141, 34, 'Fco amp', 'Idaxfen', 'ZURICH PHARMA', 1000.00, 'mg', 1000.00, 25.00, 1, NULL, NULL, '24h Temperatura de refrigeración 2-8°C\r\nProtegido de la luz', 'Agregar 25 mL de agua para inyección estéril en el vial de 1,000 mg.', 2, 8, 24, '2026-06-25 18:44:00', '2026-06-25 18:44:00'),
(142, 34, 'Fco amp', 'Oxazanov', 'KEMEX', 1000.00, 'mg', 1000.00, 20.00, 1, NULL, NULL, 'Temperatura ambiente\r\nProtegido de la luz', 'Agregar 20 mL de agua para inyección estéril en el vial de 1,000 mg.', NULL, NULL, 48, '2026-06-25 18:44:00', '2026-06-25 18:44:00'),
(143, 33, 'Fco amp', 'Infarub', 'KEMEX', 5.00, 'mg', 5.00, 5.00, 1, NULL, NULL, '48h Temperatura de refrigeración 2-8°C\r\nProtegido de la luz', 'Agregar 5 mL de agua estéril en el vial de 5 mg suavemente por la pared del vial, agitar con movimientos circulares suaves hasta que el liofilizado se disuelva por completo.', 2, 8, 48, '2026-06-25 18:55:47', '2026-06-25 18:55:47'),
(144, 33, 'Fco amp', 'Larecin', 'ZURICH PHARMA', 5.00, 'mg', 5.00, 5.00, 1, NULL, NULL, '48h Temperatura de refrigeración 2-8°C\r\nProtegido de la luz', 'Agregar 5 mL de agua estéril dirigiendo el chorro suavemente por la pared de vidrio del vial, haciendo girar el frasco con suavidad de forma circular entre las manos sin agitar bruscamente hasta que el polvo se disuelva por completo.', 2, 8, 48, '2026-06-25 18:55:47', '2026-06-25 18:55:47'),
(145, 54, 'Fco amp', 'Hidrocortisona 100', 'PISA', 100.00, 'mg', 100.00, 2.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', 'Agregar 2 mL de agua estéril dirigiendo el chorro suavemente por la pared de vidrio del vial, haciendo girar el frasco con suavidad de forma circular entre las manos sin agitar bruscamente hasta que el polvo se disuelva por completo.', NULL, NULL, 48, '2026-06-25 19:01:52', '2026-06-25 19:01:52'),
(146, 54, 'Fco amp', 'Hidrocortisona 500', 'PISA', 500.00, 'mg', 500.00, 4.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', 'Agregar 4 mL de agua estéril dirigiendo el chorro suavemente por la pared de vidrio del vial, haciendo girar el frasco con suavidad de forma circular entre las manos sin agitar bruscamente hasta que el polvo se disuelva por completo.', NULL, NULL, 48, '2026-06-25 19:01:52', '2026-06-25 19:01:52'),
(147, 32, 'Fco. amp', 'Accogem', 'ACCORD FARMA', 1000.00, 'mg', 1000.00, 25.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', 'Agregar 25 mL de solución de cloruro de sodio al 0.9% dirigiendo el líquido suavemente por la pared del vial, haciendo girar el frasco con suavidad de forma circular entre las manos sin agitar bruscamente hasta que el polvo se disuelva por completo.', NULL, NULL, 48, '2026-06-25 19:28:19', '2026-06-25 19:28:19'),
(148, 32, 'Fco amp', 'Enekamub', 'GLENMARK', 1000.00, 'mg', 1000.00, 25.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', 'Agregar 25 mL de solución de cloruro de sodio al 0.9% dirigiendo el líquido suavemente por la pared del vial, haciendo girar el frasco con suavidad de forma circular entre las manos sin agitar bruscamente hasta que el polvo se disuelva por completo.', NULL, NULL, 48, '2026-06-25 19:28:19', '2026-06-25 19:28:19'),
(149, 32, 'Fco amp', 'Gemzar', 'LILLY', 1000.00, 'mg', 1000.00, 25.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', 'Agregar 25 mL de solución de cloruro de sodio al 0.9% dirigiendo el líquido suavemente por la pared del vial, haciendo girar el frasco con suavidad de forma circular entre las manos sin agitar bruscamente hasta que el polvo se disuelva por completo.', NULL, NULL, 48, '2026-06-25 19:28:19', '2026-07-21 17:08:42'),
(150, 32, 'Fco amp', 'Gemza', 'LILLY', 200.00, 'mg', 200.00, 5.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', 'Agregar 5 mL de solución de cloruro de sodio al 0.9% dirigiendo el líquido suavemente por la pared del vial, haciendo girar el frasco con suavidad de forma circular entre las manos sin agitar bruscamente hasta que el polvo se disuelva por completo.', NULL, NULL, 48, '2026-06-25 19:28:19', '2026-07-21 17:08:50'),
(151, 32, 'Fco amp', 'Uldeus', 'ULSA TECH', 1000.00, 'mg', 1000.00, 25.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', 'Agregar 25 mL de solución de cloruro de sodio al 0.9% dirigiendo el líquido suavemente por la pared del vial, haciendo girar el frasco con suavidad de forma circular entre las manos sin agitar bruscamente hasta que el polvo se disuelva por completo.', NULL, NULL, 48, '2026-06-25 19:28:19', '2026-06-25 19:28:19'),
(152, 70, 'Fco amp 10ml', 'Sol estabilizadora', 'AMGEN', 10.00, 'ml', 0.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C. Protegido de la luz', 'no requiere reconstitución', 2, 8, 72, '2026-07-03 16:41:20', '2026-07-03 16:41:20'),
(153, 2, 'Fco amp', 'ACIDO ZOLEDRÓNICO', 'HETERO', 4.00, 'mg', 4.00, 5.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-07-16 14:53:42', '2026-07-16 14:54:28'),
(154, 20, 'Fco amp', 'UL-PLUX', 'ULSA TECH', 50.00, 'mg', 50.00, 50.00, 1, NULL, NULL, 'Temperatura ambiente / Protegido de la luz', NULL, NULL, NULL, 48, '2026-07-16 15:04:51', '2026-07-16 15:04:51'),
(155, 39, 'Fco amp', 'Zurmez', 'ZURICH', 400.00, 'mg', 400.00, 4.00, 1, NULL, NULL, 'Temperatura Ambiente /  Protegido de la luz', NULL, NULL, NULL, 24, '2026-07-16 15:20:54', '2026-07-16 15:20:54'),
(156, 47, 'Fco amp 100mg/10mL', 'Ruxience', 'PFIZER', 100.00, 'mg', 100.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-07-16 15:57:46', '2026-08-01 15:53:02'),
(157, 47, 'Fco amp', 'Ruxience', 'PFIZER', 500.00, 'mg', 500.00, 50.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-07-16 15:58:45', '2026-07-16 15:58:45'),
(158, 20, 'Fco amp', 'Zuridry', 'ZURICH', 10.00, 'mg', 10.00, 10.00, 1, NULL, NULL, 'Temperatura ambiente / Protegido de la luz', 'Agregar 10 ml de agua inyectable estéril o solución salina al 0.9%, agitar el frasco suavemente hasta que el polvo se disuelva por completo y forme una solución transparente.', NULL, NULL, 48, '2026-07-20 18:29:29', '2026-07-20 18:29:29'),
(159, 50, 'Fco amp', 'Roseunov', 'KEMEX', 10.00, 'mg', 10.00, 10.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar 10 ml de cloruro de sodio al 0.9%, agitar el frasco suavemente hasta que el polvo se disuelva y forme una solución transparente.', 2, 8, 48, '2026-07-20 19:10:12', '2026-07-20 19:10:12'),
(160, 35, 'Fco amp', 'Remsima', 'CELLTRION', 100.00, 'mg', 100.00, 10.00, 1, NULL, NULL, '24 h Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar 10 ml de agua estéril, dirigiendo el chorro suavemente hacia la pared del vial. Girar el vial suavemente para disolver el polvo liofilizado. Evitar por completo la agitación prolongada o vigorosa.', 2, 8, 24, '2026-07-20 19:31:21', '2026-07-20 19:31:21'),
(161, 48, 'Fco amp', 'Trazimera', 'PFIZER', 440.00, 'mg', 440.00, 20.00, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'Agregar 20 ml de diluyente propio, dirigiendo el chorro lentamente hacia la pared de vidrio del vial. Rotar el frasco con suavidad mediante movimientos circulares hasta que el liofilizado se disuelva por completo. NO AGITAR EL VIAL.', 2, 8, 48, '2026-07-20 20:17:25', '2026-07-20 20:17:25'),
(162, 3, 'Fco amp', 'Lemtrada', 'ZANOFIL', 12.00, 'mg', 12.00, 1.20, 1, NULL, NULL, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', NULL, 2, 8, 48, '2026-07-20 20:26:26', '2026-07-20 20:26:26'),
(163, 29, 'Fco amp', 'Taxotere', 'SANOFI', 80.00, 'mg', 80.00, 4.00, 1, NULL, NULL, 'Temperatura ambiente, 4hrs / Protegido de la luz', NULL, NULL, NULL, 4, '2026-07-20 20:35:39', '2026-07-20 20:35:39'),
(164, 38, 'Fco amp', 'Ulmextral 50', 'ZURICH', 50.00, 'mg', 50.00, 2.00, 1, NULL, NULL, 'Temperatura ambiente \r\nProtegido de la luz', 'Agregar 2 ml de agua inyectable estéril, agitar el frasco suavemente hasta que el polvo se disuelva por completo y forme una solución transparente.', NULL, NULL, 48, '2026-07-22 16:00:42', '2026-07-22 16:00:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicine_stock_movements`
--

CREATE TABLE `medicine_stock_movements` (
  `id` bigint UNSIGNED NOT NULL,
  `medicine_laboratory_stock_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `tipo` enum('entrada','salida','merma','ajuste') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad_ml` decimal(12,2) NOT NULL,
  `stock_antes` decimal(12,2) NOT NULL,
  `stock_despues` decimal(12,2) NOT NULL,
  `cantidad_frascos` decimal(12,2) DEFAULT NULL,
  `frascos_antes` decimal(12,2) DEFAULT NULL,
  `frascos_despues` decimal(12,2) DEFAULT NULL,
  `reference_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint UNSIGNED DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `medicine_stock_movements`
--

INSERT INTO `medicine_stock_movements` (`id`, `medicine_laboratory_stock_id`, `user_id`, `tipo`, `cantidad_ml`, `stock_antes`, `stock_despues`, `cantidad_frascos`, `frascos_antes`, `frascos_despues`, `reference_type`, `reference_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 'entrada', 16250.00, 0.00, 16250.00, 65.00, 0.00, 65.00, 'IngresoInventarioNutricional', 1, 'INICIO DE INVENTARIO', '2026-07-07 16:12:02', '2026-07-07 16:12:02'),
(2, 2, 4, 'entrada', 1300.00, 0.00, 1300.00, 13.00, 0.00, 13.00, 'IngresoInventarioNutricional', 2, 'Ingreso de inventario nutricional', '2026-07-07 16:21:41', '2026-07-07 16:21:41'),
(3, 3, 4, 'entrada', 350.00, 0.00, 350.00, 7.00, 0.00, 7.00, 'IngresoInventarioNutricional', 3, 'Ingreso de inventario nutricional', '2026-07-07 16:59:18', '2026-07-07 16:59:18'),
(4, 4, 4, 'entrada', 900.00, 0.00, 900.00, 18.00, 0.00, 18.00, 'IngresoInventarioNutricional', 4, 'Ingreso de inventario nutricional', '2026-07-07 17:01:48', '2026-07-07 17:01:48'),
(5, 5, 4, 'entrada', 32000.00, 0.00, 32000.00, 64.00, 0.00, 64.00, 'IngresoInventarioNutricional', 5, 'Ingreso de inventario nutricional', '2026-07-07 18:46:16', '2026-07-07 18:46:16'),
(6, 6, 4, 'entrada', 23000.00, 0.00, 23000.00, 46.00, 0.00, 46.00, 'IngresoInventarioNutricional', 6, 'Ingreso de inventario nutricional', '2026-07-07 18:48:31', '2026-07-07 18:48:31'),
(7, 7, 4, 'entrada', 27500.00, 0.00, 27500.00, 55.00, 0.00, 55.00, 'IngresoInventarioNutricional', 7, 'Ingreso de inventario nutricional', '2026-07-07 19:00:41', '2026-07-07 19:00:41'),
(8, 8, 4, 'entrada', 10000.00, 0.00, 10000.00, 20.00, 0.00, 20.00, 'IngresoInventarioNutricional', 8, 'Ingreso de inventario nutricional', '2026-07-07 19:03:42', '2026-07-07 19:03:42'),
(9, 9, 4, 'entrada', 11000.00, 0.00, 11000.00, 22.00, 0.00, 22.00, 'IngresoInventarioNutricional', 9, 'Ingreso de inventario nutricional', '2026-07-07 19:05:46', '2026-07-07 19:05:46'),
(10, 10, 4, 'entrada', 170.00, 0.00, 170.00, 34.00, 0.00, 34.00, 'IngresoInventarioNutricional', 10, 'Ingreso de inventario nutricional', '2026-07-07 19:07:35', '2026-07-07 19:07:35'),
(11, 11, 4, 'entrada', 1060.00, 0.00, 1060.00, 212.00, 0.00, 212.00, 'IngresoInventarioNutricional', 11, 'Ingreso de inventario nutricional', '2026-07-07 19:09:10', '2026-07-07 19:09:10'),
(12, 12, 4, 'entrada', 495.00, 0.00, 495.00, 99.00, 0.00, 99.00, 'IngresoInventarioNutricional', 12, 'Ingreso de inventario nutricional', '2026-07-07 19:10:04', '2026-07-07 19:10:04'),
(13, 13, 4, 'entrada', 50.00, 0.00, 50.00, 10.00, 0.00, 10.00, 'IngresoInventarioNutricional', 13, 'Ingreso de inventario nutricional', '2026-07-07 19:10:44', '2026-07-07 19:10:44'),
(14, 14, 4, 'entrada', 120.00, 0.00, 120.00, 24.00, 0.00, 24.00, 'IngresoInventarioNutricional', 14, 'Ingreso de inventario nutricional', '2026-07-07 19:12:04', '2026-07-07 19:12:04'),
(15, 15, 4, 'entrada', 740.00, 0.00, 740.00, 74.00, 0.00, 74.00, 'IngresoInventarioNutricional', 15, 'Ingreso de inventario nutricional', '2026-07-07 19:34:28', '2026-07-07 19:34:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `messages`
--

CREATE TABLE `messages` (
  `id` bigint UNSIGNED NOT NULL,
  `sender_id` bigint UNSIGNED NOT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `subject`, `body`, `created_at`, `updated_at`) VALUES
(1, 1, 'Hay una nueva solicitud', 'CBTA', '2026-05-19 11:01:02', '2026-05-19 11:01:02'),
(2, 1, 'Hay una nueva solicitud', 'CBTA', '2026-05-19 20:15:03', '2026-05-19 20:15:03'),
(3, 6, 'Hay una nueva solicitud', 'HOSPITAL SAN DIEGO', '2026-06-24 20:11:50', '2026-06-24 20:11:50'),
(4, 5, 'Hay una nueva solicitud', 'ANGELES METROPOLITANO', '2026-06-30 19:18:32', '2026-06-30 19:18:32'),
(5, 6, 'Hay una nueva solicitud', 'HOSPITAL SAN DIEGO', '2026-07-02 22:42:35', '2026-07-02 22:42:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mezclas`
--

CREATE TABLE `mezclas` (
  `id` bigint UNSIGNED NOT NULL,
  `solicitud_id` bigint UNSIGNED NOT NULL,
  `estado` enum('pendiente','aprobada','preparada','revisada','cancelada','entregada') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remision` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lote` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `volumen_dilucion` decimal(8,2) NOT NULL,
  `diluent_presentation_id` bigint UNSIGNED DEFAULT NULL,
  `tiempo_infusion` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `set_infusion` tinyint(1) NOT NULL DEFAULT '0',
  `infusor_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mezclas`
--

INSERT INTO `mezclas` (`id`, `solicitud_id`, `estado`, `remision`, `lote`, `volumen_dilucion`, `diluent_presentation_id`, `tiempo_infusion`, `set_infusion`, `infusor_id`, `created_at`, `updated_at`) VALUES
(1, 1, 'aprobada', '1', 'L28MAY26001', 100.00, 5, '120', 0, NULL, '2026-05-29 05:33:32', '2026-05-29 06:02:35'),
(2, 2, 'revisada', '2', 'L04JUN26001', 100.00, 5, '120', 0, NULL, '2026-06-04 19:29:49', '2026-06-24 20:25:03'),
(3, 3, 'revisada', '3', 'L24JUN26001', 250.00, 3, '60', 0, NULL, '2026-06-24 23:40:27', '2026-06-24 19:21:50'),
(4, 5, 'aprobada', '5', NULL, 250.00, 3, '60', 0, NULL, '2026-07-21 15:12:12', '2026-07-28 19:14:43'),
(5, 5, 'aprobada', '5', NULL, 250.00, 3, '60', 0, NULL, '2026-07-21 15:12:12', '2026-07-28 19:14:43'),
(6, 5, 'preparada', '5', 'L28JUL26001', 250.00, 3, '30', 0, NULL, '2026-07-21 15:12:12', '2026-07-28 19:14:43'),
(7, 5, 'pendiente', '5', NULL, 250.00, NULL, '60', 0, NULL, '2026-07-21 15:12:12', '2026-07-28 19:14:43'),
(8, 6, 'revisada', '4', 'L21JUL26001', 500.00, 9, '120', 0, NULL, '2026-07-21 15:17:57', '2026-07-21 18:49:28'),
(9, 7, 'pendiente', NULL, NULL, 500.00, NULL, '120', 0, NULL, '2026-07-21 15:22:27', '2026-07-21 15:22:27'),
(15, 10, 'pendiente', NULL, NULL, 250.00, NULL, '120', 0, NULL, '2026-07-21 16:06:51', '2026-07-21 16:06:51'),
(16, 10, 'pendiente', NULL, NULL, 250.00, NULL, '120', 0, NULL, '2026-07-21 16:06:51', '2026-07-21 16:06:51'),
(17, 10, 'pendiente', NULL, NULL, 100.00, NULL, '4', 0, NULL, '2026-07-21 16:06:51', '2026-07-21 16:06:51'),
(18, 10, 'pendiente', NULL, NULL, 1000.00, NULL, '1380', 0, NULL, '2026-07-21 16:06:51', '2026-07-21 16:06:51'),
(19, 11, 'aprobada', NULL, 'L24JUL26001', 100.00, 3, '60', 0, NULL, '2026-07-22 15:58:11', '2026-07-24 11:59:13'),
(20, 11, 'aprobada', NULL, 'L24JUL26001', 100.00, 3, '60', 0, NULL, '2026-07-22 15:58:11', '2026-07-24 11:59:37'),
(21, 12, 'pendiente', NULL, NULL, 3.00, NULL, '5', 0, NULL, '2026-07-22 22:46:21', '2026-07-22 22:46:21'),
(28, 16, 'aprobada', NULL, 'L24JUL26001', 250.00, 3, '30', 0, NULL, '2026-07-23 12:15:16', '2026-07-24 11:56:46'),
(29, 16, 'aprobada', NULL, 'L24JUL26001', 500.00, 4, '30', 0, NULL, '2026-07-23 12:15:16', '2026-07-24 11:57:11'),
(30, 17, 'pendiente', NULL, NULL, 500.00, 9, '60', 0, NULL, '2026-07-24 12:07:47', '2026-07-24 16:15:46'),
(31, 18, 'pendiente', NULL, NULL, 250.00, NULL, '60', 0, NULL, '2026-07-28 18:59:28', '2026-07-28 18:59:28'),
(32, 19, 'pendiente', NULL, NULL, 250.00, NULL, '120', 0, NULL, '2026-07-30 12:01:24', '2026-07-30 12:01:24'),
(33, 19, 'pendiente', NULL, NULL, 250.00, NULL, '120', 0, NULL, '2026-07-30 12:01:24', '2026-07-30 12:01:24'),
(34, 19, 'pendiente', NULL, NULL, 100.00, NULL, '240', 0, NULL, '2026-07-30 12:01:24', '2026-07-30 12:01:24'),
(35, 19, 'pendiente', NULL, NULL, 1000.00, 7, '1380', 0, NULL, '2026-07-30 12:01:24', '2026-07-30 12:05:06'),
(36, 20, 'revisada', '6', 'L01AGO26001', 500.00, 9, '120', 0, NULL, '2026-07-30 12:08:56', '2026-08-01 15:14:40'),
(37, 23, 'revisada', '7', 'L01AGO26001', 700.00, 7, '240', 0, NULL, '2026-08-01 15:46:58', '2026-08-01 20:23:07'),
(38, 24, 'pendiente', NULL, NULL, 250.00, NULL, '15', 0, NULL, '2026-08-01 20:23:22', '2026-08-01 20:23:22'),
(39, 24, 'pendiente', NULL, NULL, 400.00, NULL, '60', 0, NULL, '2026-08-01 20:23:22', '2026-08-01 20:23:22'),
(40, 26, 'pendiente', NULL, NULL, 100.00, NULL, '60', 0, NULL, '2026-08-01 20:54:26', '2026-08-01 20:54:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mezcla_medicamentos`
--

CREATE TABLE `mezcla_medicamentos` (
  `id` bigint UNSIGNED NOT NULL,
  `mezcla_id` bigint UNSIGNED NOT NULL,
  `medicamento_id` bigint UNSIGNED DEFAULT NULL,
  `nombre_medicamento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `denominacion_snapshot` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marca_snapshot` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requires_infusor_snapshot` tinyint(1) NOT NULL DEFAULT '0',
  `conc_min_snapshot` decimal(10,4) DEFAULT NULL,
  `conc_max_snapshot` decimal(10,4) DEFAULT NULL,
  `dosis` decimal(8,2) DEFAULT NULL,
  `dosis_ml` decimal(8,2) DEFAULT NULL,
  `diluyente_id` bigint UNSIGNED DEFAULT NULL,
  `via_administracion_id` bigint UNSIGNED DEFAULT NULL,
  `charge_by` enum('mg','frasco') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mg',
  `precio_mg_snapshot` decimal(12,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mezcla_medicamentos`
--

INSERT INTO `mezcla_medicamentos` (`id`, `mezcla_id`, `medicamento_id`, `nombre_medicamento`, `denominacion_snapshot`, `marca_snapshot`, `requires_infusor_snapshot`, `conc_min_snapshot`, `conc_max_snapshot`, `dosis`, `dosis_ml`, `diluyente_id`, `via_administracion_id`, `charge_by`, `precio_mg_snapshot`, `created_at`, `updated_at`) VALUES
(4, 1, 1, 'Bevacizumab', 'Bevacizumab', 'Effivia', 0, 1.4000, 16.4000, 930.00, 64.00, 1, 1, 'frasco', NULL, '2026-05-29 06:02:35', '2026-05-29 06:02:35'),
(6, 2, 1, 'Bevacizumab', 'Bevacizumab', 'Effivia', 0, 1.4000, 16.4000, 550.00, 48.00, 1, 1, 'frasco', NULL, '2026-06-04 19:50:36', '2026-06-04 19:50:36'),
(8, 3, 2, 'Docetaxel', 'Docetaxel', 'Bindu', 0, 0.0000, 0.7400, 120.00, 8.00, 1, 1, 'frasco', NULL, '2026-06-24 23:58:30', '2026-06-24 23:58:30'),
(12, 7, 5, 'Carboplatino', 'Carboplatino', NULL, 0, 0.5000, 4.0000, 454.00, NULL, 4, 1, 'frasco', NULL, '2026-07-21 15:12:12', '2026-07-21 15:12:12'),
(14, 9, 7, 'Ciclofosfamida', 'Ciclofosfamida', NULL, 0, 2.0000, 40.0000, 1000.00, NULL, 4, 1, 'frasco', NULL, '2026-07-21 15:22:27', '2026-07-21 15:22:27'),
(19, 15, 11, 'Acido Folinico', 'Acido Folinico', NULL, 0, 0.1000, 10.0000, 610.00, NULL, 4, 1, 'frasco', NULL, '2026-07-21 16:06:51', '2026-07-21 16:06:51'),
(20, 16, 6, 'Oxaliplatino', 'Oxaliplatino', NULL, 0, 0.2000, 2.0000, 130.00, NULL, 4, 1, 'frasco', NULL, '2026-07-21 16:06:51', '2026-07-21 16:06:51'),
(21, 17, 12, 'Fluorouracilo', 'Fluorouracilo', NULL, 1, 1.0000, 10.0000, 610.00, NULL, 1, 1, 'frasco', NULL, '2026-07-21 16:06:51', '2026-07-21 16:06:51'),
(22, 18, 12, 'Fluorouracilo', 'Fluorouracilo', NULL, 1, 1.0000, 10.0000, 1800.00, NULL, 1, 1, 'frasco', NULL, '2026-07-21 16:06:51', '2026-07-21 16:06:51'),
(23, 4, 3, 'Pertuzumab', 'Pertuzumab', 'Perjeta', 0, 1.5900, 3.0000, 420.00, 14.00, 1, 1, 'frasco', NULL, '2026-07-21 16:58:57', '2026-07-21 16:58:57'),
(24, 6, 2, 'Docetaxel', 'Docetaxel', 'Taxanit', 0, 0.0000, 0.7400, 97.00, 8.00, 1, 1, 'frasco', NULL, '2026-07-21 17:03:14', '2026-07-21 17:03:14'),
(25, 8, 6, 'Oxaliplatino', 'Oxaliplatino', 'Kemoxa', 0, 0.2000, 2.0000, 200.00, 40.00, 4, 1, 'frasco', NULL, '2026-07-21 18:05:20', '2026-07-21 18:05:20'),
(26, 5, 4, 'Traztuzumab', 'Traztuzumab', 'Trazimera', 0, 1.0000, 8.0000, 330.00, 20.00, 1, 1, 'frasco', NULL, '2026-07-21 22:27:45', '2026-07-21 22:27:45'),
(32, 21, 13, 'Citarabina', 'Citarabina', NULL, 0, 0.1000, 32.0000, 15.00, NULL, 6, 5, 'frasco', NULL, '2026-07-22 22:46:21', '2026-07-22 22:46:21'),
(33, 21, 14, 'Metotrexato', 'Metotrexato', NULL, 0, 0.2200, 24.0000, 30.00, NULL, 6, 5, 'frasco', NULL, '2026-07-22 22:46:21', '2026-07-22 22:46:21'),
(39, 28, 18, 'Doxorubicina', 'Doxorubicina', 'Zodox', 0, 0.0100, 2.0000, 80.00, 50.00, 1, 1, 'frasco', NULL, '2026-07-24 11:56:46', '2026-07-24 11:56:46'),
(40, 29, 7, 'Ciclofosfamida', 'Ciclofosfamida', 'Mexcikem', 0, 1.0000, 40.0000, 800.00, 50.00, 1, 1, 'frasco', NULL, '2026-07-24 11:57:11', '2026-07-24 11:57:11'),
(41, 19, 13, 'Citarabina', 'Citarabina', 'Zuphacit', 0, 0.1000, 32.0000, 370.00, 10.00, 1, 1, 'frasco', NULL, '2026-07-24 11:59:13', '2026-07-24 11:59:13'),
(42, 20, 7, 'Ciclofosfamida', 'Ciclofosfamida', 'Mexcikem', 0, 1.0000, 40.0000, 370.00, 20.00, 1, 1, 'frasco', NULL, '2026-07-24 11:59:37', '2026-07-24 11:59:37'),
(44, 30, 6, 'Oxaliplatino', 'Oxaliplatino', 'Kemoxa', 0, 0.2000, 2.0000, 230.00, 50.00, 4, 1, 'frasco', NULL, '2026-07-24 16:15:46', '2026-07-24 16:15:46'),
(45, 31, 2, 'Docetaxel', 'Docetaxel', NULL, 0, 0.0000, 0.7400, 100.00, NULL, 1, 1, 'frasco', NULL, '2026-07-28 18:59:28', '2026-07-28 18:59:28'),
(46, 32, 6, 'Oxaliplatino', 'Oxaliplatino', NULL, 0, 0.2000, 2.0000, 130.00, NULL, 4, 1, 'frasco', NULL, '2026-07-30 12:01:24', '2026-07-30 12:01:24'),
(47, 33, 11, 'Acido Folinico', 'Acido Folinico', NULL, 0, 0.1000, 10.0000, 610.00, NULL, 4, 1, 'frasco', NULL, '2026-07-30 12:01:24', '2026-07-30 12:01:24'),
(48, 34, 12, 'Fluorouracilo', 'Fluorouracilo', NULL, 1, 1.0000, 10.0000, 610.00, NULL, 1, 1, 'frasco', NULL, '2026-07-30 12:01:24', '2026-07-30 12:01:24'),
(50, 35, 12, 'Fluorouracilo', 'Fluorouracilo', 'Fuoavil', 1, 1.0000, 10.0000, 1800.00, 80.00, 1, 1, 'frasco', NULL, '2026-07-30 12:05:06', '2026-07-30 12:05:06'),
(53, 36, 7, 'Ciclofosfamida', 'Ciclofosfamida', 'Mexcikem', 0, 1.0000, 40.0000, 1000.00, 50.00, 4, 1, 'frasco', NULL, '2026-08-01 15:13:50', '2026-08-01 15:13:50'),
(55, 37, 19, 'Rituximab', 'Rituximab', 'Ruxience', 0, 1.0000, 4.0000, 700.00, 70.00, 1, 1, 'frasco', NULL, '2026-08-01 15:53:49', '2026-08-01 15:53:49'),
(56, 38, 18, 'Doxorubicina', 'Doxorubicina', NULL, 0, 0.0100, 2.0000, 90.00, NULL, 1, 1, 'frasco', NULL, '2026-08-01 20:23:22', '2026-08-01 20:23:22'),
(57, 39, 7, 'Ciclofosfamida', 'Ciclofosfamida', NULL, 0, 1.0000, 40.0000, 900.00, NULL, 1, 1, 'frasco', NULL, '2026-08-01 20:23:22', '2026-08-01 20:23:22'),
(58, 40, 20, 'Pembrolizumab', 'Pembrolizumab', NULL, 0, 1.0000, 10.0000, 200.00, NULL, 1, 1, 'frasco', NULL, '2026-08-01 20:54:26', '2026-08-01 20:54:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mezcla_medicamento_presentaciones`
--

CREATE TABLE `mezcla_medicamento_presentaciones` (
  `id` bigint UNSIGNED NOT NULL,
  `mezcla_medicamento_id` bigint UNSIGNED NOT NULL,
  `medicine_batch_id` bigint UNSIGNED NOT NULL,
  `unidades_usadas` int NOT NULL DEFAULT '1',
  `presentacion_snapshot` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cantidad_medicamento_snapshot` decimal(10,4) DEFAULT NULL,
  `volumen_diluyente_snapshot` decimal(10,4) DEFAULT NULL,
  `legend_snapshot` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lote_usado` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `caducidad_usada` date NOT NULL,
  `precio_frasco_snapshot` decimal(12,4) DEFAULT NULL,
  `subtotal` decimal(12,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mezcla_medicamento_presentaciones`
--

INSERT INTO `mezcla_medicamento_presentaciones` (`id`, `mezcla_medicamento_id`, `medicine_batch_id`, `unidades_usadas`, `presentacion_snapshot`, `cantidad_medicamento_snapshot`, `volumen_diluyente_snapshot`, `legend_snapshot`, `lote_usado`, `caducidad_usada`, `precio_frasco_snapshot`, `subtotal`, `created_at`, `updated_at`) VALUES
(5, 4, 3, 2, 'Fco amp 100mg/4mL', 100.0000, 16.0000, 'Proteger de la luz.\r\nMantener en refrigeración de 2° a 8°', '250177', '2027-05-31', 0.0000, 0.0000, '2026-05-29 06:02:35', '2026-05-29 06:02:35'),
(6, 4, 2, 2, 'Fco amp 400mg/16mL', 400.0000, 16.0000, 'Proteger de la Luz.\r\nMantener en refrigeración de 2° a 8° C.', '250358', '2028-07-31', 0.0000, 0.0000, '2026-05-29 06:02:35', '2026-05-29 06:02:35'),
(7, 6, 3, 2, 'Fco amp 100mg/4mL', 100.0000, 16.0000, 'Proteger de la luz.\r\nMantener en refrigeración de 2° a 8°', '250177', '2027-05-31', 0.0000, 0.0000, '2026-06-04 19:50:36', '2026-06-04 19:50:36'),
(8, 6, 2, 1, 'Fco amp 400mg/16mL', 400.0000, 16.0000, 'Proteger de la Luz.\r\nMantener en refrigeración de 2° a 8° C.', '250358', '2028-07-31', 0.0000, 0.0000, '2026-06-04 19:50:36', '2026-06-04 19:50:36'),
(9, 8, 5, 2, 'Fco amp 80 mg/ 4 mL', 80.0000, 4.0000, 'Temperatura ambiente, 6hrs / Protegido de la luz', '0260048A', '2027-08-09', 12420.0000, 24840.0000, '2026-06-24 23:58:30', '2026-06-24 23:58:30'),
(10, 23, 70, 1, 'Fco amp', 420.0000, 14.0000, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'H8048B09', '2027-06-14', 202865.0000, 202865.0000, '2026-07-21 16:58:57', '2026-07-21 16:58:57'),
(11, 24, 21, 2, 'Fco amp 80mg/4ml', 80.0000, 4.0000, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'M2502307', '2027-02-28', 12420.0000, 24840.0000, '2026-07-21 17:03:14', '2026-07-21 17:03:14'),
(12, 25, 41, 2, 'Fco amp 100mg/ml', 100.0000, 20.0000, NULL, '06527', '2027-07-31', 9230.0000, 18460.0000, '2026-07-21 18:05:20', '2026-07-21 18:05:20'),
(13, 26, 82, 1, 'Fco amp', 440.0000, 20.0000, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'MW4564', '2029-01-31', 90000.0000, 90000.0000, '2026-07-21 22:27:45', '2026-07-21 22:27:45'),
(14, 39, 47, 2, 'Fco amp 50mg/25mL', 50.0000, 25.0000, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'P2502488', '2027-02-28', 4995.0000, 9990.0000, '2026-07-24 11:56:46', '2026-07-24 11:56:46'),
(15, 40, 16, 1, 'Fco amp 1000mg', 1000.0000, 50.0000, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', '06809', '2027-10-31', 10980.0000, 10980.0000, '2026-07-24 11:57:11', '2026-07-24 11:57:11'),
(16, 41, 30, 1, 'Fco amp 500mg', 500.0000, 10.0000, 'Temperatura ambiente / Protegido de la luz', '1L25072', '2027-04-30', 3800.0000, 3800.0000, '2026-07-24 11:59:13', '2026-07-24 11:59:13'),
(17, 42, 14, 2, 'Fco amp 200mg', 200.0000, 10.0000, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', '06999', '2028-01-31', 2990.0000, 5980.0000, '2026-07-24 11:59:37', '2026-07-24 11:59:37'),
(18, 44, 41, 2, 'Fco amp 100mg/ml', 100.0000, 20.0000, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', '06527', '2027-07-31', 9230.0000, 18460.0000, '2026-07-24 16:15:46', '2026-07-24 16:15:46'),
(19, 44, 42, 1, 'Fco amp 50mg/ml', 50.0000, 10.0000, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', '06212', '2027-02-28', 4490.0000, 4490.0000, '2026-07-24 16:15:46', '2026-07-24 16:15:46'),
(20, 50, 34, 8, 'Fco amp 250mg/10ml', 250.0000, 10.0000, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', '1L25110', '2027-06-30', 1995.0000, 15960.0000, '2026-07-30 12:05:06', '2026-07-30 12:05:06'),
(22, 53, 16, 1, 'Fco amp 1000mg', 1000.0000, 50.0000, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', '06809', '2027-10-31', 10980.0000, 10980.0000, '2026-08-01 15:13:50', '2026-08-01 15:13:50'),
(23, 55, 69, 1, 'Fco amp', 500.0000, 50.0000, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'MC1299', '2026-11-30', 53158.7500, 53158.7500, '2026-08-01 15:53:49', '2026-08-01 15:53:49'),
(24, 55, 68, 2, 'Fco amp 100mg/10mL', 100.0000, 10.0000, 'Temperatura de refrigeración 2-8°C \r\nProtegido de la luz', 'MT8951', '2027-06-30', 10632.0000, 21264.0000, '2026-08-01 15:53:49', '2026-08-01 15:53:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(3, '2014_10_12_200000_add_two_factor_columns_to_users_table', 1),
(4, '2019_08_19_000000_create_failed_jobs_table', 1),
(5, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(6, '2023_12_27_225315_create_sessions_table', 1),
(7, '2024_01_10_035338_create_hospitals_table', 1),
(8, '2024_01_14_063113_create_categories_table', 1),
(9, '2024_02_05_235617_add_hospital_to_users_table', 1),
(10, '2024_02_21_051425_create_inputs_table', 1),
(11, '2024_02_21_052014_create_medicines_table', 1),
(12, '2024_02_21_052653_create_solicitud_details_table', 1),
(13, '2024_02_21_052921_create_solicitud_patients_table', 1),
(14, '2024_02_21_060204_create_solicituds_table', 1),
(15, '2024_02_21_060416_create_solicitud_inputs_table', 1),
(16, '2024_02_25_174406_alter_unidad_to_inputs_table', 1),
(17, '2024_02_28_130541_alter_peso_to_solicitud_patients_table', 1),
(18, '2024_03_04_021443_alter_mult_to_inputs_table', 1),
(19, '2024_03_09_203940_alter_div_to_inputs_table', 1),
(20, '2024_03_09_235538_alter_sobrellenado_to_solicitud_details_table', 1),
(21, '2024_03_27_031619_alter_precio_ml_to_solicitud_inputs_table', 1),
(22, '2024_04_08_024943_alter_tiempo_infusion_min_to_solicitud_detail_table', 1),
(23, '2024_04_11_013606_create_permission_tables', 1),
(24, '2024_04_30_025645_add_velocidad_infusion_to_solicitud_details_table', 1),
(25, '2024_05_21_100159_create_messages_table', 1),
(26, '2024_05_21_124030_create_notifications_table', 1),
(27, '2024_05_23_133117_add_notification_to_users_table', 1),
(28, '2024_05_24_134053_add_read_at_to_solicituds_table', 1),
(29, '2024_12_22_202432_add_hospital_destino_to_solicitud_details_table', 1),
(30, '2025_05_23_123500_create_medicines_catalogs_table', 1),
(31, '2025_05_23_123600_create_diluents_table', 1),
(32, '2025_05_23_123700_create_administration_routes_table', 1),
(33, '2025_05_23_123800_create_diluent_medicine_catalog_table', 1),
(34, '2025_05_23_123811_create_medicine_oncos_table', 1),
(35, '2025_05_23_123900_create_administration_route_medicine_catalog_table', 1),
(36, '2025_05_23_123900_create_solicitud_oncos_table', 1),
(37, '2025_05_23_124000_create_medicine_lists_table', 1),
(38, '2025_05_23_124100_create_infusors_table', 1),
(39, '2025_05_23_124100_create_medicine_medicine_lists_table', 1),
(40, '2025_05_23_124150_create_mezclas_table', 1),
(41, '2025_05_23_124210_create_inspeccion_mezclas_table', 1),
(42, '2025_05_23_124350_create_mezcla_medicamentos_table', 1),
(43, '2025_08_25_162118_create_diluent_presentations_table', 1),
(44, '2025_11_06_183121_create_medicine_presentations_table', 1),
(45, '2025_11_06_183220_create_laboratories_table', 1),
(46, '2025_11_06_183223_create_medicine_batches_table', 1),
(47, '2025_11_06_183256_create_mezcla_medicamento_presentaciones_table', 1),
(48, '2025_12_02_192444_create_medicine_list_presentation_table', 1),
(49, '2025_12_05_012950_add_diluent_presentation_id_to_mezclas_table', 1),
(50, '2025_12_15_221708_create_distributors_table', 1),
(51, '2026_01_15_130517_add_precio_to_infusors_table', 1),
(52, '2026_01_29_231047_drop_denominacion_comercial_from_medicines_catalog_table', 1),
(53, '2026_02_06_201859_create_clientes_table', 1),
(54, '2026_02_06_214504_create_cliente_hospital_table', 1),
(55, '2026_03_03_004410_add_laboratory_id_to_hospitals_table', 1),
(56, '2026_03_05_232105_create_medicine_batch_movements_table', 1),
(57, '2026_03_17_224700_create_nutrition_medicines_catalog_table', 1),
(58, '2026_03_17_224710_create_nutrition_medicine_presentations_table', 1),
(59, '2026_03_17_224720_create_nutrition_laboratory_active_presentations_table', 1),
(60, '2026_03_17_224820_create_medicine_laboratory_stocks_table', 1),
(61, '2026_03_17_224827_create_medicine_stock_movements_table', 1),
(62, '2026_03_17_225700_create_nutri_medicine_lists_table', 1),
(63, '2026_03_17_225819_create_nutri_medicine_list_items_table', 1),
(64, '2026_03_17_231943_add_nutri_medicine_list_id_to_hospitals_table', 1),
(65, '2026_03_30_215436_add_onco_medicine_list_id_to_hospitals_table', 1),
(66, '2026_04_29_210345_add_presentation_to_solicitud_inputs_table', 1),
(67, '2026_05_13_161239_create_inspeccion_nutricionales_table', 1),
(68, '2026_06_18_214721_add_unique_indexes_to_medicine_presentations', 1),
(69, '2026_06_18_232621_update_unique_index_for_nutrition_medicine_presentations', 1),
(70, '2026_07_01_000001_add_active_brands_to_nutri_medicine_lists', 1),
(71, '2026_07_01_000002_create_nutri_distributors_table', 1),
(72, '2026_07_07_000001_allow_multiple_nutrition_medicines_per_input', 2),
(73, '2026_07_07_000002_restore_unique_nutrition_medicine_input', 2),
(74, '2026_07_21_000001_add_inventory_to_diluent_presentations', 3),
(75, '2026_07_28_120000_add_show_label_lot_expiry_to_medicine_lists_table', 4),
(76, '2026_07_28_180000_migrate_cliente_role_to_institucion', 4),
(77, '2026_07_29_120000_add_rfc_and_telefono_to_clientes_table', 4),
(78, '2026_07_29_130000_create_institution_billings_table', 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(3, 'App\\Models\\User', 1),
(1, 'App\\Models\\User', 3),
(3, 'App\\Models\\User', 4),
(4, 'App\\Models\\User', 5),
(4, 'App\\Models\\User', 6),
(1, 'App\\Models\\User', 7),
(1, 'App\\Models\\User', 8),
(1, 'App\\Models\\User', 9),
(3, 'App\\Models\\User', 10),
(1, 'App\\Models\\User', 11),
(4, 'App\\Models\\User', 12),
(4, 'App\\Models\\User', 13),
(4, 'App\\Models\\User', 14),
(4, 'App\\Models\\User', 15),
(4, 'App\\Models\\User', 16),
(4, 'App\\Models\\User', 17),
(1, 'App\\Models\\User', 18),
(1, 'App\\Models\\User', 19),
(1, 'App\\Models\\User', 20),
(4, 'App\\Models\\User', 21),
(4, 'App\\Models\\User', 22),
(4, 'App\\Models\\User', 23),
(4, 'App\\Models\\User', 24),
(4, 'App\\Models\\User', 25),
(4, 'App\\Models\\User', 26),
(4, 'App\\Models\\User', 27),
(4, 'App\\Models\\User', 28),
(4, 'App\\Models\\User', 29),
(4, 'App\\Models\\User', 30),
(4, 'App\\Models\\User', 31),
(4, 'App\\Models\\User', 32),
(4, 'App\\Models\\User', 33),
(4, 'App\\Models\\User', 34),
(4, 'App\\Models\\User', 35),
(4, 'App\\Models\\User', 36),
(4, 'App\\Models\\User', 37),
(4, 'App\\Models\\User', 38),
(4, 'App\\Models\\User', 39),
(4, 'App\\Models\\User', 40),
(4, 'App\\Models\\User', 41),
(4, 'App\\Models\\User', 42),
(4, 'App\\Models\\User', 43),
(4, 'App\\Models\\User', 44),
(4, 'App\\Models\\User', 45),
(4, 'App\\Models\\User', 46),
(4, 'App\\Models\\User', 47),
(4, 'App\\Models\\User', 48),
(4, 'App\\Models\\User', 49),
(4, 'App\\Models\\User', 50),
(4, 'App\\Models\\User', 51),
(4, 'App\\Models\\User', 52),
(1, 'App\\Models\\User', 53),
(1, 'App\\Models\\User', 54),
(4, 'App\\Models\\User', 55),
(1, 'App\\Models\\User', 56),
(1, 'App\\Models\\User', 57),
(1, 'App\\Models\\User', 58),
(1, 'App\\Models\\User', 59),
(1, 'App\\Models\\User', 60);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint UNSIGNED NOT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nutrition_laboratory_active_presentations`
--

CREATE TABLE `nutrition_laboratory_active_presentations` (
  `id` bigint UNSIGNED NOT NULL,
  `laboratory_id` bigint UNSIGNED NOT NULL,
  `nutrition_medicine_catalog_id` bigint UNSIGNED NOT NULL,
  `nutrition_medicine_presentation_id` bigint UNSIGNED NOT NULL,
  `selected_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nutrition_medicines_catalog`
--

CREATE TABLE `nutrition_medicines_catalog` (
  `id` bigint UNSIGNED NOT NULL,
  `denominacion_generica` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` bigint UNSIGNED NOT NULL,
  `input_id` bigint UNSIGNED NOT NULL,
  `osmolaridad` decimal(12,4) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `nutrition_medicines_catalog`
--

INSERT INTO `nutrition_medicines_catalog` (`id`, `denominacion_generica`, `category_id`, `input_id`, `osmolaridad`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'AMINOACIDOS ESTANDAR AL 10%', 1, 4, 1021.0000, 1, '2024-03-14 16:29:02', '2026-05-19 06:30:40'),
(2, 'AMINOACIDOS PEDIATRICOS 10%', 1, 5, 1021.0000, 1, '2024-03-14 16:33:20', '2026-05-19 06:38:07'),
(3, 'AMINOACIDOS CRISTALINOS AL 8.5%', 1, 6, 60.0000, 1, '2024-03-14 16:38:52', '2026-03-08 02:09:17'),
(4, 'AMINOACIDOS PARA NEFROPATAS 5.4%', 1, 7, 60.0000, 1, '2024-03-14 16:44:04', '2025-10-03 05:06:24'),
(5, 'SOLUCION GLUCOSADA AL 50%', 2, 8, 2525.0000, 1, '2024-03-14 16:44:48', '2026-05-19 06:36:53'),
(6, 'LIPIDOS DE CADENA MEDIA Y LARGA 20%', 3, 9, 380.0000, 1, '2024-03-14 16:45:46', '2026-05-19 06:41:21'),
(7, 'EMULSION LIPIDICA 20% DE ACEITE DE SOJA, TRIGLICERIDOS DE CADENA MEDIA, ACEITE DE OLIVA Y DE ACEITE DE PESCADO', 3, 10, 6.0000, 1, '2024-03-14 16:49:08', '2026-05-19 06:46:21'),
(8, 'CLORURO DE SODIO AL 17.7% (3mEq/mL)', 4, 11, 5370.0000, 1, '2024-03-14 16:49:59', '2026-05-19 06:50:09'),
(9, 'ACETATO DE SODIO (4mEq/mL)', 4, 12, 20.0000, 1, '2024-03-14 16:51:06', '2026-05-19 06:50:57'),
(10, 'FOSFATO DE SODIO (4mEq/mL)', 4, 13, 7.0000, 1, '2024-03-14 16:52:10', '2026-05-19 06:51:39'),
(11, 'SULFATO DE MAGNESIO (0.81mEq/mL)', 4, 14, 1620.0000, 1, '2024-03-14 16:53:02', '2026-05-19 06:52:21'),
(12, 'CLORURO DE POTASIO (4mEq/mL)', 4, 15, 5369.0000, 1, '2024-03-14 16:53:32', '2026-05-19 06:53:04'),
(13, 'ACETATO DE POTASIO (2mEq/mL)', 4, 16, 50.0000, 1, '2024-03-14 16:54:42', '2026-05-19 06:57:14'),
(14, 'FOSFATO DE POTASIO (2mEq/mL)', 4, 17, 2667.0000, 1, '2024-03-14 16:55:12', '2026-05-19 07:00:26'),
(15, 'GLUCONATO DE CALCIO (0.465mEq/mL)', 4, 18, 697.0700, 1, '2024-03-14 16:55:56', '2026-05-19 07:02:38'),
(16, 'ACIDOS GRACOS OMEGA 3', 5, 19, 273.0000, 1, '2024-03-14 16:56:55', '2026-03-04 05:54:04'),
(17, 'ALBUMINA 25% (0.25g/ml)', 5, 20, 10.0000, 1, '2024-03-14 16:57:40', '2026-04-08 05:34:03'),
(18, 'ALBUMINA 0.2g/ml', 5, 21, 19.0000, 1, '2024-03-14 16:58:37', '2026-04-08 10:23:14'),
(19, 'GLUTAMINA 20%', 5, 22, 921.0000, 1, '2024-03-14 16:59:37', '2026-05-19 07:10:31'),
(20, 'CROMO (4mcg/mL)', 5, 23, 15.0000, 1, '2024-03-14 17:00:08', '2026-05-19 07:22:01'),
(21, 'HEPARINA (1000UI/mL)', 5, 24, 78.0000, 1, '2024-03-14 17:00:58', '2026-03-04 06:07:52'),
(22, 'L-CARNITINA (200mg/mL)', 5, 25, 56.0000, 1, '2024-03-14 17:01:31', '2026-05-19 07:11:26'),
(23, 'INSULINA (100UI/mL)', 5, 26, 90.0000, 1, '2024-03-14 17:02:05', '2025-04-30 06:39:35'),
(24, 'MANGANESO (100mcg/mL)', 5, 27, 96.0000, 1, '2024-03-14 17:02:34', '2025-10-03 05:19:00'),
(25, 'MULTIVITAMINICO ADULTO', 5, 28, 60.0000, 1, '2024-03-14 17:09:25', '2026-04-13 16:15:57'),
(26, 'OLIGOELEMENTOS', 5, 29, 5.0000, 1, '2024-03-14 17:09:50', '2026-05-19 07:13:53'),
(27, 'ACIDO FOLINICO (12.5 mg/mL)', 5, 30, 41.0000, 1, '2024-03-14 17:10:32', '2026-05-19 07:14:41'),
(28, 'SELENIO 40mcg/ml', 5, 31, 63.0000, 1, '2024-03-14 17:11:01', '2024-03-14 17:11:01'),
(29, 'VITAMINA C (100mg/mL)', 5, 32, 50.0000, 1, '2024-03-14 17:11:35', '2026-05-19 07:15:14'),
(30, 'VITAMINA K (10mg/mL)', 5, 33, 63.0000, 1, '2024-03-14 17:12:25', '2026-04-08 05:40:12'),
(31, 'ZINC (1mg/mL)', 5, 34, 85.0000, 1, '2024-03-14 17:13:00', '2026-05-19 07:16:52'),
(32, 'L-CISTEINA (50mg/mL)', 5, 35, 20.0000, 1, '2024-03-14 17:13:33', '2026-05-19 07:19:21'),
(33, 'CLORURO DE SODIO 0.9%', 8, 36, 6.0000, 1, '2024-03-14 17:15:52', '2025-08-08 04:39:09'),
(34, 'AGUA INYECTABLE', 7, 37, 21.0000, 1, '2024-04-09 11:43:55', '2026-05-19 07:23:25'),
(35, 'BOLSA EVA 3000 ML', 6, 38, 0.0000, 1, '2024-04-09 11:55:18', '2026-05-19 07:26:37'),
(36, 'BOLSA EVA 500 ML', 6, 39, 0.0000, 1, '2024-04-09 11:59:45', '2026-05-19 07:30:40'),
(37, 'SET DE INFUSIÓN', 10, 40, 0.0000, 1, '2024-04-09 16:38:48', '2024-04-09 16:38:48'),
(38, 'SERVICIO DE MEZCLADO', 11, 41, 0.0000, 1, '2024-04-23 05:05:36', '2026-03-04 06:10:58'),
(39, 'BOLSA EVA 250 ML', 6, 42, 0.0000, 1, '2024-12-13 06:38:37', '2026-05-19 07:31:21'),
(40, 'BOLSA EVA 1000 ML', 6, 43, 0.0000, 1, '2024-12-13 06:39:42', '2025-09-23 08:47:09'),
(41, 'MULTIVITAMINICO PEDIÁTRICO', 5, 44, 60.0000, 1, '2024-12-24 19:10:07', '2026-05-19 07:34:02'),
(42, 'SMOF LIPID 10%', 3, 45, 6.0000, 1, '2024-12-24 19:11:35', '2025-10-02 05:32:30'),
(43, 'LIPOFUNDIN 10%', 3, 46, 8.0000, 1, '2024-12-24 19:13:11', '2026-03-08 02:13:35'),
(44, 'OLIGOELEMENTOS', 5, 47, 5.0000, 1, '2024-12-24 19:16:19', '2026-03-04 06:10:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nutrition_medicine_presentations`
--

CREATE TABLE `nutrition_medicine_presentations` (
  `id` bigint UNSIGNED NOT NULL,
  `nutrition_medicine_catalog_id` bigint UNSIGNED NOT NULL,
  `denominacion_comercial` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fabricante` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `presentacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `presentacion_ml` decimal(12,4) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `nutrition_medicine_presentations`
--

INSERT INTO `nutrition_medicine_presentations` (`id`, `nutrition_medicine_catalog_id`, `denominacion_comercial`, `fabricante`, `presentacion`, `presentacion_ml`, `is_available`, `created_at`, `updated_at`) VALUES
(1, 1, 'LEVAMIN NORMO 10%.', NULL, 'FRASCO 500ML', 500.0000, 1, '2024-03-14 16:29:02', '2026-05-19 06:30:40'),
(2, 2, 'PRIMENE', NULL, 'FRASCO 250ML', 250.0000, 1, '2024-03-14 16:33:20', '2026-05-19 06:38:07'),
(3, 3, 'LEVAMI NORMO', NULL, 'FRASCO 500ML', 500.0000, 1, '2024-03-14 16:38:52', '2026-03-08 02:09:17'),
(4, 4, 'LEVAMIN NEP', NULL, 'FRASCO 250ML', 250.0000, 1, '2024-03-14 16:44:04', '2025-10-03 05:06:24'),
(5, 5, 'SOLUCION DX-50 PISA', NULL, 'FRASCO 500ML', 500.0000, 1, '2024-03-14 16:44:48', '2026-05-19 06:36:53'),
(6, 6, 'LIPOVENOES', NULL, 'FRASCO 500ML', 500.0000, 1, '2024-03-14 16:45:46', '2026-05-19 06:41:21'),
(7, 7, 'SMOF LIPID', NULL, 'FRASCO 500ML', 500.0000, 1, '2024-03-14 16:49:08', '2026-05-19 06:46:21'),
(8, 8, 'SOLUCION CS-C 17.7%', NULL, 'FRASCO 50ML', 50.0000, 1, '2024-03-14 16:49:59', '2026-05-19 06:50:09'),
(9, 9, 'SOLUCION AC-S', NULL, 'FRASCO 50ML', 50.0000, 1, '2024-03-14 16:51:06', '2026-05-19 06:50:57'),
(10, 10, 'SOLUCION AC-S', NULL, 'FRASCO 50ML', 50.0000, 1, '2024-03-14 16:52:10', '2026-05-19 06:51:39'),
(11, 11, 'MAGNEFUSIN', NULL, 'AMP 10ML', 10.0000, 1, '2024-03-14 16:53:02', '2026-05-19 06:52:21'),
(12, 12, 'KELEFUSIN', NULL, 'AMP 5ML', 5.0000, 1, '2024-03-14 16:53:32', '2026-05-19 06:53:04'),
(13, 13, 'CEPOSIL', NULL, 'FRASCO 20', 20.0000, 1, '2024-03-14 16:54:42', '2026-05-19 06:57:14'),
(14, 14, 'FP-20', NULL, 'FRASCO 50ML', 50.0000, 1, '2024-03-14 16:55:12', '2026-05-19 07:00:26'),
(15, 15, 'SOLUCION GC', NULL, 'AMP 10ML', 10.0000, 1, '2024-03-14 16:55:56', '2026-05-19 07:02:38'),
(16, 16, 'FRESOMEGA', NULL, 'FCO AMP 100ML', 100.0000, 1, '2024-03-14 16:56:55', '2026-03-04 05:54:04'),
(17, 17, 'OCTALBIN', NULL, 'FCO AMP 50ML', 50.0000, 1, '2024-03-14 16:57:40', '2026-04-08 05:34:03'),
(18, 18, 'ALBUMINA HUMANA GRIFOLS', NULL, 'FCO AMP 50ML', 50.0000, 1, '2024-03-14 16:58:37', '2026-04-08 10:23:14'),
(19, 19, 'DIPEPTIVEN', NULL, 'FCO AMP 50ML', 50.0000, 1, '2024-03-14 16:59:37', '2026-05-19 07:10:31'),
(20, 20, 'CROMIFUSIN', NULL, 'FCO AMP 10ML', 10.0000, 1, '2024-03-14 17:00:08', '2026-05-19 07:22:01'),
(21, 21, 'INHEPAR', NULL, 'FCO AMP 10ML', 10.0000, 1, '2024-03-14 17:00:58', '2026-03-04 06:07:52'),
(22, 22, 'EFE-CARN', NULL, 'AMP 5ML', 5.0000, 1, '2024-03-14 17:01:31', '2026-05-19 07:11:26'),
(23, 23, 'INSULEX R', NULL, 'FCO AMP 10ML', 10.0000, 1, '2024-03-14 17:02:05', '2025-04-30 06:39:35'),
(24, 24, 'MN-FUSIN', NULL, 'FCO AMP 10ML', 10.0000, 1, '2024-03-14 17:02:34', '2025-10-03 05:19:00'),
(25, 25, 'VITAFUSIN', NULL, 'FCO AMP 5ML', 5.0000, 1, '2024-03-14 17:09:25', '2026-04-13 16:15:57'),
(27, 27, 'INNEFOL', NULL, 'FCO AMP 4ML', 4.0000, 1, '2024-03-14 17:10:32', '2026-05-19 07:14:41'),
(28, 28, 'SELEFUSIN', NULL, 'FCO AMP 10ML', 10.0000, 1, '2024-03-14 17:11:01', '2024-03-14 17:11:01'),
(29, 29, 'INFALET', NULL, 'AMP 10ML', 10.0000, 1, '2024-03-14 17:11:35', '2026-05-19 07:15:14'),
(30, 30, 'UNOKAVI', NULL, 'AMP 1ML', 1.0000, 1, '2024-03-14 17:12:25', '2026-04-08 05:40:12'),
(31, 31, 'ZINC-FUSIN', NULL, 'FCO AMP 10ML', 10.0000, 1, '2024-03-14 17:13:00', '2026-05-19 07:16:52'),
(32, 32, 'FIXCANAT', NULL, 'FCO AMP 10ML', 10.0000, 1, '2024-03-14 17:13:33', '2026-05-19 07:19:21'),
(33, 33, 'CLORUROSÓDICA ALPHA', NULL, 'FRASCO 100ML', 100.0000, 1, '2024-03-14 17:15:52', '2025-08-08 04:39:09'),
(34, 34, 'FRESENIUS', NULL, 'FRASCO 500ML', 500.0000, 1, '2024-04-09 11:43:55', '2026-05-19 07:23:25'),
(35, 35, 'Industrias plásticas Médicas', NULL, 'BOLSA 3000ML', 3000.0000, 1, '2024-04-09 11:55:18', '2026-05-19 07:26:37'),
(36, 36, 'Industrias plásticas Médicas', NULL, 'BOLSA 500ML', 500.0000, 1, '2024-04-09 11:59:45', '2026-05-19 07:30:40'),
(37, 37, 'OPTIMA', NULL, 'UNIDAD', 1.0000, 1, '2024-04-09 16:38:48', '2024-04-09 16:38:48'),
(38, 38, 'SERVICIO DE PREPARACIÓN', NULL, 'serv', 0.0000, 1, '2024-04-23 05:05:36', '2026-03-04 06:10:58'),
(39, 39, 'Industrias plásticas Médicas', NULL, 'BOLSA 250ML', 250.0000, 1, '2024-12-13 06:38:37', '2026-05-19 07:31:21'),
(40, 40, 'Industrias plásticas Médicas', NULL, 'BOLSA 1000ML', 1000.0000, 1, '2024-12-13 06:39:42', '2025-09-23 08:47:09'),
(42, 42, 'LIPIDOS 10% SMOF', NULL, 'FRASCO 500ML', 500.0000, 1, '2024-12-24 19:11:35', '2025-10-02 05:32:30'),
(43, 43, 'LIPIDOS 10% MCT/LTC', NULL, 'FRASCO 500ML', 500.0000, 1, '2024-12-24 19:13:11', '2026-03-08 02:13:35'),
(44, 44, 'DESACTIVADO', NULL, 'FRASCO 20ML', 20.0000, 1, '2024-12-24 19:16:19', '2026-03-04 06:10:33'),
(45, 41, 'VITAFUSIN PED', NULL, 'FCO AMP 5ML', 5.0000, 1, '2026-05-19 20:19:46', '2026-05-19 20:19:46'),
(46, 41, 'LAFALIX', NULL, 'FCO AMP 5ML', 5.0000, 1, '2026-05-19 20:19:46', '2026-05-19 20:19:46'),
(47, 26, 'Tracefusin', NULL, 'AMP 20ML', 10.0000, 1, '2026-06-30 19:18:02', '2026-06-30 19:18:02'),
(48, 26, 'Nulanza', 'FRESENIUS', 'Ampolleta 10ml', 10.0000, 1, '2026-06-30 19:18:02', '2026-06-30 19:18:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nutri_distributors`
--

CREATE TABLE `nutri_distributors` (
  `id` bigint UNSIGNED NOT NULL,
  `nutri_medicine_list_id` bigint UNSIGNED NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `logo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nutri_medicine_lists`
--

CREATE TABLE `nutri_medicine_lists` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `active_brands` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `nutri_medicine_lists`
--

INSERT INTO `nutri_medicine_lists` (`id`, `name`, `description`, `is_active`, `active_brands`, `created_at`, `updated_at`) VALUES
(1, 'Lista 1', 'Lista nutricional base', 1, 0, '2026-05-19 10:28:57', '2026-05-19 10:28:57'),
(2, 'HOSPITALES PRIVADOS', NULL, 1, 0, '2026-05-29 21:31:07', '2026-06-30 19:28:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nutri_medicine_list_items`
--

CREATE TABLE `nutri_medicine_list_items` (
  `id` bigint UNSIGNED NOT NULL,
  `nutri_medicine_list_id` bigint UNSIGNED NOT NULL,
  `nutrition_medicine_presentation_id` bigint UNSIGNED NOT NULL,
  `precio_ml` decimal(12,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `nutri_medicine_list_items`
--

INSERT INTO `nutri_medicine_list_items` (`id`, `nutri_medicine_list_id`, `nutrition_medicine_presentation_id`, `precio_ml`, `created_at`, `updated_at`) VALUES
(46, 1, 13, 9.3900, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(47, 1, 9, 9.3900, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(48, 1, 12, 13.5600, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(49, 1, 8, 5.2100, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(50, 1, 14, 8.3400, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(51, 1, 10, 9.5000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(52, 1, 15, 7.3000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(53, 1, 11, 7.3000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(54, 1, 27, 553.0500, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(55, 1, 16, 43.8200, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(56, 1, 18, 125.2200, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(57, 1, 17, 125.2200, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(58, 1, 20, 1.0000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(59, 1, 19, 109.5600, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(60, 1, 21, 67.8200, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(61, 1, 23, 118.1000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(62, 1, 22, 29.2100, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(63, 1, 32, 91.8200, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(64, 1, 24, 1.0000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(65, 1, 25, 234.7800, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(66, 1, 46, 30.0000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(67, 1, 45, 40.0000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(69, 1, 44, 50.0800, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(70, 1, 28, 105.0000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(71, 1, 29, 27.1300, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(72, 1, 30, 424.7000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(73, 1, 31, 109.5600, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(74, 1, 34, 1.5600, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(75, 1, 3, 0.1000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(76, 1, 1, 0.1000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(77, 1, 4, 0.1000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(78, 1, 2, 6.8800, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(79, 1, 40, 0.0000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(80, 1, 39, 0.0000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(81, 1, 35, 0.0000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(82, 1, 36, 0.0000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(83, 1, 33, 0.1000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(84, 1, 7, 20.8700, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(85, 1, 6, 15.6500, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(86, 1, 43, 8.3200, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(87, 1, 42, 9.0000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(88, 1, 38, 521.7500, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(89, 1, 37, 300.0000, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(90, 1, 5, 2.0800, '2026-05-19 20:29:44', '2026-05-19 20:29:44'),
(228, 2, 13, 9.5000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(229, 2, 9, 9.5000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(230, 2, 12, 14.6000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(231, 2, 8, 5.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(232, 2, 14, 8.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(233, 2, 10, 8.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(234, 2, 15, 7.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(235, 2, 11, 8.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(236, 2, 27, 605.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(237, 2, 16, 48.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(238, 2, 18, 100.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(239, 2, 17, 100.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(240, 2, 20, 61.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(241, 2, 19, 60.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(242, 2, 21, 74.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(243, 2, 23, 135.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(244, 2, 22, 31.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(245, 2, 32, 100.4000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(246, 2, 24, 0.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(247, 2, 25, 233.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(248, 2, 46, 247.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(249, 2, 45, 247.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(250, 2, 48, 50.0800, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(251, 2, 47, 50.0800, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(252, 2, 44, 54.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(253, 2, 28, 0.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(254, 2, 29, 29.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(255, 2, 30, 465.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(256, 2, 31, 100.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(257, 2, 34, 1.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(258, 2, 3, 1.8000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(259, 2, 1, 1.8000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(260, 2, 4, 1.8000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(261, 2, 2, 4.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(262, 2, 40, 0.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(263, 2, 39, 0.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(264, 2, 35, 0.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(265, 2, 36, 0.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(266, 2, 33, 1.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(267, 2, 7, 16.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(268, 2, 6, 9.5000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(269, 2, 43, 0.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(270, 2, 42, 0.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(271, 2, 38, 1800.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(272, 2, 37, 1000.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26'),
(273, 2, 5, 1.0000, '2026-07-01 20:06:26', '2026-07-01 20:06:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'nutricionales_solicitudes_index', 'web', '2024-04-13 11:50:21', '2024-04-22 22:48:57'),
(2, 'hospitales', 'web', '2024-04-13 11:50:33', '2024-04-13 11:50:33'),
(3, 'usuarios', 'web', '2024-04-13 11:50:45', '2024-04-13 11:50:45'),
(4, 'medicamentos', 'web', '2024-04-13 11:50:53', '2024-04-13 11:50:53'),
(5, 'roles', 'web', '2024-04-13 11:51:00', '2024-04-13 11:51:00'),
(6, 'permisos', 'web', '2024-04-13 11:51:10', '2024-04-13 11:51:10'),
(7, 'medicamentos_nutricionales', 'web', '2024-04-22 22:49:10', '2024-04-22 22:49:10'),
(8, 'nutricionales_solicitudes_create', 'web', '2024-04-22 22:49:24', '2024-04-22 22:49:24'),
(9, 'nutricionales_solicitudes_store', 'web', '2024-04-22 22:49:33', '2024-04-22 22:49:33'),
(10, 'nutricionales_solicitudes_show', 'web', '2024-04-22 22:49:42', '2024-04-22 22:49:42'),
(11, 'nutricionales_solicitudes_edit', 'web', '2024-04-22 22:49:51', '2024-04-22 22:49:51'),
(18, 'nutricionales_solicitudes_update', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(19, 'oncologicos_solicitudes_index', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(20, 'oncologicos_solicitudes_create', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(21, 'oncologicos_solicitudes_store', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(22, 'oncologicos_solicitudes_show', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(23, 'oncologicos_solicitudes_edit', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(24, 'oncologicos_solicitudes_update', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(25, 'oncologicos_mezclas_index', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(26, 'oncologicos_mezclas_create', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(27, 'oncologicos_mezclas_show', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(28, 'oncologicos_mezclas_store', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(29, 'oncologicos_mezclas_edit', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(30, 'oncologicos_mezclas_update', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(31, 'oncologicos_diluents_index', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(32, 'oncologicos_diluents_create', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(33, 'oncologicos_diluents_store', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(34, 'oncologicos_diluents_edit', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(35, 'oncologicos_diluents_update', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(36, 'oncologicos_diluents_destroy', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(37, 'medicamentos_oncologicos', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(38, 'clientes', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(39, 'oncologicos_laboratory_index', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(40, 'oncologicos_laboratory_create', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(41, 'oncologicos_laboratory_store', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(42, 'oncologicos_laboratory_edit', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(43, 'oncologicos_laboratory_update', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26'),
(44, 'oncologicos_laboratory_destroy', 'web', '2026-05-27 14:03:26', '2026-05-27 14:03:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'web', '2024-04-13 11:51:26', '2024-04-13 11:51:26'),
(2, 'Cliente', 'web', '2024-04-13 11:51:41', '2024-04-13 11:51:41'),
(3, 'Super Admin', 'web', '2024-04-13 11:51:53', '2024-04-13 11:51:53'),
(4, 'Institucion', 'web', '2026-07-30 17:50:25', '2026-07-30 17:50:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(11, 1),
(18, 1),
(19, 1),
(20, 1),
(21, 1),
(22, 1),
(23, 1),
(24, 1),
(25, 1),
(26, 1),
(27, 1),
(28, 1),
(29, 1),
(30, 1),
(31, 1),
(32, 1),
(33, 1),
(34, 1),
(35, 1),
(36, 1),
(37, 1),
(38, 1),
(39, 1),
(40, 1),
(41, 1),
(42, 1),
(43, 1),
(44, 1),
(1, 2),
(8, 2),
(9, 2),
(10, 2),
(19, 2),
(20, 2),
(21, 2),
(22, 2),
(25, 2),
(26, 2),
(27, 2),
(28, 2),
(1, 4),
(8, 4),
(9, 4),
(10, 4),
(19, 4),
(20, 4),
(21, 4),
(22, 4),
(25, 4),
(26, 4),
(27, 4),
(28, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('tOmohYTjMOaHxJG7zmLPUP4pu1DLE1BjAfzoAFNA', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoibTJHcU1XYmg4V0daeUlvWTlVRlNCdG5nNnJ1R0FwblpNSDZWSVNsdSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTU6Imh0dHA6Ly9jYnRhX2FwcC50ZXN0L2FkbWluL29uY29sb2dpY29zL21lZGljaW5lcy8xL2VkaXQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjM6InVybCI7YTowOnt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9', 1785781379);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicituds`
--

CREATE TABLE `solicituds` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `solicitud_detail_id` bigint UNSIGNED NOT NULL,
  `solicitud_patient_id` bigint UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `estado` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `fecha_hora_preparacion` datetime DEFAULT NULL,
  `fecha_hora_limite_uso` timestamp NULL DEFAULT NULL,
  `lote` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remision` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicituds`
--

INSERT INTO `solicituds` (`id`, `user_id`, `solicitud_detail_id`, `solicitud_patient_id`, `is_active`, `estado`, `fecha_hora_preparacion`, `fecha_hora_limite_uso`, `lote`, `remision`, `created_at`, `updated_at`, `read_at`) VALUES
(1, 1, 1, 1055, 1, 'entregada', '2026-05-18 23:46:53', '2026-05-21 05:46:53', 'L180526001', '1', '2026-05-19 11:01:02', '2026-05-19 11:03:52', NULL),
(2, 1, 2, 1056, 1, 'cancelada', '2026-05-19 09:31:17', '2026-05-21 15:31:17', 'L190526001', '2', '2026-05-19 20:15:03', '2026-05-19 20:55:07', NULL),
(3, 6, 3, 1057, 1, 'preparada', '2026-06-30 13:34:13', '2026-07-02 19:34:13', 'L240626001', '3', '2026-06-24 20:11:50', '2026-06-30 19:34:13', NULL),
(4, 5, 4, 1058, 1, 'entregada', '2026-06-30 13:34:08', '2026-07-02 19:34:08', 'L300626001', '4', '2026-06-30 19:18:32', '2026-07-22 15:48:10', NULL),
(5, 6, 5, 1059, 1, 'entregada', '2026-07-02 16:52:06', '2026-07-04 22:52:06', 'L020726001', '5', '2026-07-02 22:42:35', '2026-07-22 15:48:04', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitud_details`
--

CREATE TABLE `solicitud_details` (
  `id` bigint UNSIGNED NOT NULL,
  `via_administracion` enum('Central','Periférica') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tiempo_infusion_min` int DEFAULT '24',
  `sobrellenado_ml` double DEFAULT NULL,
  `volumen_total` double DEFAULT NULL,
  `suma_volumen` double DEFAULT NULL,
  `volumen_total_final` double DEFAULT NULL,
  `suma_volumen_final` double DEFAULT NULL,
  `npt` enum('RNPT','LACT','INF','ADOL','ADULT') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_medico` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cedula` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_hora_entrega` datetime NOT NULL,
  `observaciones` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `suma_volumen_sobrellenado` double NOT NULL DEFAULT '0',
  `velocidad_infusion` double DEFAULT NULL,
  `hospital_destino` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicitud_details`
--

INSERT INTO `solicitud_details` (`id`, `via_administracion`, `tiempo_infusion_min`, `sobrellenado_ml`, `volumen_total`, `suma_volumen`, `volumen_total_final`, `suma_volumen_final`, `npt`, `nombre_medico`, `cedula`, `fecha_hora_entrega`, `observaciones`, `created_at`, `updated_at`, `suma_volumen_sobrellenado`, `velocidad_infusion`, `hospital_destino`) VALUES
(1, 'Central', 24, 30, 188, 129.70578494624, 218, NULL, 'INF', 'RAUL MURILLO GOMEZ', '9108971', '2026-05-28 23:00:00', NULL, '2026-05-19 11:01:02', '2026-05-19 11:01:53', 150.40351658659, 7.8, 'HOSPITAL MATERNO PERINATAL MONICA PRETELINI SAENZ'),
(2, 'Central', 24, 30, 188, 131.64578494624, 218, NULL, 'INF', 'Dra Delia Margarita Velarde Rojas', '6249670', '2026-05-21 08:14:00', NULL, '2026-05-19 20:15:03', '2026-05-19 20:52:10', 152.65309105468, 7.8, 'Materno Infantil Chalco \"Josefa Ortiz de Dominguez'),
(3, 'Central', 24, 20, 255, 177.75942493031, 275, NULL, 'INF', 'DR. MATA', '11653679', '2026-07-10 16:00:00', NULL, '2026-06-24 20:11:50', '2026-06-24 22:19:13', 191.70134061112, 10.6, 'HOSPITAL SAN DIEGO'),
(4, 'Central', 24, NULL, 1498, 1493, 1498, NULL, 'ADULT', 'ERVIN', '4768544', '2026-06-30 17:00:00', NULL, '2026-06-30 19:18:32', '2026-06-30 19:19:00', 0, NULL, 'HOSPITAL ANGE'),
(5, 'Central', 24, 20, 167, 157.53502787734, 187, NULL, 'INF', 'DR. MATA', '11653679', '2026-07-03 09:30:00', NULL, '2026-07-02 22:42:35', '2026-07-02 22:48:00', 176.40149828181, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitud_inputs`
--

CREATE TABLE `solicitud_inputs` (
  `id` bigint UNSIGNED NOT NULL,
  `input_id` bigint UNSIGNED NOT NULL,
  `nutrition_medicine_presentation_id` bigint UNSIGNED DEFAULT NULL,
  `lote` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caducidad` date DEFAULT NULL,
  `valor` double NOT NULL,
  `valor_sobrellenado` double DEFAULT NULL,
  `valor_ml` double DEFAULT NULL,
  `solicitud_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `precio_ml` double NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicitud_inputs`
--

INSERT INTO `solicitud_inputs` (`id`, `input_id`, `nutrition_medicine_presentation_id`, `lote`, `caducidad`, `valor`, `valor_sobrellenado`, `valor_ml`, `solicitud_id`, `created_at`, `updated_at`, `precio_ml`) VALUES
(16, 4, NULL, NULL, NULL, 3, 65.4, 56.4, 1, '2026-05-19 11:01:53', '2026-05-19 11:01:53', 0),
(17, 8, NULL, NULL, NULL, 8.6, 37.496, 32.336, 1, '2026-05-19 11:01:53', '2026-05-19 11:01:53', 0),
(18, 9, NULL, NULL, NULL, 3, 32.7, 28.2, 1, '2026-05-19 11:01:53', '2026-05-19 11:01:53', 0),
(19, 11, NULL, NULL, NULL, 3, 2.18, 1.88, 1, '2026-05-19 11:01:53', '2026-05-19 11:01:53', 0),
(20, 14, NULL, NULL, NULL, 0.81, 2.18, 1.88, 1, '2026-05-19 11:01:53', '2026-05-19 11:01:53', 0),
(21, 15, NULL, NULL, NULL, 2, 1.09, 0.94, 1, '2026-05-19 11:01:53', '2026-05-19 11:01:53', 0),
(22, 18, NULL, NULL, NULL, 0.46, 2.156559139785, 1.8597849462366, 1, '2026-05-19 11:01:53', '2026-05-19 11:01:53', 0),
(23, 22, NULL, NULL, NULL, 0.5, 2.8989361702128, 2.5, 1, '2026-05-19 11:01:53', '2026-05-19 11:01:53', 0),
(24, 24, NULL, NULL, NULL, 200, 0.23191489361702, 0.2, 1, '2026-05-19 11:01:53', '2026-05-19 11:01:53', 0),
(25, 25, NULL, NULL, NULL, 94, 0.545, 0.47, 1, '2026-05-19 11:01:53', '2026-05-19 11:01:53', 0),
(26, 28, NULL, NULL, NULL, 1, 1.1595744680851, 1, 1, '2026-05-19 11:01:53', '2026-05-19 11:01:53', 0),
(27, 29, NULL, NULL, NULL, 0.5, 0.57978723404255, 0.5, 1, '2026-05-19 11:01:53', '2026-05-19 11:01:53', 0),
(28, 32, NULL, NULL, NULL, 94, 1.09, 0.94, 1, '2026-05-19 11:01:53', '2026-05-19 11:01:53', 0),
(29, 34, NULL, NULL, NULL, 0.6, 0.69574468085106, 0.6, 1, '2026-05-19 11:01:53', '2026-05-19 11:01:53', 0),
(30, 37, NULL, NULL, NULL, 58.294215053763, 67.596483413407, 58.294215053763, 1, '2026-05-19 11:01:53', '2026-05-19 11:01:53', 0),
(31, 42, NULL, 'BSSAFRR', '2026-05-28', 0, NULL, 0, 1, '2026-05-19 11:01:53', '2026-05-19 11:01:53', 0),
(81, 5, NULL, NULL, NULL, 3, 65.4, 56.4, 2, '2026-05-19 20:52:10', '2026-05-19 20:52:10', 0),
(82, 8, NULL, NULL, NULL, 8.6, 37.496, 32.336, 2, '2026-05-19 20:52:10', '2026-05-19 20:52:10', 0),
(83, 9, NULL, NULL, NULL, 3, 32.7, 28.2, 2, '2026-05-19 20:52:10', '2026-05-19 20:52:10', 0),
(84, 11, NULL, NULL, NULL, 3, 2.18, 1.88, 2, '2026-05-19 20:52:10', '2026-05-19 20:52:10', 0),
(85, 14, NULL, NULL, NULL, 0.81, 2.18, 1.88, 2, '2026-05-19 20:52:10', '2026-05-19 20:52:10', 0),
(86, 16, NULL, NULL, NULL, 2, 2.18, 1.88, 2, '2026-05-19 20:52:10', '2026-05-19 20:52:10', 0),
(87, 18, NULL, NULL, NULL, 0.46, 2.156559139785, 1.8597849462366, 2, '2026-05-19 20:52:10', '2026-05-19 20:52:10', 0),
(88, 22, NULL, NULL, NULL, 0.5, 2.8989361702128, 2.5, 2, '2026-05-19 20:52:10', '2026-05-19 20:52:10', 0),
(89, 24, NULL, NULL, NULL, 200, 0.23191489361702, 0.2, 2, '2026-05-19 20:52:10', '2026-05-19 20:52:10', 0),
(90, 25, NULL, NULL, NULL, 94, 0.545, 0.47, 2, '2026-05-19 20:52:10', '2026-05-19 20:52:10', 0),
(91, 28, NULL, NULL, NULL, 1, 1.1595744680851, 1, 2, '2026-05-19 20:52:10', '2026-05-19 20:52:10', 0),
(92, 44, 45, 'MNUCOL65', '2026-05-28', 1, 1.1595744680851, 1, 2, '2026-05-19 20:52:10', '2026-05-19 20:52:10', 46.382978723404),
(93, 29, NULL, NULL, NULL, 0.5, 0.57978723404255, 0.5, 2, '2026-05-19 20:52:10', '2026-05-19 20:52:10', 0),
(94, 32, NULL, NULL, NULL, 94, 1.09, 0.94, 2, '2026-05-19 20:52:10', '2026-05-19 20:52:10', 0),
(95, 34, NULL, NULL, NULL, 0.6, 0.69574468085106, 0.6, 2, '2026-05-19 20:52:10', '2026-05-19 20:52:10', 0),
(96, 37, NULL, NULL, NULL, 56.354215053763, 65.346908945321, 56.354215053763, 2, '2026-05-19 20:52:10', '2026-05-19 20:52:10', 0),
(97, 42, NULL, '1F4', '2026-05-29', 0, NULL, 0, 2, '2026-05-19 20:52:10', '2026-05-19 20:52:10', 0),
(108, 5, 2, 'LOT-NUT-002', '2027-12-31', 3.5, 73.980392156863, 68.6, 3, '2026-06-24 22:19:13', '2026-06-24 22:19:13', 4),
(109, 8, 5, 'LOT-NUT-005', '2027-12-31', 15.8, 66.793725490196, 61.936, 3, '2026-06-24 22:19:13', '2026-06-24 22:19:13', 1),
(110, 9, 6, 'LOT-NUT-006', '2027-12-31', 3.5, 36.990196078431, 34.3, 3, '2026-06-24 22:19:13', '2026-06-24 22:19:13', 9.5),
(111, 11, 8, 'LOT-NUT-008', '2027-12-31', 3, 2.1137254901961, 1.96, 3, '2026-06-24 22:19:13', '2026-06-24 22:19:13', 5),
(112, 14, 11, 'LOT-NUT-011', '2027-12-31', 0.5, 1.3047688211087, 1.2098765432099, 3, '2026-06-24 22:19:13', '2026-06-24 22:19:13', 8),
(113, 17, 14, 'LOT-NUT-014', '2027-12-31', 2, 2.1137254901961, 1.96, 3, '2026-06-24 22:19:13', '2026-06-24 22:19:13', 8),
(114, 18, 15, 'LOT-NUT-015', '2027-12-31', 0.9, 4.0910815939279, 3.7935483870968, 3, '2026-06-24 22:19:13', '2026-06-24 22:19:13', 7),
(115, 44, 45, 'T25N010', '2027-11-30', 3.5, 3.7745098039216, 3.5, 3, '2026-06-24 22:19:13', '2026-06-24 22:19:13', 247),
(116, 29, NULL, 'LOT-NUT-026', '2027-12-31', 0.5, 0.53921568627451, 0.5, 3, '2026-06-24 22:19:13', '2026-06-24 22:19:13', 54),
(117, 37, NULL, NULL, NULL, 77.240575069693, 83.298659388885, 77.240575069693, 3, '2026-06-24 22:19:13', '2026-06-24 22:19:13', 0),
(118, 39, 36, 'LOT-NUT-036', '2027-12-31', 0, NULL, 0, 3, '2026-06-24 22:19:13', '2026-06-24 22:19:13', 0),
(128, 4, 1, 'LOT-NUT-001', '2027-12-31', 63, NULL, 630, 4, '2026-06-30 19:19:00', '2026-06-30 19:19:00', 1.8),
(129, 8, 5, 'LOT-NUT-005', '2027-12-31', 220, NULL, 440, 4, '2026-06-30 19:19:00', '2026-06-30 19:19:00', 1),
(130, 10, 7, 'LOT-NUT-007', '2027-12-31', 74, NULL, 370, 4, '2026-06-30 19:19:00', '2026-06-30 19:19:00', 16),
(131, 14, 11, 'LOT-NUT-011', '2027-12-31', 8.1, NULL, 10, 4, '2026-06-30 19:19:00', '2026-06-30 19:19:00', 8),
(132, 28, 25, 'LOT-NUT-025', '2027-12-31', 10, NULL, 10, 4, '2026-06-30 19:19:00', '2026-06-30 19:19:00', 233),
(133, 29, NULL, NULL, NULL, 10, NULL, 10, 4, '2026-06-30 19:19:00', '2026-06-30 19:19:00', 0),
(134, 32, 29, 'LOT-NUT-029', '2027-12-31', 2000, NULL, 20, 4, '2026-06-30 19:19:00', '2026-06-30 19:19:00', 29),
(135, 34, 31, 'LOT-NUT-031', '2027-12-31', 3, NULL, 3, 4, '2026-06-30 19:19:00', '2026-06-30 19:19:00', 100),
(136, 37, NULL, NULL, NULL, 5, NULL, 5, 4, '2026-06-30 19:19:00', '2026-06-30 19:19:00', 0),
(137, 38, 35, 'LOT-NUT-035', '2027-12-31', 0, NULL, 0, 4, '2026-06-30 19:19:00', '2026-06-30 19:19:00', 0),
(149, 5, 2, 'LOT-NUT-002', '2027-12-31', 3.3, 68.361377245509, 61.05, 5, '2026-07-02 22:48:00', '2026-07-02 22:48:00', 4),
(150, 8, 5, 'LOT-NUT-005', '2027-12-31', 14.4, 59.660838323353, 53.28, 5, '2026-07-02 22:48:00', '2026-07-02 22:48:00', 1),
(151, 9, 6, 'LOT-NUT-006', '2027-12-31', 2.5, 25.894461077844, 23.125, 5, '2026-07-02 22:48:00', '2026-07-02 22:48:00', 9.5),
(152, 11, 8, 'LOT-NUT-008', '2027-12-31', 3, 2.0715568862275, 1.85, 5, '2026-07-02 22:48:00', '2026-07-02 22:48:00', 5),
(153, 14, 11, 'LOT-NUT-011', '2027-12-31', 0.35, 0.89511717306128, 0.79938271604938, 5, '2026-07-02 22:48:00', '2026-07-02 22:48:00', 8),
(154, 17, 14, 'LOT-NUT-014', '2027-12-31', 2, 2.0715568862275, 1.85, 5, '2026-07-02 22:48:00', '2026-07-02 22:48:00', 8),
(155, 18, 15, 'LOT-NUT-015', '2027-12-31', 0.9, 4.0094649410855, 3.5806451612903, 5, '2026-07-02 22:48:00', '2026-07-02 22:48:00', 7),
(156, 20, 17, 'LOT-NUT-017', '2027-12-31', 2, 8.9580838323353, 8, 5, '2026-07-02 22:48:00', '2026-07-02 22:48:00', 100),
(157, 44, 45, 'T25N010', '2027-11-30', 3.5, 3.9191616766467, 3.5, 5, '2026-07-02 22:48:00', '2026-07-02 22:48:00', 247),
(158, 29, 47, 'C250107', '2028-06-30', 0.5, 0.55988023952096, 0.5, 5, '2026-07-02 22:48:00', '2026-07-02 22:48:00', 50.08),
(159, 37, NULL, NULL, NULL, 9.4649721226603, 10.598501718189, 9.4649721226603, 5, '2026-07-02 22:48:00', '2026-07-02 22:48:00', 0),
(160, 42, 39, 'LOT-NUT-039', '2027-12-31', 0, NULL, 0, 5, '2026-07-02 22:48:00', '2026-07-02 22:48:00', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitud_oncos`
--

CREATE TABLE `solicitud_oncos` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `hospital_id` bigint UNSIGNED DEFAULT NULL,
  `servicio` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_paciente` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sexo` enum('M','F') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edad` int DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `cama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `piso` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alergias` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `registro_paciente` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `diagnostico` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `nombre_medico` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cedula_medico` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pendiente','enproceso','finalizada','cancelada','no-aprobada') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remision` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicitud_oncos`
--

INSERT INTO `solicitud_oncos` (`id`, `user_id`, `hospital_id`, `servicio`, `nombre_paciente`, `sexo`, `edad`, `peso`, `cama`, `piso`, `alergias`, `registro_paciente`, `fecha_nacimiento`, `diagnostico`, `fecha_entrega`, `observaciones`, `nombre_medico`, `cedula_medico`, `estado`, `created_at`, `updated_at`, `remision`) VALUES
(1, 5, 2, 'Centro de infusión', 'KENYA SORAIDA LARES ZUÑIGA', 'F', NULL, 62.00, 'NA', 'NA', 'NA', 'NA', '1983-12-29', 'CANCER DE OVARIO EC IIC', '2026-05-29 08:00:00', 'NA', 'SARAI SILVA GARCIA', '12057412', 'pendiente', '2026-05-29 05:33:32', '2026-05-29 05:56:10', '1'),
(2, 4, 2, 'LARES', 'KENYA SORAIDA', 'F', NULL, 62.00, 'NA', 'NA', 'NA', 'ZUÑIGA', '1983-12-29', 'CANCER DE OVARIO EC IIC', '2026-06-05 08:00:00', 'NA', 'SARAI SILVA GARCIA', '12057412', 'enproceso', '2026-06-04 19:29:49', '2026-06-04 19:55:37', '2'),
(3, 5, 2, 'ONCOLOGÍA MEDICA', 'MARIA GUADALUPE DIAZ RODRIGUEZ', 'F', NULL, 62.00, 'S/D', 'S/D', '', 'S/D', '1974-08-05', 'S/D', '2026-06-28 08:00:00', NULL, 'ASLHIE OYUKI ORZUNA VAZQUEZ', '10910384', 'enproceso', '2026-06-24 23:40:27', '2026-06-24 19:21:08', '3'),
(5, 5, 2, 'ONCOLOGÍA', 'NINFA GALLEGOS SANTOS', 'F', NULL, 55.00, 'S/D', 'S/D', '', 'S/D', '1965-11-10', 'TUMOR MALIGNO DE MAMA HER2 SOBREEXPRESADO', '2026-07-21 12:00:00', NULL, 'JORGE ADAN ALEGRIA BAÑOS', 'S/D', 'enproceso', '2026-07-21 15:12:12', '2026-07-28 19:14:43', '5'),
(6, 5, 2, 'ONCOLOGÍA MEDICA', 'CLAUDIA LILIANA ARELLANO RAMIREZ', 'F', NULL, 51.00, 'S/D', 'S/D', '', 'S/D', '1974-10-22', 'CANCER DE COLÓN EC IIB', '2026-07-21 12:00:00', NULL, 'GLORIA MARTINEZ MARTINEZ', '3851543', 'enproceso', '2026-07-21 15:17:57', '2026-07-21 18:29:32', '4'),
(7, 5, 2, 'HOSPITALIZACIÓN', 'MARIA MAGDALENA VARELA ZAMORA', 'F', NULL, 77.00, '1607', '6to', '', 'S/D', '1967-10-05', 'VASCULITIS', '2026-07-21 12:00:00', NULL, 'ROMAN HERNADEZ RIOS', '6384222', 'pendiente', '2026-07-21 15:22:27', '2026-07-21 15:22:27', NULL),
(10, 5, 2, 'ONCOLOGÍA MEDICA', 'ALEJANDRA LILIANA GODINEZ RODRIGUEZ', 'F', NULL, 55.00, '1228', '2', '', 'S/D', '1979-04-24', 'CANCER GÁSTRICO', '2026-07-21 12:00:00', NULL, 'ASLHIE OYUKI ORZUNA VAZQUEZ', '10910384', 'pendiente', '2026-07-21 16:06:51', '2026-07-21 16:06:51', NULL),
(11, 5, 2, 'PEDIATRICA/ONCOLOGÍA', 'IKER ALEXIS ROSALES ESCOBEDO', 'M', NULL, 38.00, 'S/D', '2', '', 'S/D', '2015-02-03', 'LEUCEMIA LINFOBLASTICA', '2026-07-24 14:00:00', NULL, 'MARIA DE LOURDES GUTIERREZ', '09177525', 'pendiente', '2026-07-22 15:58:11', '2026-07-24 11:59:13', NULL),
(12, 5, 2, 'HEMATOLOGIA', 'GUSTAVO LUCIANO ROSAS CORTES', 'M', NULL, 90.00, '1611', '6', '', 'S/D', '1958-01-07', 'LAL', '2026-07-22 17:00:00', NULL, 'JOSE EUGENIO VAZQUEZ', '18613', 'pendiente', '2026-07-22 22:46:21', '2026-07-22 22:46:21', NULL),
(16, 5, 2, 'ONCOLOGIA', 'AURORA DE LAS MERCEDES MALFAVON RENDON', 'F', NULL, 63.00, 'N/A', 'N/A', 'N/A', 'N/A', '1982-09-24', 'TUMOR MALIGNO DE MAMA', '2026-07-24 08:00:00', NULL, 'JORGE ADAN ALEGRIA BAÑOS', '6976947', 'pendiente', '2026-07-23 12:15:16', '2026-07-24 11:56:46', NULL),
(17, 5, 2, 'ONCOLOGÍA', 'RUBEN SANCHEZ SANCHEZ', 'M', NULL, 1.00, 'NA', 'NA', 'NA', '2162428', '1977-02-09', 'CA DE RECTO', '2026-08-24 08:00:00', NULL, 'HECTOR MARTINEZ GOMEZ', '216428', 'pendiente', '2026-07-24 12:07:47', '2026-07-24 12:07:47', NULL),
(18, 5, 2, 'ONCOLOGÍA MEDICA', 'CLAUDIA SANTAMARIA CAMPOS', 'F', NULL, 84.00, 'S/D', 'S/D', '', 'S/D', '1970-03-24', 'S/D', '2026-07-28 17:00:00', NULL, 'ASLHIE OYUKI ORZUNA VAZQUEZ', '10910384', 'pendiente', '2026-07-28 18:59:28', '2026-07-28 18:59:28', NULL),
(19, 5, 2, 'ONCOLOGÍA MEDICA', 'ALEJANDRA LILIANA GODINEZ RODRIGUEZ', 'F', NULL, 55.00, '1228', '2', '', 'NA', '1979-04-24', 'CA GASTRICO', '2026-07-30 08:00:00', NULL, 'ASLHIE OYUKI ORZUNA VAZQUEZ', '10910384', 'pendiente', '2026-07-30 12:01:24', '2026-07-30 12:02:51', NULL),
(20, 5, 2, 'MEDICINA INTERNA', 'MARIA MAGDALENA VARELA ZAMORA', 'F', NULL, 1.00, 'NA', 'NA', 'NA', 'NA', '1967-10-05', 'VASCULITIS', '2026-08-01 11:00:00', NULL, 'ROMAN HERNADEZ RIOS', '4334832', 'enproceso', '2026-07-30 12:08:56', '2026-08-01 15:14:02', '6'),
(21, 8, 48, 'HEMATOLOGIA', 'RAYMUNDO TORRES MAYA', 'M', NULL, 85.00, '1333', '3', '', 'NA', '1983-11-11', 'LINFOMA DE ALTO GRADO', '2026-08-01 10:45:00', NULL, 'RAUL MARTINEZ CASTRO', '10144369', 'pendiente', '2026-08-01 15:42:28', '2026-08-01 15:42:28', NULL),
(23, 5, 2, 'HEMATOLOGIA', 'RAYMUNDO TORRES MAYA', 'M', NULL, 85.00, '1333', '3', '', 'NA', '1983-11-11', 'LINFOMA DE ALTO GRADO', '2026-08-01 10:45:00', NULL, 'RAUL MARTINEZ CASTRO', '10144369', 'enproceso', '2026-08-01 15:46:58', '2026-08-01 16:11:26', '7'),
(24, 5, 2, 'HOSPITALIZACION', 'SHARIN EDITH JIMENEZ MEDINA', 'F', NULL, 57.00, 'NA', 'NA', '', 'NA', '1986-10-09', 'CA DE MAMA', '2026-08-01 15:21:00', NULL, 'EDUARDO BUENDIA', '7785635', 'pendiente', '2026-08-01 20:23:22', '2026-08-01 20:23:22', NULL),
(26, 5, 2, 'ONCOLOGÍA', 'ADRIANA LILI VAZQUEZ', 'F', NULL, 102.00, 'NA', 'NA', '', 'NA', '1966-04-13', 'TUMOR MALIGNO DE MAMA TRIPLE NEGATIVO', '2026-08-01 15:53:00', NULL, 'JORGE ADAN ALEGRIA BAÑOS', '6976947', 'pendiente', '2026-08-01 20:54:26', '2026-08-01 20:54:26', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitud_patients`
--

CREATE TABLE `solicitud_patients` (
  `id` bigint UNSIGNED NOT NULL,
  `nombre_paciente` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos_paciente` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `servicio` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `piso` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registro` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `diagnostico` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_nacimiento` date NOT NULL,
  `edad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `peso` decimal(8,3) NOT NULL,
  `sexo` enum('Femenino','Masculino') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicitud_patients`
--

INSERT INTO `solicitud_patients` (`id`, `nombre_paciente`, `apellidos_paciente`, `servicio`, `cama`, `piso`, `registro`, `diagnostico`, `fecha_nacimiento`, `edad`, `peso`, `sexo`, `created_at`, `updated_at`) VALUES
(87, 'Hermes EDITADO Algo', 'Delgado Díaz', 'AJREHF', '44', 'PH', 'JEH721312 EDITADO', 'Anemia', '2003-08-26', '20 año(s) 7 mes(es) 27 día(s)', 75.000, 'Masculino', '2024-03-12 19:38:39', '2024-04-23 02:38:53'),
(89, 'Marco Antonio', 'Perez Moreno', 'Algo', '44', '3', 'JEH721312k', 'Diabetes', '2024-03-02', '2 mes(es) 22 día(s)', 75.000, 'Masculino', '2024-03-12 19:57:13', '2024-05-25 16:21:19'),
(90, 'Susan', 'Díaz Andrade', 'AJREHF07', '10', '5', 'JEH721312k', 'Cáncer de mama', '1975-04-24', '48 año(s) 11 mes(es) 17 día(s)', 46.000, 'Masculino', '2024-03-12 20:07:04', '2024-04-12 00:54:42'),
(91, 'Monica', 'Espinoza Romero', 'AJREHF33', '12', 'PH', 'JEH7213pL', 'Sindrome de Gillian Barret', '1965-05-05', '58 año(s) 11 mes(es) 7 día(s)', 65.000, 'Femenino', '2024-03-12 20:13:21', '2024-04-13 12:03:30'),
(92, 'Hermes', 'Delgado Díaz', 'AJREHF', '44', 'PH', 'JEH721312', 'Anemia', '2003-08-26', '20 año(s) 7 mes(es) 16 día(s)', 75.000, 'Masculino', '2024-03-21 23:35:32', '2024-04-12 00:54:24'),
(93, 'Hermes EDITADO DOS', 'Delgado Díaz', 'AJREHF', '44', 'PH', 'JEH721312', 'Anemia', '2003-08-26', '20 año(s) 7 mes(es) 16 día(s)', 75.000, 'Masculino', '2024-03-21 23:37:24', '2024-04-12 00:54:56'),
(94, 'Susan', 'Díaz Barrera', 'Ginecología', '12', '3', 'ABB3455', 'Cáncer de mama', '1975-04-24', '48 año(s) 11 mes(es) 17 día(s)', 78.000, 'Femenino', '2024-04-10 01:26:13', '2024-04-12 00:54:35'),
(95, 'Susan', 'Díaz Barrera', 'Ginecología', '56', '5', 'JEH721312', 'Cáncer de mama', '1975-04-24', '48 año(s) 11 mes(es) 17 día(s)', 75.000, 'Femenino', '2024-04-10 01:43:43', '2024-04-12 00:55:04'),
(96, 'Juan Pablo', 'Sánchez Mendoza', 'Terapia Intensiva', '12', NULL, NULL, 'Diabetes', '1956-04-02', '68 año(s) 20 día(s)', 66.000, 'Masculino', '2024-04-11 23:46:18', '2024-04-23 11:52:43'),
(97, 'Luis Angel', 'Rojas Espinoza', 'Tiene Tos', '5', '19', NULL, NULL, '2024-04-08', '14 día(s)', 55.000, 'Masculino', '2024-04-12 02:08:10', '2024-04-23 01:58:30'),
(98, 'Eduardo', 'Villada', 'Medicina Clinica', '44', '3', '1500274789', 'Linfoma de Hodgkin', '1980-06-19', '43 año(s) 9 mes(es) 23 día(s)', 60.000, 'Masculino', '2024-04-13 14:25:57', '2024-04-13 14:25:57'),
(99, 'Eduardo', 'Villada', 'Medicina Clinica', '44', '3', '1500274789', 'Linfoma de Hodgkin', '1993-06-23', '30 año(s) 9 mes(es) 19 día(s)', 70.000, 'Femenino', '2024-04-13 14:37:31', '2024-04-13 14:37:31'),
(100, 'Victor', 'Sánchez Pérez', 'Medicina Clinica', '44', '19', '1500274789', 'Linfoma de Hodgkin', '1975-03-03', '49 año(s) 1 mes(es) 19 día(s)', 66.000, 'Masculino', '2024-04-18 00:05:10', '2024-04-23 01:02:31'),
(101, 'gfdgfdgfd', 'fdsfsf', 'fdsf', 'dsdsfdsfds', 'df', 'fdsfds', 'dsfsfds', '2024-04-12', '18 día(s)', 54.000, 'Femenino', '2024-04-25 13:15:04', '2024-05-01 13:58:02'),
(102, 'gfdgfdgfd', 'fdsfdsfds', 'fdfdsfs', '342', '432', 'fdsfdsfdsf', 'fdsfdsfds', '2024-04-30', '14 día(s)', 34.000, 'Femenino', '2024-05-15 11:29:18', '2024-05-15 11:29:18'),
(103, 'fdsfdsf', 'fdsfdsfds', 'fdsfdsf', 'fdsfds', NULL, 'fdsfdsfds', 'fdsfdsfds', '2024-04-30', '16 día(s)', 32.000, 'Femenino', '2024-05-17 13:42:48', '2024-05-17 13:42:48'),
(104, 'gfdgdfgfd', 'gdfgdfgd', '4543543', NULL, NULL, NULL, NULL, '2024-05-07', '9 día(s)', 23.000, NULL, '2024-05-17 13:50:44', '2024-05-17 13:50:44'),
(105, 'sdsdsadsada', 'dsadsadsadas', 'dsadsa', NULL, NULL, NULL, NULL, '2024-05-14', '2 día(s)', 221.000, 'Femenino', '2024-05-17 16:19:26', '2024-05-17 16:19:26'),
(106, 'fdsfsd', 'fdsfds', 'fdsfds', NULL, NULL, NULL, NULL, '2024-05-09', '12 día(s)', 43.000, 'Masculino', '2024-05-22 10:35:02', '2024-05-22 10:35:02'),
(107, 'dsa', 'dsadsad', 'fdsfdsf', NULL, NULL, NULL, NULL, '2024-05-15', '6 día(s)', 21.000, NULL, '2024-05-22 11:25:03', '2024-05-22 11:25:03'),
(108, 'dsa', 'dsadsad', 'fdsfdsf', NULL, NULL, NULL, NULL, '2024-05-15', '6 día(s)', 21.000, NULL, '2024-05-22 11:29:47', '2024-05-22 11:29:47'),
(109, 'dsa', 'dsadsad', 'fdsfdsf', NULL, NULL, NULL, NULL, '2024-05-15', '6 día(s)', 21.000, NULL, '2024-05-22 11:37:25', '2024-05-22 11:37:25'),
(110, 'gfgfdgfd', 'gfdgdfg', 'fgdf', NULL, NULL, NULL, NULL, '2024-05-15', '6 día(s)', 43.000, 'Femenino', '2024-05-22 11:37:51', '2024-05-22 11:37:51'),
(111, 'gfgfdgfd', 'gfdgdfg', 'fgdf', NULL, NULL, NULL, NULL, '2024-05-15', '6 día(s)', 43.000, 'Femenino', '2024-05-22 11:50:28', '2024-05-22 11:50:28'),
(112, 'gfgfdgfd', 'gfdgdfg', 'fgdf', NULL, NULL, NULL, NULL, '2024-05-15', '6 día(s)', 43.000, 'Femenino', '2024-05-22 11:54:27', '2024-05-22 11:54:27'),
(113, 'gdfgfdgd', 'gdfgdfg', '32', NULL, NULL, NULL, NULL, '2024-05-14', '7 día(s)', 434.000, NULL, '2024-05-22 12:24:38', '2024-05-22 12:24:38'),
(114, 'gfdgdf', 'gdfgdfgd', 'gdfgf', NULL, NULL, NULL, NULL, '2024-05-16', '5 día(s)', 34.000, NULL, '2024-05-22 12:27:23', '2024-05-22 12:27:23'),
(115, 'gfdgdfgdf', 'gfdgdfgd', 'fgfdgd', NULL, NULL, NULL, NULL, '2024-05-15', '6 día(s)', 43.000, NULL, '2024-05-22 12:58:31', '2024-05-22 12:58:31'),
(116, 'gdfgdf', 'gdfgdf', 'gdfgdfg', NULL, NULL, NULL, NULL, '2024-05-16', '5 día(s)', 43.000, NULL, '2024-05-22 13:02:11', '2024-05-22 13:02:11'),
(117, 'fdsfdsf', 'fdfds', 'fdfds', NULL, NULL, NULL, NULL, '2024-05-08', '13 día(s)', 32.000, 'Masculino', '2024-05-22 13:08:42', '2024-05-22 13:08:42'),
(118, 'gfgfdg', 'fgfdgdfgdfg', 'fdsfdsf', NULL, NULL, NULL, NULL, '2024-05-07', '14 día(s)', 20.000, NULL, '2024-05-22 17:10:23', '2024-05-22 17:10:23'),
(119, 'gdfgdfgdf', 'gdfgdfgdfg', 'gfdgfd', NULL, NULL, NULL, NULL, '2024-05-08', '13 día(s)', 3.000, NULL, '2024-05-22 17:19:58', '2024-05-22 17:19:58'),
(120, 'gfhfghfg', 'hfghfghgfhg', 'hgfhgf', NULL, NULL, NULL, NULL, '2024-05-15', '7 día(s)', 54.000, NULL, '2024-05-23 12:27:56', '2024-05-23 12:27:56'),
(121, 'gdfggfg', 'fdgdfgdfg', '4343', NULL, NULL, NULL, NULL, '2024-05-16', '6 día(s)', 21.000, NULL, '2024-05-23 16:20:54', '2024-05-23 16:20:54'),
(122, 'hfghfgh', 'hfghfg', 'gfddgf', NULL, NULL, NULL, NULL, '2024-05-16', '6 día(s)', 34.000, NULL, '2024-05-23 16:25:34', '2024-05-23 16:25:34'),
(123, 'hfghfgh', 'hfghfg', 'gfddgf', NULL, NULL, NULL, NULL, '2024-05-16', '6 día(s)', 34.000, NULL, '2024-05-23 16:27:24', '2024-05-23 16:27:24'),
(124, 'gdfgdf', 'gdfgdf', 'fgd', NULL, NULL, NULL, NULL, '2024-05-14', '8 día(s)', 43.000, NULL, '2024-05-23 16:27:56', '2024-05-23 16:27:56'),
(125, 'fdsfdsfds', 'dsfdsfds', '232', NULL, NULL, NULL, NULL, '2024-05-07', '15 día(s)', 23.000, NULL, '2024-05-23 16:28:38', '2024-05-23 16:28:38'),
(126, 'fgbgcxgcx', 'bcxbbvcbvcb', '323', NULL, NULL, NULL, NULL, '2024-05-16', '7 día(s)', 32.000, NULL, '2024-05-24 13:34:55', '2024-05-24 13:34:55'),
(127, 'LUis', 'Rojas', 'fdfdsfsd', NULL, NULL, NULL, NULL, '2024-05-14', '9 día(s)', 43.000, NULL, '2024-05-24 13:51:04', '2024-05-24 13:51:04'),
(128, 'LUis', 'gdffgdfg', 'fdsfdsf', NULL, NULL, NULL, NULL, '2024-05-13', '10 día(s)', 323.000, NULL, '2024-05-24 14:05:10', '2024-05-24 14:05:10'),
(129, 'gfdgfdgfd', 'gffdgdfgfd', 'fgfdgdf', NULL, NULL, NULL, NULL, '2024-05-21', '2 día(s)', 32.000, NULL, '2024-05-24 17:20:46', '2024-05-24 17:20:46'),
(130, 'gdfgdf', 'gdfgdfgdf', 'gfgdf', NULL, NULL, NULL, NULL, '2024-05-15', '8 día(s)', 43.000, NULL, '2024-05-24 17:47:25', '2024-05-24 17:47:25'),
(131, 'hfg', 'hgfhfghfghfg', 'ghfghfg', NULL, NULL, NULL, NULL, '2024-05-22', '2 día(s)', 54.000, NULL, '2024-05-25 09:47:53', '2024-05-25 09:47:53'),
(132, 'fdsfdsfds', 'fdsfdsfds', 'fdsfds', NULL, NULL, NULL, NULL, '2024-04-30', '24 día(s)', 32.000, NULL, '2024-05-25 09:58:44', '2024-05-25 09:58:44'),
(133, 'dsadsadsa', 'dsadsads', 'sdsad', NULL, NULL, NULL, NULL, '2024-05-14', '10 día(s)', 212.000, NULL, '2024-05-25 09:59:28', '2024-05-25 09:59:28'),
(134, 'dsadsadsa', 'dsadsads', 'sdsad', NULL, NULL, NULL, NULL, '2024-05-14', '10 día(s)', 212.000, NULL, '2024-05-25 09:59:28', '2024-05-25 09:59:28'),
(135, 'fdsfdsdfs', 'fdsfds', '32', NULL, NULL, NULL, NULL, '2024-05-14', '10 día(s)', 32.000, NULL, '2024-05-25 10:22:41', '2024-05-25 10:22:41'),
(136, 'fd', 'ffdsfds', '323', NULL, NULL, NULL, NULL, '2024-05-14', '1 mes(es) 14 día(s)', 32.000, NULL, '2024-05-25 10:47:40', '2024-06-29 16:00:50'),
(137, 'LUis', 'fdsfds', '4324', NULL, NULL, NULL, NULL, '2024-05-22', '2 día(s)', 32.000, NULL, '2024-05-25 16:22:13', '2024-05-25 16:22:13'),
(138, 'fgfdgdfg', 'fgfdgdf', 'gfgdg', '32', '43', 'VIMSM432', 'Lupus', '2024-05-14', '17 día(s)', 2.000, 'Masculino', '2024-05-25 16:24:44', '2024-06-01 12:19:04'),
(139, 'fgfdgdfgdf', 'gdfgfdgdfg', '323', NULL, NULL, NULL, NULL, '2024-05-22', '9 día(s)', 32.000, NULL, '2024-05-25 16:50:16', '2024-06-01 10:49:34'),
(140, 'gfdgfdgfd', 'hfghfghfg', '43', NULL, NULL, NULL, NULL, '2024-05-14', '14 día(s)', 43.000, NULL, '2024-05-25 16:55:40', '2024-05-29 15:57:38'),
(141, 'LUis', 'Rojas', 'fdfdsfsd', NULL, NULL, NULL, NULL, '2024-05-08', '20 día(s)', 32.000, NULL, '2024-05-28 11:28:11', '2024-05-29 12:50:25'),
(142, 'gfdgfdgfd', 'Rojas', 'fdfdsfsd', NULL, NULL, NULL, NULL, '2024-05-15', '13 día(s)', 21.000, NULL, '2024-05-28 11:39:11', '2024-05-29 12:10:58'),
(143, 'fsfd', 'fdsfdsfds', '32', NULL, NULL, NULL, NULL, '2024-05-22', '1 mes(es) 6 día(s)', 434.000, 'Masculino', '2024-06-01 12:20:21', '2024-06-29 16:48:48'),
(144, 'gfdgfdgfd', 'Rojas', 'fdfdsfsd', NULL, NULL, NULL, NULL, '2024-06-03', '1 día(s)', 4343.000, 'Femenino', '2024-06-05 15:54:01', '2024-06-05 15:54:01'),
(145, 'LUis', 'fdsfdsfds', 'fdfdsfsd', NULL, NULL, NULL, NULL, '2024-06-26', '2 día(s)', 43.000, 'Femenino', '2024-06-29 12:54:11', '2024-06-29 12:54:11'),
(146, 'Katy', 'Rojas', 'Nutricional', NULL, NULL, NULL, NULL, '2024-06-26', '2 día(s)', 5436.000, 'Femenino', '2024-06-29 16:37:11', '2024-06-29 16:37:11'),
(147, 'gfdgfdgfd', 'Rojas', 'fdfdsfsd', NULL, NULL, NULL, NULL, '2024-06-04', '24 día(s)', 656.000, NULL, '2024-06-29 16:38:10', '2024-06-29 16:38:10'),
(148, 'LUis', 'fdsfdsfds', 'fdfdsfsd', NULL, NULL, NULL, NULL, '2024-06-26', '2 día(s)', 43.000, 'Masculino', '2024-06-29 17:16:14', '2024-06-29 17:16:14'),
(149, 'LUis', 'fdsfdsfds', 'fdfdsfsd', NULL, NULL, NULL, NULL, '2024-06-26', '2 día(s)', 43.000, 'Masculino', '2024-06-29 17:20:03', '2024-06-29 17:20:03'),
(150, 'gfdgfdgfd', 'fdsfdsfds', 'fdfdsfsd', NULL, NULL, NULL, NULL, '2024-06-19', '9 día(s)', 43.000, 'Femenino', '2024-06-29 17:20:41', '2024-06-29 17:20:41'),
(151, 'LUis', 'Rojas', 'fdfdsfsd', NULL, NULL, NULL, NULL, '2024-06-10', '18 día(s)', 54.000, NULL, '2024-06-29 17:32:26', '2024-06-29 17:32:26'),
(152, 'gdfgdf', 'dsadsad', 'fdfdsfsd', NULL, NULL, NULL, NULL, '2024-06-25', '3 día(s)', 54.000, 'Femenino', '2024-06-29 17:35:24', '2024-06-29 17:35:24'),
(153, 'LUis', 'fdsfdsfds', 'fdfdsfsd', NULL, NULL, NULL, NULL, '2024-06-19', '9 día(s)', 43.000, NULL, '2024-06-29 17:36:38', '2024-06-29 17:36:38'),
(154, 'LUis', 'Rojas', 'gdfgfd', NULL, NULL, NULL, NULL, '2024-06-12', '16 día(s)', 43.000, 'Femenino', '2024-06-29 17:38:46', '2024-06-29 17:38:46'),
(155, 'Eduardo', 'Lobaton Villareal', 'Medicina clinica', '4', '3', '1500274789', 'Lifoma de Hodgkin', '1963-06-06', '61 año(s) 25 día(s)', 60.000, 'Masculino', '2024-07-02 09:05:42', '2024-07-02 09:05:42'),
(156, 'Alvarado', 'Santiago', 'NEONATOLOGÍA', NULL, NULL, '1500285716', 'RECIÉN NACIDO LMÍTROFE + PB. HIRSCHPRUNG + ILEO MECONIAL', '2024-03-04', '3 mes(es) 28 día(s)', 2.880, 'Masculino', '2024-07-02 09:28:53', '2024-07-02 09:28:53'),
(157, 'RN', 'GONZALEZ GARCIA', 'UCIN', NULL, NULL, '24-8152', NULL, '2024-11-28', '7 día(s)', 1.270, 'Masculino', '2024-12-06 10:20:08', '2024-12-06 10:20:08'),
(158, 'RN', 'AGUIRRE OCAÑA', 'UCIN', '2', 'PEDIATRIA', '205114', NULL, '2024-12-02', '3 día(s)', 2.200, 'Masculino', '2024-12-06 10:41:25', '2024-12-06 10:41:25'),
(159, 'RN', 'GUTIERREZ GUERRERO', 'UCIN', '2', 'PEDIATRIA', '204999', NULL, '2024-11-26', '9 día(s)', 3.800, 'Masculino', '2024-12-06 10:43:31', '2024-12-06 10:43:31'),
(160, 'RN', 'CAMACHO MONTAÑO', 'UCIN', '1', NULL, '204558', NULL, '2024-11-07', '28 día(s)', 1.345, 'Masculino', '2024-12-06 10:46:45', '2024-12-06 10:46:45'),
(161, 'RN', 'GONZALEZ MENESES', 'UCIN', '1', NULL, '204517', NULL, '2024-11-05', '1 mes(es) ', 1.630, 'Femenino', '2024-12-06 10:50:52', '2024-12-06 10:50:52'),
(162, 'RN', 'GONZALEZ PERALTA', 'UCIN', '1', NULL, '204816', NULL, '2024-11-19', '16 día(s)', 0.775, 'Femenino', '2024-12-06 10:54:20', '2024-12-06 10:54:20'),
(163, 'RN', 'GARCIA BARREDA', 'CUNERO', NULL, NULL, '463079', NULL, '2024-11-14', '21 día(s)', 2.052, 'Masculino', '2024-12-06 11:15:21', '2024-12-06 11:15:21'),
(164, 'RN', 'SANCHEZ NOLASCO', 'CUNERO', NULL, NULL, '462596', NULL, '2024-11-14', '21 día(s)', 2.710, 'Femenino', '2024-12-06 11:22:45', '2024-12-06 11:22:45'),
(165, 'JESUS', 'MARTINEZ RAMIREZ', 'CUNERO', NULL, NULL, '463460', NULL, '2024-11-06', '29 día(s)', 3.410, 'Masculino', '2024-12-06 11:24:28', '2024-12-06 11:24:28'),
(166, 'RN', 'MARCIAL ROJAS', 'UCIN', NULL, NULL, '463483', NULL, '2024-11-26', '9 día(s)', 1.950, 'Femenino', '2024-12-06 11:26:33', '2024-12-06 11:26:33'),
(167, 'RN', 'VARGAS RUEDA', 'UCIN', NULL, NULL, '463568', NULL, '2024-11-28', '7 día(s)', 2.335, 'Femenino', '2024-12-06 11:28:12', '2024-12-06 11:28:12'),
(168, 'RN', 'GARCIA SANCHEZ', 'UCIN', NULL, NULL, '208727', NULL, '2024-11-26', '9 día(s)', 1.865, 'Masculino', '2024-12-06 11:51:24', '2024-12-06 11:51:24'),
(169, 'RN', 'GARCIA ANDRES', 'UCIN', NULL, NULL, '208617', NULL, '2024-11-22', '13 día(s)', 1.255, 'Femenino', '2024-12-06 11:52:58', '2024-12-06 11:52:58'),
(170, 'RN', 'COLIN PEREZ', 'UCIN', NULL, NULL, '208513', NULL, '2024-11-21', '14 día(s)', 1.780, 'Masculino', '2024-12-06 11:54:52', '2024-12-06 11:54:52'),
(171, 'RN', 'ARRIAGA SOTELO', 'UCIN', NULL, NULL, '208720', NULL, '2024-11-26', '9 día(s)', 1.920, 'Masculino', '2024-12-06 11:56:19', '2024-12-06 11:56:19'),
(172, 'RN', 'MEJIA ALVARADO', 'UCIN', NULL, NULL, '107716', NULL, '2024-12-03', '2 día(s)', 2.100, 'Femenino', '2024-12-06 12:30:00', '2024-12-06 12:30:00'),
(173, 'MIGUEL ANGEL', 'GARCIA CASTRO', 'PEDIATRIA', NULL, NULL, NULL, NULL, '2024-09-27', '2 mes(es) 8 día(s)', 1.900, 'Masculino', '2024-12-06 12:33:18', '2024-12-06 12:33:18'),
(174, 'RN', 'VENTURA GUADALUPE', 'UCIN', NULL, NULL, '0113563', NULL, '2024-11-07', '29 día(s)', 1.015, 'Masculino', '2024-12-07 08:33:45', '2024-12-07 08:33:45'),
(175, 'RECIEN NACIDO', 'SOLANO PEREZ CISNEROS', 'UCIN', 'UCIN1', '2 PISO', '1500636139', 'DESNUTRICIÓN', '2024-11-25', '12 día(s)', 1.110, 'Masculino', '2024-12-08 10:42:30', '2024-12-08 10:42:30'),
(176, 'EMILIO', 'RODRIGUEZ NASSAR', 'HOSPITALIZACION 6', '615', '6 PISO', '1500640877', 'TROMBOSIS  DE MIEMBROS PELVICOS', '1958-10-25', '66 año(s) 1 mes(es) 13 día(s)', 60.000, 'Masculino', '2024-12-08 15:39:01', '2024-12-08 15:39:01'),
(177, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', 'PB', '1500599769', 'SEPSIS', '1953-10-12', '71 año(s) 1 mes(es) 26 día(s)', 60.000, 'Femenino', '2024-12-08 16:17:20', '2024-12-08 16:17:20'),
(178, 'RECIEN NACIDO', 'SOLANO PEREZ CISNEROS', 'UCIN', 'UCIN1', '2 PISO', '1500636139', 'DESNUTRICIÓN', '2024-11-25', '13 día(s)', 1.110, 'Masculino', '2024-12-09 10:18:40', '2024-12-09 10:18:40'),
(179, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOSPITALIZACIÓN 6', '615', '6', '1500640877', 'TROMBOSIS  DE MIEMBROS PÉLVICOS', '1958-10-25', '66 año(s) 1 mes(es) 14 día(s)', 60.000, 'Masculino', '2024-12-09 13:40:23', '2024-12-09 13:40:23'),
(180, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICIÓN', '1953-10-12', '71 año(s) 1 mes(es) 27 día(s)', 60.000, 'Femenino', '2024-12-09 15:13:07', '2024-12-09 15:13:07'),
(181, 'RN', 'SOLANO PEREZ CISNEROS', 'UCIN', 'UCIN 1', '2DO', '1500636139', 'DESNUTRICIÓN', '2024-11-25', '14 día(s)', 1.110, 'Masculino', '2024-12-10 08:50:10', '2024-12-10 08:50:10'),
(182, 'JUDITH', 'BARCELATA HALL', 'TERAPIA', 'UTI-6', '1', '1500599769', 'SEPSIS ABDOMINAL', '1953-10-12', '71 año(s) 1 mes(es) 28 día(s)', 60.000, 'Femenino', '2024-12-10 11:56:03', '2024-12-10 11:56:03'),
(183, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOSPITALIZACIÓN', '615', '6', '1500640877', 'TROMBOSIS DE MIEMBROS PELVICOS', '1958-10-25', '66 año(s) 1 mes(es) 15 día(s)', 60.000, 'Masculino', '2024-12-10 12:11:35', '2024-12-10 12:11:35'),
(184, 'MOISES', 'GOÑI DÍAZ', 'HOSPITALIZACIÓN', '508', '5', '1500651802', 'OBSTRUCCIÓN INTESTINAL', '1949-04-20', '75 año(s) 7 mes(es) 19 día(s)', 69.000, 'Masculino', '2024-12-10 14:24:23', '2024-12-10 14:24:23'),
(185, 'RN', 'SOLANO PEREZ CISNEROS', 'UCIN', 'UCIN 1', '2DO', '1500636139', 'DESNUTRICIÓN', '2024-11-25', '15 día(s)', 1.110, 'Masculino', '2024-12-11 08:34:36', '2024-12-11 08:34:36'),
(186, 'RN', 'SOLANO PEREZ CISNEROS', 'UCIN', 'UCIN 1', '2DO', '1500636139', 'DESNUTRICIÓN', '2024-11-25', '15 día(s)', 1.110, 'Masculino', '2024-12-11 09:02:11', '2024-12-11 09:02:11'),
(187, 'JUDITH', 'BARCELATA HALL', 'TERAPIA  INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICIÓN', '1953-10-12', '71 año(s) 1 mes(es) 29 día(s)', 80.000, 'Femenino', '2024-12-11 11:32:30', '2024-12-11 11:32:30'),
(188, 'JUDITH', 'BARCELATA HALL', 'TERAPIA  INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICIÓN', '1953-10-12', '71 año(s) 1 mes(es) 29 día(s)', 80.000, 'Femenino', '2024-12-11 11:46:40', '2024-12-11 11:46:40'),
(189, 'MOISES', 'GOÑI DÍAZ', 'HOSPITALIZACIÓN', '508', '5', '1500651802', 'DESNUTRICIÓN', '1949-04-20', '75 año(s) 7 mes(es) 20 día(s)', 69.000, 'Masculino', '2024-12-11 13:36:20', '2024-12-11 13:36:20'),
(190, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOSPITALIZACIÓN', '609', '6', '1500640877', 'DESNUTRICIÓN', '1958-05-24', '66 año(s) 6 mes(es) 17 día(s)', 60.000, 'Masculino', '2024-12-11 14:48:59', '2024-12-11 14:48:59'),
(191, 'RN', 'SOLANO PEREZ CISNEROS', 'CUNAS', 'UCIN 1', '2', '1500636139', 'PREMATUREZ', '2024-11-25', '16 día(s)', 1.139, 'Masculino', '2024-12-12 10:00:55', '2024-12-12 10:00:55'),
(192, 'BERNARDO', 'RAMIREZ JOAQUIN', 'TERAPIA INTENSIVA', 'UTIP 3', '1', '1500653468', NULL, '2023-04-13', '1 año(s) 7 mes(es) 28 día(s)', 8.500, 'Masculino', '2024-12-12 13:40:22', '2024-12-12 13:40:22'),
(193, 'BERNARDO', 'RAMIREZ JOAQUIN', 'TERAPIA INTENSIVA', 'UTIP 3', '1', '1500653468', 'CUADRO INFECCIOSO PULMONAR ACTIVO', '2023-04-13', '1 año(s) 7 mes(es) 28 día(s)', 8.500, 'Masculino', '2024-12-12 14:04:21', '2024-12-12 14:04:21'),
(194, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOSPITALIZACION', '609', '6', '1500640877', 'TROMBOSIS MIEMBROS INFERIORES', '1958-10-25', '66 año(s) 1 mes(es) 17 día(s)', 60.000, 'Masculino', '2024-12-12 14:54:11', '2024-12-12 14:54:11'),
(195, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI 6', '1', '1500599769', 'SEPSIS ABDOMINAL', '1953-10-12', '71 año(s) 1 mes(es) 30 día(s)', 60.000, 'Femenino', '2024-12-12 15:07:17', '2024-12-12 15:07:17'),
(196, 'RN', 'SOLANO PEREZ CISNEROS', 'NEONATOLOGÍA', 'UCIN-1', '2', '1500636139', 'RECIEN NACIDO', '2024-11-25', '17 día(s)', 1.272, 'Masculino', '2024-12-13 09:32:29', '2024-12-13 09:32:29'),
(197, 'BERNARDO', 'RAMIREZ JOAQUIN', 'TERAPIA INTENSIVA', 'UTIP 3', '1', '150065', 'DESNUTRICION', '2023-04-13', '1 año(s) 7 mes(es) 29 día(s)', 8.500, 'Masculino', '2024-12-13 11:18:16', '2024-12-13 11:18:16'),
(198, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICION', '1953-10-12', '71 año(s) 2 mes(es) ', 60.000, 'Femenino', '2024-12-13 11:50:04', '2024-12-13 11:50:04'),
(199, 'ELENA MARIA', 'GONZÁLEZ CARDENAS', 'TERAPIA INTENSIVA', 'UTI-1', '1', '1500646263', 'DESNUTRICION', '1936-03-09', '88 año(s) 9 mes(es) 3 día(s)', 64.000, 'Femenino', '2024-12-13 14:22:43', '2024-12-13 14:22:43'),
(200, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOSPITALIZACION', '609', '6', '1500640877', 'DESNUTRICION', '1958-10-25', '66 año(s) 1 mes(es) 18 día(s)', 60.000, 'Masculino', '2024-12-13 14:30:40', '2024-12-13 14:30:40'),
(201, 'RN SOLANO', 'PEREZ CISNEROS', 'CUNAS', 'UCIN 1', '2', '1500636139', 'DESNUTRICION', '2024-11-25', '18 día(s)', 1.272, 'Masculino', '2024-12-14 10:51:08', '2024-12-14 10:51:08'),
(202, 'BERNARDO', 'RAMIREZ JOAQUIN', 'TERAPIA INTENSIVA PEDIATRICA', 'UTIP 3', '1', '1500653468', 'DESNUTRICION', '2023-04-13', '1 año(s) 8 mes(es) ', 8.500, 'Masculino', '2024-12-14 11:19:12', '2024-12-14 11:19:12'),
(203, 'JOSE ANTONIO', 'RAMIREZ HERNANDEZ', 'CUIDADOS CORONARIOS', 'UCC4', '1', '1500660821', 'DESNUTRICION', '1994-01-11', '30 año(s) 11 mes(es) 2 día(s)', 85.000, 'Masculino', '2024-12-14 12:40:48', '2024-12-14 12:40:48'),
(204, 'Prueba', 'Prueba', 'Prueba', NULL, NULL, NULL, NULL, '2024-07-31', '4 mes(es) 13 día(s)', 10.000, 'Masculino', '2024-12-14 12:48:29', '2024-12-14 12:48:29'),
(205, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICION', '1953-10-12', '71 año(s) 2 mes(es) 1 día(s)', 60.000, 'Femenino', '2024-12-14 12:52:53', '2024-12-14 12:52:53'),
(206, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICION', '1953-10-12', '71 año(s) 2 mes(es) 1 día(s)', 60.000, 'Femenino', '2024-12-14 12:52:53', '2024-12-14 12:52:53'),
(207, 'ELENA MARIA', 'GONZÁLEZ CARDENAS', 'TERAPIA INTENSIVA', 'UTI-1', '1', '1500646263', 'DESNUTRICION', '1936-03-09', '88 año(s) 9 mes(es) 4 día(s)', 65.000, 'Femenino', '2024-12-14 13:25:51', '2024-12-14 13:25:51'),
(208, 'JOSE ANTONIO', 'RAMIREZ HERNANDEZ', 'CUIDADOS CORONARIOS', 'UCC4', '1', '1500660821', 'DESNUTRICION', '1949-01-11', '75 año(s) 11 mes(es) 2 día(s)', 85.000, 'Masculino', '2024-12-14 14:23:05', '2024-12-14 14:23:05'),
(209, 'LUis', 'Rojas', 'fdfdsfsd', NULL, NULL, NULL, NULL, '2024-12-05', '8 día(s)', 32.000, 'Masculino', '2024-12-14 15:47:46', '2024-12-14 15:47:46'),
(210, 'LUis', 'Rojas', 'fdfdsfsd', NULL, NULL, NULL, NULL, '2024-12-05', '8 día(s)', 32.000, 'Masculino', '2024-12-14 15:48:21', '2024-12-14 15:48:21'),
(211, 'LUis', 'Rojas', 'fdfdsfsd', NULL, NULL, NULL, NULL, '2024-12-11', '2 día(s)', 32.000, 'Masculino', '2024-12-14 15:53:45', '2024-12-14 15:53:45'),
(212, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOSPITALIZACIÓN', '609', '6', '1500640877', 'DESNUTRICIÓN', '1958-10-25', '66 año(s) 1 mes(es) 19 día(s)', 60.000, 'Masculino', '2024-12-14 15:59:33', '2024-12-14 15:59:33'),
(213, 'RN SOLANO', 'PEREZ CISNEROS', 'CUNAS', 'UCIN 1', '2', '1500636139', 'DESNUTRICION', '2024-11-25', '19 día(s)', 1.330, 'Masculino', '2024-12-15 09:16:27', '2024-12-15 09:16:27'),
(214, 'JOSE ANTONIO', 'RAMIREZ HERNANDEZ', 'CUIDADOS CORONARIOS', 'UCC4', '1', '1500660821', 'DESNUTRICION', '1994-01-11', '30 año(s) 11 mes(es) 3 día(s)', 85.000, 'Masculino', '2024-12-15 13:42:30', '2024-12-15 13:42:30'),
(215, 'ELENA MARIA', 'GONZÁLEZ CARDENAS', 'TERAPIA INTENSIVA', 'UTI-1', '1', '1500646263', 'DESNUTRICION', '1936-03-09', '88 año(s) 9 mes(es) 5 día(s)', 64.000, 'Femenino', '2024-12-15 13:52:06', '2024-12-15 13:52:06'),
(216, 'BERNARDO', 'RAMIREZ JOAQUIN', 'TERAPIA INTENSIVA PEDIATRICA', 'UTIP 3', '1', '1500653468', 'NEUMONIA GRAVE', '2023-04-13', '1 año(s) 8 mes(es) 1 día(s)', 8.500, 'Masculino', '2024-12-15 14:05:15', '2024-12-15 14:05:15'),
(217, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOSPITALIZACION', '609', '6', '1500640877', 'TROMBOSIS MIEMBROS INFERIORES', '1958-10-25', '66 año(s) 1 mes(es) 20 día(s)', 60.000, 'Masculino', '2024-12-15 14:15:00', '2024-12-15 14:15:00'),
(218, 'BERNARDO', 'RAMIREZ JOAQUIN', 'TERAPIA INTENSIVA PEDIATRICA', 'UTIP 3', '1', '1500653468', 'NEUMONIA GRAVE', '2023-04-13', '1 año(s) 8 mes(es) 1 día(s)', 8.500, 'Masculino', '2024-12-15 14:52:19', '2024-12-15 14:52:19'),
(219, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'CHOQUE HIPOVOLEMICO', '1953-10-12', '71 año(s) 2 mes(es) 2 día(s)', 60.000, 'Femenino', '2024-12-15 15:01:51', '2024-12-15 15:01:51'),
(220, 'ELENA', 'LOZOYA IDEM', 'HOSPITALIZACION', '308', 'PISO 3', '1500662920', 'DESNUTRICION', '1947-11-14', '77 año(s) 1 mes(es) 1 día(s)', 42.000, 'Femenino', '2024-12-15 20:54:17', '2024-12-16 09:29:36'),
(221, 'RN', 'SOLANO PÉREZ CISNEROS', 'UCIN', 'UCIN 1', 'PRIMERO', '1500636139', 'DESNUTRICIÓN', '2024-11-25', '20 día(s)', 1.349, 'Masculino', '2024-12-16 09:05:37', '2024-12-16 09:05:37'),
(222, 'Bernardo', 'Ramírez Joaquín', 'Terapia intensiva', 'UTP 3', 'PRIMERO', '1500653468', 'NEUMONÍA GRAVE', '2023-04-13', '1 año(s) 8 mes(es) 2 día(s)', 8.500, 'Masculino', '2024-12-16 10:48:34', '2024-12-16 10:48:34'),
(223, 'JOSE ANTONIO', 'RAMIREZ HERNANDEZ', 'UNIDAD CORONARIA', 'UCC 4', 'PRIMERO', '1500660821', 'SINDROME CORONARIO AGUDO', '1949-01-11', '75 año(s) 11 mes(es) 4 día(s)', 85.000, NULL, '2024-12-16 12:19:13', '2024-12-16 12:19:13'),
(224, 'JUDITH', 'BARCELATA HALL', 'Terapia intensiva', 'UTI 6', 'PRIMERO', '1500599769', 'DESNUTRICIÓN', '1953-10-12', '71 año(s) 2 mes(es) 3 día(s)', 60.000, 'Femenino', '2024-12-16 12:28:28', '2024-12-16 12:28:28'),
(225, 'Emilio', 'Nassar Rodríguez', 'Hospitalización', '609', 'Sexto piso', '1500640877', 'Trombosis de miembros pélvicos', '1958-10-25', '66 año(s) 1 mes(es) 21 día(s)', 60.000, 'Masculino', '2024-12-16 12:51:05', '2024-12-16 12:51:05'),
(226, 'RN', 'SOLANO PÉREZ CISNEROS', 'NEONATOLOGÍA', 'UCIN-1', '2', '1500636139', 'DESNUTRICION', '2024-11-25', '21 día(s)', 1.357, 'Masculino', '2024-12-17 09:09:01', '2024-12-17 09:09:01'),
(227, 'RN', 'SOLANO PÉREZ CISNEROS', 'NEONATOLOGÍA', 'UCIN-1', '2', '1500636139', 'DESNUTRICION', '2024-11-25', '21 día(s)', 1.357, 'Masculino', '2024-12-17 09:24:35', '2024-12-17 09:24:35'),
(228, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICION', '1953-10-12', '71 año(s) 2 mes(es) 4 día(s)', 60.000, 'Femenino', '2024-12-17 10:52:49', '2024-12-17 10:52:49'),
(229, 'BERNARDO', 'RAMIREZ JOAQUIN', 'TERAPIA INTENSIVA', 'UTIP 3', '1', '1500653468', 'DESNUTRICION', '2023-04-13', '1 año(s) 8 mes(es) 3 día(s)', 8.500, 'Masculino', '2024-12-17 11:51:25', '2024-12-17 11:51:25'),
(230, 'BERNARDO', 'RAMIREZ JOAQUIN', 'TERAPIA INTENSIVA', 'UTIP 3', '1', '1500653468', 'DESNUTRICION', '2023-04-13', '1 año(s) 8 mes(es) 3 día(s)', 8.500, 'Masculino', '2024-12-17 11:51:27', '2024-12-17 11:51:27'),
(231, 'ELENA', 'LOZOYA IDEN', 'HOSPITALIZACIÓN', '308', '3', '1500662920', 'DESNUTRICION', '1947-11-14', '77 año(s) 1 mes(es) 2 día(s)', 42.000, 'Femenino', '2024-12-17 12:22:09', '2024-12-17 12:22:09'),
(232, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOSPITALIZACION', '609', '6', '1500640877', 'DESNUTRICION', '1958-10-25', '66 año(s) 1 mes(es) 22 día(s)', 60.000, 'Masculino', '2024-12-17 13:35:58', '2024-12-17 13:35:58'),
(233, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACIÓN', '307', '3', '1500660965', 'DESNUTRICION', '1947-07-31', '77 año(s) 4 mes(es) 16 día(s)', 68.000, 'Masculino', '2024-12-17 13:46:23', '2024-12-17 13:46:23'),
(234, 'ELENA MARIA', 'GONZALEZ CARDENAS', 'HOSPITALIZACION', '215', '2', '150064626', 'DESNUTRICION', '1936-03-09', '88 año(s) 9 mes(es) 8 día(s)', 68.000, 'Femenino', '2024-12-17 19:18:17', '2024-12-18 06:33:12'),
(235, 'ELENA MARIA', 'GONZALEZ CARDENAS', 'GINECOLOGÍA', '215', '2', '1500646263', 'DESNUTRICION', '1936-03-09', '88 año(s) 9 mes(es) 8 día(s)', 64.000, 'Femenino', '2024-12-18 08:01:55', '2024-12-18 08:01:55'),
(236, 'RN', 'RAMIREZ VILLAVICENCIO', 'NEONATOLOGIA', 'UCIN 2', '2', '1500664437', 'DESNUTRICION', '2024-12-16', '1 día(s)', 2.880, 'Masculino', '2024-12-18 09:46:41', '2024-12-18 09:46:41'),
(237, 'RN', 'SOLANO PEREZ CISNEROS', 'NEONATOLOGIA', 'UCIN-1', '2', '1500636139', 'DESNUTRICION', '2024-11-25', '22 día(s)', 1.460, 'Masculino', '2024-12-18 10:21:15', '2024-12-18 10:21:15'),
(238, 'ELIMAGDA', 'NAVARRO Y MARTINEZ SOTOMAYOR', 'HOSPITALIZACIÓN', '428', '4', '1500662587', 'DESNUTRICIÓN', '1948-09-25', '76 año(s) 2 mes(es) 22 día(s)', 59.000, 'Femenino', '2024-12-18 10:52:25', '2024-12-18 10:52:25'),
(239, 'BERNARDO', 'RAMIREZ JOAQUIN', 'TERAPIA INTENSIVA', 'UTP- 3', '3', '1500653468', 'FISTULA TRAQUEO- ESOFAGICA', '2023-04-13', '1 año(s) 8 mes(es) 4 día(s)', 8.500, 'Masculino', '2024-12-18 11:24:34', '2024-12-18 11:24:34'),
(240, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICION', '1953-10-12', '71 año(s) 2 mes(es) 5 día(s)', 60.000, 'Femenino', '2024-12-18 11:46:57', '2024-12-18 11:46:57'),
(241, 'BERTHA ELISA', 'NOEGGERATH CARDENAS', 'HOSPITALIZACION', '604', '6', '1500636067', 'DESNUTRICION', '1955-07-29', '69 año(s) 4 mes(es) 19 día(s)', 55.000, 'Femenino', '2024-12-18 13:11:50', '2024-12-18 13:11:50'),
(242, 'ELENA', 'LOZOYA IDEN', 'HOSPITALIZACIÓN', '308', '3', '1500662920', 'DESNUTRICIÓN', '1947-11-14', '77 año(s) 1 mes(es) 3 día(s)', 42.000, 'Femenino', '2024-12-18 13:30:40', '2024-12-18 13:30:40'),
(243, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACIÓN', '307', '3', '1500660965', 'DESNUTRICIÓN', '1947-07-31', '77 año(s) 4 mes(es) 17 día(s)', 68.000, 'Masculino', '2024-12-18 13:47:34', '2024-12-18 13:47:34'),
(244, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOSPITALIZACIÓN', '609', 'PISO 6', '1500640877', 'Trombosis de miembros pélvicos', '1958-10-25', '66 año(s) 1 mes(es) 23 día(s)', 60.000, 'Masculino', '2024-12-18 15:54:16', '2024-12-18 15:54:16'),
(245, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOSPITALIZACIÓN', '609', '6', '1500640877', 'Trombosis de miembros pélvicos', '1958-10-25', '66 año(s) 1 mes(es) 23 día(s)', 60.000, 'Masculino', '2024-12-18 16:10:01', '2024-12-18 16:10:01'),
(246, 'ELENA MARIA', 'GONZALES CARDENAS', 'HOSPITALIZACION', '215', '2', '1500646263', 'SANGRADO UTERINO ANORMAL', '1936-03-09', '88 año(s) 9 mes(es) 9 día(s)', 65.000, 'Femenino', '2024-12-18 22:01:06', '2024-12-19 07:23:26'),
(247, 'ELIMAGDA', 'NAVARRO Y MARTINEZ SOTOMAYOR', 'HOSPITALIZACION', '428', '4', '1500662587', 'DESNUTRICIÓN', '1948-09-25', '76 año(s) 2 mes(es) 23 día(s)', 59.000, 'Femenino', '2024-12-19 09:50:42', '2024-12-19 09:50:42'),
(248, 'RN', 'RAMIREZ VILLAVICENCIO', 'NEONATOLOGIA', 'UCIN-2', '2', '1500664437', 'DESNUTRICIÓN', '2024-12-16', '2 día(s)', 2.880, 'Masculino', '2024-12-19 10:16:52', '2024-12-19 10:16:52'),
(249, 'BERTHA ELISA', 'NOEGGERATH CARDENAS', 'HOSPITALIZACION', '604', '6', '1500636067', 'DESNUTRICIÓN', '1955-07-29', '69 año(s) 4 mes(es) 20 día(s)', 55.000, 'Femenino', '2024-12-19 10:34:28', '2024-12-19 10:34:28'),
(250, 'BERNARDO', 'RAMIREZ JOAQUIN', 'TERAPIA INTENSIVA', 'UTP- 3', '1', '1500653468', 'DESNUTRICIÓN', '2023-04-13', '1 año(s) 8 mes(es) 5 día(s)', 8.500, 'Masculino', '2024-12-19 11:04:38', '2024-12-19 11:04:38'),
(251, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICIÓN', '1953-10-12', '71 año(s) 2 mes(es) 6 día(s)', 60.000, 'Femenino', '2024-12-19 11:17:28', '2024-12-19 11:17:28'),
(252, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOSPITALIZACION', '609', '6', '1500640877', 'TROMBOSIS MIEMBROS PELVICOS', '1958-10-25', '66 año(s) 1 mes(es) 24 día(s)', 60.000, 'Masculino', '2024-12-19 12:16:59', '2024-12-19 12:16:59'),
(253, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACIÓN', '307', '3', '1500660965', 'DESNUTRICIÓN', '1947-07-31', '77 año(s) 4 mes(es) 18 día(s)', 68.000, 'Masculino', '2024-12-19 12:59:48', '2024-12-19 12:59:48'),
(254, 'ELENA', 'LOZOYA IDEN', 'HOSPITALIZACIÓN', '308', '3', '1500662920', 'DESNUTRICIÓN', '1947-11-14', '77 año(s) 1 mes(es) 4 día(s)', 42.000, 'Femenino', '2024-12-19 13:15:38', '2024-12-19 13:15:38'),
(255, 'ELENA MARIA', 'GONZALEZ CARDENAS', 'GINECOLOGÍA', '215', '2', '1500646263', 'HISTERECTOMIA', '1936-03-09', '88 año(s) 9 mes(es) 10 día(s)', 65.000, 'Femenino', '2024-12-20 07:19:34', '2024-12-20 07:19:34'),
(256, 'ELENA', 'LOZOYA IDEN', 'HOSPITALIZACION', '308', '3', '1500662920', 'DESNUTRICION', '1947-11-14', '77 año(s) 1 mes(es) 5 día(s)', 42.000, 'Femenino', '2024-12-20 09:39:19', '2024-12-20 09:39:19'),
(257, 'ELIMAGDA', 'NAVARRO Y MARTINEZ SOTOMAYOR', 'HOSPITALIZACION', '428', '4', '1500662587', 'PERFORACION GASTRICA MAS ABDOMEN AGUDO', '1948-09-25', '76 año(s) 2 mes(es) 24 día(s)', 59.000, 'Femenino', '2024-12-20 10:04:15', '2024-12-20 10:04:15'),
(258, 'RN', 'RAMIREZ VILLAVICENCIO', 'NEONATOLOGIA', 'UCIN-2', '2', '1500664437', 'DESNUTRICION', '2024-12-16', '3 día(s)', 2.880, 'Masculino', '2024-12-20 10:23:05', '2024-12-20 10:23:05'),
(259, 'JUDITH', 'BAECELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICIÓN', '1953-10-12', '71 año(s) 2 mes(es) 7 día(s)', 60.000, 'Femenino', '2024-12-20 10:52:39', '2024-12-20 10:52:39'),
(260, 'RN G2', 'RAMIREZ LARA', 'UCI', '14', NULL, '116852', NULL, '2024-11-10', '1 mes(es) 9 día(s)', 1.630, 'Masculino', '2024-12-20 12:11:29', '2024-12-20 12:11:29'),
(261, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACIÓN', '307', '3', '1500660965', 'DESNUTRICIÓN', '1947-07-31', '77 año(s) 4 mes(es) 19 día(s)', 68.000, 'Masculino', '2024-12-20 12:30:16', '2024-12-20 12:30:16'),
(262, 'RN G2', 'RAMIREZ LARA', 'INTERNOS', '14', '1', '116852', NULL, '2024-11-10', '1 mes(es) 9 día(s)', 1.630, 'Femenino', '2024-12-20 12:45:37', '2024-12-20 12:45:37'),
(263, 'BERTHA ELISA', 'NOEGGERATH CARDENAS', 'HOSPITALIZACIÓN', '604', '6', '1500636067', 'DESNUTRICIÓN', '1955-07-29', '69 año(s) 4 mes(es) 22 día(s)', 55.000, 'Femenino', '2024-12-20 14:36:09', '2024-12-21 07:55:49'),
(264, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOSPITALIZACION', '618', 'PISO 6', '1500640877', 'TROMBOSIS DE MIEMBROS PELVICOS', '1958-10-25', '66 año(s) 1 mes(es) 25 día(s)', 60.000, 'Masculino', '2024-12-20 15:20:59', '2024-12-20 15:20:59'),
(265, 'BERTHA ELISA', 'NOEGGERATH', 'HOSPITALIZACION', '604', 'PISO 6', '1500636067', 'DESNUTRICION', '1955-07-29', '69 año(s) 4 mes(es) 22 día(s)', 55.000, 'Femenino', '2024-12-20 20:48:35', '2024-12-21 07:06:33'),
(266, 'G2', 'RODRIGUEZ ANGELES', 'EXTERNOS', NULL, '1', '117796', NULL, '2024-12-13', '7 día(s)', 1.345, 'Femenino', '2024-12-21 09:17:08', '2024-12-21 09:17:08'),
(267, 'RN', 'RAMIREZ VILLAVICENCIO', 'NEONATOLOGIA', 'UCIN-2', '2', '1500664437', 'DESNUTRICION', '2024-12-16', '4 día(s)', 2.880, 'Masculino', '2024-12-21 09:30:17', '2024-12-21 09:30:17'),
(268, 'RN', 'DURAN ALVARADO', 'EXTERNOS', NULL, NULL, '115811', NULL, '2024-10-04', '2 mes(es) 16 día(s)', 1.305, 'Masculino', '2024-12-21 09:33:35', '2024-12-21 09:33:35'),
(269, 'G1', 'RODRIGUEZ ANGELES', 'EXTERNOS', NULL, '1', '117019', NULL, '2024-12-13', '7 día(s)', 1.120, 'Femenino', '2024-12-21 09:51:24', '2024-12-21 09:51:24'),
(270, 'RN', 'VAZQUEZ ENRIQUEZ', 'EXTERNOS', NULL, '1', '117019', NULL, '2024-11-16', '1 mes(es) 4 día(s)', 0.920, 'Femenino', '2024-12-21 10:23:37', '2024-12-21 10:23:37'),
(271, 'RN', 'DOMINGUEZ SANCHEZ', 'EXTERNOS', NULL, '1', '117094', NULL, '2024-11-19', '1 mes(es) 1 día(s)', 1.470, 'Femenino', '2024-12-21 10:28:34', '2024-12-21 10:28:34'),
(272, 'G2', 'RAMIREZ LARA', 'INTERNOS', NULL, '1', '116852', NULL, '2024-11-10', '1 mes(es) 10 día(s)', 1.640, 'Femenino', '2024-12-21 10:34:06', '2024-12-21 10:34:06'),
(273, 'RN', 'ANTONIO CAYETANO', 'INTERNOS', NULL, '1', '115848', NULL, '2024-10-07', '2 mes(es) 13 día(s)', 2.880, 'Femenino', '2024-12-21 10:39:07', '2024-12-21 10:39:07'),
(274, 'RN', 'MONTIEL ALBARRAN', 'INTERNOS', NULL, '1', '117764', NULL, '2024-12-11', '9 día(s)', 0.800, 'Masculino', '2024-12-21 10:43:57', '2024-12-21 10:43:57'),
(275, 'RN', 'MARIN LOPEZ', 'INTERNOS', NULL, '1', '117418', NULL, '2024-12-20', '', 1.150, 'Femenino', '2024-12-21 10:49:36', '2024-12-21 10:49:36'),
(276, 'ELIMAGDA', 'NAVARRO Y MARTINEZ SOTOMAYOR', 'HOSPILALIZACION', '428', '4', '1500662587', 'PERFORACION GASTRICA', '1948-09-25', '76 año(s) 2 mes(es) 25 día(s)', 59.000, 'Femenino', '2024-12-21 11:03:35', '2024-12-21 11:03:35'),
(277, 'ELENA', 'LOZOYA IDEN', 'HOSPITALIZACION', '308', '3', '1500662920', 'DESNUTRICION', '1947-11-14', '77 año(s) 1 mes(es) 6 día(s)', 42.000, 'Femenino', '2024-12-21 11:21:40', '2024-12-21 11:21:40'),
(278, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACION', '307', '3', '1500660965', 'DESNUTRICION', '1947-07-31', '77 año(s) 4 mes(es) 20 día(s)', 68.000, 'Masculino', '2024-12-21 11:53:04', '2024-12-21 11:53:04'),
(279, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICIÓN', '1953-10-12', '71 año(s) 2 mes(es) 8 día(s)', 60.000, 'Femenino', '2024-12-21 12:10:42', '2024-12-21 12:10:42'),
(280, 'Emilio', 'Nassar Rodriguez', 'Hospitalización', '618', '6', '1500640877', 'desnutrición', '1958-10-25', '66 año(s) 1 mes(es) 26 día(s)', 60.000, 'Masculino', '2024-12-21 14:50:13', '2024-12-21 14:50:13'),
(281, 'Anuar Javier', 'Amigon Tapia', 'Terapia intensiva', 'UTI-1', '1', '1500668161', 'desnutrición', '1980-01-28', '44 año(s) 10 mes(es) 23 día(s)', 69.000, 'Masculino', '2024-12-21 16:04:07', '2024-12-21 16:04:07'),
(282, 'Anuar Javier', 'Amigon Tapia', 'Terapia intensiva', 'UTI-1', '1', '1500668161', 'desnutrición', '1980-01-28', '44 año(s) 10 mes(es) 23 día(s)', 69.000, 'Masculino', '2024-12-21 16:55:53', '2024-12-21 16:55:53'),
(283, 'Anuar Javier', 'Amigon Tapia', 'Terapia intensiva', 'UTI-1', '1', '1500668161', 'desnutrición', '1980-01-28', '44 año(s) 10 mes(es) 23 día(s)', 69.000, 'Masculino', '2024-12-21 16:55:54', '2024-12-21 16:55:54'),
(284, 'RN G2', 'RAMIREZ LARA', 'UCIN INTERNOS', '14', '1', '116852', NULL, '2024-11-10', '1 mes(es) 11 día(s)', 1.670, 'Masculino', '2024-12-22 09:44:25', '2024-12-22 09:44:25'),
(285, 'RN', 'MARIN LOPEZ', 'UCIN INTERNOS', '4', '1', '117418', NULL, '2014-11-24', '10 año(s) 27 día(s)', 1.150, 'Femenino', '2024-12-22 10:07:14', '2024-12-22 10:07:14'),
(286, 'RN', 'ANTONIO CAYETANO', 'UCIN INTERNOS', '8', '1', '115848', NULL, '2024-10-07', '2 mes(es) 14 día(s)', 2.910, 'Femenino', '2024-12-22 10:11:57', '2024-12-22 10:11:57'),
(287, 'RN', 'MONTIEL ALBARRAN', 'UCIN INTERNOS', '2', '1', '117764', NULL, '2024-12-11', '10 día(s)', 0.815, 'Masculino', '2024-12-22 10:18:03', '2024-12-22 10:18:03'),
(288, 'RN G1', 'SANCHEZ JUAREZ', 'UCIN INTERNOS', '11', '1', 'SN', NULL, '2024-12-20', '1 día(s)', 2.290, 'Masculino', '2024-12-22 10:26:13', '2024-12-22 10:26:13'),
(289, 'ELIMAGDA', 'NAVARRO Y MARTINEZ SOTOMAYOR', 'Hospitalización', '428', '4', '1500662587', 'PERFORACION GASTRICA', '1948-09-25', '76 año(s) 2 mes(es) 26 día(s)', 59.000, 'Femenino', '2024-12-22 10:29:41', '2024-12-22 10:29:41'),
(290, 'RN', 'FLORES MARTINEZ', 'UCIN INTERNOS', '9', '1', 'SN', NULL, '2024-12-20', '1 día(s)', 2.050, 'Masculino', '2024-12-22 10:30:12', '2024-12-22 10:30:12'),
(291, 'RN', 'VALENCIA HERNANDEZ', 'UCIN INTERNOS', '10', '1', '117017', NULL, '2024-11-16', '1 mes(es) 5 día(s)', 1.470, 'Masculino', '2024-12-22 10:34:54', '2024-12-22 10:34:54'),
(292, 'RN', 'VAZQUEZ ENRIQUEZ', 'UCIN EXTERNOS', '7', '1', '117019', NULL, '2024-11-16', '1 mes(es) 5 día(s)', 0.955, 'Femenino', '2024-12-22 10:45:45', '2024-12-22 10:45:45'),
(293, 'RN', 'DOMINGUEZ SANCHEZ', 'UCIN EXTERNOS', '8', '1', '117094', NULL, '2024-11-19', '1 mes(es) 2 día(s)', 1.470, 'Masculino', '2024-12-22 10:49:14', '2024-12-22 10:49:14'),
(294, 'RN G2', 'RODRIGUEZ ANGELES', 'UCIN EXTERNOS', '11', '1', '117796', NULL, '2024-12-13', '8 día(s)', 1.340, 'Masculino', '2024-12-22 10:53:45', '2024-12-22 10:53:45'),
(295, 'RN', 'DURAN ALVARADO', 'UCIN EXTERNOS', '4', '1', '115811', NULL, '2024-10-04', '2 mes(es) 17 día(s)', 1.310, 'Masculino', '2024-12-22 11:04:02', '2024-12-22 11:04:02'),
(296, 'RN G1', 'RODRIGUEZ ANGELES', 'UCIN EXTERNOS', '1', '1', '117019', NULL, '2024-12-13', '8 día(s)', 1.135, 'Femenino', '2024-12-22 11:07:57', '2024-12-22 11:07:57'),
(297, 'LUIS', 'MAURER Y ESPINOSA', 'Hospitalización', '307', '3', '1500660965', 'DESNUTRICION', '1947-07-31', '77 año(s) 4 mes(es) 21 día(s)', 68.000, 'Masculino', '2024-12-22 12:01:53', '2024-12-22 12:01:53'),
(298, 'JUDITH', 'BARCELATA HALL', 'Terapia intensiva', 'UTI-6', '1', '1500599769', 'SEPSIS ABDOMINAL', '1953-10-12', '71 año(s) 2 mes(es) 9 día(s)', 60.000, 'Femenino', '2024-12-22 12:14:18', '2024-12-22 12:14:18'),
(299, 'EMILIO', 'NASSAR RODRIGUEZ', 'Hospitalización', '618', '6', '1500640877', 'TROMBOSIS DE MIEMBROS PELVICOS', '1958-10-25', '66 año(s) 1 mes(es) 27 día(s)', 60.000, 'Masculino', '2024-12-22 13:49:39', '2024-12-22 13:49:39'),
(300, 'ANAUR JAVIER', 'AMIGON TAPIA', 'Hospitalización', 'UTI-1', NULL, '1500668161', NULL, '1980-01-28', '44 año(s) 10 mes(es) 24 día(s)', 70.000, 'Masculino', '2024-12-22 15:48:23', '2024-12-22 15:48:23'),
(301, 'BERTHA ELISA', 'NOEGGERATH CARDENAS', 'Hospitalización', '604', '6', '1500636067', 'DESNUTRICION', '1955-07-29', '69 año(s) 4 mes(es) 24 día(s)', 55.000, 'Femenino', '2024-12-22 21:15:18', '2024-12-23 06:35:03'),
(302, 'RN', 'RODRIGUEZ ANGELES', 'UCIN EXTERNOS', '11', '1', '117796', NULL, '2024-12-13', '9 día(s)', 1.470, 'Masculino', '2024-12-23 10:05:45', '2024-12-23 10:05:45'),
(303, 'RN G1', 'RODRIGUEZ ANGELES', 'UCIN EXTERNOS', '1', '1', '117019', NULL, '2024-12-13', '9 día(s)', 1.180, 'Femenino', '2024-12-23 10:08:45', '2024-12-23 10:08:45'),
(304, 'RN', 'DURAN ALVARADO', 'UCIN EXTERNOS', '3', '1', '115811', NULL, '2024-10-04', '2 mes(es) 18 día(s)', 1.385, 'Masculino', '2024-12-23 10:12:19', '2024-12-23 10:12:19'),
(305, 'RN', 'VAZQUEZ ENRIQUEZ', 'UCIN EXTERNOS', '7', '1', '117019', NULL, '2024-11-16', '1 mes(es) 6 día(s)', 0.955, 'Masculino', '2024-12-23 10:16:02', '2024-12-23 10:16:02'),
(306, 'RN', 'DOMINGUEZ SANCHEZ', 'UCIN EXTERNOS', '4', '1', '117044', NULL, '2024-11-19', '1 mes(es) 3 día(s)', 1.660, 'Masculino', '2024-12-23 10:18:50', '2024-12-23 10:18:50'),
(307, 'RN G1', 'VEGA AVILA', 'UCIN EXTERNOS', '10', '1', '117180', NULL, '2024-11-21', '1 mes(es) 1 día(s)', 1.700, 'Masculino', '2024-12-23 10:24:37', '2024-12-23 10:24:37'),
(308, 'RN G2', 'RAMIREZ LARA', 'UCIN INTERNOS', '14', '1', '110852', NULL, '2024-11-10', '1 mes(es) 12 día(s)', 1.770, 'Masculino', '2024-12-23 10:38:33', '2024-12-23 10:38:33'),
(309, 'RN G1', 'SANCHEZ JUAREZ', 'UCIN INTERNOS', '11', '1', '118008', NULL, '2024-12-20', '2 día(s)', 2.290, 'Masculino', '2024-12-23 10:41:02', '2024-12-23 10:41:02'),
(310, 'RN', 'VALENCIA HERNANDEZ', 'UCIN INTERNOS', '10', '1', '117017', NULL, '2024-11-16', '1 mes(es) 6 día(s)', 1.330, 'Femenino', '2024-12-23 10:43:51', '2024-12-23 10:43:51'),
(311, 'RN', 'FLORES MARTINEZ', 'UCIN INTERNOS', '19', '1', '1179998', NULL, '2024-12-20', '2 día(s)', 2.010, 'Masculino', '2024-12-23 10:46:03', '2024-12-23 10:46:03'),
(312, 'RN', 'ANTONIO CAYETANO', 'UCIN INTERNOS', '8', '1', '115848', NULL, '2024-10-07', '2 mes(es) 15 día(s)', 2.920, 'Femenino', '2024-12-23 10:48:54', '2024-12-23 10:48:54'),
(313, 'RN', 'MARIN LOPEZ', 'UCIN INTERNOS', '4', '1', '117418', NULL, '2024-11-24', '28 día(s)', 1.160, 'Femenino', '2024-12-23 10:51:20', '2024-12-23 10:51:20'),
(314, 'RN', 'MONTIEL ALBARRAN', 'UCIN INTERNOS', '2', '1', '117764', NULL, '2024-12-11', '11 día(s)', 0.870, 'Masculino', '2024-12-23 10:53:40', '2024-12-23 10:53:40'),
(315, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOAPITALIZACION', '618', '6', '1500640877', 'TROMBOSIS DE  MIEMBROS PELVICOS', '1958-10-25', '66 año(s) 1 mes(es) 28 día(s)', 60.000, 'Masculino', '2024-12-23 11:27:43', '2024-12-23 11:27:43'),
(316, 'ANUAR JAVIER', 'AMIGON TAPIA', 'HOSPITALIZACION', '607', '6', '150066816', 'ADENOCARCINOMA', '1980-01-28', '44 año(s) 10 mes(es) 25 día(s)', 70.000, 'Masculino', '2024-12-23 13:03:40', '2024-12-23 13:03:40'),
(317, 'LUIS', 'MAURER Y ESPINOSA', 'T. INTERMEDIA', '307', '3', '1500660965', 'INFECCION DE VIAS URINARIAS', '1947-07-31', '77 año(s) 4 mes(es) 22 día(s)', 68.000, 'Masculino', '2024-12-23 13:18:27', '2024-12-23 13:18:27'),
(318, 'JUDITH', 'BARCELATA HALL', 'Terapia intensiva', 'UTI-6', '1', '1500599769', 'DESNUTRICION', '1953-10-12', '71 año(s) 2 mes(es) 10 día(s)', 60.000, 'Femenino', '2024-12-23 16:05:46', '2024-12-23 16:05:46'),
(319, 'BERTHA ELISA', 'NOEGGERATH CARDENAS', 'HOSPITALIZACION', '604', '6', '1500636067', 'DESNUTRICION', '1955-07-29', '69 año(s) 4 mes(es) 24 día(s)', 55.000, 'Femenino', '2024-12-23 17:24:03', '2024-12-23 17:24:03'),
(320, 'BERTHA ELISA', 'NOEGGERATH CARDENAS', 'Hospitalización', '604', '6', '1500636067', 'DESNUTRICION', '1955-07-29', '69 año(s) 4 mes(es) 25 día(s)', 55.000, 'Femenino', '2024-12-23 17:31:48', '2024-12-24 06:24:21'),
(321, 'RN', 'SANDOVAL RODAL', 'NEONATOLOGIA', 'UTIN-1', '2', '1500673741', 'DESNUTRICION', '2024-12-22', '1 día(s)', 2.220, 'Femenino', '2024-12-24 09:04:47', '2024-12-24 09:04:47'),
(322, 'RN', 'VASQUEZ ENRIQUEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-11-16', '1 mes(es) 7 día(s)', 0.925, 'Femenino', '2024-12-24 09:24:48', '2024-12-24 09:24:48'),
(323, 'RN', 'DOMINGUEZ SANCHEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-11-19', '1 mes(es) 4 día(s)', 1.680, 'Masculino', '2024-12-24 09:34:45', '2024-12-24 09:34:45'),
(324, 'RN', 'JUAN SANCHEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-20', '3 día(s)', 0.925, 'Femenino', '2024-12-24 09:40:47', '2024-12-24 09:40:47'),
(325, 'RN', 'RODRIGUEZ ANGELES G1', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-13', '10 día(s)', 1.184, 'Femenino', '2024-12-24 09:48:52', '2024-12-24 09:48:52'),
(326, 'RN', 'DURAN ALVARADO', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-10-04', '2 mes(es) 19 día(s)', 1.310, 'Masculino', '2024-12-24 09:54:48', '2024-12-24 09:54:48'),
(327, 'RN', 'MONTIEL ALBARRAN', 'INTERNOS', '2', '1', NULL, NULL, '2024-12-11', '12 día(s)', 0.820, 'Masculino', '2024-12-24 10:19:01', '2024-12-24 10:19:01'),
(328, 'RN', 'MARIN LOPEZ', 'INTERNOS', NULL, '1', NULL, NULL, '2024-11-29', '24 día(s)', 1.180, 'Femenino', '2024-12-24 10:26:40', '2024-12-24 10:26:40'),
(329, 'RN', 'ANTONIO CAYETANO', 'INTERNOS', '8', '1', NULL, NULL, '2024-10-07', '2 mes(es) 16 día(s)', 2.930, 'Femenino', '2024-12-24 10:32:54', '2024-12-24 10:32:54'),
(330, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOSPITALIZACION', '618', '6', '1500640877', 'TROMBOSIS DE MIEMBROS PELVICOS', '1958-10-25', '66 año(s) 1 mes(es) 29 día(s)', 60.000, 'Masculino', '2024-12-24 10:43:34', '2024-12-24 10:43:34'),
(331, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACION', '307', '3', '1500660965', 'DESNUTRICION', '1947-07-31', '77 año(s) 4 mes(es) 23 día(s)', 68.000, 'Masculino', '2024-12-24 12:15:49', '2024-12-24 12:15:49'),
(332, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICION', '1953-10-12', '71 año(s) 2 mes(es) 11 día(s)', 60.000, 'Femenino', '2024-12-24 14:40:01', '2024-12-24 14:40:01'),
(333, 'BERTHA ELISA', 'NOEGGERATH CARDENAS', 'HOSPITALIZACION', '604', '6', '1500636067', 'DESNUTRICION', '1955-07-29', '69 año(s) 4 mes(es) 25 día(s)', 55.000, 'Femenino', '2024-12-24 21:38:37', '2024-12-24 21:38:37'),
(334, 'BERTHA ELISA', 'NOEGGERATH CARDENAS', 'HOSPITALIZACION', '604', '6', '1500636067', 'DESNUTRICION', '1955-07-29', '69 año(s) 4 mes(es) 26 día(s)', 55.000, 'Femenino', '2024-12-24 21:38:37', '2024-12-25 06:23:45'),
(335, 'RN', 'Olvera Guerrero', 'UCIN', '1', '1', '1090479293', 'Sepsis + DBS', '2024-10-19', '2 mes(es) 5 día(s)', 1.784, 'Masculino', '2024-12-25 08:28:22', '2024-12-25 08:28:22'),
(336, 'RN', 'SANDOVAL RODAL', 'NEONATOLOGÍA', 'UTIN-1', '2', '1500673741', 'DESNUTRICIÓN', '2024-12-22', '2 día(s)', 2.220, 'Femenino', '2024-12-25 09:46:13', '2024-12-25 09:46:13'),
(337, 'RN', 'DOMINGUEZ SANCHEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-11-19', '1 mes(es) 5 día(s)', 2.000, 'Masculino', '2024-12-25 10:12:03', '2024-12-25 10:12:03'),
(338, 'RN', 'VELASQUEZ ENRIQUEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-23', '1 día(s)', 2.000, 'Masculino', '2024-12-25 10:17:42', '2024-12-25 10:17:42'),
(339, 'BERTHA ELISA', 'NOEGGERATH CARDENAS', 'HOSPITALIZACIÓN', '604', '6', '1500636067', 'DESNUTRICIÓN', '1955-07-29', '69 año(s) 4 mes(es) 26 día(s)', 54.900, 'Femenino', '2024-12-25 10:38:00', '2024-12-25 10:38:00'),
(340, 'RN', 'VASQUEZ ENRIQUEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-11-16', '1 mes(es) 8 día(s)', 0.990, 'Femenino', '2024-12-25 10:38:43', '2024-12-25 10:38:43'),
(341, 'RN', 'RODRIGUEZ ANGELES G1', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-13', '11 día(s)', 1.140, 'Femenino', '2024-12-25 10:50:25', '2024-12-25 10:50:25'),
(342, 'EMILIO', 'NASSAR RODRIGEZ', 'HOSPITALIZACION', '618', '6', '1500640877', 'TROMBOSIS DE MIEMBROS PELVICOS', '1958-10-25', '66 año(s) 1 mes(es) 30 día(s)', 2.000, 'Masculino', '2024-12-25 10:54:50', '2024-12-25 10:54:50'),
(343, 'RN', 'JUAN SANCHEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-20', '4 día(s)', 0.925, 'Femenino', '2024-12-25 10:55:40', '2024-12-25 10:55:40'),
(344, 'RN', 'ANTONIO CAYETANO', 'INTERNOS', '8', '1', NULL, NULL, '2024-10-07', '2 mes(es) 17 día(s)', 3.100, 'Femenino', '2024-12-25 11:01:04', '2024-12-25 11:01:04'),
(345, 'RN', 'MARIN LOPEZ', 'INTERNOS', '4', '1', NULL, NULL, '2024-11-29', '25 día(s)', 1.180, 'Femenino', '2024-12-25 11:06:15', '2024-12-25 11:06:15'),
(346, 'RN', 'MONTIEL ALBARRAN', 'INTERNOS', '2', '1', NULL, NULL, '2024-12-11', '13 día(s)', 0.840, 'Masculino', '2024-12-25 11:10:13', '2024-12-25 11:10:13'),
(347, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOSPITALIZACION', '618', '6', '1500640877', 'TROMBOSIS DE MIEMBROS PELVICOS', '1958-10-25', '66 año(s) 1 mes(es) 30 día(s)', 60.000, 'Masculino', '2024-12-25 11:10:13', '2024-12-25 11:10:13'),
(348, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACIÓN', '307', '3', '1500660965', 'DESNUTRICIÓN', '1947-07-31', '77 año(s) 4 mes(es) 24 día(s)', 68.000, 'Masculino', '2024-12-25 13:21:52', '2024-12-25 13:21:52'),
(349, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI -6', '1', '1500599769', 'DESNUTRICION', '1953-10-12', '71 año(s) 2 mes(es) 12 día(s)', 60.000, 'Femenino', '2024-12-25 13:38:36', '2024-12-25 13:38:36'),
(350, 'RN', 'SANDOVAL RODAL', 'NEONATOLOGIA', 'UTIN-1', '2', '1500673741', 'DESNUTRICION', '0224-12-22', '1800 año(s) 3 día(s)', 2.220, 'Femenino', '2024-12-26 10:11:19', '2024-12-26 10:11:19'),
(351, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACION', '307', '3', '1500660965', 'DESNUTRICION', '1947-07-31', '77 año(s) 4 mes(es) 25 día(s)', 68.000, 'Masculino', '2024-12-26 10:43:14', '2024-12-26 10:43:14'),
(352, 'RN', 'BECERRIL GONZALEZ', 'UCIN INTERNOS', '1', '1', '117330', NULL, '2024-11-26', '29 día(s)', 1.450, 'Femenino', '2024-12-26 11:11:14', '2024-12-26 11:11:14'),
(353, 'RN', 'MONTIEL', 'UCIN INTERNOS', '2', '1', '117764', NULL, '2024-12-11', '14 día(s)', 0.840, 'Masculino', '2024-12-26 11:15:59', '2024-12-26 11:15:59'),
(354, 'RN', 'MARIN LOPEZ', 'UCIN INTERNOS', '4', '1', '117418', NULL, '2024-11-29', '26 día(s)', 1.310, 'Femenino', '2024-12-26 11:20:42', '2024-12-26 11:20:42'),
(355, 'RN', 'FLORES MARTINEZ', 'UCIN INTERNOS', '9', '1', '117998', NULL, '2024-12-20', '5 día(s)', 1.980, 'Masculino', '2024-12-26 11:23:53', '2024-12-26 11:23:53');
INSERT INTO `solicitud_patients` (`id`, `nombre_paciente`, `apellidos_paciente`, `servicio`, `cama`, `piso`, `registro`, `diagnostico`, `fecha_nacimiento`, `edad`, `peso`, `sexo`, `created_at`, `updated_at`) VALUES
(356, 'RN', 'VELAZQUEZ ENRIQUEZ', 'UCIN EXTERNOS', '14', '1', '118045', NULL, '2024-11-25', '1 mes(es) ', 2.000, 'Masculino', '2024-12-26 11:24:56', '2024-12-26 11:24:56'),
(357, 'RN', 'RODRIGUEZ ANGELES G1', 'UCIN EXTERNOS', '1', '1', '117795', NULL, '2024-12-13', '12 día(s)', 1.200, 'Femenino', '2024-12-26 11:28:40', '2024-12-26 11:28:40'),
(358, 'RN', 'JU', 'UCIN INTERNOS', '5', '1', '118014', NULL, '2024-11-10', '1 mes(es) 15 día(s)', 1.000, 'Masculino', '2024-12-26 11:29:44', '2024-12-26 11:29:44'),
(359, 'RN', 'DOMINGUEZ SANCHEZ', 'UCIN EXTERNOS', '8', '1', '117094', NULL, '2024-12-19', '6 día(s)', 1.640, 'Masculino', '2024-12-26 11:32:05', '2024-12-26 11:32:05'),
(360, 'RN', 'MARTINEZ GONZALEZ', 'UCIN EXTERNOS', '3', '1', '118024', NULL, '2024-12-25', '', 2.000, 'Masculino', '2024-12-26 11:34:51', '2024-12-26 11:34:51'),
(361, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICIÓN', '1953-10-12', '71 año(s) 2 mes(es) 13 día(s)', 60.000, 'Femenino', '2024-12-26 12:52:23', '2024-12-26 12:52:23'),
(362, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOSPITALIZACIÓN', '618', '6', '1500640877', 'TROMBOSIS DE MIEMBROS PELVICOS', '1958-10-25', '66 año(s) 2 mes(es) ', 60.000, 'Masculino', '2024-12-26 14:18:17', '2024-12-26 14:18:17'),
(363, 'RN', 'SANDOVAL RODAL', 'NEONATOLOGÍA', 'UTIN-1', '2', '1500673741', 'DESNUTRICIÓN', '2024-12-22', '4 día(s)', 2.220, 'Femenino', '2024-12-27 09:39:05', '2024-12-27 09:39:05'),
(364, 'RN', 'MARTINEZ GONZALEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-22', '4 día(s)', 2.000, 'Masculino', '2024-12-27 09:45:10', '2024-12-27 09:45:10'),
(365, 'RN', 'RODRIGUEZ ANGELES G1', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-13', '13 día(s)', 1.205, 'Femenino', '2024-12-27 09:50:51', '2024-12-27 09:50:51'),
(366, 'RN', 'JUAN SANCHEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-20', '6 día(s)', 1.070, 'Masculino', '2024-12-27 09:57:34', '2024-12-27 09:57:34'),
(367, 'RN', 'DOMINGUEZ SANCHEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-11-19', '1 mes(es) 7 día(s)', 2.000, 'Masculino', '2024-12-27 10:04:45', '2024-12-27 10:04:45'),
(368, 'RN', 'MARIN LOPEZ', 'INTERNOS', '4', '1', NULL, NULL, '2024-11-29', '27 día(s)', 1.300, 'Femenino', '2024-12-27 10:34:20', '2024-12-27 10:34:20'),
(369, 'RN', 'MONTIEL ALBARRAN', 'INTERNOS', '2', '1', NULL, NULL, '2024-12-11', '15 día(s)', 0.860, 'Masculino', '2024-12-27 10:42:39', '2024-12-27 10:42:39'),
(370, 'RN', 'ANTONIO CAYETANO', 'INTERNOS', '8', '1', NULL, NULL, '2024-10-11', '2 mes(es) 15 día(s)', 3.250, 'Femenino', '2024-12-27 10:49:21', '2024-12-27 10:49:21'),
(371, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACIÓN', '307', '3', '1500660965', 'DESNUTRICIÓN', '1947-07-31', '77 año(s) 4 mes(es) 26 día(s)', 68.000, 'Masculino', '2024-12-27 11:26:09', '2024-12-27 11:26:09'),
(372, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICIÓN', '1953-10-12', '71 año(s) 2 mes(es) 14 día(s)', 60.000, 'Femenino', '2024-12-27 11:47:25', '2024-12-27 11:47:25'),
(373, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOSPITALIZACION', '618', '6', '1500640877', 'TROMBOSIS DE MIEMBROS PELVICOS', '1958-10-25', '66 año(s) 2 mes(es) 1 día(s)', 60.000, 'Masculino', '2024-12-27 15:11:02', '2024-12-27 15:11:02'),
(374, 'RN', 'DOMINGUEZ SANCHEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-11-19', '1 mes(es) 8 día(s)', 2.000, 'Masculino', '2024-12-28 09:30:14', '2024-12-28 09:30:14'),
(375, 'RN', 'MARTINEZ GONZALEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-22', '5 día(s)', 2.000, 'Masculino', '2024-12-28 09:34:57', '2024-12-28 09:34:57'),
(376, 'RN', 'VELASQUEZ ENRIQUEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-23', '4 día(s)', 2.000, 'Masculino', '2024-12-28 09:40:28', '2024-12-28 09:40:28'),
(377, 'RN', 'BENITEZ MARTINEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-26', '1 día(s)', 2.000, 'Masculino', '2024-12-28 09:45:50', '2024-12-28 09:45:50'),
(378, 'RN', 'RODRIGUEZ ANGELES G1', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-13', '14 día(s)', 1.205, 'Femenino', '2024-12-28 09:50:37', '2024-12-28 09:50:37'),
(379, 'RN', 'SANDOVAL RODAL', 'NEONATOLOGÍA', 'UCIN-2', '2', '1500673741', 'DESNUTRICIÓN', '2024-12-22', '5 día(s)', 2.220, 'Femenino', '2024-12-28 09:53:28', '2024-12-28 09:53:28'),
(380, 'RN', 'VALENCIA HERNANDEZ', 'INTERNOS', NULL, '1', NULL, NULL, '2024-11-16', '1 mes(es) 11 día(s)', 1.385, 'Masculino', '2024-12-28 10:40:37', '2024-12-28 10:40:37'),
(381, 'RN', 'ANTONIO CAYETANO', 'INTERNOS', '8', '1', NULL, NULL, '2024-10-07', '2 mes(es) 20 día(s)', 3.290, 'Femenino', '2024-12-28 10:46:40', '2024-12-28 10:46:40'),
(382, 'RN', 'ROJAS ROJAS G1', 'INTERNOS', '5', '1', NULL, NULL, '2024-12-26', '1 día(s)', 1.420, 'Masculino', '2024-12-28 10:51:52', '2024-12-28 10:51:52'),
(383, 'RN', 'MONTIEL ALBARRAN', 'INTERNOS', '2', '1', NULL, NULL, '2024-12-11', '16 día(s)', 1.000, 'Masculino', '2024-12-28 10:56:17', '2024-12-28 10:56:17'),
(384, 'EMILIO', 'NASSAR RODRÍGUEZ', 'HOSPITALIZACIÓN', '618', '6', '1500640877', 'TROMBOSIS VENOCAPILAR', '1958-10-25', '66 año(s) 2 mes(es) 2 día(s)', 60.000, 'Masculino', '2024-12-28 12:07:51', '2024-12-28 12:07:51'),
(385, 'TEODORO', 'MACIAS CERVANTES', 'HOSPITALIZACIÓN', '301', '3', '1500673991', 'DESNITRICIÓN', '1936-09-11', '88 año(s) 3 mes(es) 16 día(s)', 60.000, 'Masculino', '2024-12-28 12:20:32', '2024-12-28 14:24:25'),
(386, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICIÓN', '1953-10-12', '71 año(s) 2 mes(es) 15 día(s)', 60.000, 'Masculino', '2024-12-28 12:32:18', '2024-12-28 13:59:18'),
(387, 'rn', 'gabriel calixto', 'utin', '102', 'utin', '426531', NULL, '2024-10-22', '2 mes(es) 5 día(s)', 2.080, 'Femenino', '2024-12-28 12:41:50', '2024-12-28 12:41:50'),
(388, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACION', '307', '3', '1500660965', 'DESNUTRICION', '1947-07-31', '77 año(s) 4 mes(es) 27 día(s)', 68.000, 'Masculino', '2024-12-28 13:02:06', '2024-12-28 13:02:06'),
(389, 'TEODORO', 'MACIAS CERVANTES', 'TERAPIA INTERMEDIA', '301', '3', '1500673991', 'DESNUTRICION', '1936-09-11', '88 año(s) 3 mes(es) 17 día(s)', 60.000, 'Masculino', '2024-12-29 12:18:35', '2024-12-29 12:18:35'),
(390, 'LUIS', 'MAURER Y ESPINOSA', 'TERAPIA INTERMEDIA', '307', '3', '1500660965', 'DESNUTRICION', '1947-07-31', '77 año(s) 4 mes(es) 28 día(s)', 68.000, 'Masculino', '2024-12-29 12:40:57', '2024-12-29 12:40:57'),
(391, 'EMILIO', 'NASSAR RODRIGUEZ', 'HOSPITALIZACION', '618', '6', '1500640877', 'TROMBOSIS DE MIEMBROS PELVICOS', '1958-10-25', '66 año(s) 2 mes(es) 3 día(s)', 60.000, 'Masculino', '2024-12-29 12:49:48', '2024-12-29 12:49:48'),
(392, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICION', '1953-10-12', '71 año(s) 2 mes(es) 16 día(s)', 60.000, 'Femenino', '2024-12-29 14:01:38', '2024-12-29 14:01:38'),
(393, 'GRACIELA', 'HURTADO GARCIA', 'HOSPITALIZACION', '225', '2', '1500677237', 'INFECCION DE TEJIDOS BLANDOS', '1977-05-31', '47 año(s) 6 mes(es) 29 día(s)', 75.000, NULL, '2024-12-29 20:56:35', '2024-12-30 06:04:24'),
(394, 'ABRIL MICHELLE', 'GONZALEZ HUIZACHE', 'HOSPITALIZACION', '235', '2', '1500674194', 'POSTOPERADA DE DUODENO', '2023-07-09', '1 año(s) 5 mes(es) 20 día(s)', 9.800, 'Femenino', '2024-12-29 21:54:37', '2024-12-30 06:06:06'),
(395, 'TEODORO', 'MACIAS CERVANTES', 'TERAPIA INTERMEDIA', '301', '3', '1500673991', 'DESNUTRICION', '1936-09-11', '88 año(s) 3 mes(es) 18 día(s)', 60.000, 'Masculino', '2024-12-30 12:49:05', '2024-12-30 12:49:05'),
(396, 'LUIS', 'MAURER Y ESPINOSA', 'TERAPIA INTERMEDIA', '307', '3', '1500660965', 'DESNUTRICION', '1947-07-31', '77 año(s) 4 mes(es) 29 día(s)', 68.000, 'Masculino', '2024-12-30 13:00:45', '2024-12-30 13:00:45'),
(397, 'ABRIL', 'GONZALEZ HUIZACHE', 'HOSPITALIZACION', '235', '2', '1500674194', 'POSTOPERADA DE DUODENO', '2023-07-09', '1 año(s) 5 mes(es) 20 día(s)', 9.800, 'Femenino', '2024-12-30 14:41:52', '2024-12-30 14:41:52'),
(398, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICION', '1953-10-12', '71 año(s) 2 mes(es) 17 día(s)', 60.000, 'Femenino', '2024-12-30 14:53:33', '2024-12-30 14:53:33'),
(399, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'SEPSIS ABDOMINAL', '1953-10-12', '71 año(s) 2 mes(es) 17 día(s)', 60.000, 'Femenino', '2024-12-30 15:16:20', '2024-12-30 15:16:20'),
(400, 'GEMELO 1', 'SAMANO VELAZQUEZ', 'UCIN', 'S/D', 'S/D', '209008', 'S/D', '2024-12-05', '25 día(s)', 2.200, 'Masculino', '2024-12-31 10:36:54', '2024-12-31 10:36:54'),
(401, 'RN', 'HERNANDEZ FLORES', 'UCIN', 'C-2', 'S/D', '297078', 'S/D', '2024-12-21', '9 día(s)', 1.000, 'Masculino', '2024-12-31 10:44:12', '2024-12-31 10:45:25'),
(402, 'RN', 'RAMIREZ MACEDO', 'UCIN', 'C-8', 'S/D', '297078', 'S/D', '2024-12-27', '3 día(s)', 2.285, 'Femenino', '2024-12-31 10:55:06', '2024-12-31 10:55:06'),
(403, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACION', '307', '3', '1500660965', 'DESNUTRICION', '1947-07-31', '77 año(s) 4 mes(es) 30 día(s)', 68.000, 'Masculino', '2024-12-31 10:55:28', '2024-12-31 10:55:28'),
(404, 'RN', 'OLMEDO RICARDO', 'UTIN', 'C-2', 'S/D', '246931', 'S/D', '2024-12-16', '14 día(s)', 3.160, 'Masculino', '2024-12-31 11:02:36', '2024-12-31 11:02:36'),
(405, 'RN', 'RAMIREZ RUIZ', 'REANIMACION', 'S/D', 'S/D', '118149', 'S/D', '2024-12-28', '2 día(s)', 1.405, 'Masculino', '2024-12-31 11:21:54', '2024-12-31 11:21:54'),
(406, 'RN', 'GARCIA SANTIAGO', 'PEDIATRIA', 'S/D', 'S/D', '93955-24', 'S/D', '2024-12-19', '11 día(s)', 1.610, 'Masculino', '2024-12-31 11:27:34', '2024-12-31 11:27:34'),
(407, 'RN', 'RUEDA SANTILLAN', 'UTIN', 'C-6', 'S/D', '247206', 'S/D', '2024-12-26', '4 día(s)', 1.655, 'Masculino', '2024-12-31 11:32:15', '2024-12-31 11:32:15'),
(408, 'NIÑA', 'FLORES ASCENCIO', 'PEDIATRIA', 'S/D', 'S/D', '133542-1', 'S/D', '2024-12-17', '13 día(s)', 1.925, 'Femenino', '2024-12-31 11:40:30', '2024-12-31 11:40:30'),
(409, 'RN', 'BERMUDEZ MEJIA', 'UCIN', 'U-3', 'S/D', '428055', 'PREMATURO', '2024-12-09', '21 día(s)', 1.315, 'Masculino', '2024-12-31 11:44:51', '2024-12-31 11:44:51'),
(410, 'RN', 'Gabriel Calixto', 'UTIN', '102', NULL, NULL, NULL, '2024-10-22', '2 mes(es) 8 día(s)', 2.130, 'Femenino', '2024-12-31 12:16:41', '2024-12-31 12:16:41'),
(411, 'Gemelo 1', 'Lopez Colin', 'UCIN', 'U-6', '1', '428267', NULL, '2024-12-14', '16 día(s)', 2.285, NULL, '2024-12-31 12:22:29', '2024-12-31 12:22:29'),
(412, 'RN', 'Sanchez Santellano', 'UCIN-1', NULL, NULL, '205438', NULL, '2024-12-22', '8 día(s)', 3.000, 'Femenino', '2024-12-31 12:27:22', '2024-12-31 12:27:22'),
(413, 'RN', 'Carrizosa Garcia', 'Pediatria', NULL, NULL, '205308', NULL, '2024-12-10', '20 día(s)', 3.650, 'Femenino', '2024-12-31 12:33:09', '2024-12-31 12:33:09'),
(414, 'RN', 'Garcia Martinez', 'UCIN-1', NULL, NULL, '205505', NULL, '2024-12-26', '4 día(s)', 1.830, 'Femenino', '2024-12-31 12:36:30', '2024-12-31 12:36:30'),
(415, 'RN', 'Colmenares Cruz', 'UCIN-2', NULL, NULL, '203045', NULL, '0024-12-25', '2000 año(s) 5 día(s)', 2.465, 'Femenino', '2024-12-31 13:06:05', '2024-12-31 13:06:05'),
(416, 'RN', 'Guerrero Martinez', 'ucin-2', NULL, NULL, '27846', NULL, '2024-12-17', '13 día(s)', 2.625, 'Masculino', '2024-12-31 13:10:09', '2024-12-31 13:10:09'),
(417, 'RN', 'Lazaro Rosendo', 'ucin-2', NULL, NULL, '205531', NULL, '2024-12-27', '3 día(s)', 2.430, 'Masculino', '2024-12-31 13:14:11', '2024-12-31 13:14:11'),
(418, 'RN', 'Lopez Avelino', 'ucin-2', NULL, NULL, '205539', NULL, '2024-12-28', '2 día(s)', 1.520, 'Femenino', '2024-12-31 13:17:08', '2024-12-31 13:17:08'),
(419, 'RN', 'Ortiz Perez', 'ucin-2', NULL, NULL, '205502', NULL, '2024-12-26', '4 día(s)', 2.290, 'Femenino', '2024-12-31 13:19:39', '2024-12-31 13:19:39'),
(420, 'RN', 'Lara Hernandez', 'utin-2', NULL, NULL, NULL, NULL, '2024-12-23', '7 día(s)', 0.830, 'Femenino', '2024-12-31 13:28:18', '2024-12-31 13:28:18'),
(421, 'RN', 'Gonzalez Millan', 'ucin', NULL, NULL, '428203', NULL, '2024-12-14', '16 día(s)', 1.675, 'Masculino', '2024-12-31 13:32:39', '2024-12-31 13:32:39'),
(422, 'RN Femenino', 'Jacobo Carrillo', 'utin9', NULL, NULL, NULL, NULL, '2024-12-18', '12 día(s)', 1.200, 'Femenino', '2024-12-31 13:37:52', '2024-12-31 13:37:52'),
(423, 'RN Fem', 'Hernandez Perez', 'utin-6', NULL, NULL, NULL, NULL, '2024-08-27', '4 mes(es) 3 día(s)', 1.180, 'Femenino', '2024-12-31 13:41:58', '2024-12-31 13:41:58'),
(424, 'RN', 'PEÑA ROMERO', 'REANIMACION', 'S/D', 'S/D', '118152', 'S/D', '2024-12-29', '1 día(s)', 1.608, 'Masculino', '2024-12-31 13:45:48', '2024-12-31 13:45:48'),
(425, 'RN GEMELO 1', 'JUAREZ ORTIZ', 'REANIMACION', 'S/D', 'S/D', '118137', 'S/D', '2024-12-27', '3 día(s)', 1.150, 'Masculino', '2024-12-31 13:49:19', '2024-12-31 13:49:19'),
(426, 'RN', 'Garcia Rodriguez', 'NEONATOLOGIA', NULL, NULL, NULL, NULL, '2024-11-10', '1 mes(es) 20 día(s)', 1.800, 'Femenino', '2024-12-31 13:51:05', '2024-12-31 13:51:05'),
(427, 'RN', 'SANTIAGO CONTRERAS', 'REANIMACION', 'S/D', 'S/D', '118152', 'S/D', '2024-12-29', '1 día(s)', 1.800, 'Femenino', '2024-12-31 13:51:55', '2024-12-31 13:51:55'),
(428, 'RN', 'ANTONIO CAYETANO', 'UCIN INTERNOS', 'S/D', 'S/D', '115848', 'S/D', '2024-10-07', '2 mes(es) 23 día(s)', 3.890, 'Femenino', '2024-12-31 13:55:09', '2024-12-31 14:09:59'),
(429, 'Gemelo 2', 'Becerril Arana', 'ucin', NULL, NULL, NULL, NULL, '2024-12-19', '11 día(s)', 1.800, 'Masculino', '2024-12-31 13:55:14', '2024-12-31 13:55:14'),
(430, 'RN G1', 'ROJAS ROJAS', 'UCIN INTERNOS', 'S/D', 'S/D', '118109', 'S/D', '2024-12-26', '4 día(s)', 1.410, 'Masculino', '2024-12-31 13:58:05', '2024-12-31 14:18:25'),
(431, 'RN', 'Lopez Calderon', 'ucin', NULL, NULL, NULL, NULL, '2024-12-24', '6 día(s)', 1.345, 'Femenino', '2024-12-31 13:58:41', '2024-12-31 13:58:41'),
(432, 'RN GEMELO 2', 'ROJAS ROJAS', 'UCIN', 'S/D', 'S/D', '118109', 'S/D', '2024-12-26', '4 día(s)', 1.330, 'Masculino', '2024-12-31 14:01:50', '2024-12-31 14:01:50'),
(433, 'RN', 'MARIN LOPEZ', 'UCIN INTERNOS', 'S/D', 'S/D', '117418', 'S/D', '2024-11-29', '1 mes(es) 1 día(s)', 1.380, 'Femenino', '2024-12-31 14:06:08', '2024-12-31 14:21:33'),
(434, 'RN', 'MONTIEL ALABARRAN', 'UCJIN', 'S/D', 'S/D', '117764', 'S/D', '2024-12-11', '19 día(s)', 1.020, NULL, '2024-12-31 14:08:50', '2024-12-31 14:08:50'),
(435, 'RN', 'BECERRIL GONZALEZ', 'UCIN', 'S/D', 'S/D', '117330', 'S/D', '2024-11-26', '1 mes(es) 4 día(s)', 1.455, 'Femenino', '2024-12-31 14:11:13', '2024-12-31 14:11:13'),
(436, 'RN', 'VILLARREAL POSADAS RN', 'UCIN', 'S/D', 'S/D', '118116', 'S/D', '2024-12-27', '3 día(s)', 1.075, 'Femenino', '2024-12-31 14:14:45', '2024-12-31 14:14:45'),
(437, 'RN G1', 'RODRIGUEZ ANGELES', 'UCIN', 'S/D', 'S/D', '117795', 'S/D', '2024-12-13', '17 día(s)', 1.390, 'Femenino', '2024-12-31 14:17:37', '2024-12-31 14:32:55'),
(438, 'RN', 'DOMINGUEZ SANCHEZ', 'UCIN EXTERNOS', 'S/D', 'S/D', '117094', 'S/D', '2024-11-19', '1 mes(es) 11 día(s)', 1.795, 'Masculino', '2024-12-31 14:20:39', '2024-12-31 14:20:39'),
(439, 'RN', 'JUAN SANCHEZ', 'UCIN EXTERNOS', 'S/D', 'S/D', '118014', 'S/D', '2024-12-20', '10 día(s)', 1.095, 'Femenino', '2024-12-31 14:23:07', '2024-12-31 14:23:07'),
(440, 'RN', 'Martinez Gonzalez', 'UCIN EXTERNOS', NULL, NULL, NULL, NULL, '2024-12-22', '8 día(s)', 1.560, 'Masculino', '2024-12-31 14:51:38', '2024-12-31 14:51:38'),
(441, 'Teodoro', 'Macias Cervantes', 'HOSPITALIZACION', '301', '3', '1500673991', 'Neumonia', '1936-09-11', '88 año(s) 3 mes(es) 19 día(s)', 60.000, 'Masculino', '2024-12-31 14:54:51', '2024-12-31 14:54:51'),
(442, 'RN', 'Valencia Hernandez', 'UCIN INTERNOS', NULL, NULL, NULL, NULL, '2024-11-16', '1 mes(es) 14 día(s)', 1.350, 'Masculino', '2024-12-31 14:55:28', '2024-12-31 14:55:28'),
(443, 'RN', 'Pavon Martinez', 'UCIN', NULL, NULL, NULL, NULL, '2024-12-19', '11 día(s)', 1.745, 'Masculino', '2024-12-31 14:58:44', '2024-12-31 14:58:44'),
(444, 'RN', 'Peña Mora', 'ucin', NULL, NULL, NULL, NULL, '2024-12-26', '4 día(s)', 1.275, 'Femenino', '2024-12-31 15:02:57', '2024-12-31 15:02:57'),
(445, 'RN', 'Perez Cruz', 'ucin', NULL, NULL, NULL, NULL, '2024-12-19', '11 día(s)', 0.940, 'Femenino', '2024-12-31 15:06:02', '2024-12-31 15:06:02'),
(446, 'RN', 'Avilés Martinez', 'ucin', NULL, NULL, NULL, NULL, '2024-12-23', '7 día(s)', 0.975, 'Femenino', '2024-12-31 15:10:17', '2024-12-31 15:10:17'),
(447, 'Niña', 'Moreno Patricio', 'ucin', NULL, NULL, NULL, NULL, '2024-10-30', '2 mes(es) ', 1.170, 'Femenino', '2024-12-31 15:14:07', '2024-12-31 15:14:07'),
(448, 'RN', 'Lopez Martinez', 'ucin', NULL, NULL, NULL, NULL, '2024-11-27', '1 mes(es) 3 día(s)', 2.175, 'Masculino', '2024-12-31 15:50:33', '2024-12-31 15:50:33'),
(449, 'RN', 'PEÑA ROMERO', 'REANIMACION', NULL, NULL, NULL, NULL, '2024-12-29', '1 día(s)', 1.608, 'Masculino', '2024-12-31 15:58:57', '2024-12-31 15:58:57'),
(450, 'RN', 'Garcia Santiago', 'Pediatria', NULL, NULL, NULL, NULL, '2024-12-19', '11 día(s)', 1.610, 'Masculino', '2024-12-31 16:04:25', '2024-12-31 16:04:25'),
(451, 'Abril Michelle', 'Gonzalez Huizache', 'HOSPITALIZACIÓN', '235', '2', '1500674194', 'DESNUTRICION', '2023-07-09', '1 año(s) 5 mes(es) 21 día(s)', 10.000, 'Femenino', '2024-12-31 16:04:38', '2024-12-31 16:04:38'),
(452, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICION', '1953-10-12', '71 año(s) 2 mes(es) 18 día(s)', 60.000, 'Femenino', '2024-12-31 16:16:04', '2024-12-31 16:16:04'),
(453, 'GRACIELA', 'HURTADO GARCIA', 'HOSPITALIZACION', '225', '2', '1500677237', 'DESNUTRICION', '1977-05-31', '47 año(s) 6 mes(es) 30 día(s)', 74.000, 'Femenino', '2024-12-31 16:30:15', '2024-12-31 16:30:15'),
(454, 'MARIA FERNANDA', 'FIGUEROA GUTIERREZ', 'HOSPITALIZACIÓN', '433', '4', '1500676649', 'DESNUTRICION', '1987-08-01', '37 año(s) 4 mes(es) 29 día(s)', 84.000, 'Femenino', '2024-12-31 16:48:47', '2024-12-31 16:48:47'),
(455, 'MARIA FERNANDA', 'FIGUEROA GUTIERREZ', 'HOSPITALIZACIÓN', '433', '4', '1500676649', 'DESNUTRICION', '1987-08-01', '37 año(s) 4 mes(es) 29 día(s)', 84.000, 'Femenino', '2024-12-31 17:22:47', '2024-12-31 17:22:47'),
(456, 'RN', 'MONTIEL ALBARRAN', 'UCIN', 'S/D', 'S/D', '117764', NULL, '2024-12-11', '19 día(s)', 1.020, 'Masculino', '2024-12-31 17:48:09', '2024-12-31 17:48:09'),
(457, 'RN', 'RAMIREZ MACEDO', 'UCIN', 'S/D', 'S/D', '297240', 'S/D', '2024-12-27', '3 día(s)', 2.285, 'Femenino', '2024-12-31 18:14:28', '2024-12-31 18:14:28'),
(458, 'RN', 'OLMEDO RICARDO', 'UTIN', NULL, NULL, NULL, NULL, '2024-12-16', '14 día(s)', 3.160, 'Masculino', '2024-12-31 18:37:16', '2024-12-31 18:37:16'),
(459, 'GEMELO 1', 'SAMANO VELAZQUEZ', 'UCIN', 'S/D', 'S/D', '209008', 'S/D', '2024-12-05', '25 día(s)', 2.200, 'Masculino', '2024-12-31 18:43:12', '2024-12-31 18:43:12'),
(460, 'RN', 'HERNANDEZ FLORES', 'UCIN', NULL, NULL, '297078', NULL, '2024-12-21', '9 día(s)', 1.000, 'Masculino', '2024-12-31 18:47:43', '2024-12-31 18:47:43'),
(461, 'RN', 'HERNANDEZ FLORES', 'UCIN', NULL, NULL, NULL, NULL, '2024-12-21', '9 día(s)', 1.000, NULL, '2024-12-31 18:59:39', '2024-12-31 18:59:39'),
(462, 'RN', 'ESTRADA BERNARDINO', 'REANIMACIÓN', NULL, 'PB', NULL, NULL, '2024-12-30', '1 día(s)', 1.965, 'Femenino', '2025-01-01 09:05:23', '2025-01-01 09:05:23'),
(463, 'RN', 'PERALTA SANCHEZ', 'UCIN', 'IV', NULL, 'PN_0114298', NULL, '2024-12-29', '2 día(s)', 1.320, 'Masculino', '2025-01-01 09:14:35', '2025-01-01 09:14:35'),
(464, 'RN', 'GONZALEZ CARMONA', 'REANIMACIÓN', NULL, 'PB', NULL, NULL, '2024-12-31', '', 2.180, 'Femenino', '2025-01-01 09:17:10', '2025-01-01 09:17:10'),
(465, 'RN', 'PEÑA ROMERO', 'REANIMACIÓN', NULL, 'PB', NULL, NULL, '2024-12-29', '2 día(s)', 1.620, 'Masculino', '2025-01-01 09:24:38', '2025-01-01 09:24:38'),
(466, 'RN', 'MARTINEZ GUZMÁN', 'REANIMACIÓN', NULL, 'PB', NULL, NULL, '2024-12-30', '1 día(s)', 1.495, 'Masculino', '2025-01-01 09:33:03', '2025-01-01 09:33:03'),
(467, 'RN', 'HERNANDEZ FLORES', 'UCIN', '2', NULL, '247078', NULL, '2024-12-21', '10 día(s)', 0.985, 'Masculino', '2025-01-01 09:50:51', '2025-01-01 09:50:51'),
(468, 'RN', 'PERALTA SANCHEZ', 'UCIN', '2', NULL, '0114298', NULL, '2024-12-29', '2 día(s)', 1.320, 'Masculino', '2025-01-01 09:52:33', '2025-01-01 09:52:33'),
(469, 'GEMELO II', 'ORTIZ MANJARREZ', 'UCIN', '4', NULL, '247297', NULL, '2024-12-30', '1 día(s)', 2.300, 'Femenino', '2025-01-01 09:55:44', '2025-01-01 09:55:44'),
(470, 'RN', 'VALENCIA HERNANDEZ', 'INTERNOS', NULL, '1', NULL, NULL, '2024-11-16', '1 mes(es) 15 día(s)', 1.360, 'Masculino', '2025-01-01 10:04:54', '2025-01-01 10:04:54'),
(471, 'RN', 'LOPEZ AVELINO', 'UCIN', '2', NULL, '205539', NULL, '0004-12-28', '2020 año(s) 3 día(s)', 1.630, 'Femenino', '2025-01-01 10:05:45', '2025-01-01 10:05:45'),
(472, 'RN', 'JUAREZ ORTIZ G1', 'INTERNOS', NULL, '1', NULL, NULL, '2024-12-27', '4 día(s)', 1.150, 'Masculino', '2025-01-01 10:10:45', '2025-01-01 10:10:45'),
(473, 'RN', 'FLORES ASCENCIO', 'PEDIATRIA', NULL, NULL, '133592', NULL, '2024-12-17', '14 día(s)', 1.965, 'Femenino', '2025-01-01 10:11:20', '2025-01-01 10:11:20'),
(474, 'RN', 'LAZARO ROSENDO', 'UCIN', '2', NULL, '205531', NULL, '2024-12-27', '4 día(s)', 2.460, 'Masculino', '2025-01-01 10:11:55', '2025-01-01 10:11:55'),
(475, 'RN', 'OLMEDO RICARDO', 'UCIN', '2', NULL, '246939', NULL, '2024-12-16', '15 día(s)', 3.100, 'Masculino', '2025-01-01 10:16:02', '2025-01-01 10:16:02'),
(476, 'RN', 'GUERRERO MARTINEZ', 'UCIN', '2', NULL, '205366', NULL, '2024-12-17', '14 día(s)', 2.615, 'Masculino', '2025-01-01 10:17:44', '2025-01-01 10:17:44'),
(477, 'RN', 'COLMENARES CRUZ', 'UCIN', '2', NULL, '205501', NULL, '2024-12-25', '6 día(s)', 2.505, 'Masculino', '2025-01-01 10:23:36', '2025-01-01 10:23:36'),
(478, 'RN', 'SANTIAGO CONTRERAS', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-29', '2 día(s)', 1.900, 'Femenino', '2025-01-01 10:24:11', '2025-01-01 10:24:11'),
(479, 'RN', 'ORTIZ PEREZ', 'UCIN', '2', NULL, '205502', NULL, '2024-12-26', '5 día(s)', 2.370, 'Femenino', '2025-01-01 10:29:49', '2025-01-01 10:29:49'),
(480, 'RN', 'RODRIGUEZ ANGELES G1', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-13', '18 día(s)', 1.395, 'Femenino', '2025-01-01 10:30:02', '2025-01-01 10:30:02'),
(481, 'RN', 'BENITEZ MARTINEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-26', '5 día(s)', 1.625, 'Masculino', '2025-01-01 10:35:29', '2025-01-01 10:35:29'),
(482, 'RN', 'JUAN SANCHEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-20', '11 día(s)', 1.110, 'Masculino', '2025-01-01 10:42:41', '2025-01-01 10:42:41'),
(483, 'RN', 'MARTINEZ GONZALEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-22', '9 día(s)', 1.545, 'Masculino', '2025-01-01 10:46:54', '2025-01-01 10:46:54'),
(484, 'RN', 'HERNANDEZ VILLALOBOS', 'UCIN', '5', NULL, '247280', NULL, '2024-12-30', '1 día(s)', 1.610, 'Masculino', '2025-01-01 10:50:39', '2025-01-01 10:50:39'),
(485, 'RN', 'DOMINGUEZ SANCHEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-11-19', '1 mes(es) 12 día(s)', 1.810, 'Masculino', '2025-01-01 10:52:57', '2025-01-01 10:52:57'),
(486, 'RN', 'VILLAREAL POSADAS', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-27', '4 día(s)', 1.080, 'Femenino', '2025-01-01 10:56:23', '2025-01-01 10:56:23'),
(487, 'RN', 'RUEDA SANTILLAN', 'UCIN', '6', NULL, '247706', NULL, '2024-12-26', '5 día(s)', 1.795, 'Masculino', '2025-01-01 10:56:53', '2025-01-01 10:56:53'),
(488, 'RN', 'RAMIREZ RUIZ', 'UCIREN', NULL, '1', NULL, NULL, '2024-12-28', '3 día(s)', 1.425, 'Masculino', '2025-01-01 11:01:45', '2025-01-01 11:01:45'),
(489, 'RN', 'BECERRIL GONZALEZ', 'INTERNOS', NULL, '1', NULL, NULL, '2024-11-26', '1 mes(es) 5 día(s)', 1.480, 'Femenino', '2025-01-01 11:06:10', '2025-01-01 11:06:10'),
(490, 'GEMELO 1', 'SAMANO VELAZQUEZ', 'UCIN', NULL, NULL, '209008', NULL, '2024-12-05', '26 día(s)', 2.490, 'Masculino', '2025-01-01 11:06:57', '2025-01-01 11:06:57'),
(491, 'RN', 'MARIN LOPEZ', 'INTERNOS', NULL, '1', NULL, NULL, '2024-11-29', '1 mes(es) 2 día(s)', 1.380, 'Femenino', '2025-01-01 11:09:28', '2025-01-01 11:09:28'),
(492, 'RN', 'ROJAS ROJAS G1', 'INTERNOS', NULL, '1', NULL, NULL, '2024-12-26', '5 día(s)', 1.420, 'Masculino', '2025-01-01 11:13:59', '2025-01-01 11:13:59'),
(493, 'RN', 'GARCIA RODRIGUEZ', 'UCIN', NULL, NULL, '70338', NULL, '2024-11-08', '1 mes(es) 23 día(s)', 1.810, 'Femenino', '2025-01-01 11:15:10', '2025-01-01 11:15:10'),
(494, 'RN', 'ROJAS ROJAS G2', 'INTERNOS', NULL, '1', NULL, NULL, '2024-12-26', '5 día(s)', 1.330, 'Masculino', '2025-01-01 11:17:11', '2025-01-01 11:17:11'),
(495, 'RN', 'MONTIEL ALBARRAN', 'INTERNOS', NULL, '1', NULL, NULL, '2024-12-11', '20 día(s)', 1.630, 'Masculino', '2025-01-01 11:21:25', '2025-01-01 11:21:25'),
(496, 'GEMELO 2', 'BECERRIL ARANA', 'UCIN', NULL, NULL, '70822', NULL, '2024-12-19', '12 día(s)', 1.900, 'Masculino', '2025-01-01 11:21:50', '2025-01-01 11:21:50'),
(497, 'RN', 'ANTONIO CAYETANO', 'INTERNOS', NULL, '1', NULL, NULL, '2024-10-07', '2 mes(es) 24 día(s)', 3.890, 'Femenino', '2025-01-01 11:24:55', '2025-01-01 11:24:55'),
(498, 'RN', 'HERNANDEZ PEREZ', 'UCIN', NULL, NULL, '70902', NULL, '2024-12-28', '3 día(s)', 1.180, 'Femenino', '2025-01-01 11:25:49', '2025-01-01 11:25:49'),
(499, 'RN', 'JACOBO CARRILLO', 'UCIN', NULL, NULL, '70817', NULL, '2024-12-23', '8 día(s)', 1.230, 'Femenino', '2025-01-01 11:29:28', '2025-01-01 11:29:28'),
(500, 'RN', 'LARA HERNANDEZ', 'UCIN', NULL, NULL, '70846', NULL, '2024-12-23', '8 día(s)', 0.830, 'Femenino', '2025-01-01 11:32:44', '2025-01-01 11:32:44'),
(501, 'JUDITH', 'BARCELATA HALL', 'Terapia Intensiva', 'UTI-6', '1', '1500599769', 'DESNUTRICION', '1953-10-12', '71 año(s) 2 mes(es) 19 día(s)', 78.000, 'Femenino', '2025-01-01 11:33:25', '2025-01-01 11:56:44'),
(502, 'RN', 'SANCHEZ SANTELLANO', 'UCIN', '1', NULL, '205438', NULL, '2024-12-22', '9 día(s)', 3.000, 'Femenino', '2025-01-01 11:44:35', '2025-01-01 11:44:35'),
(503, 'RN', 'GARCIA MARTINEZ', 'UCIN', '1', NULL, '205505', NULL, '2024-12-26', '5 día(s)', 1.830, 'Femenino', '2025-01-01 11:49:04', '2025-01-01 11:49:04'),
(504, 'RN', 'GARCIA SANTIAGO', 'PEDIATRIA', NULL, NULL, '939555-24', NULL, '2024-12-19', '12 día(s)', 1.570, 'Masculino', '2025-01-01 11:57:20', '2025-01-01 11:57:20'),
(505, 'RN', 'PEREZ CRUZ', 'UCIN', NULL, NULL, '464299', NULL, '2024-12-19', '12 día(s)', 0.940, 'Femenino', '2025-01-01 12:05:52', '2025-01-01 12:05:52'),
(506, 'RN', 'CARRIZOSA GARCIA', 'LACTANTES', NULL, NULL, '205308', NULL, '2024-12-10', '21 día(s)', 3.650, 'Femenino', '2025-01-01 12:13:40', '2025-01-01 12:13:40'),
(507, 'RN', 'AVILES MARTINEZ', 'UCIN', NULL, NULL, '464386', NULL, '2024-12-23', '8 día(s)', 0.975, 'Femenino', '2025-01-01 12:26:10', '2025-01-01 12:26:10'),
(508, 'RN', 'BERMUDEZ MEJIA', 'UCIN', 'U3', NULL, '428022', NULL, '2024-12-09', '22 día(s)', 1.320, 'Masculino', '2025-01-01 12:37:44', '2025-01-01 14:28:25'),
(509, 'RN', 'LOPEZ COLIN', 'UCIN', '6', NULL, '428267', NULL, '2024-12-11', '20 día(s)', 2.250, 'Femenino', '2025-01-01 12:40:53', '2025-01-01 12:40:53'),
(510, 'RN', 'VALENCIA ACOSTA', 'UCIN', NULL, NULL, '428445', NULL, '2024-12-23', '8 día(s)', 2.815, 'Masculino', '2025-01-01 12:44:03', '2025-01-01 12:44:03'),
(511, 'RN', 'GONZALEZ MILLAN', 'UCIN', '4', NULL, '428208', NULL, '2024-12-14', '17 día(s)', 1.695, 'Masculino', '2025-01-01 12:48:56', '2025-01-01 12:48:56'),
(512, 'RN', 'GABRIEL CALIXTO', 'UCIN', NULL, NULL, '426531', NULL, '2024-10-22', '2 mes(es) 9 día(s)', 2.240, 'Femenino', '2025-01-01 12:55:55', '2025-01-01 12:55:55'),
(513, 'TEODORO', 'MACIAS CERVANTES', 'HOSPITALIZACIÓN', '301', '3', '1500673991', 'DESNUTRICIÓN', '1936-09-11', '88 año(s) 3 mes(es) 20 día(s)', 60.000, 'Masculino', '2025-01-01 13:00:35', '2025-01-01 13:00:35'),
(514, 'RN', 'LOPEZ MARTINEZ', 'UCIN', NULL, NULL, '024-225261', NULL, '2024-11-27', '1 mes(es) 4 día(s)', 2.235, 'Masculino', '2025-01-01 13:08:56', '2025-01-01 13:08:56'),
(515, 'RN', 'PAVON MARTINEZ', 'UCIN', NULL, NULL, '233111', NULL, '2024-12-19', '12 día(s)', 1.825, 'Masculino', '2025-01-01 13:19:54', '2025-01-01 13:19:54'),
(516, 'RN', 'LOPEZ CALDERON', 'UCIN', NULL, NULL, '233206', NULL, '2024-12-24', '7 día(s)', 1.400, 'Femenino', '2025-01-01 13:25:52', '2025-01-01 13:25:52'),
(517, 'RN', 'LOPEZ CALDERON', 'UCIN', NULL, NULL, '233206', NULL, '2024-12-24', '7 día(s)', 1.400, 'Femenino', '2025-01-01 13:25:57', '2025-01-01 13:25:57'),
(518, 'RN', 'Baños Hernandez', 'ucin', NULL, NULL, '233147', NULL, '2024-12-20', '11 día(s)', 2.280, 'Masculino', '2025-01-01 13:30:04', '2025-01-01 13:30:04'),
(519, 'RN', 'BAÑOS HERNANDEZ', 'UCIN', NULL, NULL, '233147', NULL, '2024-12-20', '11 día(s)', 2.280, 'Masculino', '2025-01-01 13:32:34', '2025-01-01 13:32:34'),
(520, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACIÓN', '307', '3', '1500660965', 'DESNUTRICION', '1947-07-31', '77 año(s) 5 mes(es) ', 68.000, 'Masculino', '2025-01-01 13:40:40', '2025-01-01 13:40:40'),
(521, 'GRACIELA', 'FERNANDEZ LARA', 'Terapia Intensiva', 'UTI-9', '1', '1500672626', 'DESNUTRICIÓN', '1937-06-12', '87 año(s) 6 mes(es) 19 día(s)', 50.000, 'Femenino', '2025-01-01 14:02:57', '2025-01-01 14:02:57'),
(522, 'RN', 'JACOBO CARRILLO', 'UCIN', NULL, NULL, '70817', NULL, '2024-12-23', '8 día(s)', 1.230, 'Femenino', '2025-01-01 14:18:50', '2025-01-01 14:18:50'),
(523, 'RN', 'GARCIA RODRIGUEZ', 'UCIN', NULL, NULL, '70338', NULL, '2024-11-08', '1 mes(es) 23 día(s)', 1.810, 'Femenino', '2025-01-01 14:24:53', '2025-01-01 14:24:53'),
(524, 'MARIA FERNANDA', 'FIGUEROA GUTIERREZ', 'HOSPITALIZACION', '433', '4', NULL, 'DESNUTRICION', '1987-08-01', '37 año(s) 4 mes(es) 30 día(s)', 84.000, 'Femenino', '2025-01-01 14:37:26', '2025-01-01 14:37:26'),
(525, 'RN', 'MORENO PATRICIO', 'UCIN', NULL, NULL, '36526', NULL, '2024-10-30', '2 mes(es) 1 día(s)', 1.150, 'Femenino', '2025-01-01 14:42:03', '2025-01-01 14:42:03'),
(526, 'MARIA FERNANDA', 'FIGUEROA GUTIERREZ', 'HOSPITALIZACIÓN', '433', '4', '1500676649', 'DESNUTRICION', '1987-08-01', '37 año(s) 4 mes(es) 30 día(s)', 84.000, 'Femenino', '2025-01-01 14:56:59', '2025-01-01 14:56:59'),
(527, 'GRACIELA', 'FERNANDEZ LARA', 'Terapia Intensiva', 'UTI-9', '1', '1500672626', 'DESNUTRICION', '1937-06-12', '87 año(s) 6 mes(es) 19 día(s)', 50.000, 'Femenino', '2025-01-01 15:11:13', '2025-01-01 15:11:13'),
(528, 'RN', 'GARCIA MARTINEZ', 'UCIN-1', NULL, NULL, '205505', NULL, '2024-12-26', '5 día(s)', 1.830, 'Femenino', '2025-01-01 17:03:09', '2025-01-01 17:03:09'),
(529, 'Abril Michelle', 'Gonzalez Huizache', 'HOSPITALIZACIÓN', '236', '2', '10500674194', 'Oclusion duodenal+ Post operada de duodeno', '2023-07-09', '1 año(s) 5 mes(es) 22 día(s)', 10.000, 'Femenino', '2025-01-01 17:10:43', '2025-01-01 17:10:43'),
(530, 'MARIA FERNANDA', 'FIGUEROA GUTIERREZ', 'HOSPITALIZACION', NULL, NULL, '1500676649', 'DESNUTRICIÓN', '1987-08-01', '37 año(s) 4 mes(es) 30 día(s)', 84.000, 'Femenino', '2025-01-01 17:20:01', '2025-01-01 17:20:01'),
(531, 'Graciela', 'Hurtado Garcia', 'Terapia Intensiva', 'UTI-3', '1', '1500677237', 'Desnutrición', '1977-05-31', '47 año(s) 7 mes(es) 1 día(s)', 71.000, 'Femenino', '2025-01-02 08:48:00', '2025-01-02 08:48:00'),
(532, 'RN', 'SANDOVAL RODAL', 'NEONATOLOGIA', 'UCIN-2', '2', '1500673741', 'DESNUTRICION', '2024-12-22', '10 día(s)', 2.246, 'Femenino', '2025-01-02 09:19:20', '2025-01-02 09:19:20'),
(533, 'RN', 'GARCIA SANTIAGO', 'PEDIATRIA', NULL, NULL, '93955', NULL, '2024-12-19', '13 día(s)', 1.570, 'Masculino', '2025-01-02 09:37:56', '2025-01-02 09:37:56'),
(534, 'RN', 'GARCIA MARTINEZ', 'UCIN 1', NULL, NULL, '205505', NULL, '2024-12-26', '6 día(s)', 1.840, 'Femenino', '2025-01-02 09:43:50', '2025-01-02 09:43:50'),
(535, 'RN', 'SANCHEZ SANTELLANO', 'UCIN', NULL, NULL, '205404', NULL, '2024-12-22', '10 día(s)', 3.400, 'Femenino', '2025-01-02 09:46:14', '2025-01-02 09:46:14'),
(536, 'RN', 'ESTRADA BERNARDINO', 'REANIMACIÓN', NULL, '1', '118160', NULL, '2024-12-30', '2 día(s)', 1.960, 'Femenino', '2025-01-02 09:48:48', '2025-01-02 09:48:48'),
(537, 'RN', 'PEÑA ROMERO', 'REANIMACIÓN', NULL, '1', '118152', NULL, '2024-12-29', '3 día(s)', 1.620, 'Masculino', '2025-01-02 09:52:02', '2025-01-02 09:52:02'),
(538, 'RN', 'MARTINEZ GUZMÁN', 'REANIMACIÓN', NULL, '1', '118171', NULL, '2024-12-30', '2 día(s)', 1.495, 'Masculino', '2025-01-02 09:56:38', '2025-01-02 09:56:38'),
(539, 'RN', 'GARCIA SANTIAGO', 'UCIN', NULL, NULL, '939555', NULL, '2024-12-19', '13 día(s)', 1.570, 'Masculino', '2025-01-02 09:59:31', '2025-01-02 09:59:31'),
(540, 'RN', 'PERALTA SANCHEZ', 'UCIN', NULL, NULL, '0114298', NULL, '2024-12-29', '3 día(s)', 1.265, 'Masculino', '2025-01-02 10:21:07', '2025-01-02 10:21:07'),
(541, 'RN', 'LOPEZ AVELINO', 'UCIN2', NULL, NULL, '205539', NULL, '2024-12-28', '4 día(s)', 1.480, 'Femenino', '2025-01-02 10:34:44', '2025-01-02 10:34:44'),
(542, 'RN', 'COLMENARES CRUZ', 'UCIN2', NULL, NULL, '203045', NULL, '2024-12-25', '7 día(s)', 2.505, 'Femenino', '2025-01-02 10:37:00', '2025-01-02 10:37:00'),
(543, 'RN', 'ORTIZ PEREZ', 'UCIN2', NULL, NULL, '305502', NULL, '2024-12-26', '6 día(s)', 2.337, 'Femenino', '2025-01-02 10:38:51', '2025-01-02 10:38:51'),
(544, 'RN', 'LAZARO ROSENDO', 'UCIN2', NULL, NULL, '205531', NULL, '2024-12-27', '5 día(s)', 2.460, 'Masculino', '2025-01-02 10:42:24', '2025-01-02 10:42:24'),
(545, 'RN', 'GUERRERO MARTINEZ', 'UCIN2', NULL, NULL, '205366', NULL, '2024-12-17', '15 día(s)', 2.615, 'Masculino', '2025-01-02 10:45:12', '2025-01-02 10:45:12'),
(546, 'RN', 'MEJIA VAZQUEZ', 'UCIN 2', NULL, NULL, '28847', NULL, '2024-12-31', '1 día(s)', 3.332, 'Masculino', '2025-01-02 10:46:54', '2025-01-02 10:46:54'),
(547, 'RN', 'RAMIREZ RUIZ', 'UCIREN 1', NULL, NULL, '118149', NULL, '2024-12-28', '4 día(s)', 1.420, 'Masculino', '2025-01-02 10:50:28', '2025-01-02 10:50:28'),
(548, 'RN', 'CARRIZOSA GARCIA', 'UCIN2', NULL, NULL, '205308', NULL, '2024-12-10', '22 día(s)', 3.650, NULL, '2025-01-02 10:50:46', '2025-01-02 10:50:46'),
(549, 'RN', 'GARCIA MARTINEZ', 'UCIN1', NULL, NULL, '205505', NULL, '2024-12-26', '6 día(s)', 1.840, 'Femenino', '2025-01-02 10:55:32', '2025-01-02 10:55:32'),
(550, 'G1', 'JUAREZ ORTIZ', 'UCIN INTERNOS', '14', '1', '118137', NULL, '2024-12-27', '5 día(s)', 1.100, 'Masculino', '2025-01-02 10:58:01', '2025-01-02 10:58:01'),
(551, 'RN', 'GABRIEL CALIXTO', 'UTIN', NULL, NULL, '426531', NULL, '2024-10-20', '2 mes(es) 12 día(s)', 2.240, 'Femenino', '2025-01-02 11:02:34', '2025-01-02 11:02:34'),
(552, 'RN', 'VALENCIA HERNANDEZ', 'UCIN INTERNOS', '10', NULL, '117017', NULL, '2024-11-16', '1 mes(es) 15 día(s)', 1.610, 'Masculino', '2025-01-02 11:03:09', '2025-01-02 11:03:09'),
(553, 'RN', 'VALENCIA ACOSTA', 'UCIN', NULL, NULL, '428445', NULL, '2024-12-22', '10 día(s)', 2.815, 'Masculino', '2025-01-02 11:04:31', '2025-01-02 11:04:31'),
(554, 'RN', 'LOPEZ COLIN G1', 'UCIN', NULL, NULL, '428267', NULL, '2024-11-13', '1 mes(es) 18 día(s)', 2.250, 'Femenino', '2025-01-02 11:06:24', '2025-01-02 11:06:24'),
(555, 'RN', 'ANTONIO CAYETANO', 'UCIN INTERNOS', '8', NULL, '115848', NULL, '2024-10-07', '2 mes(es) 25 día(s)', 3.790, 'Femenino', '2025-01-02 11:07:19', '2025-01-02 11:07:19'),
(556, 'RN', 'GONZALEZ MILLAN', 'UCIN', NULL, NULL, '428208', NULL, '2024-12-14', '18 día(s)', 1.695, 'Masculino', '2025-01-02 11:08:09', '2025-01-02 11:08:09'),
(557, 'RN', 'SANTIAGO CONTRERAS', 'UCIN EXTERNOS', NULL, '1', '118152', NULL, '2024-12-29', '3 día(s)', 1.800, 'Femenino', '2025-01-02 11:11:00', '2025-01-02 11:11:00'),
(558, 'RN', 'FLORES ASCENCIO', 'PEDIATRIA', NULL, NULL, '133542', NULL, '2024-12-17', '15 día(s)', 1.968, 'Femenino', '2025-01-02 11:12:03', '2025-01-02 11:12:03'),
(559, 'RN', 'ARZATE LOPEZ', 'UCIN EXTERNOS', NULL, NULL, '118185', NULL, '2024-12-31', '1 día(s)', 1.360, 'Masculino', '2025-01-02 11:14:26', '2025-01-02 11:14:26'),
(560, 'RN', 'GONZALEZ CARMONA', 'UCIN EXTERNOS', '11', '1', '118178', NULL, '2024-12-31', '1 día(s)', 2.180, 'Femenino', '2025-01-02 11:17:24', '2025-01-02 11:17:24'),
(561, 'RN', 'BAÑOS HERNANDEZ', 'UCIN', NULL, NULL, '233147', NULL, '2024-12-20', '12 día(s)', 2.270, 'Masculino', '2025-01-02 11:19:17', '2025-01-02 11:19:17'),
(562, 'RN', 'PAVON MARTINEZ', 'UCIN', NULL, NULL, '233111', NULL, '2024-12-19', '13 día(s)', 1.830, 'Masculino', '2025-01-02 11:20:48', '2025-01-02 11:20:48'),
(563, 'RN', 'DOMINGUEZ SANCHEZ', 'UCIN EXTERNOS', '8', '1', '117094', NULL, '2024-11-19', '1 mes(es) 12 día(s)', 1.820, 'Masculino', '2025-01-02 11:22:07', '2025-01-02 11:22:07'),
(564, 'RN', 'LOPEZ CALDERON', 'UCIN', NULL, NULL, '233206', NULL, '2024-12-24', '8 día(s)', 1.420, 'Femenino', '2025-01-02 11:22:24', '2025-01-02 11:22:24'),
(565, 'RN', 'BENITEZ MARTINEZ', 'UCIN EXTERNOS', '7', '1', '118100', NULL, '2024-12-26', '6 día(s)', 1.630, 'Masculino', '2025-01-02 11:25:36', '2025-01-02 11:25:36'),
(566, 'RN', 'MARTINEZ GONZALEZ', 'UCIN EXTERNOS', NULL, '1', '118025', NULL, '2024-12-22', '10 día(s)', 1.570, 'Masculino', '2025-01-02 11:29:25', '2025-01-02 11:29:25'),
(567, 'G2', 'ROJAS ROJAS', 'UCIN INTERNOS', '6', '1', '118110', NULL, '2024-12-26', '6 día(s)', 1.260, 'Masculino', '2025-01-02 11:34:03', '2025-01-02 11:34:03'),
(568, 'G1', 'ROJAS ROJAS', 'UCIN INTERNOS', '5', '1', '118110', NULL, '2024-12-26', '6 día(s)', 1.160, 'Masculino', '2025-01-02 11:37:03', '2025-01-02 11:37:03'),
(569, 'RN', 'GARCIA RODRIGUEZ', 'UCIN', NULL, NULL, '70338', NULL, '2024-11-08', '1 mes(es) 23 día(s)', 1.800, 'Femenino', '2025-01-02 11:50:23', '2025-01-02 11:50:23'),
(570, 'RN', 'JACOBO CARRILLO', 'UCIN', NULL, NULL, '70692', NULL, '2024-12-08', '24 día(s)', 1.359, 'Femenino', '2025-01-02 11:52:31', '2025-01-02 11:52:31'),
(571, 'Graciela', 'Fernandez Lara', 'Terapia intensiva', 'UTI-9', '1', '1500672626', 'DESNUTRICION', '1937-06-12', '87 año(s) 6 mes(es) 19 día(s)', 50.000, 'Femenino', '2025-01-02 11:54:03', '2025-01-02 11:54:03'),
(572, 'RN', 'LARA HERNANDEZ', 'UCIN', NULL, NULL, '70863', NULL, '2024-12-23', '9 día(s)', 0.750, 'Femenino', '2025-01-02 11:54:25', '2025-01-02 11:54:25'),
(573, 'RN', 'AVILES MARTINEZ', 'UCIN', NULL, NULL, '464386', NULL, '2024-12-23', '9 día(s)', 0.905, 'Femenino', '2025-01-02 12:06:00', '2025-01-02 12:06:00'),
(574, 'JUDITH', 'BARCELATA HALL', 'Terapia Intensiva', 'UTI-6', '1', '1500599769', 'Desnutrición', '1953-10-12', '71 año(s) 2 mes(es) 20 día(s)', 60.000, 'Femenino', '2025-01-02 12:07:29', '2025-01-02 12:07:29'),
(575, 'RN', 'PEREZ CRUZ', 'UCIN', NULL, NULL, '464299', NULL, '2024-12-19', '13 día(s)', 0.940, 'Femenino', '2025-01-02 12:08:06', '2025-01-02 12:08:06'),
(576, 'RN', 'PATRICIO MORENO', 'UCIN', NULL, NULL, NULL, NULL, '2024-10-30', '2 mes(es) 2 día(s)', 1.190, 'Femenino', '2025-01-02 12:23:27', '2025-01-02 12:59:54'),
(577, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACION', '307', '3', '1500660965', 'Desnutrición', '1947-07-31', '77 año(s) 5 mes(es) 1 día(s)', 68.000, 'Masculino', '2025-01-02 12:23:56', '2025-01-02 12:23:56'),
(578, 'RN', 'SANCHEZ RINCON', 'UCIN', NULL, NULL, '225885', NULL, '2024-12-21', '11 día(s)', 1.068, 'Femenino', '2025-01-02 12:27:45', '2025-01-02 12:27:45'),
(579, 'RN', 'HERNANDEZ FLORES', 'UCIN', NULL, NULL, '247078', NULL, '2024-12-21', '11 día(s)', 1.025, 'Masculino', '2025-01-02 12:32:02', '2025-01-02 12:32:02'),
(580, 'RN', 'ORTIZ MANJARRES G1', 'UCIN', NULL, NULL, '247297', NULL, '0004-12-30', '2020 año(s) 2 día(s)', 2.355, 'Femenino', '2025-01-02 12:33:50', '2025-01-02 12:33:50'),
(581, 'RN', 'OLMEDO RICARDO', 'UCIN', NULL, NULL, '246939', NULL, '2024-12-16', '16 día(s)', 3.100, 'Masculino', '2025-01-02 12:35:33', '2025-01-02 12:35:33'),
(582, 'RN', 'HERNANDEZ VILLALOBOS', 'UCIN', NULL, NULL, '247280', NULL, '2024-12-30', '2 día(s)', 1.795, 'Masculino', '2025-01-02 12:40:11', '2025-01-02 12:40:11'),
(583, 'RN', 'RICARDO SANTILLAN', 'UCIN', '6', NULL, '297206', NULL, '2024-12-26', '6 día(s)', 1.790, 'Masculino', '2025-01-02 12:44:11', '2025-01-02 12:44:11'),
(584, 'TEODORO', 'MACIAS CERVANTES', 'HOSPITALIZACIÓN', '301', '3', '1500673991', 'DESNUTRICIÓN', '1936-09-11', '88 año(s) 3 mes(es) 20 día(s)', 60.000, 'Masculino', '2025-01-02 13:01:26', '2025-01-02 13:01:26'),
(585, 'MARIA FERNANDA', 'FIGUEROA GUTIERREZ', 'HOSPITALIZACIÓN', '433', '4', '1500676649', 'DESNUTRICIÓN', '1987-08-01', '37 año(s) 5 mes(es) ', 84.000, 'Femenino', '2025-01-02 13:09:28', '2025-01-02 13:09:28'),
(586, 'RN', 'ANTONIO CAYETANO', 'UCIN INTERNOS', NULL, NULL, '115848', NULL, '2024-10-07', '2 mes(es) 25 día(s)', 3.790, 'Femenino', '2025-01-02 14:30:45', '2025-01-02 14:30:45'),
(587, 'RN', 'VALENCIA HERNANDEZ', 'UCIN INTERNOS', NULL, NULL, '117017', NULL, '2024-11-16', '1 mes(es) 15 día(s)', 1.610, 'Masculino', '2025-01-02 14:32:38', '2025-01-02 14:32:38'),
(588, 'ABRIL', 'GONZALEZ HUIZACHE', 'PEDIATRIA', '236', '2', '1500674194', 'OBSTRUCCION DUODENAL', '2023-07-09', '1 año(s) 5 mes(es) 23 día(s)', 9.800, 'Femenino', '2025-01-02 15:34:44', '2025-01-02 15:34:44'),
(589, 'RN', 'PATRICIO MORENO', 'UCIN', NULL, NULL, NULL, NULL, '2024-10-30', '2 mes(es) 2 día(s)', 1.190, 'Femenino', '2025-01-02 15:44:18', '2025-01-02 15:44:18'),
(590, 'GEMELO 1', 'SAMANO VELAZQUEZ', 'UCIN', NULL, NULL, '209008', NULL, '2024-12-05', '27 día(s)', 2.490, 'Masculino', '2025-01-02 15:59:39', '2025-01-02 15:59:39'),
(591, 'RN', 'PERALTA SANCHEZ', 'UCIN', NULL, NULL, '114298', NULL, '2024-12-29', '4 día(s)', 1.255, 'Masculino', '2025-01-03 09:11:24', '2025-01-03 09:11:24'),
(592, 'RN', 'GARCIA RODRIGUEZ', 'UCIN', NULL, NULL, '70338', NULL, '2024-11-08', '1 mes(es) 24 día(s)', 1.810, 'Femenino', '2025-01-03 09:14:49', '2025-01-03 09:14:49'),
(593, 'RN', 'FLORES ASCENCIO', 'PEDIATRIA', NULL, NULL, '133592-1', NULL, '2024-12-17', '16 día(s)', 2.020, 'Femenino', '2025-01-03 09:43:55', '2025-01-03 09:43:55'),
(594, 'RN', 'SAMANO VELAZQUEZ G1', 'UCIN', NULL, NULL, '209008', NULL, '2024-12-05', '28 día(s)', 2.490, 'Masculino', '2025-01-03 09:56:49', '2025-01-03 09:56:49'),
(595, 'RN', 'GONZALEZ MALVAEZ', 'UCIREN', NULL, '1', NULL, NULL, '2024-12-27', '6 día(s)', 1.400, 'Femenino', '2025-01-03 10:09:37', '2025-01-03 10:09:37'),
(596, 'RN', 'JACOBO CARRILLO', 'UTIN', '9', NULL, '70821', NULL, '2024-12-18', '15 día(s)', 1.200, 'Femenino', '2025-01-03 10:12:53', '2025-01-03 10:12:53'),
(597, 'RN', 'RAMIREZ RUIZ', 'UCIREN', NULL, '1', NULL, NULL, '2024-12-28', '5 día(s)', 1.420, 'Masculino', '2025-01-03 10:14:11', '2025-01-03 10:14:11'),
(598, 'RN', 'LARA HERNANDEZ', 'UTIN', '2', NULL, '70863', NULL, '2024-12-23', '10 día(s)', 0.830, 'Femenino', '2025-01-03 10:18:15', '2025-01-03 10:18:15'),
(599, 'RN', 'AVILES LOPEZ G1', 'REANIMACION', NULL, 'PB', NULL, NULL, '2024-12-31', '2 día(s)', 1.110, 'Masculino', '2025-01-03 10:19:10', '2025-01-03 10:19:10'),
(600, 'RN', 'HERNANDEZ PEREZ', 'UTIN', '6', NULL, '70902', NULL, '2024-12-28', '5 día(s)', 1.230, 'Femenino', '2025-01-03 10:20:27', '2025-01-03 10:20:27'),
(601, 'RN', 'ESTRADA BERNARDINO', 'REANIMACION', NULL, 'PB', NULL, NULL, '2024-12-30', '3 día(s)', 1.930, 'Femenino', '2025-01-03 10:24:37', '2025-01-03 10:24:37'),
(602, 'RN', 'ORDAZ GONZALEZ', 'UCIN', NULL, NULL, '248598', NULL, '2024-12-19', '14 día(s)', 1.280, 'Femenino', '2025-01-03 10:25:14', '2025-01-03 10:25:14'),
(603, 'RN', 'MARTINEZ GUZMAN', 'REANIMACION', NULL, 'PB', NULL, NULL, '2024-12-30', '3 día(s)', 1.495, 'Masculino', '2025-01-03 10:29:52', '2025-01-03 10:29:52'),
(604, 'RN', 'MARIN LOPEZ', 'INTERNOS', '4', '1', NULL, NULL, '2024-11-29', '1 mes(es) 3 día(s)', 1.390, 'Femenino', '2025-01-03 10:35:48', '2025-01-03 10:35:48'),
(605, 'RN', 'ROJAS ROJAS G1', 'INTERNOS', '5', '1', NULL, NULL, '2024-12-26', '7 día(s)', 1.265, 'Masculino', '2025-01-03 10:40:34', '2025-01-03 10:40:34'),
(606, 'RN', 'HERNANDEZ FLOREZ', 'UCIN', NULL, NULL, '247078', NULL, '2024-12-21', '12 día(s)', 1.050, 'Masculino', '2025-01-03 10:52:22', '2025-01-03 10:52:22'),
(607, 'RN', 'SANDOVAL RODAL', 'NEONATOLOGÍA', 'UCIN-2', '2', '1500673741', 'DESNUTRICIÓN', '2024-12-22', '11 día(s)', 2.300, 'Femenino', '2025-01-03 10:52:34', '2025-01-03 10:52:34'),
(608, 'RN', 'VILLAGOMEZ GONZALEZ', 'UCIN', NULL, NULL, '245462', NULL, '2025-01-02', '', 0.900, 'Masculino', '2025-01-03 10:54:20', '2025-01-03 10:54:20'),
(609, 'RN', 'OLMEDO RICARDO', 'UCIN', NULL, NULL, '246939', NULL, '2024-12-10', '23 día(s)', 3.255, 'Masculino', '2025-01-03 10:56:00', '2025-01-03 10:56:00'),
(610, 'RN', 'JUAREZ ORTIZ G1', 'INTERNOS', NULL, '1', NULL, NULL, '2024-12-27', '6 día(s)', 1.110, 'Masculino', '2025-01-03 11:02:27', '2025-01-03 11:02:27'),
(611, 'RN', 'VALENCIA HERNANDEZ', 'INTERNOS', NULL, '1', NULL, NULL, '2024-11-16', '1 mes(es) 16 día(s)', 1.610, 'Masculino', '2025-01-03 11:06:37', '2025-01-03 11:06:37'),
(612, 'RN', 'AVILES LOPEZ G2', 'INTERNOS', NULL, '1', NULL, NULL, '2024-12-31', '2 día(s)', 1.870, 'Masculino', '2025-01-03 11:09:55', '2025-01-03 11:09:55'),
(613, 'RN', 'AVILES MARTINEZ', 'UCIN', NULL, NULL, '464386', NULL, '2024-12-23', '10 día(s)', 0.975, 'Femenino', '2025-01-03 11:12:48', '2025-01-03 11:12:48'),
(614, 'RN', 'ANTONIO CAYETANO', 'INTERNOS', '8', '1', NULL, NULL, '2024-10-07', '2 mes(es) 26 día(s)', 3.890, 'Femenino', '2025-01-03 11:13:02', '2025-01-03 11:13:02'),
(615, 'RN', 'Carrizosa Garcia', 'Lactantes', NULL, NULL, '205308', NULL, '2024-12-10', '23 día(s)', 3.656, 'Femenino', '2025-01-03 11:13:07', '2025-01-03 11:13:07'),
(616, 'RN', 'PEREZ CRUZ', 'UCIN', NULL, NULL, '464299', NULL, '2024-12-29', '4 día(s)', 0.990, 'Femenino', '2025-01-03 11:14:38', '2025-01-03 11:14:38'),
(617, 'RN', 'CRUZ RODRIGUEZ', 'UCIN', NULL, NULL, '464659', NULL, '2025-01-01', '1 día(s)', 1.850, 'Masculino', '2025-01-03 11:16:16', '2025-01-03 11:16:16'),
(618, 'RN', 'PEÑA MORA', 'UCIN', NULL, NULL, '464497', NULL, '2024-12-26', '7 día(s)', 1.275, 'Femenino', '2025-01-03 11:18:04', '2025-01-03 11:18:04'),
(619, 'RN', 'ROJAS ROJAS G2', 'INTERNOS', '6', '1', NULL, NULL, '2024-12-26', '7 día(s)', 1.190, 'Masculino', '2025-01-03 11:18:55', '2025-01-03 11:18:55'),
(620, 'RN', 'Gomez Corona', 'Lactantes', NULL, NULL, '205541', NULL, '2024-12-09', '24 día(s)', 2.800, 'Femenino', '2025-01-03 11:19:46', '2025-01-03 12:23:54'),
(621, 'RN', 'ARZATE LOPEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-31', '2 día(s)', 1.345, 'Masculino', '2025-01-03 11:24:30', '2025-01-03 11:24:30'),
(622, 'RN', 'Sanchez Santellano', 'UCIN 1', NULL, NULL, '205438', NULL, '2024-12-22', '11 día(s)', 3.365, 'Femenino', '2025-01-03 11:27:16', '2025-01-03 11:27:16'),
(623, 'RN', 'SANTIAGO CONTRERAS', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-29', '4 día(s)', 1.820, 'Femenino', '2025-01-03 11:29:09', '2025-01-03 11:29:09'),
(624, 'RN', 'GARCIA SANTIAGO', 'UCIN', NULL, NULL, '93955', NULL, '2024-12-19', '14 día(s)', 1.610, 'Masculino', '2025-01-03 11:30:04', '2025-01-03 11:30:04'),
(625, 'RN', 'Bermudez Mejia', 'UCIN', 'U3', '1', '428022', 'Prematurez', '2024-12-09', '24 día(s)', 1.320, 'Masculino', '2025-01-03 11:31:28', '2025-01-03 11:31:28'),
(626, 'RN', 'Garcia Martinez', 'UCIN 1', NULL, NULL, '205505', NULL, '2024-12-26', '7 día(s)', 1.945, 'Femenino', '2025-01-03 11:33:04', '2025-01-03 12:53:59'),
(627, 'RN', 'MARTINEZ GONZALEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-22', '11 día(s)', 1.635, 'Masculino', '2025-01-03 11:33:16', '2025-01-03 11:33:16'),
(628, 'RN', 'Valencia Acosta', 'UCIN', 'U1', '1', '428445', 'Hiperbilirrubinemia', '2024-12-23', '10 día(s)', 2.735, 'Masculino', '2025-01-03 11:34:56', '2025-01-03 11:34:56'),
(629, 'RN', 'BENITEZ MARTINEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-26', '7 día(s)', 1.600, 'Masculino', '2025-01-03 11:36:49', '2025-01-03 11:36:49'),
(630, 'RN', 'GONZALEZ CARMONA', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-31', '2 día(s)', 2.150, 'Femenino', '2025-01-03 11:39:58', '2025-01-03 11:39:58'),
(631, 'RN', 'Gonzalez Millan', 'UCIN', 'U4', '1', '428208', 'Prematurez', '2024-12-14', '19 día(s)', 1.730, 'Masculino', '2025-01-03 11:40:33', '2025-01-03 11:40:33'),
(632, 'RN', 'GUERRERO MARTINEZ', 'PEDIATRIA UCIN2', NULL, NULL, '205366', NULL, '2024-12-17', '16 día(s)', 2.740, 'Masculino', '2025-01-03 11:41:14', '2025-01-03 11:41:14'),
(633, 'RN', 'Lopez Cdin Gemela 1', 'UCIN', 'U6', '1', '428267', 'TCE', '2024-12-14', '19 día(s)', 2.605, NULL, '2025-01-03 11:43:16', '2025-01-03 11:43:16'),
(634, 'RN', 'DOMINGUEZ SANCHEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-11-19', '1 mes(es) 13 día(s)', 1.835, 'Masculino', '2025-01-03 11:43:25', '2025-01-03 11:43:25'),
(635, 'RN', 'Mejia Vazquez', 'PEDIATRIA UCIN 2', NULL, NULL, '205587', NULL, '2024-12-31', '2 día(s)', 3.300, 'Femenino', '2025-01-03 11:45:14', '2025-01-03 11:45:14'),
(636, 'RN', 'COLMENARES CRUZ', 'PEDIATRIA UCIN 2', NULL, NULL, '203045', NULL, '2024-12-25', '8 día(s)', 2.700, 'Femenino', '2025-01-03 11:49:26', '2025-01-03 13:53:22'),
(637, 'RN', 'Gabriel Calixto', 'UCIN', '102', '1', '426521', 'PO LAPE', '2024-10-22', '2 mes(es) 11 día(s)', 2.200, 'Femenino', '2025-01-03 11:53:23', '2025-01-03 11:53:23'),
(638, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACIÓN', '307', '3', '1500660965', 'DESNUTRICIÓN', '1947-07-31', '77 año(s) 5 mes(es) 2 día(s)', 68.000, 'Masculino', '2025-01-03 12:05:28', '2025-01-03 12:05:28'),
(639, 'GRACIELA', 'HURTADO GARCÍA', 'TERAPIA INTENSIVA', 'UTI-3', '1', '1500677237', 'DESNUTRICIÓN', '1977-05-31', '47 año(s) 7 mes(es) 2 día(s)', 70.000, 'Femenino', '2025-01-03 12:18:35', '2025-01-03 12:18:35'),
(640, 'JUDITH', 'BARCELATA HALL', 'Terapia Intensiva', 'UTI-6', '1', '1500599769', 'Desnutrición', '1953-10-12', '71 año(s) 2 mes(es) 21 día(s)', 60.000, 'Femenino', '2025-01-03 12:33:58', '2025-01-03 12:33:58'),
(641, 'GRACIELA', 'FERNANDEZ LARA', 'Terapia Intensiva', 'UTI-9', '1', '1500672626', 'Neumonía', '1937-06-12', '87 año(s) 6 mes(es) 20 día(s)', 51.000, 'Femenino', '2025-01-03 12:46:19', '2025-01-03 12:46:19'),
(642, 'MARIA FERNANDA', 'FIGUEROA GUTIERREZ', 'HOSPITALIZACIÓN', '433', '4', '1500676649', 'PANCREATITIS AGUDA', '1987-08-01', '37 año(s) 5 mes(es) 1 día(s)', 84.000, 'Femenino', '2025-01-03 13:50:54', '2025-01-03 13:50:54'),
(643, 'RN', 'Carrizosa Garcia', 'Lactantes', NULL, NULL, '205308', NULL, '2024-12-10', '23 día(s)', 3.656, 'Femenino', '2025-01-03 14:20:10', '2025-01-03 14:20:10'),
(644, 'RN', 'Bermudez Mejia', 'UCIN', 'U3', '1', '428022', 'Prematurez', '2024-12-08', '25 día(s)', 1.320, 'Masculino', '2025-01-03 14:37:19', '2025-01-03 14:37:19'),
(645, 'TEODORO', 'MACIAS CERVANTES', 'HOSPITALIZACION', '301', '3', '1500673991', 'DESNUTRICION', '1936-09-11', '88 año(s) 3 mes(es) 21 día(s)', 60.000, 'Masculino', '2025-01-03 15:38:17', '2025-01-03 15:38:17'),
(646, 'TEODORO', 'MACIAS CERVANTES', 'HOSPITALIZACION', '3', '301', '1500673991', 'DESNUTRICION', '1936-09-11', '88 año(s) 3 mes(es) 21 día(s)', 60.000, 'Masculino', '2025-01-03 15:43:55', '2025-01-03 15:43:55');
INSERT INTO `solicitud_patients` (`id`, `nombre_paciente`, `apellidos_paciente`, `servicio`, `cama`, `piso`, `registro`, `diagnostico`, `fecha_nacimiento`, `edad`, `peso`, `sexo`, `created_at`, `updated_at`) VALUES
(647, 'RN', 'MEJIA SANABRIA', 'UCIN', NULL, NULL, '0114283', NULL, '2024-12-27', '7 día(s)', 1.865, 'Femenino', '2025-01-04 08:41:53', '2025-01-04 08:41:53'),
(648, 'RN', 'PERALTA SANCHEZ', 'UCIN', NULL, NULL, '114298', NULL, '2024-12-29', '5 día(s)', 1.245, 'Masculino', '2025-01-04 08:43:14', '2025-01-04 08:43:14'),
(649, 'RN', 'SANDOVAL  RODAL', 'Neonatología', 'UCIN 2', '2', '1500673741', 'Desnutrición', '2024-12-22', '12 día(s)', 2.253, 'Femenino', '2025-01-04 08:52:47', '2025-01-04 08:52:47'),
(650, 'RN', 'GARCIA RODRIGUEZ', 'NEONATOLOGÍA', 'AISLADO', '2', '70338', 'SEPSIS POR STAPHYLOCOCCUS AUREUS', '2024-11-08', '1 mes(es) 25 día(s)', 1.830, 'Femenino', '2025-01-04 09:17:59', '2025-01-04 09:17:59'),
(651, 'RN', 'RAMIREZ RUIZ', 'UCIREN 1', NULL, '1', NULL, NULL, '2024-12-28', '6 día(s)', 1.420, 'Masculino', '2025-01-04 09:18:07', '2025-01-04 09:18:07'),
(652, 'RN', 'GONZALEZ MALVAEZ', 'UCIREN 1', NULL, '1', NULL, NULL, '2024-12-27', '7 día(s)', 1.350, 'Femenino', '2025-01-04 09:24:12', '2025-01-04 09:24:12'),
(653, 'RN', 'JUAREZ ORTIZ G1', 'INTERNOS', NULL, '1', NULL, NULL, '2024-12-27', '7 día(s)', 1.125, 'Masculino', '2025-01-04 09:27:46', '2025-01-04 09:27:46'),
(654, 'RN', 'AVILES LOPEZ G2', 'INTERNOS', NULL, '1', NULL, NULL, '2024-12-31', '3 día(s)', 1.860, 'Masculino', '2025-01-04 09:31:40', '2025-01-04 09:31:40'),
(655, 'RN', 'AVILES LOPEZ G2', 'REANIMACIÓN', NULL, 'PB', NULL, NULL, '2024-12-31', '3 día(s)', 1.105, 'Masculino', '2025-01-04 09:35:32', '2025-01-04 09:35:32'),
(656, 'RN', 'FLORES ASCENCIO', 'PEDIATRIA', NULL, NULL, '13592-1', NULL, '2024-12-27', '7 día(s)', 2.060, 'Femenino', '2025-01-04 09:37:22', '2025-01-04 09:37:22'),
(657, 'RN', 'MARTINEZ GUZMÁN', 'REANIMACIÓN', NULL, 'PB', NULL, NULL, '2024-12-30', '4 día(s)', 1.462, 'Masculino', '2025-01-04 09:43:50', '2025-01-04 09:43:50'),
(658, 'RN', 'ESTRADA BERNARDINO', 'REANIMACIÓN', NULL, 'PB', NULL, NULL, '2024-12-30', '4 día(s)', 1.945, 'Femenino', '2025-01-04 09:51:15', '2025-01-04 09:51:15'),
(659, 'RN', 'VALENCIA HERNANDEZ', 'INTERNOS', NULL, '1', NULL, NULL, '2024-11-16', '1 mes(es) 17 día(s)', 1.710, 'Masculino', '2025-01-04 09:58:01', '2025-01-04 09:58:01'),
(660, 'RN', 'ROJAS ROJAS G1', 'INTERNOS', '5', '1', NULL, NULL, '2024-12-26', '8 día(s)', 1.340, 'Masculino', '2025-01-04 10:02:20', '2025-01-04 10:02:20'),
(661, 'RN', 'ROJAS ROJAS G2', 'INTERNOS', NULL, '1', NULL, NULL, '2024-12-26', '8 día(s)', 1.180, 'Masculino', '2025-01-04 10:06:05', '2025-01-04 10:06:05'),
(662, 'RN', 'ORDAZ GONZALEZ', 'UCIN', NULL, NULL, '24-8598', NULL, '2024-12-19', '15 día(s)', 1.290, 'Femenino', '2025-01-04 10:11:59', '2025-01-04 10:11:59'),
(663, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACION', '307', '3', '1500660965', 'DESNUTRICION', '1947-07-31', '77 año(s) 5 mes(es) 3 día(s)', 68.000, 'Masculino', '2025-01-04 10:16:43', '2025-01-04 10:16:43'),
(664, 'RN', 'ORDAZ GONZALEZ', 'UCIN', NULL, NULL, '24-8598', NULL, '2024-12-19', '15 día(s)', 1.290, 'Femenino', '2025-01-04 10:18:29', '2025-01-04 10:18:29'),
(665, 'RN', 'GUERRERO MARTINEZ', 'PDRIATRIA UCIN 2', NULL, NULL, '205366', NULL, '2024-12-17', '17 día(s)', 2.800, 'Masculino', '2025-01-04 10:22:14', '2025-01-04 10:22:14'),
(666, 'RN', 'ORDAZ GONZALEZ', 'UCIN', NULL, NULL, '24-8598', NULL, '2024-12-19', '15 día(s)', 1.290, 'Femenino', '2025-01-04 10:23:18', '2025-01-04 10:23:18'),
(667, 'RN', 'LOPEZ AVELINO', 'PEDIATRIA UCIN 2', NULL, NULL, '205539', NULL, '2024-12-28', '6 día(s)', 1.655, 'Femenino', '2025-01-04 10:26:19', '2025-01-04 11:11:47'),
(668, 'TEODORO', 'MACIAS CERVANTES', 'HOSPITALIZACION', '301', '3', '1500673991', 'DESNUTRICION', '1936-09-11', '88 año(s) 3 mes(es) 22 día(s)', 60.000, 'Masculino', '2025-01-04 10:27:23', '2025-01-04 10:27:23'),
(669, 'RN', 'MEJIA VAZQUEZ', 'PEDRIATRIA UCIN 2', NULL, NULL, '205587', NULL, '2024-12-31', '3 día(s)', 3.320, 'Femenino', '2025-01-04 10:30:24', '2025-01-04 10:30:24'),
(670, 'RN', 'ANTONIO CAYETANO', 'INTERNOS', NULL, '1', NULL, NULL, '2024-10-07', '2 mes(es) 27 día(s)', 3.780, 'Femenino', '2025-01-04 10:32:01', '2025-01-04 10:32:01'),
(671, 'RN', 'GOMEZ CORONA', 'LACTANTES', NULL, NULL, '205541', NULL, '2024-12-09', '25 día(s)', 2.750, 'Femenino', '2025-01-04 10:35:05', '2025-01-04 11:32:06'),
(672, 'RN', 'AVILES MARTINEZ', 'UCIN', NULL, NULL, '464386', NULL, '2024-12-23', '11 día(s)', 0.975, 'Femenino', '2025-01-04 10:36:15', '2025-01-04 10:36:15'),
(673, 'RN', 'DOMINGUEZ SANCHEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-11-19', '1 mes(es) 14 día(s)', 1.835, 'Masculino', '2025-01-04 10:40:59', '2025-01-04 10:40:59'),
(674, 'RN', 'CARRIZOSA GARCIA', 'LACTANTES', NULL, NULL, '205308', NULL, '2024-12-10', '24 día(s)', 3.610, 'Femenino', '2025-01-04 10:41:56', '2025-01-04 11:35:29'),
(675, 'RN', 'Bermudez Mejia', 'UCIN', 'U3', '1', '428022', 'PO LAPE', '2024-12-09', '25 día(s)', 1.420, 'Masculino', '2025-01-04 10:43:26', '2025-01-04 10:43:26'),
(676, 'RN', 'OLMEDO RICARDO', 'UCIN', NULL, NULL, '246939', NULL, '2024-12-16', '18 día(s)', 3.185, 'Masculino', '2025-01-04 10:43:26', '2025-01-04 10:43:26'),
(677, 'RN', 'CURZ RODRIGUEZ', 'UCIN', NULL, NULL, '4464659', NULL, '2025-01-01', '2 día(s)', 1.850, 'Masculino', '2025-01-04 10:44:26', '2025-01-04 10:44:26'),
(678, 'RN', 'Gemela I Lopez Colin', 'UCIN', 'U6', '1', '428267', 'Disfunción cortical', '2024-12-14', '20 día(s)', 2.610, 'Femenino', '2025-01-04 10:48:17', '2025-01-04 10:48:17'),
(679, 'RN', 'PEÑA MORAN', 'UCIN', NULL, NULL, NULL, NULL, '2024-12-26', '8 día(s)', 1.275, 'Femenino', '2025-01-04 10:50:12', '2025-01-04 10:50:12'),
(680, 'RN', 'VILLAGOMEZ GONZALEZ', 'UCIN', NULL, NULL, '247364', NULL, '2025-01-02', '1 día(s)', 0.840, 'Masculino', '2025-01-04 10:51:59', '2025-01-04 10:51:59'),
(681, 'RN', 'Gabriel Calixto', 'UCIN', '102', '1', '426331', 'Prematurez', '2024-10-22', '2 mes(es) 12 día(s)', 2.215, 'Femenino', '2025-01-04 10:53:16', '2025-01-04 10:53:16'),
(682, 'RN', 'GONZALEZ CARMONA', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-31', '3 día(s)', 2.205, 'Femenino', '2025-01-04 11:09:51', '2025-01-04 11:09:51'),
(683, 'RN', 'VELASQUEZ ENRIQUEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-23', '11 día(s)', 2.225, 'Masculino', '2025-01-04 11:13:58', '2025-01-04 11:13:58'),
(684, 'RN', 'MARTINEZ GONZALEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-22', '12 día(s)', 1.635, 'Masculino', '2025-01-04 11:17:55', '2025-01-04 11:17:55'),
(685, 'RN', 'BENITEZ MARTINEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-26', '8 día(s)', 1.440, 'Masculino', '2025-01-04 11:21:47', '2025-01-04 11:21:47'),
(686, 'RN', 'SANTIAGO CONTRERAS', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-29', '5 día(s)', 1.760, 'Femenino', '2025-01-04 11:26:59', '2025-01-04 11:26:59'),
(687, 'RN', 'ARZATE LOPEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-31', '3 día(s)', 1.300, 'Masculino', '2025-01-04 11:30:19', '2025-01-04 11:30:19'),
(688, 'RN', 'GARCIA RODRIGUEZ', 'NEONATOLOGÍA', 'AISLADO', '2', '70338', 'SEPSIS POR STAPHYLOCOCCUS AUREUS', '2024-11-08', '1 mes(es) 25 día(s)', 1.830, 'Femenino', '2025-01-04 11:32:40', '2025-01-04 11:32:40'),
(689, 'RN', 'AVILES LOPEZ G2', 'INTERNOS', NULL, '1', NULL, NULL, '2024-12-31', '3 día(s)', 1.860, 'Masculino', '2025-01-04 11:34:27', '2025-01-04 11:34:27'),
(690, 'RN', 'AVILES LOPEZ G1', 'REANIMACIÓN', NULL, 'PB', NULL, NULL, '2024-12-31', '3 día(s)', 1.105, 'Masculino', '2025-01-04 11:37:35', '2025-01-04 11:37:35'),
(691, 'RN', 'JACOBO CARRILLO', 'UCIN', '5', '1', '70817', 'SEPSIS', '2024-12-18', '16 día(s)', 1320.000, 'Femenino', '2025-01-04 11:39:39', '2025-01-04 11:39:39'),
(692, 'RN', 'JACOBO CARRILLO', 'UCIN', '5', '1', '70817', 'SEPSIS', '0024-12-18', '2000 año(s) 16 día(s)', 1.320, 'Femenino', '2025-01-04 11:54:23', '2025-01-04 13:31:14'),
(693, 'RN', 'SAMANO VELAZQUEZ G1', 'UCIN', NULL, NULL, '209008', NULL, '2024-12-05', '29 día(s)', 2.545, 'Masculino', '2025-01-04 12:35:12', '2025-01-04 12:35:12'),
(694, 'RN', 'GARCIA SANTIAGO', 'PEDIATRIA', NULL, NULL, '939655-24', NULL, '2024-12-19', '15 día(s)', 1.630, 'Masculino', '2025-01-04 12:42:50', '2025-01-04 12:42:50'),
(695, 'RN', 'VILLAGOMEZ GONZALEZ', 'UCIN', NULL, NULL, '247364', NULL, '2025-01-02', '1 día(s)', 0.840, 'Masculino', '2025-01-04 12:57:02', '2025-01-04 12:57:02'),
(696, 'GRACIELA', 'FERNANDEZ LARA', 'TERAPIA INTENSIVA', 'UTI-9', '1', '1500672626', 'DESNUTRICIÓN', '1937-06-12', '87 año(s) 6 mes(es) 21 día(s)', 48.000, 'Femenino', '2025-01-04 13:19:53', '2025-01-04 13:19:53'),
(697, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICIÓN', '1953-10-12', '71 año(s) 2 mes(es) 22 día(s)', 60.000, 'Femenino', '2025-01-04 13:24:10', '2025-01-04 13:24:10'),
(698, 'MARIA FERNANDA', 'FIGUEROA GUTIERREZ', 'HOSPITALIZACIÓN', '433', '4', '1500676649', 'DESNUTRICIÓN', '1987-08-01', '37 año(s) 5 mes(es) 2 día(s)', 84.000, 'Femenino', '2025-01-04 13:49:04', '2025-01-04 13:49:04'),
(699, 'RN', 'ORDAZ GONZALEZ', 'UCIN', NULL, NULL, '24-8598', NULL, '2024-12-19', '15 día(s)', 1.290, 'Femenino', '2025-01-04 15:56:11', '2025-01-04 15:56:11'),
(700, 'RN', 'JACOBO CARRILLO', 'UCIN', '5', '1', '70817', 'SEPSIS', '2024-12-18', '16 día(s)', 1.320, 'Femenino', '2025-01-04 16:37:43', '2025-01-04 16:37:43'),
(701, 'RN', 'JACOBO CARRILLO', 'UCIN', '5', '1', '70817', 'SEPSIS', '2024-12-18', '16 día(s)', 1.320, NULL, '2025-01-04 16:54:05', '2025-01-04 16:54:05'),
(702, 'RN', 'ORDAZ GONZALEZ', 'UCIN', NULL, NULL, '24-8598', NULL, '2024-12-19', '15 día(s)', 1.290, 'Femenino', '2025-01-04 17:15:16', '2025-01-04 17:15:16'),
(703, 'GRACIELA', 'HURTADO GARCIA', 'Terapia Intensiva', 'UTI-3', '1', '1500677237', NULL, '1977-05-31', '47 año(s) 7 mes(es) 3 día(s)', 70.000, 'Femenino', '2025-01-04 18:03:51', '2025-01-04 18:03:51'),
(704, 'FELIPE DE JESUS', 'ESCALANTE CASTILLO', 'Terapia Intensiva', 'UCC-1', '1', '1500676225', 'INSUFICIENCIA RESPIRATORIA', '1944-02-05', '80 año(s) 10 mes(es) 27 día(s)', 70.000, 'Masculino', '2025-01-04 18:13:52', '2025-01-04 18:13:52'),
(705, 'FELIPE DE JESUS', 'ESCALANTE CASTILLO', 'Terapia Intensiva', 'UCC-1', '1', '1500676225', 'INSUFICIENCIA RESPIRATORIA', '1944-02-05', '80 año(s) 10 mes(es) 27 día(s)', 70.000, 'Masculino', '2025-01-04 21:18:21', '2025-01-04 21:18:21'),
(706, 'RECIEN NACIDO', 'GARCIA RODRIGUEZ', 'NEONATOLOGÍA', 'AISLADO', '2', '70338', 'SEPSIS POR STAPHYLOCOCCUS AUREUS', '2024-11-08', '1 mes(es) 26 día(s)', 2.535, 'Femenino', '2025-01-05 09:15:32', '2025-01-05 09:15:32'),
(707, 'RN', 'LARA HERNANDEZ', 'UCIN', '6', '1', '70863', 'PREMATUREZ', '2024-12-23', '12 día(s)', 0.960, 'Femenino', '2025-01-05 09:43:16', '2025-01-05 10:11:49'),
(708, 'RN', 'SANDOVAL RODAL', 'NEONATOLOGIA', 'UCIN 2', '2', '1500673741', 'DESNUTRICION', '2024-12-22', '13 día(s)', 2.324, 'Femenino', '2025-01-05 10:12:14', '2025-01-05 10:12:14'),
(709, 'RN', 'GARCIA SANTIAGO', 'PEDIATRIA', NULL, NULL, '93955-24', NULL, '2024-12-19', '16 día(s)', 1.630, 'Femenino', '2025-01-05 10:12:20', '2025-01-05 10:12:20'),
(710, 'RECIEN NACIDO', 'JACOBO CARRILLO', 'NEONATOLOGÍA', '5', '2', '70817', 'SEPSIS NEONATAL', '2024-12-18', '17 día(s)', 1.330, 'Femenino', '2025-01-05 10:18:37', '2025-01-05 10:18:37'),
(711, 'RN', 'LOPEZ AVELINO', 'UCIN 2', '1', NULL, '205539', NULL, '2024-12-28', '7 día(s)', 1.655, 'Femenino', '2025-01-05 10:20:10', '2025-01-05 10:20:10'),
(712, 'RN', 'ROJAS ROJAS G1', 'INTERNOS', NULL, '1', '118109', NULL, '2024-12-26', '9 día(s)', 1.390, 'Masculino', '2025-01-05 10:27:25', '2025-01-05 10:27:25'),
(713, 'RN', 'GUERRERO MARTINEZ', 'UCIN 2', NULL, NULL, '205366', NULL, '2024-12-17', '18 día(s)', 2.940, 'Masculino', '2025-01-05 10:30:40', '2025-01-05 10:30:40'),
(714, 'RN', 'ARZATE LOPEZ', 'EXTERNOS', '1', '1', '118185', NULL, '2024-12-30', '5 día(s)', 1.300, 'Masculino', '2025-01-05 10:33:20', '2025-01-05 10:33:20'),
(715, 'RN', 'ROJAS ROJAS G2', 'INTERNOS', NULL, '1', '118110', NULL, '2024-12-26', '9 día(s)', 1.210, 'Masculino', '2025-01-05 10:35:26', '2025-01-05 10:35:26'),
(716, 'RN', 'MEJIA VAZQUEZ', 'UCIN 2', '8', NULL, '205587', NULL, '2024-12-31', '4 día(s)', 3.320, 'Femenino', '2025-01-05 10:36:19', '2025-01-05 10:36:19'),
(717, 'RN', 'LOPEZ AVELINO', 'UCIN 2', '1', NULL, '205539', NULL, '2024-12-28', '7 día(s)', 1.655, 'Femenino', '2025-01-05 10:36:24', '2025-01-05 10:36:24'),
(718, 'RN', 'SANTIAGO CONTRERAS', 'EXTERNOS', '2', '1', '118152', NULL, '2024-12-24', '11 día(s)', 1.800, 'Femenino', '2025-01-05 10:37:45', '2025-01-05 10:37:45'),
(719, 'RN', 'VALENCIA HERNANDEZ', 'INTERNOS', NULL, '1', '117017', NULL, '2024-11-16', '1 mes(es) 18 día(s)', 1.720, 'Masculino', '2025-01-05 10:39:53', '2025-01-05 10:39:53'),
(720, 'RN', 'MARTINEZ GONZALEZ', 'EXTERNOS', '3', '1', '118025', NULL, '2024-12-22', '13 día(s)', 1.700, 'Masculino', '2025-01-05 10:42:37', '2025-01-05 10:42:37'),
(721, 'RN', 'JUAREZ ORTIZ G1', 'INTERNOS', NULL, '1', '118137', NULL, '2024-12-27', '8 día(s)', 1.140, 'Masculino', '2025-01-05 10:50:53', '2025-01-05 10:50:53'),
(722, 'RN', 'BENITEZ MARTINEZ', 'EXTERNOS', '7', '1', '118100', NULL, '2024-12-26', '9 día(s)', 1.440, 'Masculino', '2025-01-05 10:54:07', '2025-01-05 10:54:07'),
(723, 'RN', 'PATRICIO MORENO', 'UCIN', NULL, NULL, NULL, NULL, '2024-10-30', '2 mes(es) 5 día(s)', 1.210, 'Femenino', '2025-01-05 11:15:51', '2025-01-05 11:15:51'),
(724, 'RN', 'PATRICIO MORENO', 'UCIN', NULL, NULL, NULL, NULL, '2024-10-30', '2 mes(es) 5 día(s)', 1.210, 'Femenino', '2025-01-05 11:19:04', '2025-01-05 11:19:04'),
(725, 'RN', 'FLORES ASSENCIO', 'NEONATOLOGIA', NULL, NULL, NULL, NULL, '2024-12-17', '18 día(s)', 2.080, 'Femenino', '2025-01-05 11:26:48', '2025-01-05 11:26:48'),
(726, 'RN', 'SANTIAGO DE LOS SANTOS', 'UCIN', '3', NULL, '464270', NULL, '2024-12-18', '17 día(s)', 2.480, 'Masculino', '2025-01-05 11:33:14', '2025-01-05 11:33:14'),
(727, 'LUIS', 'MAURER Y ESPINOSA', 'TERAPIA INTERMEDIA', '307', '3', '1500660965', 'DESNUTRICION', '1947-07-31', '77 año(s) 5 mes(es) 4 día(s)', 68.000, 'Masculino', '2025-01-05 11:36:54', '2025-01-05 11:36:54'),
(728, 'RN', 'SANTIAGO DE LOS SANTOS', 'UCIN', '3', NULL, '464270', NULL, '2024-12-18', '17 día(s)', 2.480, 'Masculino', '2025-01-05 11:38:31', '2025-01-05 11:38:31'),
(729, 'RN', 'DOMINGUEZ SANCHEZ', 'EXTERNOS', '8', '1', '117094', NULL, '2024-11-19', '1 mes(es) 15 día(s)', 2.005, 'Masculino', '2025-01-05 11:40:47', '2025-01-05 11:40:47'),
(730, 'RN', 'AVILES MARTINEZ', 'UCIN', NULL, NULL, '464386', NULL, '2024-12-23', '12 día(s)', 1.050, 'Femenino', '2025-01-05 11:42:59', '2025-01-05 11:42:59'),
(731, 'RN', 'PEÑA MORA', 'UCIN', NULL, NULL, '464497', NULL, '2024-12-26', '9 día(s)', 1.275, 'Femenino', '2025-01-05 11:47:05', '2025-01-05 11:47:05'),
(732, 'RN', 'AVILES LOPEZ G2', 'INTERNOS', NULL, '1', '118187', NULL, '2024-12-31', '4 día(s)', 1.860, NULL, '2025-01-05 11:48:24', '2025-01-05 11:48:24'),
(733, 'RN', 'GONZÁLEZ CARMONA', 'EXTERNOS', '11', '1', '118178', NULL, '2024-12-31', '4 día(s)', 2.205, 'Femenino', '2025-01-05 11:48:27', '2025-01-05 11:48:27'),
(734, 'RN', 'ESPINOZA', 'UCIN', '6', NULL, '464667', NULL, '2025-01-01', '3 día(s)', 0.795, 'Masculino', '2025-01-05 11:53:13', '2025-01-05 11:53:13'),
(735, 'RN', 'VELAZQUEZ ENRIQUEZ', 'EXTERNOS', '12', '1', '118045', NULL, '2024-12-23', '12 día(s)', 2.200, 'Masculino', '2025-01-05 11:53:29', '2025-01-05 11:53:29'),
(736, 'RN', 'ANTONIO CAYETANO', 'INTERNOS', NULL, '1', '115848', NULL, '2024-10-07', '2 mes(es) 28 día(s)', 3.790, NULL, '2025-01-05 11:54:19', '2025-01-05 11:54:19'),
(737, 'MARIA FERNANDA', 'FIGUEROA GUTIERREZ', 'HOSPITALIZACION', '433', '4', '1500676649', 'PANCREATITIS AGUDA', '1987-08-01', '37 año(s) 5 mes(es) 3 día(s)', 84.000, 'Femenino', '2025-01-05 11:55:55', '2025-01-05 11:55:55'),
(738, 'RN', 'CRUZ RODRIGUEZ', 'UCIN', NULL, NULL, '464659', NULL, '2025-01-01', '3 día(s)', 1.850, 'Masculino', '2025-01-05 11:56:42', '2025-01-05 11:56:42'),
(739, 'RN', 'RAMIREZ RUIZ', 'UCIREN 1', '3', '1', '118149', NULL, '2024-12-28', '7 día(s)', 1.450, 'Masculino', '2025-01-05 11:58:50', '2025-01-05 11:58:50'),
(740, 'RN', 'AVILES LOPEZ G1', 'UCIREN 1', '5', '1', '118152', NULL, '2024-12-31', '4 día(s)', 1.100, 'Masculino', '2025-01-05 12:02:31', '2025-01-05 12:02:31'),
(741, 'RN', 'PERALTA SANCHEZ', 'UCIN', NULL, NULL, NULL, NULL, '2024-12-29', '6 día(s)', 1.250, 'Masculino', '2025-01-05 12:03:17', '2025-01-05 12:03:17'),
(742, 'RN', 'MEJIA SANABRIA', 'UCIN', NULL, NULL, NULL, NULL, '2024-12-26', '9 día(s)', 1.890, 'Femenino', '2025-01-05 12:08:02', '2025-01-05 12:08:02'),
(743, 'TEODORO', 'MACIAS CERVANTES', 'TERAPIA INTERMEDIA', '301', '3', '1500673991', 'DESNUTRICION', '1936-09-11', '88 año(s) 3 mes(es) 23 día(s)', 60.000, 'Masculino', '2025-01-05 12:13:12', '2025-01-05 12:13:12'),
(744, 'RN', 'OLMEDO RICARDO', 'UCIN', '2', NULL, '246939', NULL, '2024-12-16', '19 día(s)', 3.300, 'Masculino', '2025-01-05 12:13:29', '2025-01-05 13:09:37'),
(745, 'RN', 'VILLAGOMEZ GONZALEZ', 'UCIN', '3', NULL, '247364', NULL, '2025-01-02', '2 día(s)', 0.795, 'Masculino', '2025-01-05 12:16:38', '2025-01-05 12:16:38'),
(746, 'RN', 'MARTINEZ GUZMAN', 'EXTERNOS', NULL, '1', 'S/N', NULL, '2024-12-30', '5 día(s)', 1.495, 'Masculino', '2025-01-05 12:18:32', '2025-01-05 12:18:32'),
(747, 'GRACIELA', 'HURTADO GARCIA', 'TERAPIA INTENSIVA', 'UTI-3', '1', '1500677237', 'LAPAROTOMIA EXPLORADORA', '1977-05-31', '47 año(s) 7 mes(es) 4 día(s)', 71.000, 'Femenino', '2025-01-05 12:31:05', '2025-01-05 12:31:05'),
(748, 'RN', 'SAMARE VELAZQUEZ', 'UCIN', NULL, NULL, '209008', NULL, '2024-12-05', '30 día(s)', 2.525, 'Masculino', '2025-01-05 12:35:56', '2025-01-05 13:02:14'),
(749, 'RN', 'BERMUDEZ MEJIA', 'UCIN', NULL, NULL, '428022', NULL, '2024-12-09', '26 día(s)', 1.420, NULL, '2025-01-05 13:07:10', '2025-01-05 13:07:10'),
(750, 'RN', 'GABRIEL CALIXTO', 'UCIN', '102', NULL, '426531', NULL, '2024-10-22', '2 mes(es) 13 día(s)', 2.215, 'Femenino', '2025-01-05 13:10:39', '2025-01-05 13:10:39'),
(751, 'RN', 'LOPEZ COLIN', 'UCIN', '6', NULL, '426790', NULL, '2024-12-14', '21 día(s)', 2.610, 'Femenino', '2025-01-05 13:13:08', '2025-01-05 13:13:08'),
(752, 'RN', 'RUIZ SANCHEZ', 'UCIN', NULL, NULL, '138304', NULL, '2024-12-13', '22 día(s)', 0.730, 'Masculino', '2025-01-05 13:17:21', '2025-01-05 13:17:21'),
(753, 'GRACIELA', 'FERNANDEZ LARA', 'TERAPIA INTENSIVA', 'UTI-9', '1', '1500672626', 'NEUMONIA', '1937-06-12', '87 año(s) 6 mes(es) 22 día(s)', 50.000, 'Femenino', '2025-01-05 13:40:38', '2025-01-05 13:40:38'),
(754, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'PANCREATITIS', '1953-10-12', '71 año(s) 2 mes(es) 23 día(s)', 60.000, 'Femenino', '2025-01-05 13:48:32', '2025-01-05 13:48:32'),
(755, 'RN', 'PATRICIO MORENO', 'UCIN', NULL, NULL, NULL, NULL, '2024-10-30', '2 mes(es) 5 día(s)', 1.210, 'Femenino', '2025-01-05 14:03:59', '2025-01-05 14:03:59'),
(756, 'RN', 'PATRICIO MORENO', 'UCIN', NULL, NULL, NULL, NULL, '2024-10-30', '2 mes(es) 5 día(s)', 1.210, 'Femenino', '2025-01-05 14:07:38', '2025-01-05 14:07:38'),
(757, 'FELIPE DE JESUS', 'ESCALANTE CASTILLO', 'TERAPIA INTENSIVA', 'UCC-1', '1', '1500676225', 'NEUMONIA', '1944-02-05', '80 año(s) 10 mes(es) 28 día(s)', 70.000, 'Masculino', '2025-01-05 14:57:14', '2025-01-05 14:57:14'),
(758, 'RN', 'PERALTA SANCHEZ', 'UCIN', NULL, NULL, '114298', NULL, '2024-12-29', '6 día(s)', 1.245, 'Masculino', '2025-01-05 15:23:22', '2025-01-05 15:23:22'),
(759, 'RECIEN NACIDO', 'JACOBO CARRILLO', 'NEONATOLOGÍA', '5', '2', '70817', 'SEPSIS NEONATAL', '2024-12-18', '18 día(s)', 1.410, 'Femenino', '2025-01-06 09:35:51', '2025-01-06 09:35:51'),
(760, 'RECIEN NACIDO', 'JACOBO CARRILLO', 'NEONATOLOGÍA', '5', '2', '70817', 'SEPSIS NEONATAL', '2024-12-18', '18 día(s)', 1.410, 'Femenino', '2025-01-06 09:35:51', '2025-01-06 09:35:51'),
(761, 'RECIEN NACIDO', 'GARCIA RODRIGUEZ', 'NEONATOLOGÍA', 'AISLADO', '2', '70338', 'SEPSIS POR STAPHYLOCOCCUS AUREUS', '2024-11-08', '1 mes(es) 27 día(s)', 1.990, 'Femenino', '2025-01-06 10:00:26', '2025-01-06 10:00:26'),
(762, 'RN', 'SANDOVAL RODAL', 'NEONATOLOGIA', 'UCIN 2', '2', '1500673741', 'DESNUTRICION', '2024-12-22', '14 día(s)', 2.316, 'Femenino', '2025-01-06 10:03:57', '2025-01-06 10:03:57'),
(763, 'RN', 'GARCIA SANTIAGO', 'PEDIATRIA', NULL, NULL, '93955-24', NULL, '2024-12-19', '17 día(s)', 1.630, 'Femenino', '2025-01-06 10:07:40', '2025-01-06 10:07:40'),
(764, 'RN', 'ARZATE LOPEZ', 'EXTERNOS', '1', '1', '118185', NULL, '2024-12-31', '5 día(s)', 1.350, 'Masculino', '2025-01-06 10:10:51', '2025-01-06 10:10:51'),
(765, 'RN', 'LARA HERNANDEZ', 'UCIN', '6', '1', '70863', 'PREMATUREZ', '2024-12-23', '13 día(s)', 0.940, 'Femenino', '2025-01-06 10:11:26', '2025-01-06 10:11:26'),
(766, 'RN', 'ESPINOZA GARCIA', 'UCIN', '6', NULL, '464667', NULL, '2025-01-01', '4 día(s)', 0.795, 'Masculino', '2025-01-06 10:13:27', '2025-01-06 10:13:27'),
(767, 'RN', 'SANTIAGO CONTRERAS', 'EXTERNOS', '2', '1', '118152', NULL, '2024-12-29', '7 día(s)', 1.760, 'Femenino', '2025-01-06 10:16:51', '2025-01-06 10:16:51'),
(768, 'RN', 'PEÑA MORA', 'UCIN', '1', NULL, '464497', NULL, '2024-12-26', '10 día(s)', 1.275, 'Femenino', '2025-01-06 10:17:19', '2025-01-06 10:17:19'),
(769, 'RN', 'VILCHIS ALARCON', 'EXTERNOS', '6', '1', '118228', NULL, '2025-01-04', '1 día(s)', 0.950, 'Femenino', '2025-01-06 10:20:51', '2025-01-06 10:20:51'),
(770, 'RN', 'RODRIGUEZ', 'UCIN', '8', NULL, '464659', NULL, '2025-01-01', '4 día(s)', 1.840, 'Masculino', '2025-01-06 10:21:46', '2025-01-06 10:21:46'),
(771, 'RN', 'BENITEZ MARTINEZ', 'EXTERNOS', '7', '1', '118100', NULL, '2024-12-26', '10 día(s)', 1.450, 'Masculino', '2025-01-06 10:23:43', '2025-01-06 10:23:43'),
(772, 'RN', 'GOMEZ CORONA', 'LACTANTE', NULL, NULL, '205541', NULL, '2024-12-09', '27 día(s)', 2.750, 'Femenino', '2025-01-06 10:24:26', '2025-01-06 10:24:26'),
(773, 'RN', 'SANTIAGO DE LOS SANTOS', 'UCIN', '3', NULL, '464270', NULL, '2024-12-18', '18 día(s)', 2.480, NULL, '2025-01-06 10:25:32', '2025-01-06 10:56:54'),
(774, 'RN', 'DOMINGUEZ SANCHEZ', 'EXTERNOS', '8', '1', '117094', NULL, '2024-12-19', '17 día(s)', 1.835, 'Masculino', '2025-01-06 10:27:17', '2025-01-06 10:27:17'),
(775, 'RN', 'AVILES MARTINEZ', 'UCIN', '5', NULL, '4643386', NULL, '2024-12-23', '13 día(s)', 1.050, 'Femenino', '2025-01-06 10:29:11', '2025-01-06 10:29:11'),
(776, 'RN', 'GONZALEZ CARMONA', 'EXTERNOS', '11', '1', '118178', NULL, '2024-12-31', '5 día(s)', 2.210, 'Femenino', '2025-01-06 10:31:20', '2025-01-06 11:02:21'),
(777, 'RN', 'GUERRERO MARTINEZ', 'UCIN 2', '5', NULL, '205366', NULL, '2024-12-17', '19 día(s)', 3.060, 'Masculino', '2025-01-06 10:31:50', '2025-01-06 10:31:50'),
(778, 'RN', 'OLMEDO RICARDO', 'UCIN', '2', NULL, '246939', NULL, '2024-12-16', '20 día(s)', 3.355, NULL, '2025-01-06 10:34:45', '2025-01-06 10:34:45'),
(779, 'RN', 'MARTINEZ GUZMAN', 'EXTERNOS', '12', '1', '118171', NULL, '2024-12-30', '6 día(s)', 1.430, 'Masculino', '2025-01-06 10:36:31', '2025-01-06 11:11:15'),
(780, 'RN', 'LOPEZ AVELINO', 'UCIN 2', '1', NULL, '205539', NULL, '2024-12-28', '8 día(s)', 1.700, 'Femenino', '2025-01-06 10:36:56', '2025-01-06 10:36:56'),
(781, 'RN', 'VILLAGOMEZ GONZALEZ', 'UCIN', '3', NULL, '247364', NULL, '2024-12-02', '1 mes(es) 3 día(s)', 0.795, 'Masculino', '2025-01-06 10:38:31', '2025-01-06 10:38:31'),
(782, 'RN', 'VELAZQUEZ ENRIQUEZ', 'EXTERNOS', '14', '1', '118045', NULL, '2024-12-23', '13 día(s)', 2.190, 'Masculino', '2025-01-06 10:40:16', '2025-01-06 10:40:16'),
(783, 'RN', 'MEJIA VAZQUEZ', 'UCIN2', '8', NULL, '205587', NULL, '2024-12-31', '5 día(s)', 3.400, 'Femenino', '2025-01-06 10:40:23', '2025-01-06 10:40:23'),
(784, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICION', '1953-10-12', '71 año(s) 2 mes(es) 24 día(s)', 60.000, 'Femenino', '2025-01-06 10:42:20', '2025-01-06 10:42:20'),
(785, 'RN', 'AVILES LOPEZ G1', 'EXTERNOS', '3', '1', '118152', NULL, '2024-12-31', '5 día(s)', 1.100, 'Masculino', '2025-01-06 10:44:16', '2025-01-06 10:44:16'),
(786, 'RN', 'HERNANDEZ VILLALOBOS', 'UTIN', '5', NULL, '247280', NULL, '2024-12-31', '5 día(s)', 1.580, 'Masculino', '2025-01-06 10:44:47', '2025-01-06 10:44:47'),
(787, 'RN', 'LUCIANO HERNÁNDEZ G2', 'UCIREN 1', '9', '1', '112847', NULL, '2024-12-16', '20 día(s)', 1.420, 'Masculino', '2025-01-06 10:49:32', '2025-01-06 10:49:32'),
(788, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICION', '1953-10-12', '71 año(s) 2 mes(es) 24 día(s)', 60.000, 'Femenino', '2025-01-06 10:53:23', '2025-01-06 10:53:23'),
(789, 'RN', 'BERMUDEZ MEJIA', 'UCIN', '3', NULL, '428055', NULL, '2024-12-09', '27 día(s)', 1.580, 'Masculino', '2025-01-06 10:53:36', '2025-01-06 10:53:36'),
(790, 'RN', 'GONZALEZ MALVAEZ', 'UCIREN 1', '10', '1', '118130', NULL, '2024-12-22', '14 día(s)', 1.350, 'Masculino', '2025-01-06 10:54:24', '2025-01-06 10:54:24'),
(791, 'RN', 'LOPEZ COLIN', '428267', '6', NULL, NULL, NULL, '2024-12-14', '22 día(s)', 2.660, 'Femenino', '2025-01-06 10:55:13', '2025-01-06 12:07:02'),
(792, 'GRACIELA', 'FERNANDEZ LARA', 'TERAPIA INTENSIVA', 'UTI-9', '1', '1500672626', 'DESNUTRICION', '1937-06-12', '87 año(s) 6 mes(es) 23 día(s)', 50.000, 'Femenino', '2025-01-06 11:05:23', '2025-01-06 11:05:23'),
(793, 'RN', 'GABRIEL CALIXTO', 'UCIN', '102', NULL, '426531', NULL, '2024-10-23', '2 mes(es) 13 día(s)', 2.235, 'Femenino', '2025-01-06 11:17:49', '2025-01-06 11:17:49'),
(794, 'RN', 'ANTONIO CAYETANO', 'INTERNOS', NULL, '1', '115848', NULL, '2024-10-07', '2 mes(es) 29 día(s)', 3.780, 'Femenino', '2025-01-06 11:56:44', '2025-01-06 11:56:44'),
(795, 'RN', 'JUAREZ ORTIZ G1', 'INTERNOS', NULL, '1', '118137', NULL, '2024-12-27', '9 día(s)', 1.130, 'Masculino', '2025-01-06 12:01:39', '2025-01-06 12:01:39'),
(796, 'RN', 'VALENCIA HERNANDEZ', 'INTERNOS', NULL, '1', '117017', NULL, '2024-11-16', '1 mes(es) 19 día(s)', 1.750, 'Masculino', '2025-01-06 12:05:46', '2025-01-06 12:05:46'),
(797, 'RN', 'ROJAS ROJAS G2', 'INTERNOS', NULL, '1', '118109', NULL, '2024-12-26', '10 día(s)', 1.310, 'Masculino', '2025-01-06 12:09:51', '2025-01-06 12:09:51'),
(798, 'RN', 'AVILES LOPEZ G2', 'INTERNOS', NULL, '1', '118187', NULL, '2024-12-31', '5 día(s)', 1.870, 'Masculino', '2025-01-06 12:13:00', '2025-01-06 12:13:00'),
(799, 'LUIS', 'MAURER Y ESPINOSA', 'TERAPIA INTERMEDIA', '307', '3', '1500660965', 'DESNUTRICION', '1947-07-31', '77 año(s) 5 mes(es) 5 día(s)', 68.000, 'Masculino', '2025-01-06 12:32:08', '2025-01-06 12:32:08'),
(800, 'RN', 'RUIZ SANCHEZ', 'UCIN', NULL, NULL, '138304', NULL, '2024-12-13', '23 día(s)', 0.780, 'Masculino', '2025-01-06 12:37:48', '2025-01-06 12:37:48'),
(801, 'RN', 'MEJIA SANABRIA', 'UCIN', NULL, NULL, NULL, NULL, '2024-12-27', '9 día(s)', 1.890, 'Femenino', '2025-01-06 13:51:23', '2025-01-06 13:51:23'),
(802, 'TEODORO', 'MACIAS CERVANTES', 'TERAPIA INTERMEDIA', '301', '3', '1500673991', 'DESNUTRICION', '1936-09-11', '88 año(s) 3 mes(es) 24 día(s)', 60.000, 'Masculino', '2025-01-06 13:51:27', '2025-01-06 13:51:27'),
(803, 'RN', 'PERALTA SANCHEZ', 'UCIN', NULL, NULL, NULL, NULL, '2024-12-29', '7 día(s)', 1.250, 'Masculino', '2025-01-06 13:54:12', '2025-01-06 13:54:12'),
(804, 'GRACIELA', 'HURTADO GARCIA', 'HOSPITALIZACION', '227', '2', '1500677237', 'LAPAROTOMIA EXPLORADORA', '1977-05-31', '47 año(s) 7 mes(es) 5 día(s)', 70.000, 'Femenino', '2025-01-06 14:11:54', '2025-01-06 14:11:54'),
(805, 'FELIPE DE JESUS', 'ESCALANTE CASTILLO', 'TERAPIA INTENSIVA', 'UCC-1', '1', '1500676225', 'NEUMONIA', '1944-02-05', '80 año(s) 11 mes(es) ', 70.000, 'Masculino', '2025-01-06 14:25:25', '2025-01-06 14:25:25'),
(806, 'MARIA FERNANDA', 'FIGUEROA GUTIERREZ', 'HOSPITALIZACION', '433', '4', '1500676649', 'PANCREATITIS AGUDA', '1987-08-01', '37 año(s) 5 mes(es) 4 día(s)', 84.000, 'Femenino', '2025-01-06 14:41:55', '2025-01-06 14:41:55'),
(807, 'RN', 'FLORES ASCENCIO', 'UCIN', NULL, NULL, NULL, NULL, '2024-12-17', '19 día(s)', 2.185, 'Femenino', '2025-01-06 16:24:31', '2025-01-06 16:24:31'),
(808, 'RECIEN NACIDO', 'GARCIA RODRIGUEZ', 'NEONATOLOGÍA', 'AISLADO', '2', '70338', 'SEPSIS POR STAPHYLOCOCCUS AUREUS', '2024-11-08', '1 mes(es) 28 día(s)', 1.970, 'Femenino', '2025-01-07 09:22:58', '2025-01-07 09:22:58'),
(809, 'RN', 'PERALTA SANCHEZ', 'UCIN', NULL, NULL, '0114298', NULL, '2024-12-29', '8 día(s)', 1.290, 'Masculino', '2025-01-07 09:35:34', '2025-01-07 09:35:34'),
(810, 'RM', 'JACOBO CARRILLO', 'UCIN', '5', '1', '70817', 'SEPSIS', '2024-12-18', '19 día(s)', 1.310, 'Femenino', '2025-01-07 09:36:37', '2025-01-07 09:56:47'),
(811, 'RN', 'GONZALEZ MALVAEZ', 'UCIREN 1', NULL, '1', NULL, NULL, '2024-12-22', '15 día(s)', 1.370, 'Femenino', '2025-01-07 09:36:38', '2025-01-07 09:36:38'),
(812, 'RN', 'MEJIA SANABRIA', 'UCIN', NULL, NULL, '0114283', NULL, '2024-12-29', '8 día(s)', 1.890, NULL, '2025-01-07 09:40:37', '2025-01-07 09:40:37'),
(813, 'RN', 'LARA HERNANDEZ', 'UCIN', '6', '1', '70863', 'SEPSIS', '2024-12-23', '14 día(s)', 0.940, 'Femenino', '2025-01-07 09:40:40', '2025-01-07 10:01:59'),
(814, 'RN', 'LUCIANO HERNANDEZ G2', 'UCIREN 1', NULL, '1', NULL, NULL, '2024-12-16', '21 día(s)', 1.430, 'Masculino', '2025-01-07 09:41:49', '2025-01-07 09:41:49'),
(815, 'RN', 'ANTONIO CAYETANO', 'INTERNOS', NULL, '1', NULL, NULL, '2024-10-07', '2 mes(es) 30 día(s)', 3.780, 'Femenino', '2025-01-07 09:46:08', '2025-01-07 09:46:08'),
(816, 'RN', 'VALENCIA HERNANDEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-11-16', '1 mes(es) 20 día(s)', 1.730, 'Masculino', '2025-01-07 09:50:13', '2025-01-07 09:50:13'),
(817, 'RN', 'HERNANDEZ FLORES', 'UCIN', '2', NULL, '247078', NULL, '2024-12-21', '16 día(s)', 1.100, NULL, '2025-01-07 09:58:04', '2025-01-07 09:58:04'),
(818, 'RN', 'JUAREZ ORTIZ G1', 'INTERNOS', NULL, '1', NULL, NULL, '2024-12-27', '10 día(s)', 1.130, 'Masculino', '2025-01-07 10:00:16', '2025-01-07 10:00:16'),
(819, 'RN', 'VILLAGOMEZ GONZALEZ', 'UCIN', '3', NULL, '247364', NULL, '2025-01-02', '4 día(s)', 0.790, 'Masculino', '2025-01-07 10:01:57', '2025-01-07 10:01:57'),
(820, 'ANGELA', 'ROBLEDO HERNANDEZ', 'PEDIATRIA', '84', NULL, '247416', NULL, '2024-11-18', '1 mes(es) 18 día(s)', 3.400, 'Femenino', '2025-01-07 10:05:21', '2025-01-07 10:05:21'),
(821, 'RN', 'AVILES LOPEZ G2', 'INTERNOS', NULL, '1', NULL, NULL, '2024-12-31', '6 día(s)', 1.870, 'Masculino', '2025-01-07 10:06:32', '2025-01-07 10:06:32'),
(822, 'RN', 'VILCHIS ALARCON', 'EXTERNOS', NULL, '1', NULL, NULL, '2025-01-04', '2 día(s)', 0.800, 'Femenino', '2025-01-07 10:11:08', '2025-01-07 10:11:08'),
(823, 'RN', 'MARTINEZ GUZMAN', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-30', '7 día(s)', 1.470, 'Masculino', '2025-01-07 10:15:18', '2025-01-07 10:15:18'),
(824, 'RN', 'BENITEZ MARTINEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-26', '11 día(s)', 1.502, 'Masculino', '2025-01-07 10:20:23', '2025-01-07 10:20:23'),
(825, 'RN', 'DOMINGUEZ SANCHEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-11-19', '1 mes(es) 17 día(s)', 1.835, 'Masculino', '2025-01-07 10:25:34', '2025-01-07 10:25:34'),
(826, 'RN', 'SANDOVAL RODAL', 'NEONATOLOGÍA', 'UCIN-2', '2', '1500673741', 'DESNUTRICIÓN', '2024-12-22', '15 día(s)', 2.394, 'Femenino', '2025-01-07 10:28:39', '2025-01-07 10:28:39'),
(827, 'RN', 'GONZALEZ CARMONA', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-31', '6 día(s)', 2.130, 'Femenino', '2025-01-07 10:29:51', '2025-01-07 10:29:51'),
(828, 'RN', 'OLMEDO RICARDO', 'UTIN', '2', NULL, '246939', NULL, '2024-12-16', '21 día(s)', 3.415, 'Masculino', '2025-01-07 10:32:29', '2025-01-07 10:32:29'),
(829, 'RN', 'VELAZQUEZ ENRIQUEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-23', '14 día(s)', 2.260, 'Masculino', '2025-01-07 10:34:35', '2025-01-07 10:34:35'),
(830, 'RN', 'SANTIAGO CONTRERAS', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-29', '8 día(s)', 1.785, 'Femenino', '2025-01-07 10:38:51', '2025-01-07 10:38:51'),
(831, 'RN', 'ARZATE LOPEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-31', '6 día(s)', 1.420, 'Masculino', '2025-01-07 10:42:09', '2025-01-07 10:42:09'),
(832, 'RN', 'AVILES LOPEZ G1', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-31', '6 día(s)', 1.320, 'Masculino', '2025-01-07 10:46:08', '2025-01-07 10:46:08'),
(833, 'RN', 'BECERRIL MENDOZA', 'REANIMACIÓN', NULL, 'PB', NULL, NULL, '2025-01-05', '1 día(s)', 2.360, 'Femenino', '2025-01-07 10:49:38', '2025-01-07 10:49:38'),
(834, 'RN', 'GONZALEZ ECHEVERRIA', 'QUIROFANO 2', NULL, 'PB', NULL, NULL, '2025-01-01', '5 día(s)', 2.050, 'Masculino', '2025-01-07 10:53:24', '2025-01-07 10:53:24'),
(835, 'RN', 'GOMEZ CORONA', 'LACTANTES', NULL, NULL, '205541', NULL, '2024-12-09', '28 día(s)', 2.800, 'Femenino', '2025-01-07 10:58:32', '2025-01-07 10:58:32'),
(836, 'RN', 'CRUZ RODRIGUEZ', 'UCIN', '8', NULL, '464659', NULL, '2025-01-01', '5 día(s)', 1.850, 'Masculino', '2025-01-07 10:59:54', '2025-01-07 10:59:54'),
(837, 'RN', 'SANTIAGO DE LOS SANTOS', 'UCIN', '3', NULL, '464270', NULL, '2024-12-18', '19 día(s)', 2.600, NULL, '2025-01-07 11:03:08', '2025-01-07 11:03:08'),
(838, 'RN', 'AVILES MARTINEZ', 'UCIN', '5', NULL, '464386', NULL, '2024-12-23', '14 día(s)', 1.120, 'Femenino', '2025-01-07 11:06:15', '2025-01-07 11:06:15'),
(839, 'RN', 'CARRIZOSA GARCIA', 'LACTANTES', NULL, NULL, '205308', NULL, '2024-12-10', '27 día(s)', 3.600, 'Femenino', '2025-01-07 11:07:55', '2025-01-07 11:07:55'),
(840, 'RN', 'ESPINOZA GARCIA', 'UCIN', '6', NULL, '464667', NULL, '2025-01-01', '5 día(s)', 0.735, 'Masculino', '2025-01-07 11:09:09', '2025-01-07 11:09:09'),
(841, 'RN', 'PEÑA MORA', 'UCIN', '1', NULL, NULL, NULL, '2024-12-26', '11 día(s)', 1.615, 'Femenino', '2025-01-07 11:12:22', '2025-01-07 11:12:22'),
(842, 'RN', 'GARCIA RODRIGUEZ', 'NEONATOLOIA', 'AISLADO', '2', '70338', 'SEPSIS POR STAPHYLOCOCCUS AUREUS', '2024-11-09', '1 mes(es) 27 día(s)', 1.970, 'Femenino', '2025-01-07 11:13:27', '2025-01-07 11:13:27'),
(843, 'RN', 'Gabriel Calixto', 'UCIN', '102', '1', '426331', 'Prematurez', '2024-10-20', '2 mes(es) 17 día(s)', 2.265, 'Femenino', '2025-01-07 11:49:18', '2025-01-07 11:49:18'),
(844, 'RN', 'Lopez Cdin', 'UCIN', 'u6', '1', '428267', 'TCE', '2024-12-14', '23 día(s)', 2.700, 'Femenino', '2025-01-07 11:53:50', '2025-01-07 11:53:50'),
(845, 'RN', 'Bermudez Mejia', 'UCIN', 'U3', '1', '428055', 'PO LAPE', '2024-12-09', '28 día(s)', 1.580, 'Masculino', '2025-01-07 11:59:32', '2025-01-07 11:59:32'),
(846, 'RN', 'MORENO PATRICIO', 'UCIN', NULL, NULL, NULL, NULL, '2024-10-30', '2 mes(es) 7 día(s)', 1.250, NULL, '2025-01-07 12:26:01', '2025-01-07 12:26:01'),
(847, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACIÓN', '307', '3', '1500660965', 'DESNUTRICIÓN', '1947-07-31', '77 año(s) 5 mes(es) 6 día(s)', 68.000, 'Masculino', '2025-01-07 12:31:16', '2025-01-07 12:31:16'),
(848, 'RN', 'GARCIA SANTIAGO', 'PEDIATRIA', NULL, NULL, NULL, NULL, '2024-12-19', '18 día(s)', 1.640, 'Masculino', '2025-01-07 12:42:39', '2025-01-07 12:42:39'),
(849, 'RN', 'MATIAS HERNANDEZ', 'UCIN', NULL, NULL, '25-52', NULL, '2025-01-03', '3 día(s)', 1.145, 'Femenino', '2025-01-07 12:58:43', '2025-01-07 12:58:43'),
(850, 'RN', 'ORDAZ GONZALEZ', 'UCIN', NULL, NULL, '24-8598', NULL, '2024-12-19', '18 día(s)', 1.440, 'Femenino', '2025-01-07 13:02:53', '2025-01-07 13:02:53'),
(851, 'TEODORO', 'MACIAS CERVANTES', 'HOSPITALIZACIÓN', '301', '3', '1500673991', 'DESNUTRICIÓN', '1936-09-11', '88 año(s) 3 mes(es) 25 día(s)', 60.000, 'Masculino', '2025-01-07 13:29:54', '2025-01-07 13:29:54'),
(852, 'GRACIELA', 'FERNANDEZ LARA', 'TERAPIA INTENSIVA', 'UTI 9', '1ER', '1500672626', 'NEUMONÍA', '1937-06-12', '87 año(s) 6 mes(es) 24 día(s)', 50.000, 'Femenino', '2025-01-07 13:46:05', '2025-01-07 13:46:05'),
(853, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1ERO', '1500599769', 'DESNUTRICIÓN', '1953-10-12', '71 año(s) 2 mes(es) 25 día(s)', 60.000, 'Femenino', '2025-01-07 14:52:16', '2025-01-07 14:52:16'),
(854, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1ERO', '1500599769', 'DESNUTRICIÓN', '1953-10-12', '71 año(s) 2 mes(es) 25 día(s)', 60.000, 'Femenino', '2025-01-07 14:52:16', '2025-01-07 14:52:16'),
(855, 'FELIPE DE JESUS', 'ESCALANTE CASTILLO', 'TERAPIA INTENSIVA', 'UCC 1', 'PISO 1', '1500676225', 'INSUFICIENCIA RESPIRATORIA', '1944-02-05', '80 año(s) 11 mes(es) 1 día(s)', 70.000, 'Masculino', '2025-01-07 15:11:22', '2025-01-07 15:11:22'),
(856, 'GRACIELA', 'HURTADO GARCIA', 'HOSPITALIZACION', '227', '2', '1500677237', 'DESNUTRICION', '1977-05-31', '47 año(s) 7 mes(es) 6 día(s)', 70.000, 'Femenino', '2025-01-07 15:30:59', '2025-01-07 15:30:59'),
(857, 'FELIPE DE JESUS', 'ESCALANTE CASTILLO', 'TERAPIA INTENSIVA', 'UCC1', '1', '1500676225', 'INSUFICIENCIA RESPIRATORIA', '1944-02-05', '80 año(s) 11 mes(es) 1 día(s)', 70.000, NULL, '2025-01-07 18:25:35', '2025-01-07 18:25:35'),
(858, 'RN', 'GONZALEZ ECHEVERRIA', 'QUIROFANO 2', NULL, 'PB', NULL, NULL, '2025-01-01', '6 día(s)', 2.062, 'Masculino', '2025-01-08 09:03:36', '2025-01-08 09:03:36'),
(859, 'RN', 'LUCIANO HERNANDEZ G2', 'UCIREN 1', NULL, '1', NULL, NULL, '2024-12-22', '16 día(s)', 1.500, 'Masculino', '2025-01-08 09:08:48', '2025-01-08 09:08:48'),
(860, 'RECIEN NACIDO', 'GARCIA RODRIGUEZ', 'NEONATOLOGÍA', 'AISLADO', '2', '70338', 'SEPSIS POR STAPHYLOCOCCUS AUREUS', '2024-11-08', '1 mes(es) 29 día(s)', 1.970, 'Femenino', '2025-01-08 09:09:45', '2025-01-08 09:09:45'),
(861, 'RN', 'BECERRIL MENDOZA', 'INTERNOS', NULL, '1', NULL, NULL, '2025-01-05', '2 día(s)', 2.360, 'Masculino', '2025-01-08 09:12:50', '2025-01-08 09:12:50'),
(862, 'RN', 'JACOBO CARRILLO', 'UCIN', '5', '1', '70817', 'SEPSIS', '2024-12-18', '20 día(s)', 1.310, 'Femenino', '2025-01-08 09:12:55', '2025-01-08 09:23:37'),
(863, 'RN', 'LARA HERNANDEZ', 'UCIN', '6', '1', '70863', 'SEPSIS', '2024-12-23', '15 día(s)', 0.940, 'Femenino', '2025-01-08 09:15:53', '2025-01-08 09:25:36'),
(864, 'RN', 'AVILES LOPEZ G2', 'INTERNOS', NULL, '1', NULL, NULL, '0004-12-31', '2020 año(s) 7 día(s)', 1.890, 'Masculino', '2025-01-08 09:17:14', '2025-01-08 09:17:14'),
(865, 'RN', 'MAYO MORENO', 'INTERNOS', NULL, '1', NULL, NULL, '2025-01-06', '1 día(s)', 1.220, 'Masculino', '2025-01-08 09:20:38', '2025-01-08 09:20:38'),
(866, 'RN', 'GONZALEZ MALVAEZ', 'UCIREN 1', NULL, '1', NULL, NULL, '2024-12-27', '11 día(s)', 1.410, 'Femenino', '2025-01-08 09:25:15', '2025-01-08 09:25:15'),
(867, 'RN', 'JUAREZ ORTIZ G1', 'INTERNOS', NULL, '1', NULL, NULL, '2024-12-27', '11 día(s)', 1.250, 'Femenino', '2025-01-08 09:30:20', '2025-01-08 09:30:20'),
(868, 'RN', 'MEJIA SANABRIA', 'UCIN', NULL, NULL, '0114283', NULL, '2024-12-27', '11 día(s)', 1.965, NULL, '2025-01-08 09:31:11', '2025-01-08 09:31:11'),
(869, 'RN', 'VASQUEZ SANCHEZ', 'INTERNOS', NULL, '1', NULL, NULL, '2025-01-04', '3 día(s)', 1.432, 'Masculino', '2025-01-08 09:36:53', '2025-01-08 09:36:53'),
(870, 'RN', 'PERALTA SANCHEZ', 'UCIN', NULL, NULL, '0114298', NULL, '2024-12-29', '9 día(s)', 1.290, NULL, '2025-01-08 09:39:24', '2025-01-08 09:39:24'),
(871, 'RN', 'VALENCIA HERNANDEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-11-16', '1 mes(es) 21 día(s)', 1.750, 'Masculino', '2025-01-08 09:41:43', '2025-01-08 09:41:43'),
(872, 'RN', 'VILLAGOMEZ GONZALEZ', 'UCIN', '3', NULL, '247364', NULL, '2025-01-02', '5 día(s)', 0.825, 'Masculino', '2025-01-08 09:44:09', '2025-01-08 09:44:09'),
(873, 'RN', 'HERNANDEZ FLORES', 'UCIN', '2', NULL, '247078', NULL, '2024-12-21', '17 día(s)', 1.030, 'Masculino', '2025-01-08 09:46:52', '2025-01-08 09:46:52'),
(874, 'ANGELA', 'ROBLEDO HERNANDEZ', 'PEDIATRIA', '84', NULL, '227416', NULL, '2024-11-18', '1 mes(es) 19 día(s)', 3.400, NULL, '2025-01-08 09:53:43', '2025-01-08 09:53:43'),
(875, 'RN', 'ANTONIO CAYETANO', 'INTERNOS', NULL, '1', NULL, NULL, '2024-10-07', '3 mes(es) ', 3.780, 'Masculino', '2025-01-08 09:56:47', '2025-01-08 09:56:47'),
(876, 'RN', 'SANTIAGO CONTRERAS', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-29', '9 día(s)', 1.790, 'Femenino', '2025-01-08 10:02:20', '2025-01-08 10:02:20'),
(877, 'RN', 'ARZATE LOPEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-31', '7 día(s)', 1.420, 'Masculino', '2025-01-08 10:06:59', '2025-01-08 10:06:59'),
(878, 'RN', 'CARRIZOSA GARCIA', 'LACTANTES', NULL, NULL, '205308', NULL, '2024-12-10', '28 día(s)', 3.600, 'Femenino', '2025-01-08 10:12:20', '2025-01-08 10:12:20'),
(879, 'RN', 'MARTINEZ GUZMAN', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-30', '8 día(s)', 1.460, 'Masculino', '2025-01-08 10:12:25', '2025-01-08 10:12:25'),
(880, 'RN', 'GOMEZ CORONA', 'LACTANTES', NULL, NULL, '205541', NULL, '2024-12-09', '29 día(s)', 2.800, 'Femenino', '2025-01-08 10:15:25', '2025-01-08 10:15:25'),
(881, 'RN', 'VELASQUEZ ENRIQUEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-23', '15 día(s)', 2.245, 'Masculino', '2025-01-08 10:17:01', '2025-01-08 10:17:01'),
(882, 'RN', 'GONZALEZ CARMONA', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-31', '7 día(s)', 2.180, 'Femenino', '2025-01-08 10:21:19', '2025-01-08 10:21:19'),
(883, 'RN', 'AVILES LOPEZ G1', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-31', '7 día(s)', 1.320, 'Masculino', '2025-01-08 10:24:26', '2025-01-08 10:24:26'),
(884, 'RN', 'HERNANDEZ VILLALOBOS', 'UTIN', '5', NULL, '247280', NULL, '2024-12-30', '8 día(s)', 1.600, 'Masculino', '2025-01-08 10:25:11', '2025-01-08 10:25:11'),
(885, 'RN', 'DOMINGUEZ SANCHEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-11-19', '1 mes(es) 18 día(s)', 1.835, 'Masculino', '2025-01-08 10:27:34', '2025-01-08 10:27:34'),
(886, 'RN', 'OLMEDO RICARDO', 'UTIN', '2', NULL, '246939', NULL, '2024-12-16', '22 día(s)', 3.490, 'Masculino', '2025-01-08 10:29:09', '2025-01-08 10:29:09'),
(887, 'RN', 'BENITES MARTINEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-26', '12 día(s)', 1.490, 'Masculino', '2025-01-08 10:30:39', '2025-01-08 10:30:39'),
(888, 'RN', 'SANDOVAL RODAL', 'NEONATOLOGIA', 'UCIN 2', '2', '1500673741', 'DESNUTRICION', '2024-02-22', '10 mes(es) 14 día(s)', 2.332, 'Femenino', '2025-01-08 10:36:48', '2025-01-08 10:36:48'),
(889, 'RN', 'CRUZ RODRIGUEZ', 'UCIN', NULL, NULL, '464659', NULL, '2025-01-01', '6 día(s)', 1.850, 'Masculino', '2025-01-08 10:37:12', '2025-01-08 10:37:12'),
(890, 'RN', 'ESPINOZA GARCIA', 'UCIN', NULL, NULL, '464667', NULL, '2025-01-01', '6 día(s)', 0.735, NULL, '2025-01-08 10:42:23', '2025-01-08 10:42:23'),
(891, 'RN', 'AVILES MARTINEZ}', 'UCIN', NULL, NULL, '464386', NULL, '2024-12-23', '15 día(s)', 1.120, NULL, '2025-01-08 10:45:38', '2025-01-08 10:45:38'),
(892, 'RN', 'SANTIAGO DE LOS SANTOS', 'UCIN', NULL, NULL, NULL, NULL, '2025-01-07', '', 2.600, 'Masculino', '2025-01-08 10:48:33', '2025-01-08 10:48:33'),
(893, 'RN', 'Bermudez Mejia', 'UCIN', 'U3', '1', '428055', 'PO LAPE', '2024-12-09', '29 día(s)', 1.700, 'Masculino', '2025-01-08 10:57:20', '2025-01-08 10:57:20'),
(894, 'RN', 'SANCHEZ RINCON', 'PEDIATRIA', NULL, '1', '024-225885', NULL, '2024-12-21', '17 día(s)', 0.940, 'Femenino', '2025-01-08 11:07:47', '2025-01-08 11:07:47'),
(895, 'RN', 'GARCIA SANTIAGO', 'PEDIATRIA', NULL, NULL, '93955-24', NULL, '2024-12-19', '19 día(s)', 1.630, 'Masculino', '2025-01-08 11:14:52', '2025-01-08 11:14:52'),
(896, 'RN', 'SANCHEZ RINCON', 'PEDIATRIA', NULL, NULL, '024-225885', NULL, '2024-12-21', '17 día(s)', 0.940, 'Femenino', '2025-01-08 11:36:42', '2025-01-08 11:36:42'),
(897, 'TEODORO', 'MACIAS CERVANTES', 'HOSPITALIZACION', '301', '3', '1500673991', 'NEUMONIA', '1936-09-11', '88 año(s) 3 mes(es) 26 día(s)', 60.000, 'Masculino', '2025-01-08 11:38:24', '2025-01-08 11:38:24'),
(898, 'RN', 'ORDAZ GONZALEZ', 'UCIN', NULL, NULL, '24-8598', NULL, '2024-12-19', '19 día(s)', 1.430, 'Femenino', '2025-01-08 11:40:45', '2025-01-08 11:40:45'),
(899, 'RN', 'MATIAS HERNANDEZ', 'UCIN', NULL, NULL, '25-52', NULL, '2025-01-03', '4 día(s)', 1.110, 'Femenino', '2025-01-08 11:43:45', '2025-01-08 11:43:45'),
(900, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACION', '307', '3', '1500660965', 'INFECCION DE VIAS URINARIAS', '1947-07-31', '77 año(s) 5 mes(es) 7 día(s)', 68.000, 'Masculino', '2025-01-08 11:47:42', '2025-01-08 11:47:42'),
(901, 'RN', 'MORENO PATRICIO', 'UCIN', NULL, NULL, NULL, NULL, '2024-10-30', '2 mes(es) 8 día(s)', 1.230, 'Femenino', '2025-01-08 12:28:52', '2025-01-08 12:28:52'),
(902, 'TEODORO', 'MACIAS CERVANTES', 'HOSPITALIZACION', '301', '3', '1500673991', 'NEUMONIA', '1936-09-11', '88 año(s) 3 mes(es) 26 día(s)', 60.000, 'Masculino', '2025-01-08 12:40:29', '2025-01-08 12:40:29'),
(903, 'RN', 'SANTIAGO CONTRERAS', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-29', '9 día(s)', 1.790, 'Femenino', '2025-01-08 13:21:16', '2025-01-08 13:21:16'),
(904, 'RN', 'JUAREZ ORTIZ G1', 'INTERNOS', NULL, '1', NULL, NULL, '2024-12-27', '11 día(s)', 1.250, 'Femenino', '2025-01-08 13:43:37', '2025-01-08 13:43:37'),
(905, 'RN', 'BENITEZ MARTINEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-26', '12 día(s)', 1.490, 'Masculino', '2025-01-08 13:57:30', '2025-01-08 13:57:30'),
(906, 'GRACIELA', 'HURTADO GARCIA', 'HOSPITALIZACION', '227', '2', '1500677237', 'DESNUTRICION', '1977-05-31', '47 año(s) 7 mes(es) 7 día(s)', 70.000, 'Femenino', '2025-01-08 14:46:29', '2025-01-08 14:46:29'),
(907, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICION', '1953-10-12', '71 año(s) 2 mes(es) 26 día(s)', 60.000, 'Femenino', '2025-01-08 14:50:42', '2025-01-08 14:50:42'),
(908, 'JOSE FRANCISCO', 'GONZALEZ PENSADO', 'HOSPITALIZACION', '427', '4', '1500686131', 'DESNUTRICION', '1979-03-14', '45 año(s) 9 mes(es) 24 día(s)', 68.000, 'Masculino', '2025-01-08 15:01:16', '2025-01-08 15:01:16'),
(909, 'FELIPE DE JESUS', 'ESCALANTE CASTILLO', 'HOSPITALIZACION', 'UCC-1', '1', '1500676225', 'DESNUTRICION', '1944-02-05', '80 año(s) 11 mes(es) 2 día(s)', 70.000, 'Masculino', '2025-01-08 15:14:40', '2025-01-08 15:14:40'),
(910, 'GRACIELA', 'FERNANDEZ LARA', 'TERAPIA INTENSIVA', 'UTI-9', '1', '1500672626', 'NEUMONIA', '1937-06-12', '87 año(s) 6 mes(es) 25 día(s)', 51.000, 'Femenino', '2025-01-08 15:22:47', '2025-01-08 15:22:47'),
(911, 'GRACIELA', 'FERNANDEZ LARA', 'TERAPIA INTENSIVA', 'UTI-9', '1', '1500672626', 'NEUMONIA', '1937-06-12', '87 año(s) 6 mes(es) 25 día(s)', 50.000, 'Femenino', '2025-01-08 15:32:46', '2025-01-08 15:32:46'),
(912, 'RN', 'PERALTA SANCHEZ', 'UCIN', NULL, NULL, '0114298', NULL, '2024-12-29', '10 día(s)', 1.415, NULL, '2025-01-09 08:39:09', '2025-01-09 08:39:09'),
(913, 'RECIEN NACIDO', 'GARCIA RODRIGUEZ', 'NEONATOLOGÍA', 'AISLADO', '2', '70338', 'SEPSIS POR STAPHYLOCOCCUS AUREUS', '2024-11-08', '2 mes(es) ', 1.970, 'Femenino', '2025-01-09 08:39:16', '2025-01-09 08:39:16'),
(914, 'RN', 'ROJAS ROJAS G1', 'METABOLICOS', NULL, 'PB', '117418', NULL, '2024-12-29', '10 día(s)', 1.415, 'Masculino', '2025-01-09 08:40:37', '2025-01-09 08:40:37'),
(915, 'RN', 'MEJIA SANABRIA', 'UCIN', NULL, NULL, '0114283', NULL, '2024-12-27', '12 día(s)', 1.955, 'Femenino', '2025-01-09 08:41:33', '2025-01-09 08:41:33'),
(916, 'RN', 'LUCIANO HERNANDEZ G2', 'UCIREN 1', NULL, '1', '112847', NULL, '2024-12-16', '23 día(s)', 1.560, 'Masculino', '2025-01-09 09:07:01', '2025-01-09 09:07:01'),
(917, 'RN', 'GONZALEZ MALVAEZ', 'UCIREN 1', NULL, '1', '118130', NULL, '2024-12-27', '12 día(s)', 1.430, 'Femenino', '2025-01-09 09:15:07', '2025-01-09 09:15:07'),
(918, 'RN', 'SANTIAGO CONTRERAS', 'UCIREN 1', NULL, '1', '118152', NULL, '2024-12-29', '10 día(s)', 1.780, 'Femenino', '2025-01-09 09:21:32', '2025-01-09 09:21:32'),
(919, 'RN', 'GONZALEZ ECHEVERRIA', 'TOCO', NULL, 'PB', '118199', NULL, '2025-01-01', '7 día(s)', 2.098, 'Masculino', '2025-01-09 09:29:23', '2025-01-09 09:29:23'),
(920, 'RN', 'DE LA CRUZ VILLAVICENCIO', 'TOCO', NULL, '1.77', '118292', NULL, '2025-01-07', '1 día(s)', 1.770, 'Femenino', '2025-01-09 09:33:52', '2025-01-09 09:33:52'),
(921, 'RN', 'JACOBO CARRILLO', 'UCIN', '5', '1', '70817', 'SEPSIS', '2024-12-18', '21 día(s)', 1.200, 'Femenino', '2025-01-09 09:39:27', '2025-01-09 10:40:43'),
(922, 'RN', 'JUAREZ ORTIZ G1', 'INTERNOS', NULL, '1', '118137', NULL, '2024-12-27', '12 día(s)', 1.230, 'Masculino', '2025-01-09 09:42:20', '2025-01-09 09:42:20'),
(923, 'RN', 'VASQUEZ SANCHEZ', 'INTERNOS', NULL, '1', '117094', NULL, '2025-01-04', '4 día(s)', 1.445, 'Femenino', '2025-01-09 09:48:03', '2025-01-09 09:48:03'),
(924, 'RN', 'SANDOVAL RODAL', 'NEONATOLOGÍA', 'UCIN-2', '2', '1500673741', 'DESNUTRICIÓN', '2024-12-22', '17 día(s)', 2.332, 'Femenino', '2025-01-09 09:57:28', '2025-01-09 09:57:28'),
(925, 'RN', 'MAYA MORENO', 'INTERNOS', NULL, '1', '118265', NULL, '2025-01-06', '2 día(s)', 1.235, 'Masculino', '2025-01-09 10:01:25', '2025-01-09 10:01:25'),
(926, 'RN', 'ORDAZ GONZALEZ', 'UCIN', NULL, NULL, '24-8598', NULL, '2024-12-19', '20 día(s)', 1.430, 'Femenino', '2025-01-09 10:05:30', '2025-01-09 10:05:30'),
(927, 'RN', 'VALENCIA HERNANDEZ', 'INTERNOS', NULL, '1', '117017', NULL, '2024-11-16', '1 mes(es) 22 día(s)', 2.000, 'Masculino', '2025-01-09 10:06:45', '2025-01-09 10:06:45'),
(928, 'RN', 'MATIAS HERNANDEZ', 'UCIN', NULL, NULL, '25-52', NULL, '2025-01-03', '5 día(s)', 1.140, 'Femenino', '2025-01-09 10:08:39', '2025-01-09 10:08:39'),
(929, 'RN', 'BECERRIL MENDOZA', 'INTERNOS', NULL, '1', '118243', NULL, '2025-01-05', '3 día(s)', 2.300, 'Masculino', '2025-01-09 10:12:30', '2025-01-09 10:12:30'),
(930, 'RN', 'GARCIA SANTIAGO', 'PEDIATRIA', NULL, NULL, '93955-24', NULL, '2024-12-19', '20 día(s)', 1.640, 'Masculino', '2025-01-09 10:13:36', '2025-01-09 10:13:36'),
(931, 'RN', 'AVILES LOPEZ G2', 'INTERNOS', NULL, '1', '118187', NULL, '2024-12-31', '8 día(s)', 1.890, 'Masculino', '2025-01-09 10:17:25', '2025-01-09 10:17:25'),
(932, 'RN', 'BENITEZ MARTINEZ', 'EXTERNOS', NULL, '1', '118100', NULL, '0025-12-08', '1999 año(s) 1 mes(es) ', 1.505, 'Masculino', '2025-01-09 10:24:04', '2025-01-09 10:24:04'),
(933, 'RN', 'ARZATE LOPEZ', 'EXTERNOS', NULL, '1', '118178', NULL, '2024-12-31', '8 día(s)', 1.280, 'Masculino', '2025-01-09 10:30:21', '2025-01-09 10:30:21'),
(934, 'RN', 'DOMINGUEZ SANCHEZ', 'EXTERNOS', NULL, '1', '117094', NULL, '2024-11-19', '1 mes(es) 19 día(s)', 2.130, 'Masculino', '2025-01-09 10:36:11', '2025-01-09 10:36:11'),
(935, 'RN', 'GOMEZ CORONA', 'LACTANTES', NULL, NULL, '205541', NULL, '2024-12-09', '30 día(s)', 2.800, 'Femenino', '2025-01-09 10:38:49', '2025-01-09 10:38:49'),
(936, 'RN', 'GONZALEZ CARMONA', 'EXTERNOS', NULL, '1', '118178', NULL, '0024-12-31', '2000 año(s) 8 día(s)', 2.070, 'Femenino', '2025-01-09 10:41:24', '2025-01-09 10:41:24'),
(937, 'ANGELA', 'ROBLEDO HERNANDEZ', 'PEDIATRIA', '84', NULL, '247416', NULL, '2024-11-18', '1 mes(es) 20 día(s)', 3.400, 'Femenino', '2025-01-09 10:46:11', '2025-01-09 10:46:11'),
(938, 'RN', 'VELAZQUEZ ENRIQUEZ', 'EXTERNOS', NULL, '1', '118045', NULL, '2024-12-23', '16 día(s)', 2.285, 'Masculino', '2025-01-09 10:48:47', '2025-01-09 10:48:47');
INSERT INTO `solicitud_patients` (`id`, `nombre_paciente`, `apellidos_paciente`, `servicio`, `cama`, `piso`, `registro`, `diagnostico`, `fecha_nacimiento`, `edad`, `peso`, `sexo`, `created_at`, `updated_at`) VALUES
(939, 'RN', 'HERNANDEZ FLORES', 'UCIN', '2', NULL, '247078', NULL, '2024-12-21', '18 día(s)', 1.050, 'Masculino', '2025-01-09 10:52:19', '2025-01-09 10:52:19'),
(940, 'RN', 'MARTINEZ GUZMAN', 'EXTERNOS', NULL, '1', '118116', NULL, '2024-12-30', '9 día(s)', 1.485, 'Masculino', '2025-01-09 10:52:59', '2025-01-09 10:52:59'),
(941, 'RN', 'VILLAGOMEZ GONZALEZ', 'UCIN', '3', NULL, '247364', NULL, '2025-01-02', '6 día(s)', 0.780, 'Masculino', '2025-01-09 10:55:46', '2025-01-09 10:55:46'),
(942, 'RN', 'AVILES LOPEZ G1', 'EXTERNOS', NULL, '1', '118186', NULL, '2024-12-31', '8 día(s)', 1.375, 'Femenino', '2025-01-09 10:57:30', '2025-01-09 10:57:30'),
(943, 'RN', 'HERNANDEZ VILLALOBOS', 'UTIN', NULL, NULL, '247280', NULL, '2024-12-30', '9 día(s)', 1.670, 'Masculino', '2025-01-09 10:58:07', '2025-01-09 10:58:07'),
(944, 'RN', 'COLIN COLIN', 'EXTERNOS', NULL, '1', '118186', NULL, '2025-01-07', '1 día(s)', 1.370, 'Masculino', '2025-01-09 11:00:35', '2025-01-09 11:00:35'),
(945, 'RN', 'OLMEDO RICARDO', 'UTIN', '2', NULL, '246939', NULL, '2024-12-16', '23 día(s)', 3.575, 'Masculino', '2025-01-09 11:01:31', '2025-01-09 11:01:31'),
(946, 'RN', 'VILCHIS ALARCON', 'EXTERNOS', NULL, '1', '118228', NULL, '2025-01-08', '', 0.730, 'Femenino', '2025-01-09 11:04:56', '2025-01-09 11:04:56'),
(947, 'RN', 'MORENO PATRICIO', 'UCIN', NULL, NULL, NULL, NULL, '2024-10-30', '2 mes(es) 9 día(s)', 1.250, NULL, '2025-01-09 11:06:30', '2025-01-09 11:06:30'),
(948, 'RN', 'ANTONIO CAYETANO', 'INTERNOS', NULL, '1', '118100', NULL, '2024-10-07', '3 mes(es) 1 día(s)', 3.780, NULL, '2025-01-09 11:11:38', '2025-01-09 11:11:38'),
(949, 'MARCO ANTONIO', 'HINOJOSA LOPEZ', 'PEDIATRIA', NULL, NULL, '024-225582', NULL, '2024-11-19', '1 mes(es) 19 día(s)', 0.940, 'Masculino', '2025-01-09 11:13:43', '2025-01-09 11:13:43'),
(950, 'RN', 'Bermudez Mejia', 'UCIN', 'U3', '1', '428055', 'PO LAPE', '2024-12-09', '30 día(s)', 1.700, 'Masculino', '2025-01-09 11:20:30', '2025-01-09 11:20:30'),
(951, 'RN', 'CRUZ RODRIGUEZ', 'UCIN', NULL, NULL, '464659', NULL, '2025-01-01', '7 día(s)', 1.950, NULL, '2025-01-09 11:31:47', '2025-01-09 11:31:47'),
(952, 'GRACIELA', 'FERNANDEZ LARA', 'TERAPIA INTENSIVA', 'UTI-9', '1', '1500672626', 'NEUMONIA', '1937-06-12', '87 año(s) 6 mes(es) 26 día(s)', 50.000, 'Femenino', '2025-01-09 12:24:43', '2025-01-09 12:24:43'),
(953, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICIÓN', '1953-10-12', '71 año(s) 2 mes(es) 27 día(s)', 60.000, 'Femenino', '2025-01-09 12:38:44', '2025-01-09 12:38:44'),
(954, 'JOSÉ FRANCISCO', 'GONZALEZ PENSADO', 'HOSPITALIZACIÓN', '427', '4', '1500686131', 'DESNUTRICIÓN', '1979-03-14', '45 año(s) 9 mes(es) 25 día(s)', 68.000, 'Masculino', '2025-01-09 12:48:19', '2025-01-09 12:48:19'),
(955, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACIÓN', '307', '3', '1500660965', 'DESNUTRICIÓN', '1947-07-31', '77 año(s) 5 mes(es) 8 día(s)', 68.000, 'Masculino', '2025-01-09 13:00:17', '2025-01-09 13:00:17'),
(956, 'RN', 'LEGARRETA ROMERO', 'PEDIATRIA', NULL, NULL, '209791', NULL, '2024-12-30', '9 día(s)', 1.610, 'Femenino', '2025-01-09 13:00:29', '2025-01-09 13:00:29'),
(957, 'RN', 'LUCIANO HERNANDEZ G2', 'UCIREN 1', NULL, '1', '112847', NULL, '2024-12-16', '23 día(s)', 1.560, 'Masculino', '2025-01-09 13:29:35', '2025-01-09 13:31:14'),
(958, 'RN', 'CRUZ RODRIGUEZ', 'UCIN', NULL, NULL, '464659', NULL, '2025-01-01', '7 día(s)', 1.950, NULL, '2025-01-09 13:51:55', '2025-01-09 13:51:55'),
(959, 'TEODORO', 'MACIAS CERVANTES', 'HOSPITALIZACIÓN', '301', '3', '1500673991', 'DESNITRICIÓN', '1936-09-11', '88 año(s) 3 mes(es) 27 día(s)', 60.000, 'Masculino', '2025-01-09 13:54:21', '2025-01-09 13:54:21'),
(960, 'RN', 'CRUZ RODRIGUEZ', 'UCIN', NULL, NULL, '464659', NULL, '2025-01-01', '7 día(s)', 1.950, NULL, '2025-01-09 13:57:58', '2025-01-09 13:57:58'),
(961, 'FELIPE DE JESUS', 'ESCALANTE CASTILLO', 'TERAPIA INTENSIVA', 'UCC-1', '1', '1500676225', 'INSUFICIENCIA RESPIRATORIA', '1944-02-05', '80 año(s) 11 mes(es) 3 día(s)', 70.000, 'Masculino', '2025-01-09 14:49:48', '2025-01-09 14:49:48'),
(962, 'GRACIELA', 'HURTADO GARCIA', 'HOSPITALIZACION', '227', '2', '1500677237', 'DESNUTRICION', '1977-05-31', '47 año(s) 7 mes(es) 8 día(s)', 70.000, 'Femenino', '2025-01-09 16:37:32', '2025-01-09 16:37:32'),
(963, 'RN', 'MORENO PATRICIO', 'UCIN', NULL, NULL, NULL, NULL, '2024-10-30', '2 mes(es) 9 día(s)', 1.250, NULL, '2025-01-09 16:46:43', '2025-01-09 16:46:43'),
(964, 'RECIEN NACIDO', 'GARCIA RODRIGUEZ', 'NEONATOLOGÍA', 'AISLADO', '2', '70338', 'SEPSIS POR STAPHYLOCOCCUS AUREUS', '2024-11-08', '2 mes(es) 1 día(s)', 1.970, 'Femenino', '2025-01-10 09:04:31', '2025-01-10 09:04:31'),
(965, 'RN', 'MEJIA SANABRIA', 'UCIN', NULL, NULL, '0114283', NULL, '2024-12-27', '13 día(s)', 2.005, 'Femenino', '2025-01-10 09:24:11', '2025-01-10 09:24:11'),
(966, 'RN', 'PERALTA SANCHEZ', 'UCIN', NULL, NULL, '0114298', NULL, '2024-12-29', '11 día(s)', 1.430, 'Masculino', '2025-01-10 09:26:15', '2025-01-10 09:26:15'),
(967, 'RN', 'BERNAL FUENTES', 'PEDIATRIA UCIN', NULL, NULL, '250757', NULL, '0204-12-30', '1820 año(s) 10 día(s)', 0.970, 'Masculino', '2025-01-10 09:47:15', '2025-01-10 09:47:15'),
(968, 'RN', 'GARCIA SANTIAGO', 'PEDIATRIA', NULL, NULL, '93955-24', NULL, '2024-12-19', '21 día(s)', 1.640, NULL, '2025-01-10 09:51:27', '2025-01-10 09:51:27'),
(969, 'RN', 'SANDOVAL RODAL', 'NEONATOLOGÍA', 'UCIN 2', '2', '1500673741', 'DESNUTRICIÓN', '2024-12-22', '18 día(s)', 2.544, 'Femenino', '2025-01-10 10:01:28', '2025-01-10 10:01:28'),
(970, 'LUIS', 'MAURER Y ESPINOSA', 'HOSPITALIZACIÓN', '307', '3', '1500660965', 'DESNUTRICIÓN', '1947-07-31', '77 año(s) 5 mes(es) 9 día(s)', 68.000, 'Masculino', '2025-01-10 10:17:38', '2025-01-10 10:17:38'),
(971, 'RN', 'HERNANDEZ FLORES', 'UCIN', '2', NULL, '247078', NULL, '2024-12-21', '19 día(s)', 1.150, 'Masculino', '2025-01-10 10:30:23', '2025-01-10 10:30:23'),
(972, 'RN', 'VILLAGOMEZ GONZALEZ', 'UCIN', '3', NULL, '247364', NULL, '2025-01-02', '7 día(s)', 0.905, 'Masculino', '2025-01-10 10:34:09', '2025-01-10 10:34:09'),
(973, 'RN', 'OLMEDO RICARDO', 'UTIN', '2', NULL, '246939', NULL, '2024-12-16', '24 día(s)', 3.540, 'Masculino', '2025-01-10 10:37:05', '2025-01-10 10:37:05'),
(974, 'RN', 'CRUZ RODRIGUEZ', 'UCIN', NULL, NULL, '464659', NULL, '2025-01-01', '8 día(s)', 1.950, 'Masculino', '2025-01-10 10:42:46', '2025-01-10 10:42:46'),
(975, 'RN', 'ESPINOZA GARCIA', 'UCIN', NULL, NULL, '464667', NULL, '2025-01-01', '8 día(s)', 0.740, 'Masculino', '2025-01-10 10:45:31', '2025-01-10 10:45:31'),
(976, 'RN', 'AVILES MARTINEZ', 'UCIN', NULL, NULL, '464386', NULL, '2024-12-23', '17 día(s)', 1.150, 'Femenino', '2025-01-10 10:48:51', '2025-01-10 10:48:51'),
(977, 'RN', 'CARRASCO GALICIA', 'PEDIATRIA UCIN 1', NULL, NULL, '205688', NULL, '2025-01-08', '1 día(s)', 3.500, 'Femenino', '2025-01-10 10:51:10', '2025-01-10 10:51:10'),
(978, 'RN', 'MATIAS HERNANDEZ', 'UCIN', NULL, NULL, '25-52', NULL, '2025-01-03', '6 día(s)', 1.160, 'Femenino', '2025-01-10 11:09:42', '2025-01-10 11:09:42'),
(979, 'RN', 'ORDAZ GONZALEZ', 'UCIN', NULL, NULL, '24-8598', NULL, '2024-12-19', '21 día(s)', 1.430, 'Femenino', '2025-01-10 11:16:09', '2025-01-10 11:16:09'),
(980, 'RN', 'GONZALEZ MALVAEZ', 'UCIREN 1', NULL, NULL, '118139', NULL, '2024-12-27', '13 día(s)', 1.440, 'Femenino', '2025-01-10 11:16:48', '2025-01-10 11:16:48'),
(981, 'RN', 'LUCIANO HERNANDEZ', 'UCIREN 1', NULL, NULL, '112847', NULL, '2024-12-16', '24 día(s)', 1.575, 'Masculino', '2025-01-10 11:19:48', '2025-01-10 11:19:48'),
(982, 'RN', 'SANTIAGO CONTRERAS', 'UCIREN 1', NULL, NULL, '118152', NULL, '2024-12-29', '11 día(s)', 1.790, 'Femenino', '2025-01-10 11:24:42', '2025-01-10 11:24:42'),
(983, 'RN', 'SANCHEZ RINCON', 'PEDIATRIA', NULL, NULL, '024-225888', NULL, '2024-12-21', '19 día(s)', 0.975, 'Femenino', '2025-01-10 11:27:22', '2025-01-10 11:27:22'),
(984, 'RN', 'DE LA CRUZ VICENCIO', 'UCIREN 2', NULL, NULL, '118292', NULL, '2025-01-07', '2 día(s)', 1.785, 'Femenino', '2025-01-10 11:31:21', '2025-01-10 11:31:21'),
(985, 'RN', 'VELAZQUEZ ENRIQUEZ', 'UCIN EXTERNOS', NULL, NULL, '118045', NULL, '2024-12-23', '17 día(s)', 2.300, 'Masculino', '2025-01-10 11:36:44', '2025-01-10 11:36:44'),
(986, 'RN', 'MARTINEZ GUZMAN', 'UCIN EXTERNOS', NULL, NULL, '118116', NULL, '2024-12-30', '10 día(s)', 1.515, 'Masculino', '2025-01-10 11:38:07', '2025-01-10 11:38:07'),
(987, 'RN', 'VILCHIS ALARCON', 'UCIN EXTERNOS', NULL, NULL, '118137', NULL, '2025-01-04', '5 día(s)', 0.730, 'Masculino', '2025-01-10 11:39:35', '2025-01-10 11:39:35'),
(988, 'RN', 'DOMINGUEZ SANCHEZ', 'UCIN EXTERNOS', NULL, NULL, '117094', NULL, '2024-11-19', '1 mes(es) 20 día(s)', 2.125, 'Masculino', '2025-01-10 11:41:48', '2025-01-10 11:41:48'),
(989, 'RN', 'LEGARRETA ROMERO', 'PEDIATRIA', NULL, NULL, '209781', NULL, '2024-12-30', '10 día(s)', 1.555, NULL, '2025-01-10 11:42:25', '2025-01-10 11:42:25'),
(990, 'RN GEMELO 1', 'AVILES LOPEZ', 'UCIN EXTERNOS', NULL, NULL, '118186', NULL, '2024-12-31', '9 día(s)', 1.400, 'Masculino', '2025-01-10 11:44:05', '2025-01-10 11:44:05'),
(991, 'RN', 'GONZALEZ CARMONA', 'UCIN EXTERNOS', NULL, NULL, '118178', NULL, '2024-12-31', '9 día(s)', 2.090, 'Femenino', '2025-01-10 11:47:05', '2025-01-10 11:47:05'),
(992, 'RN', 'BENITEZ MARTINEZ', 'UCIN EXTERNOS', NULL, NULL, '118100', NULL, '2024-12-26', '14 día(s)', 1.570, 'Masculino', '2025-01-10 11:48:19', '2025-01-10 11:48:19'),
(993, 'RN', 'Bermudez Mejia', 'UCIN', 'U3', '1', '428055', 'Perforación intestinal', '2024-12-02', '1 mes(es) 7 día(s)', 1.700, 'Masculino', '2025-01-10 11:50:57', '2025-01-10 11:50:57'),
(994, 'JOSE FRANCISCO', 'GONZALEZ PENSADO', 'HOSPITALAZACIÓN', '427', '3', '1500686131', 'DESNUTRICIÓN', '1979-03-14', '45 año(s) 9 mes(es) 26 día(s)', 68.000, 'Masculino', '2025-01-10 11:51:04', '2025-01-10 11:51:04'),
(995, 'RN', 'COLIN COLIN', 'UCIN EXTERNOS', NULL, NULL, '118186', NULL, '2025-01-07', '2 día(s)', 1.455, 'Masculino', '2025-01-10 11:51:31', '2025-01-10 11:51:31'),
(996, 'RN G1', 'ROJAS ROJAS', 'UCIN INTERNOS', NULL, NULL, '118109', NULL, '2024-12-26', '14 día(s)', 1.420, 'Masculino', '2025-01-10 11:52:14', '2025-01-10 11:52:14'),
(997, 'RN', 'MAYA MORENO', 'UCIN INTERNOS', NULL, NULL, '118265', NULL, '2025-01-06', '3 día(s)', 1.235, 'Masculino', '2025-01-10 11:55:43', '2025-01-10 11:55:43'),
(998, 'RN', 'ARZATE LOPEZ', 'UCIN EXTERNO', NULL, NULL, '118178', NULL, '2024-12-31', '9 día(s)', 1.340, 'Masculino', '2025-01-10 11:57:46', '2025-01-10 11:57:46'),
(999, 'RN', 'AVILES LOPEZ', 'UCIN INTERNOS', NULL, NULL, '118187', NULL, '2024-12-31', '9 día(s)', 1.890, 'Masculino', '2025-01-10 11:58:32', '2025-01-10 11:58:32'),
(1000, 'RN FEM', 'ANTONIO CAYETANO', 'UCIN INTERNOS', NULL, NULL, '115848', NULL, '2024-10-07', '3 mes(es) 2 día(s)', 3.815, 'Femenino', '2025-01-10 12:01:27', '2025-01-10 12:01:27'),
(1001, 'RN', 'VALENCIA HERNANDEZ', 'UCIN INTERNOS', NULL, NULL, '117017', NULL, '2024-11-16', '1 mes(es) 23 día(s)', 3.815, NULL, '2025-01-10 12:05:18', '2025-01-10 12:05:18'),
(1002, 'RN', 'VASQUEZ SANCHEZ', 'UCIN INTERNOS', NULL, NULL, '117094', NULL, '2025-01-04', '5 día(s)', 3.815, 'Femenino', '2025-01-10 12:08:27', '2025-01-10 12:08:27'),
(1003, 'RN', 'BECERRIL MENDOZA', 'UCIN INTERNOS', NULL, NULL, '118243', NULL, '2025-01-05', '4 día(s)', 2.300, 'Masculino', '2025-01-10 12:11:44', '2025-01-10 12:11:44'),
(1004, 'RN GEMELO 1', 'JUAREZ ORTIZ', 'UCIN INTERNOS', NULL, NULL, '117019', NULL, '2024-12-27', '13 día(s)', 1.240, 'Femenino', '2025-01-10 12:14:39', '2025-01-10 12:14:39'),
(1005, 'RN', 'BERNAL FUENTES VARON', 'UCIN', NULL, NULL, '250757', NULL, '2024-12-30', '10 día(s)', 0.940, 'Masculino', '2025-01-10 12:47:11', '2025-01-10 12:47:11'),
(1006, 'TEODORO', 'MACIAS CERVANTES', 'HOSPITALIZACIÓN', '301', '3', '1500673991', 'DESNUTRICIÓN', '1936-09-11', '88 año(s) 3 mes(es) 28 día(s)', 60.000, 'Masculino', '2025-01-10 12:51:15', '2025-01-10 12:51:15'),
(1007, 'RN', 'MORENO PATRICIO', 'UCIN', NULL, NULL, NULL, NULL, '2024-10-30', '2 mes(es) 10 día(s)', 1.330, 'Femenino', '2025-01-10 12:59:52', '2025-01-10 12:59:52'),
(1008, 'GRACIELA', 'FERNÁNDEZ LARA', 'TERAPIA INTENSIVA', 'UTI-9', '1', '1500672626', 'DESNUTRICIÓN', '1937-06-12', '87 año(s) 6 mes(es) 27 día(s)', 50.000, 'Femenino', '2025-01-10 13:01:05', '2025-01-10 13:01:05'),
(1009, 'RN', 'JACOBO CARRILLO', 'UCIN', '5', '1', '70817', 'SEPSIS', '2024-12-18', '22 día(s)', 1.200, 'Femenino', '2025-01-10 13:58:02', '2025-01-10 14:28:57'),
(1010, 'RECIEN NACIDO', 'GARCIA RODRIGUEZ', 'NEONATOLOGÍA', 'AISLADO', '2', '70338', 'SEPSIS POR STAPHYLOCOCCUS AUREUS', '2025-01-08', '1 día(s)', 1.970, 'Femenino', '2025-01-10 14:18:01', '2025-01-10 14:18:01'),
(1011, 'JUDITH', 'BARCELATA HALL', 'TERAPIA INTENSIVA', 'UTI-6', '1', '1500599769', 'DESNUTRICION', '1953-10-12', '71 año(s) 2 mes(es) 28 día(s)', 60.000, 'Femenino', '2025-01-10 15:23:28', '2025-01-10 15:23:28'),
(1012, 'FELIPE DE JESUS', 'ESCALANTE CASTILLO', 'CORONARIA', 'UCC-1', '1', '1500676225', 'DESNUTRICION', '1944-01-05', '81 año(s) 4 día(s)', 70.000, 'Masculino', '2025-01-10 15:50:48', '2025-01-10 15:50:48'),
(1013, 'RN', 'MORENO PATRICIO', 'UCIN', NULL, NULL, NULL, NULL, '2024-10-30', '2 mes(es) 10 día(s)', 1.330, 'Femenino', '2025-01-10 16:39:42', '2025-01-10 16:39:42'),
(1014, 'RN', 'BENITEZ MARTINEZ', 'METABOLICOS', NULL, '1', NULL, NULL, '2024-12-26', '15 día(s)', 1.600, 'Masculino', '2025-01-11 08:15:02', '2025-01-11 08:15:02'),
(1015, 'RN', 'BENITEZ MARTINEZ', 'METABOLICOS', NULL, '1', NULL, NULL, '2024-12-26', '15 día(s)', 1.600, 'Masculino', '2025-01-11 08:32:58', '2025-01-11 08:34:18'),
(1016, 'RN', 'GONZALEZ MALVAEZ', 'UCIREN1', NULL, '1', NULL, NULL, '2024-12-27', '14 día(s)', 1.450, 'Femenino', '2025-01-11 08:35:33', '2025-01-11 08:35:33'),
(1017, 'RN', 'DE LA CRUZ VICENCIO', 'UCIREN 2', NULL, '1', NULL, NULL, '2025-01-07', '3 día(s)', 1.780, 'Femenino', '2025-01-11 08:40:44', '2025-01-11 08:40:44'),
(1018, 'RN', 'JACOBO CARRILLO', 'UCIN', '5', '1', '70817', 'SEPSIS', '2024-12-18', '23 día(s)', 1.430, 'Femenino', '2025-01-11 09:01:34', '2025-01-11 09:32:51'),
(1019, 'RECIEN NACIDO', 'GARCIA RODRIGUEZ', 'NEONATOLOGÍA', 'AISLADO', '2', '70338', 'SEPSIS POR STAPHYLOCOCCUS AUREUS', '2024-11-08', '2 mes(es) 2 día(s)', 1.970, 'Femenino', '2025-01-11 09:04:37', '2025-01-11 09:04:37'),
(1020, 'RN', 'SANTIAGO CONTRERAS', 'UCIREN 1', NULL, '1', NULL, NULL, '2024-12-29', '12 día(s)', 1.810, 'Femenino', '2025-01-11 09:05:50', '2025-01-11 09:05:50'),
(1021, 'RN', 'LUCIANO HERNANDEZ G2', 'UCIREN 1', NULL, '1', NULL, NULL, '2024-12-16', '25 día(s)', 1.720, 'Femenino', '2025-01-11 09:10:21', '2025-01-11 09:10:21'),
(1022, 'RN', 'MEJIA SANABRIA', 'UCIN', NULL, NULL, '0114283', NULL, '2024-12-27', '14 día(s)', 2.020, 'Femenino', '2025-01-11 09:32:29', '2025-01-11 09:32:29'),
(1023, 'RN', 'GARCIA SANTIAGO', 'PEDIATRIA', NULL, NULL, NULL, NULL, '2024-12-19', '22 día(s)', 1.640, 'Masculino', '2025-01-11 09:36:48', '2025-01-11 09:36:48'),
(1024, 'RN', 'ARZATE LOPEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-31', '10 día(s)', 1.350, 'Masculino', '2025-01-11 09:53:42', '2025-01-11 09:53:42'),
(1025, 'RN', 'VILCHIS ALARCON', 'REANIMACION', NULL, 'PB', NULL, NULL, '2025-01-04', '6 día(s)', 0.695, 'Masculino', '2025-01-11 09:57:54', '2025-01-11 09:57:54'),
(1026, 'RN', 'COLIN COLIN', 'REANIMACIÓN', NULL, 'PB', NULL, NULL, '2025-01-07', '3 día(s)', 1.405, 'Masculino', '2025-01-11 10:01:59', '2025-01-11 10:01:59'),
(1027, 'RN', 'VELAZQUEZ ENRIQUEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-23', '18 día(s)', 2.315, 'Masculino', '2025-01-11 10:11:47', '2025-01-11 10:11:47'),
(1028, 'RN', 'DOMINGUEZ SANCHEZ', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-11-19', '1 mes(es) 21 día(s)', 2.140, 'Masculino', '2025-01-11 10:19:56', '2025-01-11 10:19:56'),
(1029, 'RN', 'MARTINEZ GUZMÁN', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-30', '11 día(s)', 1.520, 'Masculino', '2025-01-11 10:24:56', '2025-01-11 10:24:56'),
(1030, 'RN', 'VILLAGOMEZ GONZALEZ', 'UCIN', NULL, NULL, '247464', NULL, '2025-01-02', '8 día(s)', 0.880, NULL, '2025-01-11 10:26:25', '2025-01-11 10:26:25'),
(1031, 'RN', 'GONZALEZ CARMONA', 'EXTERNOS', NULL, '1', NULL, NULL, '2024-12-31', '10 día(s)', 2.090, 'Femenino', '2025-01-11 10:28:37', '2025-01-11 10:28:37'),
(1032, 'RN', 'HERNANDEZ FLORES', 'UCIN', NULL, NULL, '247078', NULL, '2024-12-21', '20 día(s)', 1.160, 'Masculino', '2025-01-11 10:29:48', '2025-01-11 10:29:48'),
(1033, 'RN', 'ANTONIO CAYETANO', 'INTERNOS', NULL, '1', NULL, NULL, '2024-10-07', '3 mes(es) 3 día(s)', 3.840, 'Femenino', '2025-01-11 10:32:14', '2025-01-11 10:32:14'),
(1034, 'RN', 'JUAREZ ORTIZ G1', 'INTERNOS', NULL, '1', NULL, NULL, '2024-12-27', '14 día(s)', 1.280, 'Masculino', '2025-01-11 10:36:12', '2025-01-11 10:36:12'),
(1035, 'RN', 'OLMEDO RICARDO', 'UTIN', '2', NULL, '246939', NULL, '2024-12-16', '25 día(s)', 3.605, 'Masculino', '2025-01-11 10:39:24', '2025-01-11 10:39:24'),
(1036, 'RN', 'VALENCIA HERNANDEZ', 'INTERNOS', NULL, '1', NULL, NULL, '2024-11-16', '1 mes(es) 24 día(s)', 1.558, 'Masculino', '2025-01-11 10:39:45', '2025-01-11 10:39:45'),
(1037, 'RN', 'VASQUEZ SANCHEZ', 'INTERNOS', NULL, '1', NULL, NULL, '2025-01-04', '6 día(s)', 1.400, 'Masculino', '2025-01-11 10:43:06', '2025-01-11 10:43:06'),
(1038, 'RN', 'ORDAZ GONZALEZ', 'UCIN', NULL, NULL, '24-8598', NULL, '2024-12-19', '22 día(s)', 1.450, NULL, '2025-01-11 10:46:40', '2025-01-11 10:46:40'),
(1039, 'RN', 'CARRASCO GALICIA', 'UCIN 1', NULL, NULL, '205688', NULL, '2025-01-08', '2 día(s)', 3.655, 'Femenino', '2025-01-11 10:47:18', '2025-01-11 10:47:18'),
(1040, 'RN', 'ROJAS ROJAS G1', 'INTERNOS', NULL, '1', NULL, NULL, '2024-12-26', '15 día(s)', 1.425, 'Masculino', '2025-01-11 10:49:06', '2025-01-11 10:49:06'),
(1041, 'RN', 'MATIAS HERNANDEZ', 'UCIN', NULL, NULL, '25-52', NULL, '2025-01-03', '7 día(s)', 1.190, 'Femenino', '2025-01-11 10:49:50', '2025-01-11 10:49:50'),
(1042, 'RN', 'MATIAS HERNANDEZ', 'UCIN', NULL, NULL, '25-52', NULL, '2025-01-03', '7 día(s)', 1.190, 'Femenino', '2025-01-11 10:52:48', '2025-01-11 10:52:48'),
(1043, 'RN', 'AVILES LOPEZ G2', 'INTERNOS', NULL, '1', NULL, NULL, '2024-12-31', '10 día(s)', 1.980, 'Masculino', '2025-01-11 10:53:19', '2025-01-11 10:53:19'),
(1044, 'RN', 'MAYA MORENO', 'INTERNOS', NULL, '1', NULL, NULL, '2025-01-06', '4 día(s)', 1.230, 'Masculino', '2025-01-11 10:56:59', '2025-01-11 10:56:59'),
(1045, 'RN', 'BECERRIL MENDOZA', 'INTERNOS', NULL, '1', NULL, NULL, '2025-01-05', '5 día(s)', 2.300, 'Masculino', '2025-01-11 11:00:39', '2025-01-11 11:00:39'),
(1046, 'RN', 'MATIAS HERNANDEZ', 'UCIN', NULL, NULL, '25-52', NULL, '2025-01-03', '7 día(s)', 1.190, 'Femenino', '2025-01-11 11:00:47', '2025-01-11 11:00:47'),
(1047, 'RN', 'INIESTA CAMACHO', 'INTERNOS', NULL, '1', NULL, NULL, '2025-01-08', '2 día(s)', 1.310, 'Masculino', '2025-01-11 11:04:38', '2025-01-11 11:04:38'),
(1048, 'RN', 'HERNANDEZ GARCIA', 'UCIN', NULL, NULL, '425753', NULL, '2025-01-09', '1 día(s)', 1.170, 'Masculino', '2025-01-11 11:31:43', '2025-01-11 11:31:43'),
(1049, 'RN', 'BERMUDEZ MEJIA', 'UCIN', NULL, NULL, '428055', NULL, '2024-12-09', '1 mes(es) 1 día(s)', 1.890, NULL, '2025-01-11 11:34:40', '2025-01-11 11:34:40'),
(1050, 'RN', 'RIVERA', 'UCIN', NULL, NULL, '464943', NULL, '2025-01-09', '5 mes(es) 7 día(s)', 1.420, 'Femenino', '2025-01-11 11:39:40', '2025-06-17 11:19:19'),
(1051, 'RN', 'ESPINOZA GARCIA', 'UCIN', NULL, NULL, NULL, NULL, '2025-01-01', '3 mes(es) 15 día(s)', 0.735, 'Masculino', '2025-01-11 11:43:01', '2025-04-17 13:58:11'),
(1052, 'RN', 'VELAZQUEZ DE JESUS', 'UCIREN-1', '5', '1', '120899', NULL, '2025-04-04', '5 mes(es) 28 día(s)', 2.280, 'Masculino', '2025-04-17 13:53:57', '2025-10-03 23:01:32'),
(1053, 'Angel', 'Rojkas', 'NP', '34', '2', NULL, NULL, '2025-02-05', '4 mes(es) 11 día(s)', 12.000, 'Femenino', '2025-04-17 14:04:03', '2025-06-17 11:16:14'),
(1054, 'ffdsfds', 'fdsfdsf', 'fdsfds', NULL, NULL, NULL, NULL, '2025-04-08', '14 día(s)', 32.000, 'Masculino', '2025-04-23 13:38:37', '2025-04-23 13:38:37'),
(1055, 'R/N', 'Mejia Borgua', 'infectologia', '5', '54', '69765', NULL, '2026-05-01', '17 día(s)', 1.880, 'Femenino', '2026-05-19 11:01:02', '2026-05-19 11:01:02'),
(1056, 'R/N', 'Mejia Borgua', 'infectologia', '5', '5', '69765', NULL, '2026-04-14', '1 mes(es) 5 día(s)', 1.880, 'Femenino', '2026-05-19 20:15:03', '2026-05-19 20:15:03'),
(1057, 'T1', 'HERNANDEZ GUILLEN', 'UCIN', '1', NULL, NULL, 'PREMATUREZ', '2026-06-15', '9 día(s)', 1.960, 'Femenino', '2026-06-24 20:11:50', '2026-06-24 20:11:50'),
(1058, 'MARIANA ESTRELLA VICTORIA', 'FLORES VARELA', 'TERAPIA INTENDIVA', 'UTI-8', '3', '1501540119', 'HEMOPERITONEO LACERACIÓN ARTERIAL HEPÁTICA', '1984-12-06', '41 año(s) 6 mes(es) 24 día(s)', 95.000, 'Femenino', '2026-06-30 19:18:32', '2026-06-30 19:18:32'),
(1059, 'RN', 'HERNANDEZ GUILLEN T3', 'UCIN', '3', '1', NULL, 'PRETERMINO', '2026-06-15', '17 día(s)', 1.850, 'Femenino', '2026-07-02 22:42:35', '2026-07-02 22:42:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `hospital_id` bigint UNSIGNED DEFAULT NULL,
  `notification` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `lastname`, `username`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `remember_token`, `is_active`, `created_at`, `updated_at`, `hospital_id`, `notification`) VALUES
(1, 'Itzel Azucena', 'Itzel Azucena', 'ItzelD', '$2y$12$Lxbfz4PvceGC8gRNaT5D9uCsrUUt30r15YMdHjbggQeF4LSys1hpm', NULL, NULL, NULL, 'tiJZ0ivJa41Dio9aqifFutOc41fIDBlJIC62ygj2qD5ZhiTHbLQgJnT90vgu', 1, '2024-02-22 06:37:46', '2025-10-12 11:36:25', 1, 0),
(3, 'Luis Angel', 'Rojas Espinoza', 'AngelR', '$2y$12$CvNCB4mILLHwURMX9Bp3NeQVRfOc5CqDJjPgeEguWidmLrazEjhgq', NULL, NULL, NULL, NULL, 1, '2024-04-11 23:43:13', '2025-04-23 13:38:40', 2, 922),
(4, 'Gabriela', 'Cortez', 'GCORTES', '$2y$12$uWCob9d1JTR8DWV.vAA4oufWy9k6tzJ96XAWp8drW5vk3UfuNb2y6', NULL, NULL, NULL, NULL, 1, '2026-05-19 21:54:17', '2026-07-21 18:02:51', 48, 0),
(5, 'FARMACIA INTRAHOSPITALARIA METROPOLITANO', 'METROPOLITANO', 'FARMACIAMETROPOLITANO', '$2y$12$BMtY82p/SEjnY9Y2enpFPOWsM/BA.TsYDcWGTsqM.DFCNawTD746K', NULL, NULL, NULL, NULL, 1, '2026-05-29 05:27:27', '2026-07-23 11:54:20', 2, 0),
(6, 'HOSPITAL', 'SAN DIEGO', 'HOSPITALSANDIEGO', '$2y$12$doa5qaZRhms6jHWyUEk.keeCFtnSHDx94tL.n73EiGUyUuxNzHFhO', NULL, NULL, NULL, NULL, 1, '2026-06-24 19:58:07', '2026-06-24 19:58:07', 47, 0),
(7, 'HANNIA RUBI', 'CARBAJAL SALAZAR', 'HCARBAJAL', '$2y$12$/BhvPnX.ndUSpLEWV2PnzeUf1RwbOW/wc5YcQ1WjZwTO9Ptc0uRo2', NULL, NULL, NULL, NULL, 1, '2026-06-24 20:55:44', '2026-07-21 18:02:34', 48, 0),
(8, 'Cristian David', 'Guerrero Jorge', 'CGUERRERO', '$2y$12$5XKYO0ik1d6PuEBR.kWv3.RuoEuhTQcyXHdy00Z2lcNFZEHLE5kNK', NULL, NULL, NULL, NULL, 1, '2026-06-24 21:47:23', '2026-07-03 16:58:25', 48, 0),
(9, 'PAULINA FABIOLA', 'VAZQUEZ GOMEZ', 'PVAZQUEZ', '$2y$12$WhHhJr9E7DVatOEBaRAGC.5q92NfGjOK/3lPAkQgqLI/lBt99Q2KO', NULL, NULL, NULL, NULL, 1, '2026-07-02 22:49:17', '2026-07-23 11:45:13', 48, 0),
(10, 'Guillermo', 'Guerrero', 'guillermog', '$2y$12$S4a248Jt1.3Mfv8QalSa0eozw0vWDK0dc5fIyCeUT/.HweF/U646G', NULL, NULL, NULL, NULL, 1, '2026-07-16 17:52:40', '2026-07-16 17:52:40', 1, 0),
(11, 'LUIS ADOLFO', 'MORALES HERNANDEZ', 'LMORALES', '$2y$12$E3dmL3wcKuu3/0SOKmM3f.WBiA1HMCNo5ACDXLTUI20ymOCJwUu.2', NULL, NULL, NULL, NULL, 1, '2026-08-01 16:09:38', '2026-08-01 16:15:14', 48, 0);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administration_routes`
--
ALTER TABLE `administration_routes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `administration_route_medicine_catalog`
--
ALTER TABLE `administration_route_medicine_catalog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_route_fk` (`administration_route_id`),
  ADD KEY `med_catalog_fk` (`medicine_catalog_id`);

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clientes_razon_social_nombre_index` (`razon_social`,`nombre`),
  ADD KEY `clientes_rfc_index` (`rfc`);

--
-- Indices de la tabla `cliente_hospital`
--
ALTER TABLE `cliente_hospital`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cliente_hospital_cliente_id_hospital_id_unique` (`cliente_id`,`hospital_id`),
  ADD KEY `cliente_hospital_cliente_id_index` (`cliente_id`),
  ADD KEY `cliente_hospital_hospital_id_index` (`hospital_id`);

--
-- Indices de la tabla `diluents`
--
ALTER TABLE `diluents`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `diluent_medicine_catalog`
--
ALTER TABLE `diluent_medicine_catalog`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `diluent_medicine_catalog_diluent_id_medicine_catalog_id_unique` (`diluent_id`,`medicine_catalog_id`),
  ADD KEY `diluent_medicine_catalog_medicine_catalog_id_foreign` (`medicine_catalog_id`);

--
-- Indices de la tabla `diluent_presentations`
--
ALTER TABLE `diluent_presentations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `diluent_presentations_caducidad_index` (`caducidad`),
  ADD KEY `diluent_presentations_lote_index` (`lote`),
  ADD KEY `diluent_presentations_diluent_id_idx` (`diluent_id`),
  ADD KEY `diluent_presentations_laboratory_id_foreign` (`laboratory_id`);

--
-- Indices de la tabla `diluent_stock_movements`
--
ALTER TABLE `diluent_stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `diluent_stock_movements_laboratory_id_foreign` (`laboratory_id`),
  ADD KEY `diluent_stock_movements_user_id_foreign` (`user_id`),
  ADD KEY `dsm_presentation_type_idx` (`diluent_presentation_id`,`movement_type`),
  ADD KEY `dsm_reference_idx` (`reference_type`,`reference_id`);

--
-- Indices de la tabla `distributors`
--
ALTER TABLE `distributors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `distributors_medicine_list_id_foreign` (`medicine_list_id`);

--
-- Indices de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indices de la tabla `hospitals`
--
ALTER TABLE `hospitals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hospitals_laboratory_id_foreign` (`laboratory_id`),
  ADD KEY `hospitals_nutri_medicine_list_id_foreign` (`nutri_medicine_list_id`),
  ADD KEY `hospitals_onco_medicine_list_id_foreign` (`onco_medicine_list_id`);

--
-- Indices de la tabla `infusors`
--
ALTER TABLE `infusors`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inputs`
--
ALTER TABLE `inputs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inputs_category_id_foreign` (`category_id`);

--
-- Indices de la tabla `inspeccion_mezclas`
--
ALTER TABLE `inspeccion_mezclas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inspeccion_mezclas_mezcla_id_foreign` (`mezcla_id`);

--
-- Indices de la tabla `inspeccion_nutricionales`
--
ALTER TABLE `inspeccion_nutricionales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inspeccion_nutricionales_solicitud_id_foreign` (`solicitud_id`);

--
-- Indices de la tabla `institution_billings`
--
ALTER TABLE `institution_billings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `institution_billings_origen_unique` (`origen_tipo`,`origen_id`),
  ADD KEY `institution_billings_hospital_id_foreign` (`hospital_id`),
  ADD KEY `institution_billings_inst_hosp_index` (`institucion_id`,`hospital_id`);

--
-- Indices de la tabla `laboratories`
--
ALTER TABLE `laboratories`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicines_category_id_foreign` (`category_id`),
  ADD KEY `medicines_input_id_foreign` (`input_id`);

--
-- Indices de la tabla `medicines_catalog`
--
ALTER TABLE `medicines_catalog`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `medicine_batches`
--
ALTER TABLE `medicine_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_batch_per_lab_presentation` (`laboratory_id`,`medicine_presentation_id`,`lote`),
  ADD KEY `medicine_batches_medicine_presentation_id_foreign` (`medicine_presentation_id`),
  ADD KEY `idx_batch_lab_pres_current` (`laboratory_id`,`medicine_presentation_id`,`is_current`),
  ADD KEY `idx_batch_lab_exp` (`laboratory_id`,`caducidad`),
  ADD KEY `idx_batch_exp` (`caducidad`),
  ADD KEY `idx_batch_lab_pres_active` (`laboratory_id`,`medicine_presentation_id`,`is_active`),
  ADD KEY `idx_batch_lab_stock` (`laboratory_id`,`stock_actual`);

--
-- Indices de la tabla `medicine_batch_movements`
--
ALTER TABLE `medicine_batch_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mbm_batch_date` (`medicine_batch_id`,`created_at`),
  ADD KEY `idx_mbm_lab_date` (`laboratory_id`,`created_at`),
  ADD KEY `idx_mbm_type` (`movement_type`),
  ADD KEY `idx_mbm_reference` (`reference_type`,`reference_id`),
  ADD KEY `idx_mbm_user` (`user_id`);

--
-- Indices de la tabla `medicine_laboratory_stocks`
--
ALTER TABLE `medicine_laboratory_stocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mls_presentation_lab_lote_unique` (`nutrition_medicine_presentation_id`,`laboratory_id`,`lote`),
  ADD KEY `medicine_laboratory_stocks_laboratory_id_foreign` (`laboratory_id`);

--
-- Indices de la tabla `medicine_lists`
--
ALTER TABLE `medicine_lists`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `medicine_list_presentation`
--
ALTER TABLE `medicine_list_presentation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mlp_unique` (`medicine_list_id`,`medicine_presentation_id`),
  ADD KEY `medicine_list_presentation_medicine_presentation_id_foreign` (`medicine_presentation_id`);

--
-- Indices de la tabla `medicine_medicine_lists`
--
ALTER TABLE `medicine_medicine_lists`
  ADD PRIMARY KEY (`medicine_list_id`,`medicine_id`),
  ADD KEY `medicine_medicine_lists_medicine_id_foreign` (`medicine_id`);

--
-- Indices de la tabla `medicine_oncos`
--
ALTER TABLE `medicine_oncos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_oncos_catalog_id_foreign` (`catalog_id`);

--
-- Indices de la tabla `medicine_presentations`
--
ALTER TABLE `medicine_presentations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_onco_catalog_presentacion_marca` (`catalog_id`,`presentacion`,`marca`),
  ADD KEY `medicine_presentations_catalog_id_index` (`catalog_id`),
  ADD KEY `medicine_presentations_is_available_index` (`is_available`);

--
-- Indices de la tabla `medicine_stock_movements`
--
ALTER TABLE `medicine_stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_stock_movements_medicine_laboratory_stock_id_foreign` (`medicine_laboratory_stock_id`),
  ADD KEY `medicine_stock_movements_user_id_foreign` (`user_id`),
  ADD KEY `medicine_stock_movements_reference_index` (`reference_type`,`reference_id`),
  ADD KEY `medicine_stock_movements_tipo_index` (`tipo`);

--
-- Indices de la tabla `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `messages_sender_id_foreign` (`sender_id`);

--
-- Indices de la tabla `mezclas`
--
ALTER TABLE `mezclas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mezclas_solicitud_id_foreign` (`solicitud_id`),
  ADD KEY `mezclas_infusor_id_foreign` (`infusor_id`),
  ADD KEY `mezclas_diluent_presentation_id_foreign` (`diluent_presentation_id`);

--
-- Indices de la tabla `mezcla_medicamentos`
--
ALTER TABLE `mezcla_medicamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mezcla_medicamentos_mezcla_id_foreign` (`mezcla_id`),
  ADD KEY `mezcla_medicamentos_medicamento_id_foreign` (`medicamento_id`),
  ADD KEY `mezcla_medicamentos_diluyente_id_foreign` (`diluyente_id`),
  ADD KEY `mezcla_medicamentos_via_administracion_id_foreign` (`via_administracion_id`);

--
-- Indices de la tabla `mezcla_medicamento_presentaciones`
--
ALTER TABLE `mezcla_medicamento_presentaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mezcla_medicamento_presentaciones_mezcla_medicamento_id_index` (`mezcla_medicamento_id`),
  ADD KEY `mezcla_medicamento_presentaciones_medicine_batch_id_index` (`medicine_batch_id`),
  ADD KEY `mezcla_medicamento_presentaciones_lote_usado_index` (`lote_usado`),
  ADD KEY `mezcla_medicamento_presentaciones_caducidad_usada_index` (`caducidad_usada`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indices de la tabla `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indices de la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indices de la tabla `nutrition_laboratory_active_presentations`
--
ALTER TABLE `nutrition_laboratory_active_presentations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nlap_lab_catalog_date_unique` (`laboratory_id`,`nutrition_medicine_catalog_id`,`selected_date`),
  ADD KEY `nlap_catalog_fk` (`nutrition_medicine_catalog_id`),
  ADD KEY `nlap_presentation_fk` (`nutrition_medicine_presentation_id`);

--
-- Indices de la tabla `nutrition_medicines_catalog`
--
ALTER TABLE `nutrition_medicines_catalog`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nmc_input_unique` (`input_id`),
  ADD KEY `nutrition_medicines_catalog_category_id_foreign` (`category_id`),
  ADD KEY `nutrition_medicines_catalog_denominacion_generica_index` (`denominacion_generica`);

--
-- Indices de la tabla `nutrition_medicine_presentations`
--
ALTER TABLE `nutrition_medicine_presentations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_nutri_catalog_comercial_presentacion` (`nutrition_medicine_catalog_id`,`denominacion_comercial`,`presentacion`),
  ADD KEY `nutrition_medicine_presentations_denominacion_comercial_index` (`denominacion_comercial`),
  ADD KEY `nmp_catalog_idx` (`nutrition_medicine_catalog_id`);

--
-- Indices de la tabla `nutri_distributors`
--
ALTER TABLE `nutri_distributors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nutri_distributors_nutri_medicine_list_id_unique` (`nutri_medicine_list_id`);

--
-- Indices de la tabla `nutri_medicine_lists`
--
ALTER TABLE `nutri_medicine_lists`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `nutri_medicine_list_items`
--
ALTER TABLE `nutri_medicine_list_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nutri_list_item_unique_presentation` (`nutri_medicine_list_id`,`nutrition_medicine_presentation_id`),
  ADD KEY `nml_items_presentation_fk` (`nutrition_medicine_presentation_id`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indices de la tabla `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indices de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indices de la tabla `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indices de la tabla `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indices de la tabla `solicituds`
--
ALTER TABLE `solicituds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `solicituds_user_id_foreign` (`user_id`),
  ADD KEY `solicituds_solicitud_detail_id_foreign` (`solicitud_detail_id`),
  ADD KEY `solicituds_solicitud_patient_id_foreign` (`solicitud_patient_id`),
  ADD KEY `solicituds_estado_index` (`estado`),
  ADD KEY `solicituds_lote_index` (`lote`),
  ADD KEY `solicituds_remision_index` (`remision`);

--
-- Indices de la tabla `solicitud_details`
--
ALTER TABLE `solicitud_details`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `solicitud_inputs`
--
ALTER TABLE `solicitud_inputs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `solicitud_inputs_input_id_foreign` (`input_id`),
  ADD KEY `solicitud_inputs_solicitud_id_foreign` (`solicitud_id`),
  ADD KEY `solicitud_inputs_nutrition_medicine_presentation_id_foreign` (`nutrition_medicine_presentation_id`);

--
-- Indices de la tabla `solicitud_oncos`
--
ALTER TABLE `solicitud_oncos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `solicitud_oncos_hospital_id_foreign` (`hospital_id`);

--
-- Indices de la tabla `solicitud_patients`
--
ALTER TABLE `solicitud_patients`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `users_hospital_id_foreign` (`hospital_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administration_routes`
--
ALTER TABLE `administration_routes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `administration_route_medicine_catalog`
--
ALTER TABLE `administration_route_medicine_catalog`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cliente_hospital`
--
ALTER TABLE `cliente_hospital`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `diluents`
--
ALTER TABLE `diluents`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `diluent_medicine_catalog`
--
ALTER TABLE `diluent_medicine_catalog`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT de la tabla `diluent_presentations`
--
ALTER TABLE `diluent_presentations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `diluent_stock_movements`
--
ALTER TABLE `diluent_stock_movements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `distributors`
--
ALTER TABLE `distributors`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `hospitals`
--
ALTER TABLE `hospitals`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de la tabla `infusors`
--
ALTER TABLE `infusors`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inputs`
--
ALTER TABLE `inputs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de la tabla `inspeccion_mezclas`
--
ALTER TABLE `inspeccion_mezclas`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `inspeccion_nutricionales`
--
ALTER TABLE `inspeccion_nutricionales`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `institution_billings`
--
ALTER TABLE `institution_billings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `laboratories`
--
ALTER TABLE `laboratories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de la tabla `medicines_catalog`
--
ALTER TABLE `medicines_catalog`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT de la tabla `medicine_batches`
--
ALTER TABLE `medicine_batches`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT de la tabla `medicine_batch_movements`
--
ALTER TABLE `medicine_batch_movements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT de la tabla `medicine_laboratory_stocks`
--
ALTER TABLE `medicine_laboratory_stocks`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `medicine_lists`
--
ALTER TABLE `medicine_lists`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `medicine_list_presentation`
--
ALTER TABLE `medicine_list_presentation`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT de la tabla `medicine_oncos`
--
ALTER TABLE `medicine_oncos`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `medicine_presentations`
--
ALTER TABLE `medicine_presentations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

--
-- AUTO_INCREMENT de la tabla `medicine_stock_movements`
--
ALTER TABLE `medicine_stock_movements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `mezclas`
--
ALTER TABLE `mezclas`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `mezcla_medicamentos`
--
ALTER TABLE `mezcla_medicamentos`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de la tabla `mezcla_medicamento_presentaciones`
--
ALTER TABLE `mezcla_medicamento_presentaciones`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT de la tabla `nutrition_laboratory_active_presentations`
--
ALTER TABLE `nutrition_laboratory_active_presentations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `nutrition_medicines_catalog`
--
ALTER TABLE `nutrition_medicines_catalog`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de la tabla `nutrition_medicine_presentations`
--
ALTER TABLE `nutrition_medicine_presentations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de la tabla `nutri_distributors`
--
ALTER TABLE `nutri_distributors`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `nutri_medicine_lists`
--
ALTER TABLE `nutri_medicine_lists`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `nutri_medicine_list_items`
--
ALTER TABLE `nutri_medicine_list_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=274;

--
-- AUTO_INCREMENT de la tabla `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `solicituds`
--
ALTER TABLE `solicituds`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `solicitud_details`
--
ALTER TABLE `solicitud_details`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `solicitud_inputs`
--
ALTER TABLE `solicitud_inputs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- AUTO_INCREMENT de la tabla `solicitud_oncos`
--
ALTER TABLE `solicitud_oncos`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `solicitud_patients`
--
ALTER TABLE `solicitud_patients`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1060;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `administration_route_medicine_catalog`
--
ALTER TABLE `administration_route_medicine_catalog`
  ADD CONSTRAINT `admin_route_fk` FOREIGN KEY (`administration_route_id`) REFERENCES `administration_routes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `med_catalog_fk` FOREIGN KEY (`medicine_catalog_id`) REFERENCES `medicines_catalog` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cliente_hospital`
--
ALTER TABLE `cliente_hospital`
  ADD CONSTRAINT `cliente_hospital_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cliente_hospital_hospital_id_foreign` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `diluent_medicine_catalog`
--
ALTER TABLE `diluent_medicine_catalog`
  ADD CONSTRAINT `diluent_medicine_catalog_diluent_id_foreign` FOREIGN KEY (`diluent_id`) REFERENCES `diluents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `diluent_medicine_catalog_medicine_catalog_id_foreign` FOREIGN KEY (`medicine_catalog_id`) REFERENCES `medicines_catalog` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `diluent_presentations`
--
ALTER TABLE `diluent_presentations`
  ADD CONSTRAINT `diluent_presentations_diluent_id_foreign` FOREIGN KEY (`diluent_id`) REFERENCES `diluents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `diluent_presentations_laboratory_id_foreign` FOREIGN KEY (`laboratory_id`) REFERENCES `laboratories` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `diluent_stock_movements`
--
ALTER TABLE `diluent_stock_movements`
  ADD CONSTRAINT `diluent_stock_movements_diluent_presentation_id_foreign` FOREIGN KEY (`diluent_presentation_id`) REFERENCES `diluent_presentations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `diluent_stock_movements_laboratory_id_foreign` FOREIGN KEY (`laboratory_id`) REFERENCES `laboratories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `diluent_stock_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `distributors`
--
ALTER TABLE `distributors`
  ADD CONSTRAINT `distributors_medicine_list_id_foreign` FOREIGN KEY (`medicine_list_id`) REFERENCES `medicine_lists` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `hospitals`
--
ALTER TABLE `hospitals`
  ADD CONSTRAINT `hospitals_laboratory_id_foreign` FOREIGN KEY (`laboratory_id`) REFERENCES `laboratories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hospitals_nutri_medicine_list_id_foreign` FOREIGN KEY (`nutri_medicine_list_id`) REFERENCES `nutri_medicine_lists` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hospitals_onco_medicine_list_id_foreign` FOREIGN KEY (`onco_medicine_list_id`) REFERENCES `medicine_lists` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `inputs`
--
ALTER TABLE `inputs`
  ADD CONSTRAINT `inputs_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Filtros para la tabla `inspeccion_mezclas`
--
ALTER TABLE `inspeccion_mezclas`
  ADD CONSTRAINT `inspeccion_mezclas_mezcla_id_foreign` FOREIGN KEY (`mezcla_id`) REFERENCES `mezclas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `inspeccion_nutricionales`
--
ALTER TABLE `inspeccion_nutricionales`
  ADD CONSTRAINT `inspeccion_nutricionales_solicitud_id_foreign` FOREIGN KEY (`solicitud_id`) REFERENCES `solicituds` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `institution_billings`
--
ALTER TABLE `institution_billings`
  ADD CONSTRAINT `institution_billings_hospital_id_foreign` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `institution_billings_institucion_id_foreign` FOREIGN KEY (`institucion_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `medicines_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `medicines_input_id_foreign` FOREIGN KEY (`input_id`) REFERENCES `inputs` (`id`);

--
-- Filtros para la tabla `medicine_batches`
--
ALTER TABLE `medicine_batches`
  ADD CONSTRAINT `medicine_batches_laboratory_id_foreign` FOREIGN KEY (`laboratory_id`) REFERENCES `laboratories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medicine_batches_medicine_presentation_id_foreign` FOREIGN KEY (`medicine_presentation_id`) REFERENCES `medicine_presentations` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `medicine_batch_movements`
--
ALTER TABLE `medicine_batch_movements`
  ADD CONSTRAINT `medicine_batch_movements_laboratory_id_foreign` FOREIGN KEY (`laboratory_id`) REFERENCES `laboratories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medicine_batch_movements_medicine_batch_id_foreign` FOREIGN KEY (`medicine_batch_id`) REFERENCES `medicine_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medicine_batch_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `medicine_laboratory_stocks`
--
ALTER TABLE `medicine_laboratory_stocks`
  ADD CONSTRAINT `medicine_laboratory_stocks_laboratory_id_foreign` FOREIGN KEY (`laboratory_id`) REFERENCES `laboratories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mls_presentation_fk` FOREIGN KEY (`nutrition_medicine_presentation_id`) REFERENCES `nutrition_medicine_presentations` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `medicine_list_presentation`
--
ALTER TABLE `medicine_list_presentation`
  ADD CONSTRAINT `medicine_list_presentation_medicine_list_id_foreign` FOREIGN KEY (`medicine_list_id`) REFERENCES `medicine_lists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medicine_list_presentation_medicine_presentation_id_foreign` FOREIGN KEY (`medicine_presentation_id`) REFERENCES `medicine_presentations` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `medicine_medicine_lists`
--
ALTER TABLE `medicine_medicine_lists`
  ADD CONSTRAINT `medicine_medicine_lists_medicine_id_foreign` FOREIGN KEY (`medicine_id`) REFERENCES `medicine_oncos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medicine_medicine_lists_medicine_list_id_foreign` FOREIGN KEY (`medicine_list_id`) REFERENCES `medicine_lists` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `medicine_oncos`
--
ALTER TABLE `medicine_oncos`
  ADD CONSTRAINT `medicine_oncos_catalog_id_foreign` FOREIGN KEY (`catalog_id`) REFERENCES `medicines_catalog` (`id`);

--
-- Filtros para la tabla `medicine_presentations`
--
ALTER TABLE `medicine_presentations`
  ADD CONSTRAINT `medicine_presentations_catalog_id_foreign` FOREIGN KEY (`catalog_id`) REFERENCES `medicines_catalog` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `medicine_stock_movements`
--
ALTER TABLE `medicine_stock_movements`
  ADD CONSTRAINT `medicine_stock_movements_medicine_laboratory_stock_id_foreign` FOREIGN KEY (`medicine_laboratory_stock_id`) REFERENCES `medicine_laboratory_stocks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medicine_stock_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `mezclas`
--
ALTER TABLE `mezclas`
  ADD CONSTRAINT `mezclas_diluent_presentation_id_foreign` FOREIGN KEY (`diluent_presentation_id`) REFERENCES `diluent_presentations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mezclas_infusor_id_foreign` FOREIGN KEY (`infusor_id`) REFERENCES `infusors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mezclas_solicitud_id_foreign` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitud_oncos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mezcla_medicamentos`
--
ALTER TABLE `mezcla_medicamentos`
  ADD CONSTRAINT `mezcla_medicamentos_diluyente_id_foreign` FOREIGN KEY (`diluyente_id`) REFERENCES `diluents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mezcla_medicamentos_medicamento_id_foreign` FOREIGN KEY (`medicamento_id`) REFERENCES `medicine_oncos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mezcla_medicamentos_mezcla_id_foreign` FOREIGN KEY (`mezcla_id`) REFERENCES `mezclas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mezcla_medicamentos_via_administracion_id_foreign` FOREIGN KEY (`via_administracion_id`) REFERENCES `administration_routes` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `mezcla_medicamento_presentaciones`
--
ALTER TABLE `mezcla_medicamento_presentaciones`
  ADD CONSTRAINT `mezcla_medicamento_presentaciones_medicine_batch_id_foreign` FOREIGN KEY (`medicine_batch_id`) REFERENCES `medicine_batches` (`id`),
  ADD CONSTRAINT `mezcla_medicamento_presentaciones_mezcla_medicamento_id_foreign` FOREIGN KEY (`mezcla_medicamento_id`) REFERENCES `mezcla_medicamentos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `nutrition_laboratory_active_presentations`
--
ALTER TABLE `nutrition_laboratory_active_presentations`
  ADD CONSTRAINT `nlap_catalog_fk` FOREIGN KEY (`nutrition_medicine_catalog_id`) REFERENCES `nutrition_medicines_catalog` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nlap_laboratory_fk` FOREIGN KEY (`laboratory_id`) REFERENCES `laboratories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nlap_presentation_fk` FOREIGN KEY (`nutrition_medicine_presentation_id`) REFERENCES `nutrition_medicine_presentations` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `nutrition_medicines_catalog`
--
ALTER TABLE `nutrition_medicines_catalog`
  ADD CONSTRAINT `nutrition_medicines_catalog_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nutrition_medicines_catalog_input_id_foreign` FOREIGN KEY (`input_id`) REFERENCES `inputs` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `nutrition_medicine_presentations`
--
ALTER TABLE `nutrition_medicine_presentations`
  ADD CONSTRAINT `nmp_catalog_fk` FOREIGN KEY (`nutrition_medicine_catalog_id`) REFERENCES `nutrition_medicines_catalog` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `nutri_distributors`
--
ALTER TABLE `nutri_distributors`
  ADD CONSTRAINT `nutri_distributors_nutri_medicine_list_id_foreign` FOREIGN KEY (`nutri_medicine_list_id`) REFERENCES `nutri_medicine_lists` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `nutri_medicine_list_items`
--
ALTER TABLE `nutri_medicine_list_items`
  ADD CONSTRAINT `nml_items_presentation_fk` FOREIGN KEY (`nutrition_medicine_presentation_id`) REFERENCES `nutrition_medicine_presentations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nutri_medicine_list_items_nutri_medicine_list_id_foreign` FOREIGN KEY (`nutri_medicine_list_id`) REFERENCES `nutri_medicine_lists` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `solicituds`
--
ALTER TABLE `solicituds`
  ADD CONSTRAINT `solicituds_solicitud_detail_id_foreign` FOREIGN KEY (`solicitud_detail_id`) REFERENCES `solicitud_details` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `solicituds_solicitud_patient_id_foreign` FOREIGN KEY (`solicitud_patient_id`) REFERENCES `solicitud_patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `solicituds_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `solicitud_inputs`
--
ALTER TABLE `solicitud_inputs`
  ADD CONSTRAINT `solicitud_inputs_input_id_foreign` FOREIGN KEY (`input_id`) REFERENCES `inputs` (`id`),
  ADD CONSTRAINT `solicitud_inputs_nutrition_medicine_presentation_id_foreign` FOREIGN KEY (`nutrition_medicine_presentation_id`) REFERENCES `nutrition_medicine_presentations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `solicitud_inputs_solicitud_id_foreign` FOREIGN KEY (`solicitud_id`) REFERENCES `solicituds` (`id`);

--
-- Filtros para la tabla `solicitud_oncos`
--
ALTER TABLE `solicitud_oncos`
  ADD CONSTRAINT `solicitud_oncos_hospital_id_foreign` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_hospital_id_foreign` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
