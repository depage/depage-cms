-- MySQL dump 10.9
--
-- Host: localhost    Database: depage_1_0
-- ------------------------------------------------------
-- Server version	4.1.22

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `tt_xmldata_cache`
--

DROP TABLE IF EXISTS `tt_xmldata_cache`;
CREATE TABLE `tt_xmldata_cache` (
  `id` int(10) unsigned NOT NULL default '0',
  `nodefunc` varchar(20) default NULL,
  `value` mediumtext,
  KEY `SECONDARY` (`nodefunc`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='depage 0.9.14';

