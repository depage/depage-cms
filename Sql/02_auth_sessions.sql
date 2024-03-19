/*
    Session Table
    -----------------------------------

    @tablename _auth_sessions
    @connection _auth_user
    @version 1.5.0-beta.1
*/

CREATE TABLE `_auth_sessions` (
    `sid` varchar(32) NOT NULL DEFAULT '',
    `userId` int(11) unsigned DEFAULT NULL,
    `project` varchar(50) DEFAULT NULL,
    `ip` varchar(29) DEFAULT NULL,
    `dateLastUpdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `dateLogin` datetime DEFAULT NULL,
    `useragent` varchar(255) NOT NULL DEFAULT '',
    `sessionData` longblob,
    PRIMARY KEY (`sid`),
    KEY `userId` (`userId`),
    CONSTRAINT `_auth_sessions_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `_auth_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*
    @version 1.5.3
*/
/*
    @version 1.5.4
*/
ALTER TABLE `_auth_sessions` MODIFY `ip` varchar(45) DEFAULT NULL;

/*
    @version 2.5.0
*/
ALTER TABLE `_auth_sessions` ADD `seqno` int(11) unsigned NOT NULL DEFAULT 0 AFTER `ip`;
