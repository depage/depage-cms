/*
    Comments Tabe
    -----------------------------------

    @tablename _proj_PROJECTNAME_comments
    @version 1.5.0-beta.1
*/
CREATE TABLE `_proj_PROJECTNAME_comments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(11) DEFAULT NULL,
  `comment` text CHARACTER SET latin1 NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `author_name` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `author_email` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `author_url` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `author_ip` binary(16) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `author_user_id` int(11) DEFAULT NULL,
  `type` varchar(10) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `hidden` tinyint(4) NOT NULL DEFAULT '0',
  `spam` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
