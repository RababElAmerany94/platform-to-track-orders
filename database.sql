-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 10, 2022 at 05:18 PM
-- Server version: 5.7.38-0ubuntu0.18.04.1
-- PHP Version: 7.4.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `test`
--

-- --------------------------------------------------------

--
-- Table structure for table `job_queue`
--

CREATE TABLE `job_queue` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `payload` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `job_queue`
--

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `endpoint` varchar(199) NOT NULL,
  `p256dh` varchar(199) DEFAULT NULL,
  `auth` varchar(199) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `notifications`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `OrderId` bigint(20) UNSIGNED NOT NULL,
  `ProductId` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `OrderNo` varchar(11) NOT NULL,
  `OrderStatus` enum('New','Dispatched','Delivered','Returned','Cancelled','No Response') NOT NULL,
  `OrderDate` datetime NOT NULL,
  `RecipientFullName` varchar(32) NOT NULL,
  `RecipientAddress` varchar(128) NOT NULL,
  `RecipientCity` varchar(16) NOT NULL,
  `RecipientZipCode` varchar(5) NOT NULL,
  `RecipientPhone` varchar(16) NOT NULL,
  `OrderTotalPayment` float(5,2) NOT NULL,
  `Quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `orders`
--

-- --------------------------------------------------------

--
-- Table structure for table `order_product`
--

CREATE TABLE `order_product` (
  `id` int(11) NOT NULL,
  `OrderId` bigint(20) NOT NULL,
  `ProductId` bigint(20) NOT NULL,
  `Quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `order_product`
--

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `ProductId` bigint(20) NOT NULL,
  `Name` varchar(128) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Dumping data for table `products`
--


-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `Id_Role` int(11) NOT NULL,
  `Name` varchar(55) COLLATE latin1_general_ci NOT NULL,
  `Description` varchar(99) COLLATE latin1_general_ci NOT NULL,
  `Can_List` set('Users','Roles','Products','Orders','Shipments','Dashboard','DeliveryRates','ProductStats') COLLATE latin1_general_ci NOT NULL,
  `Can_Read` set('Users','Roles','Products','Orders','Shipments') COLLATE latin1_general_ci NOT NULL,
  `Can_Add` set('Users','Roles','Products','Orders','Shipments') COLLATE latin1_general_ci NOT NULL,
  `Can_Edit` set('Users','Roles','Settings','Products','Orders','Shipments') COLLATE latin1_general_ci NOT NULL,
  `Can_Delete` set('Users','Roles','Products','Orders','Shipments') COLLATE latin1_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`Id_Role`, `Name`, `Description`, `Can_List`, `Can_Read`, `Can_Add`, `Can_Edit`, `Can_Delete`) VALUES
(1, 'Administrators', 'Administrators can do everything.', 'Users,Roles,Products,Orders,Shipments,Dashboard,DeliveryRates,ProductStats', 'Users,Roles,Products,Orders,Shipments', 'Users,Roles,Products,Orders,Shipments', 'Users,Roles,Settings,Products,Orders,Shipments', 'Users,Roles,Products,Orders,Shipments');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key` varchar(128) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `value` varchar(255) COLLATE latin1_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`) VALUES
(1, 'app_name', 'Company Name'),
(2, 'app_address1', '123 Main Street'),
(3, 'app_address2', 'Suite 101'),
(4, 'app_city', 'Tangier'),
(5, 'app_postalCode', '90010'),
(6, 'app_phone', '0603036555'),
(7, 'app_if', '11111111'),
(8, 'app_ice', '000000000000000'),
(9, 'app_rc', '00000'),
(10, 'app_patente', '12345678'),
(11, 'app_rib', '001 640 0000000000000000 00'),

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `ShipmentId` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ProductId` bigint(20) DEFAULT NULL,
  `TrackingNumber` varchar(32) COLLATE latin1_general_ci NOT NULL,
  `ShipmentDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `InitialQuantity` int(11) NOT NULL,
  `RemainingQuantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Dumping data for table `shipments`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `Id_Role` int(11) DEFAULT NULL,
  `username` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `password` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `full_name` varchar(64) COLLATE latin1_general_ci NOT NULL,
  `email` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `phone_number` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `phone_number2` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `address` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `city` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `zipcode` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `last_login` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login_ip` varchar(255) COLLATE latin1_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `Id_Role`, `username`, `password`, `full_name`, `email`, `phone_number`, `phone_number2`, `address`, `city`, `zipcode`, `last_login`, `last_login_ip`) VALUES
(1, 1, 'admin', 'c3284d0f94606de1fd2af172aba15bf3|2|e86cebe1', 'System Administrator', 'test@test.ma', '+21200000000', '+21200000000', 'Rue 1', 'Tangier', '90010', '2022-03-07 15:10:26', '196.70.217.118'),

--
-- Indexes for dumped tables
--

--
-- Indexes for table `job_queue`
--
ALTER TABLE `job_queue`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`OrderId`),
  ADD UNIQUE KEY `OrderId` (`OrderId`),
  ADD KEY `ProductId` (`ProductId`),
  ADD KEY `DeliveryAgentId` (`user_id`);

--
-- Indexes for table `order_product`
--
ALTER TABLE `order_product`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`ProductId`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`Id_Role`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`ShipmentId`),
  ADD KEY `DeliveryAgentId` (`user_id`),
  ADD KEY `ProductId` (`ProductId`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role` (`Id_Role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `job_queue`
--
ALTER TABLE `job_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `OrderId` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2147483647;

--
-- AUTO_INCREMENT for table `order_product`
--
ALTER TABLE `order_product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `ProductId` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2147483647;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `Id_Role` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `ShipmentId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`Id_Role`) REFERENCES `roles` (`Id_Role`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
