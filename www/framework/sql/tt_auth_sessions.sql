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
-- Table structure for table `tt_auth_sessions`
--

DROP TABLE IF EXISTS `tt_auth_sessions`;
CREATE TABLE `tt_auth_sessions` (
  `sid` varchar(16) NOT NULL default '',
  `userid` int(11) NOT NULL default '0',
  `project` varchar(50) default NULL,
  `ip` varchar(16) default NULL,
  `last_update` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`sid`,`userid`)
) ENGINE=HEAP DEFAULT CHARSET=latin1 COMMENT='depage 0.9.14';

