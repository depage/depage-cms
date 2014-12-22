/*
    Auth Project Table
    -----------------------------------

    @tablename _project_auth
    @connection _projects
    @connection _auth_user
    @version 1.5.0-beta.1
*/
CREATE TABLE `_project_auth` (
  `userId` int(11) unsigned DEFAULT NULL,
  `projectId` int(11) unsigned DEFAULT NULL,
  KEY `ids` (`userId`, `projectId`),
  CONSTRAINT `_project_auth_ibfk_1` FOREIGN KEY (`projectId`) REFERENCES `_projects` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `_project_auth_ibfk_2` FOREIGN KEY (`userId`) REFERENCES `_auth_user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
