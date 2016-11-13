/*
    Comments Tabe
    -----------------------------------

    @tablename _proj_PROJECTNAME_newsletter_subscribers
    @version 1.5.0-beta.2
*/
CREATE TABLE `_proj_PROJECTNAME_newsletter_subscribers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255)  NOT NULL DEFAULT '',
  `firstame` varchar(255)  NOT NULL DEFAULT '',
  `lastname` varchar(255)  NOT NULL DEFAULT '',
  `lang` char(5) NOT NULL DEFAULT '',
  `description` varchar(255)  NOT NULL DEFAULT '',
  `category` varchar(35)  NOT NULL DEFAULT 'default',
  `subscribedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `SECONDARY` (`email`, `category`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

