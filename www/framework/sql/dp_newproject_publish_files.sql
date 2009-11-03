CREATE TABLE `dp_newproject_publish_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `path` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `filename` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `sha1` varchar(40) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `lastmod` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `exist` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COMMENT='depage::cms 1.1';
