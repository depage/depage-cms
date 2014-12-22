/*
    Project Groups Table
    -----------------------------------

    @tablename _project_groups
    @version 1.5.0-beta.1
*/
CREATE TABLE `_project_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(35) NOT NULL DEFAULT '',
  `pos` int(11) unsigned DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

INSERT INTO `_project_groups` (`id`, `name`, `pos`) VALUES (1, 'Default', 1000000);
