/*
    Subtasks Table
    -----------------------------------

    @tablename _subtasks
    @connection _tasks
    @version 1.5.0-beta.1
*/
CREATE TABLE `_subtasks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int(11) unsigned NOT NULL,
  `depends_on` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `status` varchar(255) DEFAULT NULL,
  `php` longblob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  CONSTRAINT `_subtasks_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `_tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*
    @version 2.1.1
*/
ALTER TABLE `_subtasks` ADD KEY `status` (`status`);
