/*
    Tasks Table
    -----------------------------------

    @tablename _tasks
    @version 1.5.0-beta.1
*/
CREATE TABLE `_tasks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `projectname` varchar(35) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `time_added` datetime NOT NULL,
  `time_started` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*
    @version 2.1.1
*/
ALTER TABLE `_tasks` ADD KEY `scondary` (`name`, `status`);
