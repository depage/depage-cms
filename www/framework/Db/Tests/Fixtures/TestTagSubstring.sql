# @tablename    testTable
# @connection   testView
# @connection   testTableSubstring

# @version version 0.1
    CREATE TABLE testTable (
        uid int(10) unsigned NOT NULL DEFAULT '0',
        pid int(10) unsigned NOT NULL DEFAULT '0'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

# @version version 0.2
    CREATE VIEW testView AS
        SELECT id, name
        FROM testTableSubstring
        WHERE someCondition=TRUE;
