/*
    Published Files Table
    -----------------------------------

    @tablename _proj_PROJECTNAME_published_files
    @version 1.5.0-beta.1
*/
CREATE TABLE _proj_PROJECTNAME_published_files (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `publishId` int(10) unsigned NOT NULL DEFAULT '0',
  `filename` text NOT NULL DEFAULT '',
  `hash` varchar(40) NOT NULL DEFAULT '',
  `lastmod` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `exist` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

/*
    @version 1.5.0-beta.2
*/
CREATE INDEX publishId ON _proj_PROJECTNAME_published_files (publishId);
CREATE INDEX filename ON _proj_PROJECTNAME_published_files (filename(20));

/*
    @version 1.5.11
*/
ALTER TABLE _proj_PROJECTNAME_published_files DROP KEY filename;
ALTER TABLE _proj_PROJECTNAME_published_files ADD filenamehash varchar(40) NOT NULL DEFAULT '' AFTER filename;
UPDATE _proj_PROJECTNAME_published_files SET filenamehash = SHA1(filename);
ALTER TABLE _proj_PROJECTNAME_published_files ADD UNIQUE `filename`(`publishId`,`filenamehash`);
