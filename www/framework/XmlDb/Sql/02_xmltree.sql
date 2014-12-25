/*
    XmlDb Tree Table
    -----------------------------------

    @tablename _proj_PROJECTNAME_xmltree
    @connection _proj_PROJECTNAME_xmldocs
    @version 1.5.0-beta.1
*/
CREATE TABLE `_proj_PROJECTNAME_xmltree` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_doc` int(10) unsigned DEFAULT '0',
  `id_parent` int(10) unsigned DEFAULT NULL,
  `pos` mediumint(8) unsigned DEFAULT '0',
  `name` varchar(50) DEFAULT NULL,
  `value` mediumtext NOT NULL,
  `type` enum('ELEMENT_NODE','TEXT_NODE','CDATA_SECTION_NODE','PI_NODE','COMMENT_NODE','ENTITY_REF_NODE','WAIT_FOR_REPLACE','DELETED') DEFAULT 'ELEMENT_NODE',
  PRIMARY KEY (`id`),
  KEY `SECONDARY` (`id_parent`,`id_doc`,`type`),
  KEY `THIRD` (`name`),
  KEY `id_doc` (`id_doc`),
  CONSTRAINT `_proj_PROJECTNAME_xmltree_ibfk_1` FOREIGN KEY (`id_parent`) REFERENCES `_proj_PROJECTNAME_xmltree` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `_proj_PROJECTNAME_xmltree_ibfk_2` FOREIGN KEY (`id_doc`) REFERENCES `_proj_PROJECTNAME_xmldocs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
