/*
    User Table
    -----------------------------------

    @tablename _auth_user
    @version 1.5.0-beta.1
*/

CREATE TABLE `_auth_user` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `type` varchar(100) NOT NULL DEFAULT '',
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

INSERT INTO `_auth_user` (`id`, `type`, `name`, `fullname`, `sortname`, `passwordhash`, `email`, `settings`, `level`, `dateRegistered`, `dateLastlogin`, `dateUpdated`, `dateResetPassword`, `confirmId`, `resetPasswordId`, `loginTimeout`)
VALUES
	(1, 'Depage\\Cms\\Auth\\Admin', 'root', 'Frank Hellenkamp (root)', '', '$2y$10$fxWturVSrYr6.ALesEqqdekFoMrIzqfXizvv.kBpIa3O7130Uzy/i', 'jonas@depage.net', '', 1, NULL, '2014-12-07 20:19:22', NULL, NULL, NULL, NULL, 0),
	(2, 'Depage\\Auth\\User', 'dev', 'Frank Hellenkamp (dev)', '', '646203ef7abbbda1bb1b3e1393bc9315', 'jonas@depage.net', '', 2, NULL, '2014-03-31 19:42:22', NULL, NULL, NULL, NULL, 0),
	(3, 'Depage\\Auth\\User', 'mainuser', 'Frank Hellenkamp (mainuser)', '', '8e8385a51056edfc61d0b8b13aa6b623', 'jonas@depage.net', '', 3, NULL, NULL, NULL, NULL, NULL, NULL, 0),
	(4, 'Depage\\Auth\\User', 'user', 'Frank Hellenkamp (user)', '', 'bb612c9fe29dc492fab3f4e10c0e361e', 'jonas@depage.net', '', 4, NULL, '2014-05-09 14:49:11', NULL, NULL, NULL, NULL, 0),
	(5, 'Depage\\Auth\\User', 'editor', 'Frank Hellenkamp (editor)', '', '8e7c35fefc1a5f0907b9db197d3e0481', 'jonas@depage.net', '', 5, NULL, NULL, NULL, NULL, NULL, NULL, 0);

/*
    @version 1.5.3
*/
ALTER TABLE _auth_user DROP COLUMN level;

/*
    @version 1.5.4
*/
ALTER TABLE auth_user
    CHANGE `name` `name` varchar(255) NOT NULL DEFAULT '',
    CHANGE `fullname` `fullname` varchar(255) NOT NULL DEFAULT '',
    CHANGE `sortname` `sortname` varchar(32) NOT NULL DEFAULT '',
    CHANGE `email` `email` varchar(255) NOT NULL DEFAULT '';
