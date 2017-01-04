/*
    Session Table
    -----------------------------------

    @tablename _auth_log
    @connection _auth_user
    @version 1.5.3
*/

CREATE TABLE `_auth_log` (
    `userId` int(11) unsigned DEFAULT NULL,
    `dateLogin` datetime DEFAULT NULL,
    `ip` varchar(29) DEFAULT NULL,
    `useragent` varchar(255) NOT NULL DEFAULT '',
    KEY `userId` (`userId`),
    CONSTRAINT `_auth_log_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `_auth_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
