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
-- Table structure for table `tt_xmldata_elements`
--

DROP TABLE IF EXISTS `tt_xmldata_elements`;
CREATE TABLE `tt_xmldata_elements` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_parent` int(10) unsigned default NULL,
  `id_doc` int(10) unsigned default '0',
  `pos` mediumint(8) unsigned default '0',
  `name` varchar(50) default NULL,
  `value` mediumtext NOT NULL,
  `type` enum('ELEMENT_NODE','TEXT_NODE','CDATA_SECTION_NODE','PI_NODE','COMMENT_NODE','DOCUMENT_NODE','ENTITY_REF_NODE','WAIT_FOR_REPLACE','DELETED') NOT NULL default 'ELEMENT_NODE',
  PRIMARY KEY  (`id`),
  KEY `SECONDARY` (`id_parent`,`id_doc`,`type`),
  KEY `THIRD` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=30110 DEFAULT CHARSET=latin1 COMMENT='depage 0.9.14';

