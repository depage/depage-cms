/*
    User Table
    -----------------------------------

    @tablename _auth_user
    @version 1.5.0-beta.1
*/

CREATE TABLE `_auth_user` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `type` enum('Depage\\Auth\\User') DEFAULT NULL,
    `name` varchar(32) NOT NULL DEFAULT '',
    `fullname` varchar(100) NOT NULL DEFAULT '',
    `sortname` varchar(25) NOT NULL DEFAULT '',
    `passwordhash` varchar(255) NOT NULL DEFAULT '',
    `email` varchar(100) NOT NULL DEFAULT '',
    `settings` mediumtext NOT NULL,
    `level` tinyint(3) unsigned NOT NULL DEFAULT '0',
    `dateRegistered` datetime DEFAULT NULL,
    `dateLastlogin` datetime DEFAULT NULL,
    `dateUpdated` datetime DEFAULT NULL,
    `dateResetPassword` datetime DEFAULT NULL,
    `confirmId` varchar(255) DEFAULT NULL,
    `resetPasswordId` varchar(255) DEFAULT NULL,
    `loginTimeout` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

