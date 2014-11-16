/*
    Session Table
    -----------------------------------

    @tablename auth_sessions
    @connection auth_user
    @version 1.5.0-beta.1
*/

CREATE TABLE `auth_sessions` (
    `sid` varchar(32) NOT NULL DEFAULT '',
    `userid` int(11) unsigned DEFAULT NULL,
    `project` varchar(50) DEFAULT NULL,
    `ip` varchar(29) DEFAULT NULL,
    `dateLastUpdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `dateLogin` datetime DEFAULT NULL,
    `useragent` varchar(255) NOT NULL DEFAULT '',
    `sessionData` longblob,
    PRIMARY KEY (`sid`),
    KEY `userId` (`userid`),
    CONSTRAINT `userId` FOREIGN KEY (`userid`) REFERENCES `auth_user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

