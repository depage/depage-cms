/*
    Published Urls Table
    -----------------------------------

    @tablename _proj_PROJECTNAME_published_urls
    @version 2.0.7
*/
CREATE TABLE _proj_PROJECTNAME_published_urls (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `publishId` int(10) unsigned NOT NULL DEFAULT '0',
  `pageId` int(10) unsigned NOT NULL DEFAULT '0',
  `url` text NOT NULL DEFAULT '',
  `canonical` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

CREATE INDEX publishId ON _proj_PROJECTNAME_published_urls (publishId);
