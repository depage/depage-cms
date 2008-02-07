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
-- Table structure for table `tt_mediathumbs`
--

DROP TABLE IF EXISTS `tt_mediathumbs`;
CREATE TABLE `tt_mediathumbs` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `projectId` int(11) unsigned NOT NULL default '0',
  `path` varchar(255) NOT NULL default '',
  `filename` varchar(255) NOT NULL default '',
  `mtime` int(10) default NULL,
  `size` int(10) unsigned default NULL,
  `thumb` mediumblob,
  PRIMARY KEY  (`id`),
  KEY `project` (`projectId`),
  KEY `path` (`path`),
  KEY `filename` (`filename`)
) ENGINE=MyISAM AUTO_INCREMENT=239 DEFAULT CHARSET=latin1 COMMENT='depage 0.9.14';

