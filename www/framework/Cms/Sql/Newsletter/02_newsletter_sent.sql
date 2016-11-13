/*
    Comments Tabe
    -----------------------------------

    @tablename _proj_PROJECTNAME_newsletter_sent
    @connection _proj_PROJECTNAME_newsletter_subscribers
    @connection _proj_PROJECTNAME_xmldocs
    @version 1.5.0-beta.2
*/
CREATE TABLE `_proj_PROJECTNAME_newsletter_sent` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `newsletter_id` int(11) unsigned NOT NULL,
  `email` varchar(255)  NOT NULL DEFAULT '',
  `lang` char(5) NOT NULL DEFAULT '',
  `hash` varchar(40) NOT NULL DEFAULT '',
  `sendAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `readAt` timestamp NULL DEFAULT NULL,
  `bouncedAt` timestamp NULL DEFAULT NULL,
  `status` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY `newsletter_id` (`newsletter_id`),
  KEY `email` (`email`),
  CONSTRAINT `_proj_PROJECTNAME_newsletter_newsletter_sent_ibfk_1` FOREIGN KEY (`newsletter_id`) REFERENCES `_proj_PROJECTNAME_xmldocs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
