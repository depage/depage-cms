/*
    Published Files Table
    -----------------------------------

    @tablename _proj_PROJECTNAME_published_files
    @version 1.5.0-beta.1
*/
CREATE TABLE `_proj_PROJECTNAME_published_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `filename` text NOT NULL DEFAULT '',
  `hash` varchar(40) NOT NULL DEFAULT '',
  `lastmod` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `exist` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=801 DEFAULT CHARSET=utf8mb4;
