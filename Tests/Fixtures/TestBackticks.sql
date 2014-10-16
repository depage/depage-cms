# @tablename    table backticks
# @connection   view backticks
# @connection   connection backticks

# @version version 0.1
    CREATE TABLE `table backticks` (
        uid int(10) unsigned NOT NULL DEFAULT '0',
        pid int(10) unsigned NOT NULL DEFAULT '0'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

# @version version 0.2
    CREATE VIEW `view backticks` AS
        SELECT id, name
        FROM `connection backticks`
        WHERE someCondition=TRUE;
