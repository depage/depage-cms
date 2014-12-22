/*
    Project Table
    -----------------------------------

    @tablename _projects
    @connection _project_groups
    @version 1.5.0-beta.1
*/
CREATE TABLE `_projects` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(35) NOT NULL DEFAULT '',
  `fullname` varchar(255) NOT NULL DEFAULT '',
  `groupId` int(11) unsigned DEFAULT 1,
  PRIMARY KEY (`id`,`name`),
  CONSTRAINT `_projects_ibfk_1` FOREIGN KEY (`groupId`) REFERENCES `_project_groups` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
