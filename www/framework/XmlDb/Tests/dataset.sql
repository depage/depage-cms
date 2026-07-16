/*
    Dummy Table for initial dataset
    -----------------------------------

    @tablename _auth_user
    @connection _proj_test_xmldocs
    @connection _proj_test_xmltree
    @connection _proj_test_history
    @version 1.0
*/

CREATE TABLE `_auth_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(100) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `fullname` varchar(255) NOT NULL DEFAULT '',
  `sortname` varchar(32) NOT NULL DEFAULT '',
  `passwordhash` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `settings` mediumtext NOT NULL,
  `lang` varchar(20) NOT NULL DEFAULT '',
  `dateRegistered` datetime DEFAULT NULL,
  `dateLastlogin` datetime DEFAULT NULL,
  `dateUpdated` datetime DEFAULT NULL,
  `dateResetPassword` datetime DEFAULT NULL,
  `confirmId` varchar(255) DEFAULT NULL,
  `resetPasswordId` varchar(255) DEFAULT NULL,
  `loginTimeout` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4760 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `_auth_user` (`id`, `type`, `name`, `fullname`, `sortname`, `passwordhash`, `email`, `settings`, `dateRegistered`, `dateLastlogin`, `dateUpdated`, `dateResetPassword`, `confirmId`, `resetPasswordId`, `loginTimeout`)
VALUES
	(1,'','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,0),
	(42,'','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,0);

INSERT INTO `_proj_test_history` (`doc_id`, `hash`, `xml`, `first_saved_at`, `last_saved_at`, `user_id`, `published`)
VALUES
	(3,'c8780f81274114f9f97771cd2e1428d2c39c2961','<?xml version=\"1.0\"?><dpg:pages xmlns:db=\"http://cms.depagecms.net/ns/database\" xmlns:dpg=\"http://www.depagecms.net/ns/depage\" xmlns:pg=\"http://www.depagecms.net/ns/page\" name=\"\" db:id=\"4\" db:lastchangeUid=\"\"><pg:page name=\"Home3\" db:id=\"5\"><pg:page name=\"P3.1\" db:id=\"6\">bla bla blub <pg:page name=\"P3.1.2\" db:id=\"7\"/></pg:page><pg:page name=\"P3.2\" db:id=\"8\"/></pg:page></dpg:pages>','2016-02-03 16:03:00','2016-02-03 16:03:00',1,0),
	(3,'f80107795f6da964ce7e3ccf472b42931ea0884eb15dd40d0bc718d71ba94bf5','<?xml version=\"1.0\"?><dpg:pages xmlns:db=\"http://cms.depagecms.net/ns/database\" xmlns:dpg=\"http://www.depagecms.net/ns/depage\" xmlns:pg=\"http://www.depagecms.net/ns/page\" name=\"ver2\" db:id=\"4\" db:lastchangeUid=\"\"><pg:page name=\"Home3\" db:id=\"5\"><pg:page name=\"P3.1\" db:id=\"6\">bla bla blub <pg:page name=\"P3.1.2\" db:id=\"7\"/></pg:page><pg:page name=\"P3.2\" db:id=\"8\"/></pg:page></dpg:pages>','2016-02-03 16:02:00','2016-02-03 16:02:00',1,1),
	(3,'5ceae27386aa1518d346c3129ef9c2d530c18769','<?xml version=\"1.0\"?><dpg:pages xmlns:db=\"http://cms.depagecms.net/ns/database\" xmlns:dpg=\"http://www.depagecms.net/ns/depage\" xmlns:pg=\"http://www.depagecms.net/ns/page\" name=\"ver1\" db:id=\"4\" db:lastchangeUid=\"\"><pg:page name=\"Home3\" db:id=\"5\"><pg:page name=\"P3.1\" db:id=\"6\">bla bla blub <pg:page name=\"P3.1.2\" db:id=\"7\"/></pg:page><pg:page name=\"P3.2\" db:id=\"8\"/></pg:page></dpg:pages>','2016-02-03 16:01:00','2016-02-03 16:01:00',1,0);

INSERT INTO `_proj_test_xmldocs` (`id`, `name`, `type`, `ns`, `entities`, `rootid`, `lastchange`, `lastchange_uid`)
VALUES
	(1,'tpl_templates','Depage\\XmlDb\\XmlDoctypes\\Base','xmlns:dpg=\"http://www.depagecms.net/ns/depage\" xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\" xmlns:sec=\"http://www.depagecms.net/ns/section\" xmlns:edit=\"http://www.depagecms.net/ns/edit\" xmlns:pg=\"http://www.depagecms.net/ns/page\"','',1,'2016-02-03 16:09:05',NULL),
	(2,'tpl_newnodes','Depage\\XmlDb\\XmlDoctypes\\Base','xmlns:dpg=\"http://www.depagecms.net/ns/depage\" xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\" xmlns:sec=\"http://www.depagecms.net/ns/section\" xmlns:edit=\"http://www.depagecms.net/ns/edit\" xmlns:pg=\"http://www.depagecms.net/ns/page\"','',3,'2016-02-03 16:09:05',NULL),
	(3,'pages','Depage\\XmlDb\\XmlDoctypes\\Base','xmlns:dpg=\"http://www.depagecms.net/ns/depage\" xmlns:pg=\"http://www.depagecms.net/ns/page\"','',4,'2016-02-03 16:09:05',NULL),
	(4,'pages2','Depage\\XmlDb\\XmlDoctypes\\Base','xmlns:dpg=\"http://www.depagecms.net/ns/depage\" xmlns:pg=\"http://www.depagecms.net/ns/page\"','',9,'2016-02-03 16:09:05',NULL),
	(5,'pages3','Depage\\XmlDb\\XmlDoctypes\\Base','xmlns:dpg=\"http://www.depagecms.net/ns/depage\" xmlns:pg=\"http://www.depagecms.net/ns/page\"','',15,'2016-02-03 16:09:05',NULL);

INSERT INTO `_proj_test_xmltree` (`id`, `id_doc`, `id_parent`, `pos`, `name`, `value`, `type`)
VALUES
	(1,1,NULL,0,'dpg:templates','db:name=\"tree_name_template_root\"','ELEMENT_NODE'),
	(2,1,1,0,'dpg:tpl_template_set','db:invalid=\"del,move,name,dupl\" name=\"html\"','ELEMENT_NODE'),
	(3,2,NULL,0,'dpg:tpl_newnodes','db:name=\"tree_name_newnodes_root\"','ELEMENT_NODE'),
	(4,3,NULL,0,'dpg:pages','name=\"\"','ELEMENT_NODE'),
	(5,3,4,0,'pg:page','name=\"Home3\"','ELEMENT_NODE'),
	(6,3,5,0,'pg:page','name=\"P3.1\"','ELEMENT_NODE'),
	(7,3,6,1,'pg:page','name=\"P3.1.2\"','ELEMENT_NODE'),
	(8,3,5,1,'pg:page','name=\"P3.2\"','ELEMENT_NODE'),
	(9,4,NULL,0,'dpg:pages','name=\"\"','ELEMENT_NODE'),
	(10,4,9,0,'pg:page','name=\"Home4\"','ELEMENT_NODE'),
	(11,4,10,0,'pg:page','name=\"P4.1\"','ELEMENT_NODE'),
	(12,4,10,1,'pg:page','name=\"P4.2\"','ELEMENT_NODE'),
	(13,4,10,2,'pg:page','name=\"P4.3\"','ELEMENT_NODE'),
	(14,4,10,4,'pg:page','name=\"P4.5\"','ELEMENT_NODE'),
	(15,5,NULL,0,'dpg:pages','name=\"\"','ELEMENT_NODE'),
	(16,5,15,0,'pg:page','name=\"Home5\" multilang=\"true\" file_type=\"html\"','ELEMENT_NODE'),
	(17,5,16,0,'pg:folder','name=\"F5.1\" multilang=\"true\" file_type=\"html\"','ELEMENT_NODE'),
	(18,5,17,0,'pg:page','name=\"P5.1.1\" multilang=\"true\" file_type=\"html\"','ELEMENT_NODE'),
	(19,5,17,1,'pg:page','name=\"P5.1.2\" multilang=\"false\" file_type=\"html\"','ELEMENT_NODE'),
	(20,5,19,0,'pg:folder','name=\"F5.1.2.1\"','ELEMENT_NODE'),
	(21,5,19,2,'pg:page','name=\"P5.1.2.3\"','ELEMENT_NODE'),
	(22,5,17,2,'pg:page','name=\"P5.1.3\"','ELEMENT_NODE'),
	(23,5,17,3,'page','name=\"P5.1.4\"','ELEMENT_NODE'),
	(24,5,17,4,'dpg:page','name=\"P5.1.5\"','ELEMENT_NODE'),
	(25,5,17,5,'pg:folder','name=\"P5.1.6\"','ELEMENT_NODE'),
	(26,5,17,6,'folder','name=\"P5.1.7\"','ELEMENT_NODE'),
	(27,5,16,1,'pg:page','name=\"P5.2\"','ELEMENT_NODE'),
	(28,5,16,2,'pg:folder','name=\"F5.3\"','ELEMENT_NODE'),
	(29,5,16,5,'pg:folder','name=\"P5.5\"','ELEMENT_NODE'),
	(30,3,6,0,NULL,'bla bla blub ','TEXT_NODE'),
	(31,4,10,3,NULL,'bla bla bla ','TEXT_NODE'),
	(32,4,14,0,NULL,'bla bla blub ','TEXT_NODE'),
	(33,5,19,1,NULL,'bla bla bla ','TEXT_NODE'),
	(34,5,21,0,NULL,'bla bla blub ','TEXT_NODE'),
	(35,5,16,3,NULL,'bla bla bla ','TEXT_NODE'),
	(36,5,29,0,NULL,'bla bla bla ','TEXT_NODE');
