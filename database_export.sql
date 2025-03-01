-- MySQL dump 10.19  Distrib 10.3.39-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: mariadb
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
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (3,'Make Sale','Sold 1 Youngs Town Sardines (pcs) with a total of 15 PHP',23,'2025-02-27 18:25:41'),(5,'Restock','Restocked 1 Youngs Town Sardines (pcs) with a total cost of 10 PHP',23,'2025-02-27 10:31:45'),(6,'Insert Product','Performed action: Insert Product on 10 Pancit Canton (pcs) with a cost of 10 PHP each',23,'2025-02-27 10:35:35'),(7,'Credit','Recorded credit for 1 Youngs Town Sardines (pcs) with a total of 15 PHP',23,'2025-02-27 18:43:57'),(8,'Edit Product','Edited product: 2 Youngs Town Sardines (pcs) with a new cost price of 10.00 PHP',23,'2025-02-27 11:08:30'),(9,'Restock','Restocked 1 Youngs Town Sardines (pcs)',23,'2025-02-27 11:12:12'),(10,'Make Sale','Performed action: Make Sale on 2 Youngs Town Sardines (pcs)',23,'2025-02-27 20:01:01'),(11,'Make Sale','Performed action: Make Sale on 1 Pancit Canton (pcs)',23,'2025-02-28 10:30:45'),(12,'Restock','Restocked 10 Youngs Town Sardines (pcs)',23,'2025-02-28 02:32:21'),(13,'Insert Product','Performed action: Insert Product on 1 1 (pcs)',23,'2025-03-01 04:39:41'),(14,'Insert Product','Performed action: Insert Product on 2 2 (pcs)',23,'2025-03-01 04:39:47'),(15,'Insert Product','Performed action: Insert Product on 3 3 (pcs)',23,'2025-03-01 04:39:52'),(16,'Insert Product','Performed action: Insert Product on 4 4 (pcs)',23,'2025-03-01 04:39:57'),(17,'Insert Product','Performed action: Insert Product on 5 5 (pcs)',23,'2025-03-01 04:40:02'),(18,'Insert Product','Performed action: Insert Product on 6 6 (pcs)',23,'2025-03-01 05:08:00'),(19,'Insert Product','Performed action: Insert Product on 7 7 (pcs)',23,'2025-03-01 06:01:11'),(20,'Make Sale','Performed action: Make Sale on 13 Youngs Town Sardines (pcs)',23,'2025-03-01 14:57:01'),(21,'Credit','Recorded credit for 1 Pancit Canton (pcs)',23,'2025-03-01 15:08:59'),(22,'Credit','Recorded credit for 1 Pancit Canton (pcs)',23,'2025-03-01 15:09:07'),(23,'Credit','Recorded credit for 1 3 (pcs)',23,'2025-03-01 15:09:12'),(24,'Credit','Recorded credit for 1 5 (pcs)',23,'2025-03-01 15:09:19'),(25,'Credit','Recorded credit for 1 6 (pcs)',23,'2025-03-01 15:09:24'),(26,'Credit','Recorded credit for 1 Pancit Canton (pcs)',23,'2025-03-01 15:09:31'),(27,'Make Sale','Performed action: Make Sale on 6 Pancit Canton (pcs)',23,'2025-03-01 15:11:44');
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
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `batchItem`
--

LOCK TABLES `batchItem` WRITE;
/*!40000 ALTER TABLE `batchItem` DISABLE KEYS */;
INSERT INTO `batchItem` VALUES (39,1,1.00,'2025-03-01 04:39:41',48,23),(40,2,2.00,'2025-03-01 04:39:47',49,23),(41,2,3.00,'2025-03-01 07:09:12',50,23),(42,4,4.00,'2025-03-01 04:39:57',51,23),(43,4,5.00,'2025-03-01 07:09:19',52,23),(44,5,6.00,'2025-03-01 07:09:24',53,23),(45,7,7.00,'2025-03-01 06:01:11',54,23);
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (12,'Canned Goods'),(13,'Instant Noodles');
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
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `creditor`
--

