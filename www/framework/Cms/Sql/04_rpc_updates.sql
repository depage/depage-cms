/*
    Flash Updates Table
    -----------------------------------

    @tablename _rpc_updates
    @connection _auth_sessions
    @version 1.5.0-beta.1
*/

CREATE TABLE `_rpc_updates` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `sid` varchar(32) NOT NULL DEFAULT '',
    `projectname` varchar(35) NOT NULL DEFAULT '',
    `message` longblob,
    PRIMARY KEY (`id`),
    KEY `sid` (`sid`, `projectname`),
    CONSTRAINT `_rpc_updates_ibfk_1` FOREIGN KEY (`sid`) REFERENCES `_auth_sessions` (`sid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

