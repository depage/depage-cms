/*
    XmlDb Docs Table
    -----------------------------------

    @tablename _xmldocs
    @connection _auth_user
    @version 1.5.0-beta.1
*/
CREATE TABLE `_xmldocs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(50) NOT NULL DEFAULT '',
  `ns` mediumtext NOT NULL,
  `entities` mediumtext NOT NULL,
  `rootid` int(11) unsigned DEFAULT NULL,
  `lastchange` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastchange_uid` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `SECONDARY` (name(100)),
  KEY `rootid` (`rootid`),
  KEY `lastchange_uid` (`lastchange_uid`),
  CONSTRAINT `_xmldocs_ibfk_1` FOREIGN KEY (`lastchange_uid`) REFERENCES `_auth_user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

/*
    @version 1.9.0
*/
ALTER TABLE _xmldocs MODIFY `ns` mediumtext NOT NULL DEFAULT '';
ALTER TABLE _xmldocs MODIFY `entities` mediumtext NOT NULL DEFAULT '';
