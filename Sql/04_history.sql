/*
    Document History Table
    -----------------------------------

    @tablename _history
    @connection _auth_user
    @version 1.5.0-beta.1
*/
CREATE TABLE `_history` (
  `doc_id` int(11) unsigned NOT NULL,
  `hash` varchar(40) NOT NULL DEFAULT '',
  `xml` mediumtext NOT NULL,
  `last_saved_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_id` int(11) unsigned NOT NULL,
  `published` tinyint unsigned NOT NULL,
  CONSTRAINT `_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `_auth_user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*
    @version 1.5.14
*/

ALTER TABLE _history ADD COLUMN `first_saved_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER xml;

/*
    @version 1.5.25
*/
ALTER TABLE _history MODIFY `hash` varchar(64) NOT NULL DEFAULT '';

/*
    @version 2.0.0
*/
