-- MySQL dump 10.19  Distrib 10.3.39-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: 127.0.0.1    Database: mariadb
-- ------------------------------------------------------
-- Server version	10.3.39-MariaDB-0ubuntu0.20.04.2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `logId` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(50) NOT NULL COMMENT 'Type of action (e.g., Add Product, Restock, Sale, etc.)',
  `details` text DEFAULT NULL COMMENT 'Details of the action (e.g., JSON or descriptive text)',
  `userId` int(11) NOT NULL COMMENT 'User who performed the action',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Time of the action',
  PRIMARY KEY (`logId`),
  KEY `users_audit_logs` (`userId`),
  CONSTRAINT `users_audit_logs` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,'Database Reset','User Nathrix  performed a complete database reset. Backup created: db_backup_2025-03-13_10-14-25.sql',28,'2025-03-13 10:14:25'),(2,'Insert Product','Performed action: Insert Product on 100 Magic Sarap (pcs)',28,'2025-03-13 10:15:48'),(3,'Sale','Sold 1 Magic Sarap (pcs)',28,'2025-03-13 18:16:02'),(4,'Insert Product','Performed action: Insert Product on 30 Pancit Canton (pcs)',28,'2025-03-15 14:10:04');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `batchItem`
--

DROP TABLE IF EXISTS `batchItem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `batchItem` (
  `batchId` int(11) NOT NULL AUTO_INCREMENT,
  `quantity` int(11) NOT NULL,
  `costPrice` decimal(10,2) NOT NULL,
  `dateAdded` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `productId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  PRIMARY KEY (`batchId`),
  KEY `products_batchItem` (`productId`),
  KEY `users_batchItem` (`userId`),
  CONSTRAINT `products_batchItem` FOREIGN KEY (`productId`) REFERENCES `products` (`productId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `users_batchItem` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `batchItem`
--

LOCK TABLES `batchItem` WRITE;
/*!40000 ALTER TABLE `batchItem` DISABLE KEYS */;
INSERT INTO `batchItem` VALUES (1,99,3.00,'2025-03-13 10:16:02',1,28),(2,30,10.00,'2025-03-15 14:10:04',2,28);
/*!40000 ALTER TABLE `batchItem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `categoryId` int(11) NOT NULL AUTO_INCREMENT,
  `categoryName` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`categoryId`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'General'),(2,'Seasoning'),(3,'Instant Noodles');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `creditor`
--

DROP TABLE IF EXISTS `creditor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `creditor` (
  `creditorId` int(11) NOT NULL AUTO_INCREMENT,
  `customerName` varchar(100) NOT NULL,
  `phoneNumber` varchar(15) DEFAULT NULL,
  `amountPaid` decimal(10,2) DEFAULT NULL,
  `creditBalance` decimal(10,2) NOT NULL,
  PRIMARY KEY (`creditorId`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `creditor`
--

LOCK TABLES `creditor` WRITE;
/*!40000 ALTER TABLE `creditor` DISABLE KEYS */;
INSERT INTO `creditor` VALUES (33,'Nathan','',15.00,0.00),(34,'Nathan','',15.00,0.00),(35,'1','',0.00,12.00),(36,'2','',0.00,12.00),(37,'3','',0.00,3.00),(38,'4','',0.00,5.00),(39,'6','',6.00,0.00),(40,'7','',12.00,0.00),(41,'t','',0.00,6.00),(42,'t','',0.00,3.00),(43,'Nate','',39.00,0.00),(44,'Nate','',75.00,0.00),(45,'Nathan','09127436617',579.00,0.00),(46,'Hanna','',27.00,0.00);
/*!40000 ALTER TABLE `creditor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credits`
--

DROP TABLE IF EXISTS `credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credits` (
  `creditId` int(11) NOT NULL AUTO_INCREMENT,
  `paymentStatus` enum('Paid','Unpaid','Partially Paid') DEFAULT 'Unpaid',
  `lastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `transactionDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `creditorId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  PRIMARY KEY (`creditId`),
  KEY `users_credits` (`userId`),
  KEY `creditor_credits` (`creditorId`),
  CONSTRAINT `creditor_credits` FOREIGN KEY (`creditorId`) REFERENCES `creditor` (`creditorId`) ON DELETE CASCADE,
  CONSTRAINT `users_credits` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credits`
--

LOCK TABLES `credits` WRITE;
/*!40000 ALTER TABLE `credits` DISABLE KEYS */;
/*!40000 ALTER TABLE `credits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory` (
  `inventoryId` int(11) NOT NULL AUTO_INCREMENT,
  `totalStock` int(11) DEFAULT NULL,
  `reorderLevel` int(11) DEFAULT 5,
  `productId` int(11) NOT NULL,
  PRIMARY KEY (`inventoryId`),
  KEY `products_inventory` (`productId`),
  CONSTRAINT `products_inventory` FOREIGN KEY (`productId`) REFERENCES `products` (`productId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` VALUES (1,99,5,1),(2,30,5,2);
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `productId` int(11) NOT NULL AUTO_INCREMENT,
  `productName` varchar(100) NOT NULL,
  `productCategory` varchar(100) NOT NULL,
  `unitPrice` decimal(10,2) NOT NULL,
  `categoryId` int(11) NOT NULL,
  `unit` enum('pcs','kg','L','pack','mL','doz','m') NOT NULL DEFAULT 'pcs',
  `imagePath` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`productId`),
  KEY `categories_products` (`categoryId`),
  CONSTRAINT `categories_products` FOREIGN KEY (`categoryId`) REFERENCES `categories` (`categoryId`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'Magic Sarap','2',5.00,2,'pcs',NULL),(2,'Pancit Canton','3',12.00,3,'pcs',NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_item`
