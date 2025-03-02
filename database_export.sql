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
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (3,'Make Sale','Sold 1 Youngs Town Sardines (pcs) with a total of 15 PHP',23,'2025-02-27 18:25:41'),(5,'Restock','Restocked 1 Youngs Town Sardines (pcs) with a total cost of 10 PHP',23,'2025-02-27 10:31:45'),(6,'Insert Product','Performed action: Insert Product on 10 Pancit Canton (pcs) with a cost of 10 PHP each',23,'2025-02-27 10:35:35'),(7,'Credit','Recorded credit for 1 Youngs Town Sardines (pcs) with a total of 15 PHP',23,'2025-02-27 18:43:57'),(8,'Edit Product','Edited product: 2 Youngs Town Sardines (pcs) with a new cost price of 10.00 PHP',23,'2025-02-27 11:08:30'),(9,'Restock','Restocked 1 Youngs Town Sardines (pcs)',23,'2025-02-27 11:12:12'),(10,'Make Sale','Performed action: Make Sale on 2 Youngs Town Sardines (pcs)',23,'2025-02-27 20:01:01'),(11,'Make Sale','Performed action: Make Sale on 1 Pancit Canton (pcs)',23,'2025-02-28 10:30:45'),(12,'Restock','Restocked 10 Youngs Town Sardines (pcs)',23,'2025-02-28 02:32:21'),(13,'Insert Product','Performed action: Insert Product on 1 1 (pcs)',23,'2025-03-01 04:39:41'),(14,'Insert Product','Performed action: Insert Product on 2 2 (pcs)',23,'2025-03-01 04:39:47'),(15,'Insert Product','Performed action: Insert Product on 3 3 (pcs)',23,'2025-03-01 04:39:52'),(16,'Insert Product','Performed action: Insert Product on 4 4 (pcs)',23,'2025-03-01 04:39:57'),(17,'Insert Product','Performed action: Insert Product on 5 5 (pcs)',23,'2025-03-01 04:40:02'),(18,'Insert Product','Performed action: Insert Product on 6 6 (pcs)',23,'2025-03-01 05:08:00'),(19,'Insert Product','Performed action: Insert Product on 7 7 (pcs)',23,'2025-03-01 06:01:11'),(20,'Make Sale','Performed action: Make Sale on 13 Youngs Town Sardines (pcs)',23,'2025-03-01 14:57:01'),(21,'Credit','Recorded credit for 1 Pancit Canton (pcs)',23,'2025-03-01 15:08:59'),(22,'Credit','Recorded credit for 1 Pancit Canton (pcs)',23,'2025-03-01 15:09:07'),(23,'Credit','Recorded credit for 1 3 (pcs)',23,'2025-03-01 15:09:12'),(24,'Credit','Recorded credit for 1 5 (pcs)',23,'2025-03-01 15:09:19'),(25,'Credit','Recorded credit for 1 6 (pcs)',23,'2025-03-01 15:09:24'),(26,'Credit','Recorded credit for 1 Pancit Canton (pcs)',23,'2025-03-01 15:09:31'),(27,'Make Sale','Performed action: Make Sale on 6 Pancit Canton (pcs)',23,'2025-03-01 15:11:44'),(28,'Make Sale','Performed action: Make Sale on 1 1 (pcs), 1 2 (pcs)',24,'2025-03-01 17:38:07'),(29,'Make Sale','Performed action: Make Sale on 1 2 (pcs)',24,'2025-03-01 17:38:55'),(30,'Make Sale','Performed action: Make Sale on 1 5 (pcs)',24,'2025-03-01 17:40:05'),(31,'Credit','Recorded credit for 1 6 (pcs)',24,'2025-03-01 17:45:13'),(32,'Credit','Recorded credit for 1 3 (pcs)',24,'2025-03-01 17:46:44'),(33,'Insert Product','Performed action: Insert Product on 1 test (pcs)',24,'2025-03-01 10:01:34'),(34,'Insert Product','Performed action: Insert Product on 1 awdawdawd (pcs)',24,'2025-03-01 10:02:01'),(35,'Restock','Restocked 100 Youngs Town Sardines (pcs)',24,'2025-03-01 10:48:42'),(36,'Restock','Restocked 100 Pancit Canton (pcs)',24,'2025-03-01 10:48:42'),(37,'Restock','Restocked 100 1 (pcs)',23,'2025-03-01 13:20:12'),(38,'Restock','Restocked 15 Youngs Town Sardines (pcs)',23,'2025-03-01 13:20:12'),(39,'Sale','Performed action: Sale on 1 1 (pcs)',23,'2025-03-02 07:16:53'),(40,'Sale','Sold 1 Youngs Town Sardines (pcs)',23,'2025-03-02 07:18:26'),(41,'Restock','Restocked 1 2 (pcs)',23,'2025-03-02 00:07:59'),(42,'Restock','Restocked 1 3 (pcs)',23,'2025-03-02 00:08:23'),(43,'Restock','Restocked 1 Pancit Canton (pcs)',23,'2025-03-02 00:08:42'),(44,'Sale','Sold 1 Youngs Town Sardines (pcs)',23,'2025-03-02 08:09:28'),(45,'Credit','Recorded credit for 1 Youngs Town Sardines (pcs), 1 1 (pcs), 1 4 (pcs), 1 7 (pcs), 1 Pancit Canton (pcs)',23,'2025-03-02 08:10:02'),(46,'Sale','Sold 112 Youngs Town Sardines (pcs)',23,'2025-03-02 12:16:59'),(47,'Sale','Sold 100 Pancit Canton (pcs)',23,'2025-03-02 12:17:18'),(48,'Sale','Sold 98 1 (pcs)',23,'2025-03-02 12:17:32'),(49,'Insert Product','Performed action: Insert Product on 1 TEST PRODUCT1 (pcs)',23,'2025-03-02 08:37:59'),(50,'Insert Product','Performed action: Insert Product on 10 TEST PRODUCT2 (pcs)',23,'2025-03-02 08:42:04'),(51,'Restock','Restocked 10 Youngs Town Sardines (pcs)',23,'2025-03-02 09:14:57'),(52,'Sale','Sold 5 Youngs Town Sardines (pcs)',23,'2025-03-02 17:15:50'),(53,'Credit','Recorded credit for 5 Youngs Town Sardines (pcs)',23,'2025-03-02 17:16:07'),(54,'Insert Product','Performed action: Insert Product on 2 tes (pcs)',23,'2025-03-02 09:26:26'),(55,'Insert Product','Performed action: Insert Product on 2323 Youngs Town Sardindes (pcs)',23,'2025-03-02 09:31:47'),(56,'Insert Product','Performed action: Insert Product on 2 8awdh (pcs)',23,'2025-03-02 09:31:56'),(57,'Insert Product','Performed action: Insert Product on 32 dawd (pcs)',23,'2025-03-02 09:32:07'),(58,'Insert Product','Performed action: Insert Product on 323232 Youngs Town Sardinesd (pcs)',23,'2025-03-02 09:32:26'),(59,'Insert Product','Performed action: Insert Product on 2 dadwadawd (pcs)',23,'2025-03-02 09:33:17');
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
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `batchItem`
--

