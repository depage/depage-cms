# @tablename test

    CREATE TABLE test (
        uid int(10) unsigned NOT NULL DEFAULT '0',
        pid int(10) unsigned NOT NULL DEFAULT '0'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

# @version version 0.2
    ALTER TABLE test
    ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid;
