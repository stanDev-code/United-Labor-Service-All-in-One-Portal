-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 21, 2026 at 01:30 PM
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
-- Database: `ulsc_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `breakdowns`
--

CREATE TABLE `breakdowns` (
  `id` int(11) NOT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `date_reported` date NOT NULL,
  `location` varchar(150) DEFAULT NULL,
  `category` enum('Mechanical Failure','Electrical Failure','Wear and Tear','Misuse / Improper Operation','Lack of Maintenance','Other') NOT NULL,
  `category_other` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `detailed_findings` text DEFAULT NULL,
  `parts_cost` decimal(12,2) DEFAULT NULL,
  `labor_cost` decimal(12,2) DEFAULT NULL,
  `other_costs` decimal(12,2) DEFAULT NULL,
  `total_repair_cost` decimal(12,2) DEFAULT NULL,
  `replacement_recommendation` varchar(50) DEFAULT NULL,
  `replacement_remarks` text DEFAULT NULL,
  `usage_reminders` text DEFAULT NULL,
  `misuse_recommendations` text DEFAULT NULL,
  `misuse_remarks` text DEFAULT NULL,
  `repair_action` text DEFAULT NULL,
  `downtime_minutes` int(11) DEFAULT 0,
  `status` enum('Ongoing','Resolved') NOT NULL DEFAULT 'Ongoing',
  `report_by_name` varchar(100) DEFAULT NULL,
  `checked_by` varchar(100) DEFAULT NULL,
  `prepared_by` varchar(100) DEFAULT NULL,
  `prepared_position` varchar(100) DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `vehicle_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `breakdowns`
--

INSERT INTO `breakdowns` (`id`, `equipment_id`, `date_reported`, `location`, `category`, `category_other`, `description`, `detailed_findings`, `parts_cost`, `labor_cost`, `other_costs`, `total_repair_cost`, `replacement_recommendation`, `replacement_remarks`, `usage_reminders`, `misuse_recommendations`, `misuse_remarks`, `repair_action`, `downtime_minutes`, `status`, `report_by_name`, `checked_by`, `prepared_by`, `prepared_position`, `approved_by`, `reported_by`, `created_at`, `updated_at`, `vehicle_id`) VALUES
(3, NULL, '2026-02-21', 'LUNA TUY BATANGAS', 'Electrical Failure', '', 'asdasd', 'asdasd', 500.00, 500.00, 300.00, 1300.00, 'Not Required', '', 'asd', 'Operator Re-orientation / Training', 'asdasd', 'asdasd', 120, 'Ongoing', 'asdasd', 'asdasd', 'asd', 'asd', 'asdasd', 1, '2026-02-21 04:22:50', '2026-02-21 04:22:50', 8);

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int(11) NOT NULL,
  `item_id` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `category` enum('Vehicle','Equipment') NOT NULL,
  `plate_number` varchar(20) DEFAULT NULL,
  `assigned_operator_id` int(11) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `status` enum('Operational','Under Repair','Out of Service') NOT NULL DEFAULT 'Operational',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`id`, `item_id`, `name`, `model`, `category`, `plate_number`, `assigned_operator_id`, `purchase_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(7, '', 'Keyboard', 'Attack Shark', 'Equipment', NULL, NULL, '2026-02-26', 'Operational', '', '2026-02-21 04:18:22', '2026-02-21 04:55:09'),
(10, 'EQ0002', 'Vape', 'Black', 'Equipment', NULL, NULL, '2026-02-21', 'Operational', '', '2026-02-21 04:19:59', '2026-02-21 04:19:59'),
(11, 'EQ0003', 'Vape', 'v2', 'Equipment', NULL, NULL, '2026-02-28', 'Operational', '', '2026-02-21 04:20:46', '2026-02-21 04:20:46');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_records`
--

