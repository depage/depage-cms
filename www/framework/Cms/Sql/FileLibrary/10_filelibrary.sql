/*
    FileLibray Files Table
    -----------------------------------

    @tablename _proj_PROJECTNAME_library_files
    @version 2.2.0
*/
CREATE TABLE _proj_PROJECTNAME_library_files (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `folder` int(10) unsigned NOT NULL,
  `filename` text NOT NULL DEFAULT '',
  `mime` varchar(255) NOT NULL DEFAULT '',
  `hash` varchar(64) NOT NULL DEFAULT '',
  `filesize` int(10) unsigned NOT NULL DEFAULT 0,
  `lastmod` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `width` int(10) unsigned DEFAULT NULL,
  `height` int(10) unsigned DEFAULT NULL,
  `displayAspectRatio` float unsigned DEFAULT NULL,
  `duration` int(10) unsigned DEFAULT NULL,
  `artist` text DEFAULT '',
  `album` text DEFAULT '',
  `title` text DEFAULT '',
  `copyright` text DEFAULT '',
  `description` text DEFAULT '',
  `keywords` text DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `filename`(`folder`,`filename`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
