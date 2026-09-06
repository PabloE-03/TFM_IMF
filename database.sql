-- MySQL dump 10.13  Distrib 8.0.32, for Win64 (x86_64)
--
-- Host: localhost    Database: vuln_db
-- ------------------------------------------------------
-- Server version	8.0.32

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `uuid` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `precio` decimal(10,0) NOT NULL,
  `img_url` varchar(255) NOT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES ('1c8ea69a-652e-4d49-96fd-eb6de4763bd6','Mesa Comedor',460,'/uploads/products/mesa-comedor.jpg',NULL,'2026-09-05 16:12:02'),('26ef4dc6-6e48-4687-a10e-1783055ff186','Silla Oficina',200,'/uploads/products/silla-oficina.jpg',NULL,'2026-09-05 16:12:02'),('2d5d0afe-ebfe-4439-b221-2d114d74a5c2','Cama King',1300,'/uploads/products/cama-king.jpg',NULL,'2026-09-05 16:12:02'),('3aea2344-b6f0-4e6a-b390-15e3ca23a168','Nevera',1100,'/uploads/products/nevera.jpg',NULL,'2026-09-05 16:12:02'),('4293c729-657c-41fb-a024-1a4e5eb348e5','Lavabo',250,'/uploads/products/lavabo.jpg',NULL,'2026-09-05 16:12:02'),('714123f1-a794-4929-948d-c54baaa868d9','Estanteria',150,'/uploads/products/estanteria.jpg',NULL,'2026-09-05 16:12:02'),('73c3ebe8-d0de-4804-b8a4-4b78dac43820','Sofa',900,'/uploads/products/sofa.jpg',NULL,'2026-09-05 16:12:02'),('94afbd9e-c862-4279-a012-7ac06ffac153','Lampara',80,'/uploads/products/lampara.jpg',NULL,'2026-09-05 16:12:02'),('a72a9f97-e29c-43c7-893e-b250bb9fae51','Armario',700,'/uploads/products/armario.jpg',NULL,'2026-09-05 16:12:02'),('ccaad865-9feb-4cbd-974a-4cd1810a1c94','Banera',550,'/uploads/products/banera.jpg',NULL,'2026-09-05 16:12:02');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchases` (
  `uuid` varchar(36) NOT NULL,
  `final_price` decimal(10,0) NOT NULL,
  `card` varchar(16) DEFAULT NULL,
  `expiration` varchar(5) DEFAULT NULL,
  `cvv` varchar(3) DEFAULT NULL,
  `finished` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `uuid_user` varchar(36) NOT NULL,
  `uuid_product` varchar(36) NOT NULL,
  `cantidad` int NOT NULL,
  `purchase_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`uuid`),
  KEY `fk_purchases_users` (`uuid_user`),
  KEY `fk_purchases_products` (`uuid_product`),
  CONSTRAINT `fk_purchases_products` FOREIGN KEY (`uuid_product`) REFERENCES `products` (`uuid`),
  CONSTRAINT `fk_purchases_users` FOREIGN KEY (`uuid_user`) REFERENCES `users` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
INSERT INTO `purchases` VALUES ('8ac89645-ad29-4309-90b3-f36aec47bbcd',800,'4321789654321211','02/12','024',1,'2026-09-06 16:37:13','c0861ce2-e7fc-427e-9371-406b26c1ae40','26ef4dc6-6e48-4687-a10e-1783055ff186',4,'2026-09-06 16:38:10');
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `uuid` varchar(36) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','client') NOT NULL DEFAULT 'client',
  `access` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_access` varchar(255) NOT NULL,
  PRIMARY KEY (`uuid`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('c0861ce2-e7fc-427e-9371-406b26c1ae40','admin','admin@email.com','$2y$10$t2JgpXXg.0.c8aeTxOYnqujEB7ctPNa8bAFrY1AHotizR4OHZKro6','admin','2026-09-06 14:53:49','2026-09-06 12:13:42','127.0.0.1');
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

-- Dump completed on 2026-09-06 18:47:03
