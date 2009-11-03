CREATE TABLE `dp_newproject_xmldata_elements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_parent` int(10) unsigned DEFAULT NULL,
  `id_doc` int(10) unsigned DEFAULT '0',
  `pos` mediumint(8) unsigned DEFAULT '0',
  `name` varchar(50) CHARACTER SET latin1 DEFAULT NULL,
  `value` mediumtext CHARACTER SET latin1 NOT NULL,
  `type` enum('ELEMENT_NODE','TEXT_NODE','CDATA_SECTION_NODE','PI_NODE','COMMENT_NODE','DOCUMENT_NODE','ENTITY_REF_NODE','WAIT_FOR_REPLACE','DELETED') CHARACTER SET latin1 NOT NULL DEFAULT 'ELEMENT_NODE',
  PRIMARY KEY (`id`),
  KEY `SECONDARY` (`id_parent`,`id_doc`,`type`),
  KEY `THIRD` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COMMENT='depage::cms 1.1';
