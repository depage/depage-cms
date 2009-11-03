CREATE TABLE `dp_newproject_xmldata_cache` (
  `id` int(10) unsigned NOT NULL DEFAULT '0',
  `nodefunc` varchar(20) CHARACTER SET latin1 DEFAULT NULL,
  `value` mediumtext CHARACTER SET latin1,
  KEY `SECONDARY` (`nodefunc`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='depage::cms 1.1';