--

DROP TABLE IF EXISTS `sale_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_item` (
  `sale_itemId` int(20) NOT NULL AUTO_INCREMENT,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subTotal` decimal(10,2) NOT NULL,
  `saleId` int(11) DEFAULT NULL,
  `creditId` int(11) DEFAULT NULL,
  `batchId` int(11) NOT NULL,
  `productId` int(11) NOT NULL,
  PRIMARY KEY (`sale_itemId`),
  KEY `sale_sale_item` (`saleId`),
  KEY `credits_sale_item` (`creditId`),
  KEY `batchItem_sale_item` (`batchId`),
  KEY `products_sale_item` (`productId`),
  CONSTRAINT `credits_sale_item` FOREIGN KEY (`creditId`) REFERENCES `credits` (`creditId`) ON DELETE CASCADE,
  CONSTRAINT `products_sale_item` FOREIGN KEY (`productId`) REFERENCES `products` (`productId`),
  CONSTRAINT `sale_sale_item` FOREIGN KEY (`saleId`) REFERENCES `sales` (`saleId`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_item`
--

LOCK TABLES `sale_item` WRITE;
/*!40000 ALTER TABLE `sale_item` DISABLE KEYS */;
INSERT INTO `sale_item` VALUES (1,1,5.00,5.00,1,NULL,1,1);
/*!40000 ALTER TABLE `sale_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales` (
  `saleId` int(11) NOT NULL AUTO_INCREMENT,
  `totalPrice` decimal(10,2) NOT NULL,
  `transactionType` enum('Cash','Credit') NOT NULL,
  `dateSold` timestamp NOT NULL DEFAULT current_timestamp(),
  `userId` int(11) NOT NULL,
  `creditorId` int(11) DEFAULT NULL,
  `creditId` int(11) DEFAULT NULL,
  PRIMARY KEY (`saleId`),
  KEY `users_sale` (`userId`),
  KEY `creditor_sale` (`creditorId`),
  KEY `credits_sales` (`creditId`),
  CONSTRAINT `creditor_sale` FOREIGN KEY (`creditorId`) REFERENCES `creditor` (`creditorId`),
  CONSTRAINT `credits_sales` FOREIGN KEY (`creditId`) REFERENCES `credits` (`creditId`),
  CONSTRAINT `users_sale` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (1,5.00,'Cash','2025-03-13 18:16:02',28,NULL,NULL);
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `userId` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `login_count` int(11) DEFAULT 0,
  PRIMARY KEY (`userId`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (22,'test','$2y$10$R1bfEuhmhAOgMALkQn0AX..mKuRc9Yf6PaIjjgqTwZY3H3LFSEj6q',0),(23,'testAdmin','$2y$10$EO1ypxhIAqv1/alLEMwppOg2aEy4dX/WJDT4VhvLOnOIVoBHYRBKq',12),(24,'test2','$2y$10$0lB.HqlAM/A738vupKcwtOAfjWGAA7VtEgVOLyovuvP.FPY6CytOG',6),(25,'Nate','$2y$10$UkxGown0aqtSig.T266jo.eSNpsLSZm8EWh923UqKbF/.O.L3aftO',0),(26,'Nigga','$2y$10$EG8/5gWrlL2e5E47MakosOOiup6nLhgPYM5Xtwn9bsFi0GQACF3xe',10),(27,'testAccount','$2y$10$IuO1etgMsh4clDMnfsrQNusPDKrOpvQJ4718PhtK8yelgxM/luMiG',9),(28,'Nathrix ','$2y$10$RWUZqAU7bvTXrBtiXOW1Aeo5umFXXukiNgbo9v4H3hh2H0xifpJIK',8);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-03-15 15:07:23