LOCK TABLES `batchItem` WRITE;
/*!40000 ALTER TABLE `batchItem` DISABLE KEYS */;
INSERT INTO `batchItem` VALUES (41,1,3.00,'2025-03-01 09:46:44',50,23),(42,3,4.00,'2025-03-02 00:10:02',51,23),(43,3,5.00,'2025-03-01 09:40:05',52,23),(44,4,6.00,'2025-03-01 09:45:13',53,23),(45,6,7.00,'2025-03-02 00:10:02',54,23),(52,1,10.00,'2025-03-02 00:07:59',49,23),(53,1,10.00,'2025-03-02 00:08:23',50,23);
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (12,'Canned Goods'),(13,'Instant Noodles'),(14,'ttt'),(15,'TEST CATEGORY');
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
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `creditor`
--

LOCK TABLES `creditor` WRITE;
/*!40000 ALTER TABLE `creditor` DISABLE KEYS */;
INSERT INTO `creditor` VALUES (33,'Nathan','',15.00,0.00),(34,'Nathan','',15.00,0.00),(35,'1','',0.00,12.00),(36,'2','',0.00,12.00),(37,'3','',0.00,3.00),(38,'4','',0.00,5.00),(39,'6','',6.00,0.00),(40,'7','',10.00,2.00),(41,'t','',0.00,6.00),(42,'t','',0.00,3.00),(43,'Nate','',30.00,9.00),(44,'Nate','',0.00,75.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credits`
--

LOCK TABLES `credits` WRITE;
/*!40000 ALTER TABLE `credits` DISABLE KEYS */;
INSERT INTO `credits` VALUES (32,'Paid','2025-02-27 20:44:05','2025-02-27 17:33:35',33,23),(33,'Paid','2025-02-27 20:43:59','2025-02-27 18:43:57',34,23),(34,'Unpaid','2025-03-01 07:08:59','2025-03-01 15:08:59',35,23),(35,'Unpaid','2025-03-01 07:09:07','2025-03-01 15:09:07',36,23),(36,'Unpaid','2025-03-01 07:09:12','2025-03-01 15:09:12',37,23),(37,'Unpaid','2025-03-01 07:09:19','2025-03-01 15:09:19',38,23),(38,'Paid','2025-03-01 17:35:46','2025-03-01 15:09:24',39,23),(39,'Partially Paid','2025-03-01 17:35:24','2025-03-01 15:09:31',40,23),(40,'Unpaid','2025-03-01 09:45:13','2025-03-01 17:45:13',41,24),(41,'Unpaid','2025-03-01 09:46:44','2025-03-01 17:46:44',42,24),(42,'Partially Paid','2025-03-02 08:11:09','2025-03-02 08:10:02',43,23),(43,'Unpaid','2025-03-02 09:16:07','2025-03-02 17:16:07',44,23);
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
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` VALUES (19,0,5,42),(20,0,5,43),(21,0,5,48),(22,1,5,49),(23,2,5,50),(24,3,5,51),(25,3,5,52),(26,4,5,53),(27,6,5,54);
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
  PRIMARY KEY (`productId`),
  KEY `categories_products` (`categoryId`),
  CONSTRAINT `categories_products` FOREIGN KEY (`categoryId`) REFERENCES `categories` (`categoryId`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (42,'Youngs Town Sardines','12',15.00,12,'pcs'),(43,'Pancit Canton','13',12.00,13,'pcs'),(48,'1','12',1.00,12,'pcs'),(49,'2','12',2.00,12,'pcs'),(50,'3','12',3.00,12,'pcs'),(51,'4','12',4.00,12,'pcs'),(52,'5','12',5.00,12,'pcs'),(53,'6','12',6.00,12,'pcs'),(54,'7','12',7.00,12,'pcs');
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
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_item`
--

LOCK TABLES `sale_item` WRITE;
/*!40000 ALTER TABLE `sale_item` DISABLE KEYS */;
INSERT INTO `sale_item` VALUES (75,1,15.00,15.00,53,NULL,24,42),(76,1,15.00,15.00,NULL,32,25,42),(77,1,15.00,15.00,54,NULL,26,42),(78,1,15.00,15.00,55,NULL,27,42),(79,1,15.00,15.00,56,NULL,28,42),(80,1,15.00,15.00,57,NULL,28,42),(81,1,15.00,15.00,58,NULL,29,42),(82,1,15.00,15.00,NULL,33,29,42),(83,2,15.00,30.00,59,NULL,30,42),(84,1,12.00,12.00,62,NULL,32,43),(85,2,15.00,195.00,63,NULL,31,42),(86,1,15.00,195.00,63,NULL,33,42),(87,10,15.00,195.00,63,NULL,34,42),(88,1,12.00,12.00,NULL,34,32,43),(89,1,12.00,12.00,NULL,35,32,43),(90,1,3.00,3.00,NULL,36,41,50),(91,1,5.00,5.00,NULL,37,43,52),(92,1,6.00,6.00,NULL,38,44,53),(93,1,12.00,12.00,NULL,39,32,43),(94,6,12.00,72.00,64,NULL,32,43),(95,1,1.00,1.00,66,NULL,39,48),(96,1,2.00,2.00,66,NULL,40,49),(97,1,2.00,2.00,67,NULL,40,49),(98,1,5.00,5.00,68,NULL,43,52),(99,1,6.00,6.00,NULL,40,44,53),(100,1,3.00,3.00,NULL,41,41,50),(101,1,1.00,1.00,69,NULL,50,48),(102,1,15.00,15.00,70,NULL,48,42),(103,1,15.00,15.00,71,NULL,51,42),(104,1,15.00,15.00,NULL,42,48,42),(105,1,1.00,1.00,NULL,42,50,48),(106,1,4.00,4.00,NULL,42,42,51),(107,1,7.00,7.00,NULL,42,45,54),(108,1,12.00,12.00,NULL,42,49,43),(109,14,15.00,1680.00,72,NULL,51,42),(110,98,15.00,1680.00,72,NULL,48,42),(111,1,12.00,1200.00,73,NULL,54,43),(112,99,12.00,1200.00,73,NULL,49,43),(113,98,1.00,98.00,74,NULL,50,48),(114,5,15.00,75.00,75,NULL,57,42),(115,5,15.00,75.00,NULL,43,57,42);
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
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (53,15.00,'Cash','2025-02-23 17:14:39',23,NULL,NULL),(54,15.00,'Cash','2025-02-25 17:38:03',23,NULL,NULL),(55,15.00,'Cash','2025-02-26 17:39:14',23,NULL,NULL),(56,15.00,'Cash','2025-02-27 17:48:28',23,NULL,NULL),(57,15.00,'Cash','2025-02-27 18:11:09',23,NULL,NULL),(58,15.00,'Cash','2025-02-28 18:25:41',23,NULL,NULL),(59,30.00,'Cash','2025-02-27 20:01:01',23,NULL,NULL),(60,15.00,'Credit','2025-02-27 20:43:59',23,NULL,33),(61,15.00,'Credit','2025-02-27 20:44:05',23,NULL,32),(62,12.00,'Cash','2025-02-28 10:30:45',23,NULL,NULL),(63,195.00,'Cash','2025-03-01 14:57:01',23,NULL,NULL),(64,72.00,'Cash','2025-03-01 15:11:44',23,NULL,NULL),(65,6.00,'Credit','2025-03-01 17:35:46',23,NULL,38),(66,3.00,'Cash','2025-03-01 17:38:07',24,NULL,NULL),(67,2.00,'Cash','2025-03-01 17:38:55',24,NULL,NULL),(68,5.00,'Cash','2025-03-01 17:40:05',24,NULL,NULL),(69,1.00,'Cash','2025-03-02 07:16:53',23,NULL,NULL),(70,15.00,'Cash','2025-03-02 07:18:26',23,NULL,NULL),(71,15.00,'Cash','2025-03-02 08:09:28',23,NULL,NULL),(72,1680.00,'Cash','2025-03-02 12:16:59',23,NULL,NULL),(73,1200.00,'Cash','2025-03-02 12:17:18',23,NULL,NULL),(74,98.00,'Cash','2025-03-02 12:17:32',23,NULL,NULL),(75,75.00,'Cash','2025-03-02 17:15:50',23,NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (22,'test','$2y$10$R1bfEuhmhAOgMALkQn0AX..mKuRc9Yf6PaIjjgqTwZY3H3LFSEj6q',0),(23,'testAdmin','$2y$10$EO1ypxhIAqv1/alLEMwppOg2aEy4dX/WJDT4VhvLOnOIVoBHYRBKq',10),(24,'test2','$2y$10$JGHyUK6iPLDBqcBtzUTQiuCLmfAKBmWzELpfu6T5YjpCWkNfI6FcO',5),(25,'Nate','$2y$10$UkxGown0aqtSig.T266jo.eSNpsLSZm8EWh923UqKbF/.O.L3aftO',0);
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

-- Dump completed on 2025-03-02  9:45:46
