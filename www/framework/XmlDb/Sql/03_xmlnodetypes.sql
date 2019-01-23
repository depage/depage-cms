/*
    XmlDb Nodetypes Table
    -----------------------------------

    @tablename _xmlnodetypes
    @version 1.5.0-beta.1
*/
CREATE TABLE `_xmlnodetypes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pos` int(10) unsigned NOT NULL,
  `nodename` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `newname` varchar(255) NOT NULL DEFAULT '',
  `validparents` varchar(255) NOT NULL DEFAULT '',
  `icon` varchar(255) NOT NULL DEFAULT '',
  `xmltemplate` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4;

/*
    @version 2.0.0
*/
