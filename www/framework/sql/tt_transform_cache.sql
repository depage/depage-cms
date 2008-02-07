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
-- Table structure for table `tt_transform_cache`
--

DROP TABLE IF EXISTS `tt_transform_cache`;
CREATE TABLE `tt_transform_cache` (
  `id_project` int(10) unsigned NOT NULL default '0',
  `id_page` int(10) unsigned NOT NULL default '0',
  `lang` varchar(12) NOT NULL default '',
  `type` varchar(100) NOT NULL default '',
  `access` enum('browse','preview','index','css') NOT NULL default 'browse',
  `ids_used` text NOT NULL,
  `content_type` varchar(30) NOT NULL default '',
  `content_encoding` varchar(10) NOT NULL default '',
  `value` mediumtext NOT NULL,
  KEY `SECONDARY` (`id_project`,`id_page`,`lang`,`type`,`access`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='depage 0.9.14';

