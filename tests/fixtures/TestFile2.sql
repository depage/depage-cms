# @tablename test2

# @version version 0.1
    CREATE TABLE test2 (
        uid int(10) unsigned NOT NULL DEFAULT '0',
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='version 0.1';

# @version version 0.2
    ALTER TABLE test2
    ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid;

    ALTER TABLE test2
    COMMENT 'version 0.2';
