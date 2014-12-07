/*
    Auth Project Table
    -----------------------------------

    @tablename _project_auth
    @connection _projects
    @connection _auth_user
    @version 1.5.0-beta.1
*/
CREATE TABLE `_project_auth` (
  `userid` int(11) unsigned DEFAULT NULL,
  `projectid` int(11) unsigned DEFAULT NULL,
  KEY `ids` (`userid`, `projectid`),
  CONSTRAINT `projectid` FOREIGN KEY (`projectid`) REFERENCES `_projects` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `projectuserid` FOREIGN KEY (`userid`) REFERENCES `_auth_user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