LOCK TABLES `creditor` WRITE;
/*!40000 ALTER TABLE `creditor` DISABLE KEYS */;
INSERT INTO `creditor` VALUES (33,'Nathan','',15.00,0.00),(34,'Nathan','',15.00,0.00),(35,'1','',0.00,12.00),(36,'2','',0.00,12.00),(37,'3','',0.00,3.00),(38,'4','',0.00,5.00),(39,'6','',0.00,6.00),(40,'7','',0.00,12.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credits`
--

LOCK TABLES `credits` WRITE;
/*!40000 ALTER TABLE `credits` DISABLE KEYS */;
INSERT INTO `credits` VALUES (32,'Paid','2025-02-27 20:44:05','2025-02-27 17:33:35',33,23),(33,'Paid','2025-02-27 20:43:59','2025-02-27 18:43:57',34,23),(34,'Unpaid','2025-03-01 07:08:59','2025-03-01 15:08:59',35,23),(35,'Unpaid','2025-03-01 07:09:07','2025-03-01 15:09:07',36,23),(36,'Unpaid','2025-03-01 07:09:12','2025-03-01 15:09:12',37,23),(37,'Unpaid','2025-03-01 07:09:19','2025-03-01 15:09:19',38,23),(38,'Unpaid','2025-03-01 07:09:24','2025-03-01 15:09:24',39,23),(39,'Unpaid','2025-03-01 07:09:31','2025-03-01 15:09:31',40,23);
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
  `reorderLevel` int(11) NOT NULL,
  `productId` int(11) NOT NULL,
  PRIMARY KEY (`inventoryId`),
  KEY `products_inventory` (`productId`),
  CONSTRAINT `products_inventory` FOREIGN KEY (`productId`) REFERENCES `products` (`productId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` VALUES (19,0,3,42),(20,0,5,43),(21,1,1,48),(22,2,2,49),(23,2,3,50),(24,4,4,51),(25,4,5,52),(26,5,6,53),(27,7,7,54);
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
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `unit` enum('pcs','kg','L','pack','mL','doz','m') NOT NULL DEFAULT 'pcs',
  PRIMARY KEY (`productId`),
  KEY `categories_products` (`categoryId`),
  CONSTRAINT `categories_products` FOREIGN KEY (`categoryId`) REFERENCES `categories` (`categoryId`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (42,'Youngs Town Sardines','12',15.00,12,'Active','pcs'),(43,'Pancit Canton','13',12.00,13,'Active','pcs'),(48,'1','12',1.00,12,'Active','pcs'),(49,'2','12',2.00,12,'Active','pcs'),(50,'3','12',3.00,12,'Active','pcs'),(51,'4','12',4.00,12,'Active','pcs'),(52,'5','12',5.00,12,'Active','pcs'),(53,'6','12',6.00,12,'Active','pcs'),(54,'7','12',7.00,12,'Active','pcs');
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
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_item`
--

LOCK TABLES `sale_item` WRITE;
/*!40000 ALTER TABLE `sale_item` DISABLE KEYS */;
INSERT INTO `sale_item` VALUES (75,1,15.00,15.00,53,NULL,24,42),(76,1,15.00,15.00,NULL,32,25,42),(77,1,15.00,15.00,54,NULL,26,42),(78,1,15.00,15.00,55,NULL,27,42),(79,1,15.00,15.00,56,NULL,28,42),(80,1,15.00,15.00,57,NULL,28,42),(81,1,15.00,15.00,58,NULL,29,42),(82,1,15.00,15.00,NULL,33,29,42),(83,2,15.00,30.00,59,NULL,30,42),(84,1,12.00,12.00,62,NULL,32,43),(85,2,15.00,195.00,63,NULL,31,42),(86,1,15.00,195.00,63,NULL,33,42),(87,10,15.00,195.00,63,NULL,34,42),(88,1,12.00,12.00,NULL,34,32,43),(89,1,12.00,12.00,NULL,35,32,43),(90,1,3.00,3.00,NULL,36,41,50),(91,1,5.00,5.00,NULL,37,43,52),(92,1,6.00,6.00,NULL,38,44,53),(93,1,12.00,12.00,NULL,39,32,43),(94,6,12.00,72.00,64,NULL,32,43);
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
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (53,15.00,'Cash','2025-02-23 17:14:39',23,NULL,NULL),(54,15.00,'Cash','2025-02-25 17:38:03',23,NULL,NULL),(55,15.00,'Cash','2025-02-26 17:39:14',23,NULL,NULL),(56,15.00,'Cash','2025-02-27 17:48:28',23,NULL,NULL),(57,15.00,'Cash','2025-02-27 18:11:09',23,NULL,NULL),(58,15.00,'Cash','2025-02-28 18:25:41',23,NULL,NULL),(59,30.00,'Cash','2025-02-27 20:01:01',23,NULL,NULL),(60,15.00,'Credit','2025-02-27 20:43:59',23,NULL,33),(61,15.00,'Credit','2025-02-27 20:44:05',23,NULL,32),(62,12.00,'Cash','2025-02-28 10:30:45',23,NULL,NULL),(63,195.00,'Cash','2025-03-01 14:57:01',23,NULL,NULL),(64,72.00,'Cash','2025-03-01 15:11:44',23,NULL,NULL);
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
  `accountLevel` enum('Admin','nonAdmin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `login_count` int(11) DEFAULT 0,
  PRIMARY KEY (`userId`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (22,'test','$2y$10$R1bfEuhmhAOgMALkQn0AX..mKuRc9Yf6PaIjjgqTwZY3H3LFSEj6q','Admin','2025-03-01 07:18:13',0),(23,'testAdmin','$2y$10$7siMviSfzB583qkno5m5Cuk9tMegAzgRiOGudogox4JD8Pa5T.AUK','Admin','2025-03-01 07:18:13',0),(24,'test2','$2y$10$JGHyUK6iPLDBqcBtzUTQiuCLmfAKBmWzELpfu6T5YjpCWkNfI6FcO','Admin','2025-03-01 07:20:53',2);
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

-- Dump completed on 2025-03-01  9:34:28
