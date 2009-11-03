CREATE TABLE `dp_newproject_mediathumbs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `projectId` int(11) unsigned NOT NULL DEFAULT '0',
  `path` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `filename` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `mtime` int(10) DEFAULT NULL,
  `size` int(10) unsigned DEFAULT NULL,
  `thumb` mediumblob,
  PRIMARY KEY (`id`),
  KEY `project` (`projectId`),
  KEY `path` (`path`),
  KEY `filename` (`filename`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COMMENT='depage::cms 1.1';
