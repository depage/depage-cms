CREATE TABLE `dp_newproject_transform_cache` (
  `id_project` int(10) unsigned NOT NULL DEFAULT '0',
  `id_page` int(10) unsigned NOT NULL DEFAULT '0',
  `lang` varchar(12) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `type` varchar(100) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `access` enum('browse','preview','index','css') CHARACTER SET latin1 NOT NULL DEFAULT 'browse',
  `ids_used` text CHARACTER SET latin1 NOT NULL,
  `content_type` varchar(30) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `content_encoding` varchar(10) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `value` mediumtext CHARACTER SET latin1 NOT NULL,
  KEY `SECONDARY` (`id_project`,`id_page`,`lang`,`type`,`access`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='depage::cms 1.1';
