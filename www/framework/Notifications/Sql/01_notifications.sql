/*
    Notification Table
    -----------------------------------

    @tablename _auth_notifications
    @connection _auth_user
    @connection _auth_sessions
    @version 1.5.0-beta.1
*/

CREATE TABLE `_auth_notifications` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `sid` varchar(32) DEFAULT NULL,
    `uid` int(11) unsigned DEFAULT NULL,
    `tag` varchar(255) NOT NULL DEFAULT '',
    `title` varchar(255) NOT NULL DEFAULT '',
    `message` longblob NOT NULL DEFAULT '',
    `options` longblob NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    CONSTRAINT `_auth_notifications_ibfk_1` FOREIGN KEY (`sid`) REFERENCES `_auth_sessions` (`sid`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `_auth_notifications_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `_auth_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

