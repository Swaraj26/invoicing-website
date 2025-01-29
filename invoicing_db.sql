-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 28, 2025 at 04:26 AM
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
-- Database: `invoicing_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_gstin` varchar(15) DEFAULT NULL,
  `commodity` varchar(255) DEFAULT NULL,
  `taxable_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cgst` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sgst` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `invoice_type` enum('SALE','PURCHASE') NOT NULL DEFAULT 'SALE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `invoice_date`, `customer_name`, `customer_gstin`, `commodity`, `taxable_amount`, `cgst`, `sgst`, `total`, `created_at`, `invoice_type`) VALUES
(1, 'SSPL/01/23-24', '2023-04-21', 'ADVY CHEMICAL PVT. LTD', '27AAICA9203E1ZV', NULL, 169920.00, 15292.80, 15292.80, 200505.60, '2025-01-24 22:26:47', 'SALE'),
(2, 'CC/2386', '2023-07-25', 'S M PHARMA', '27ARPPR8169M1ZL', 'MEDICINE', 20110.42, 1206.63, 1206.63, 22523.68, '2025-01-24 22:39:35', 'PURCHASE'),
(3, 'SSPL/02/23-24', '2023-07-21', 'ADVY CHEMICAL PVT. LTD', '27AAICA9203E1ZV', NULL, 1577467.00, 94648.02, 94648.02, 1766763.04, '2025-01-24 22:40:45', 'SALE'),
(4, '1494', '2023-07-10', 'SHREENATH TRADERS', '27CBLPK3863K1Z4', 'PIPE', 36339.00, 3270.51, 3270.51, 42880.02, '2025-01-24 22:48:41', 'PURCHASE');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `rate` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