CREATE TABLE `maintenance_records` (
  `id` int(11) NOT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `date_performed` date NOT NULL,
  `maintenance_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `performed_by` varchar(100) NOT NULL,
  `total_price` decimal(12,2) DEFAULT NULL,
  `price_breakdown` text DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `vehicle_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_records`
--

INSERT INTO `maintenance_records` (`id`, `equipment_id`, `date_performed`, `maintenance_type`, `description`, `performed_by`, `total_price`, `price_breakdown`, `next_maintenance_date`, `notes`, `created_by`, `created_at`, `updated_at`, `vehicle_id`) VALUES
(9, NULL, '2026-02-17', 'Spark Plug', '', 'Charles Vulcanizing', 300.00, NULL, '2026-05-18', '', 1, '2026-02-21 04:21:36', '2026-02-21 04:21:36', 8),
(10, 7, '2026-03-20', 'Change Oil', '', 'Charles Vulcanizing', 500.00, NULL, NULL, '', 1, '2026-02-21 04:21:57', '2026-02-21 04:23:32', NULL),
(11, 7, '2026-04-21', 'Change Oil', '', 'Charles Vulcanizing', 300.00, NULL, NULL, '', 1, '2026-02-21 04:25:20', '2026-02-21 04:25:20', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tools`
--

CREATE TABLE `tools` (
  `id` int(11) NOT NULL,
  `tool_name` varchar(150) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `condition_status` enum('Good','Fair','Poor','Needs Replacement') NOT NULL DEFAULT 'Good',
  `assigned_to_id` int(11) DEFAULT NULL,
  `last_inspection_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$LQ/Zb7meGgxkLMb2m1VosOmfbXQ0SKZWw9uoc9qLJ6xeL2/j00oOi', 'System Administrator', NULL, 'admin', 1, '2026-02-15 17:41:31', '2026-02-16 02:31:49'),
(2, 'stan', '$2y$10$DpinyW42IHwxMiYgsmam/uhwuY4Tcr/CQh5Bc8dPJD7QUTGiqegdO', 'Stanley Garcia', 'stan@gmail.com', 'staff', 1, '2026-02-15 17:47:24', '2026-02-17 11:36:07');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `location` varchar(150) DEFAULT NULL,
  `last_oil_change_date` date DEFAULT NULL,
  `next_oil_due_date` date DEFAULT NULL,
  `status` enum('Operational','Under Repair','Out of Service') NOT NULL DEFAULT 'Operational',
  `facilitated_by` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `name`, `plate_number`, `location`, `last_oil_change_date`, `next_oil_due_date`, `status`, `facilitated_by`, `purchase_date`, `notes`, `created_at`, `updated_at`) VALUES
(8, 'HONDA CIVIC', 'QWE345', 'LUNA TUY BATANGAS', '2026-02-23', '2026-05-23', 'Operational', NULL, '2026-02-01', '', '2026-02-21 04:21:15', '2026-02-21 04:21:23');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_oil_changes`
--

CREATE TABLE `vehicle_oil_changes` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `change_date` date NOT NULL,
  `next_due_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_oil_changes`
--

INSERT INTO `vehicle_oil_changes` (`id`, `vehicle_id`, `change_date`, `next_due_date`, `notes`, `created_at`) VALUES
(8, 8, '2026-02-23', '2026-05-23', NULL, '2026-02-21 04:21:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `breakdowns`
--
ALTER TABLE `breakdowns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `idx_breakdowns_equipment` (`equipment_id`),
  ADD KEY `idx_breakdowns_status` (`status`),
  ADD KEY `idx_breakdowns_date` (`date_reported`),
  ADD KEY `idx_breakdowns_vehicle` (`vehicle_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_id` (`item_id`),
  ADD KEY `assigned_operator_id` (`assigned_operator_id`),
  ADD KEY `idx_equipment_category` (`category`),
  ADD KEY `idx_equipment_status` (`status`);

--
-- Indexes for table `maintenance_records`
--
ALTER TABLE `maintenance_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_maintenance_equipment` (`equipment_id`),
  ADD KEY `idx_maintenance_date` (`date_performed`),
  ADD KEY `idx_maintenance_vehicle` (`vehicle_id`);

--
-- Indexes for table `tools`
--
ALTER TABLE `tools`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to_id` (`assigned_to_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plate_number` (`plate_number`),
  ADD KEY `idx_vehicles_status` (`status`);

--
-- Indexes for table `vehicle_oil_changes`
--
ALTER TABLE `vehicle_oil_changes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vehicle_oil_changes_vehicle` (`vehicle_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `breakdowns`
--
ALTER TABLE `breakdowns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `maintenance_records`
--
ALTER TABLE `maintenance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tools`
--
ALTER TABLE `tools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vehicle_oil_changes`
--
ALTER TABLE `vehicle_oil_changes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `breakdowns`
--
ALTER TABLE `breakdowns`
  ADD CONSTRAINT `breakdowns_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `breakdowns_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_bd_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `equipment`
--
ALTER TABLE `equipment`
  ADD CONSTRAINT `equipment_ibfk_1` FOREIGN KEY (`assigned_operator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `maintenance_records`
--
ALTER TABLE `maintenance_records`
  ADD CONSTRAINT `fk_mr_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `maintenance_records_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `maintenance_records_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tools`
--
ALTER TABLE `tools`
  ADD CONSTRAINT `tools_ibfk_1` FOREIGN KEY (`assigned_to_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `vehicle_oil_changes`
--
ALTER TABLE `vehicle_oil_changes`
  ADD CONSTRAINT `vehicle_oil_changes_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
