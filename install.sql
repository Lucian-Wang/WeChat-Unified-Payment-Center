-- MySQL dump 10.13  Distrib 5.7.43, for Linux (x86_64)
--
-- Host: localhost    Database: pay_wsx_tax
-- ------------------------------------------------------
-- Server version	5.7.43-log

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
-- Table structure for table `pay_orders`
--

DROP TABLE IF EXISTS `pay_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pay_order_no` varchar(64) NOT NULL,
  `biz_sys` varchar(32) NOT NULL,
  `biz_order_no` varchar(64) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `subject` varchar(128) DEFAULT NULL,
  `status` varchar(16) DEFAULT 'unpaid',
  `paid_at` datetime DEFAULT NULL,
  `notify_url` varchar(255) DEFAULT NULL,
  `return_url` varchar(255) DEFAULT NULL,
  `extra` text,
  `wx_qrcode_url` text,
  `pay_type` varchar(10) NOT NULL DEFAULT '' COMMENT '支付方式，如W01微信，A01支付宝，U01云闪付',
  `wx_notify_raw` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `wx_notified` tinyint(1) NOT NULL DEFAULT '0' COMMENT '微信回调是否成功',
  `biz_notified` tinyint(1) NOT NULL DEFAULT '0' COMMENT '业务系统是否回调成功',
  `biz_notify_result` varchar(255) DEFAULT NULL COMMENT '业务系统回调返回内容',
  PRIMARY KEY (`id`),
  UNIQUE KEY `pay_order_no` (`pay_order_no`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_orders`
--

LOCK TABLES `pay_orders` WRITE;
/*!40000 ALTER TABLE `pay_orders` DISABLE KEYS */;
-- Deployment data removed from public source.

/*!40000 ALTER TABLE `pay_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'pay_wsx_tax'
--

--
-- Dumping routines for database 'pay_wsx_tax'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-07  1:21:50
