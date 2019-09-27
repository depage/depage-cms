/*
    Published Files Table
    -----------------------------------

    @tablename _proj_PROJECTNAME_transform_used_docs
    @version 1.5.0-beta.1
*/
CREATE TABLE `_proj_PROJECTNAME_transform_used_docs` (
  `transformId` int(10)  unsigned NOT NULL DEFAULT '0',
  `docId` int(10) unsigned NOT NULL DEFAULT '0',
  `template` varchar(255) NOT NULL DEFAULT '',
  KEY ids (`transformId`, `docId`),
  KEY template (`template`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*
    @version 1.5.11
*/
DELETE FROM `_proj_PROJECTNAME_transform_used_docs` WHERE 1=1;
ALTER TABLE `_proj_PROJECTNAME_transform_used_docs` CHANGE `template` `template` varchar(30) NOT NULL DEFAULT '';
ALTER TABLE `_proj_PROJECTNAME_transform_used_docs` DROP KEY `ids`;
ALTER TABLE `_proj_PROJECTNAME_transform_used_docs` DROP KEY `template`;
ALTER TABLE `_proj_PROJECTNAME_transform_used_docs` ADD UNIQUE `unique_index`(`transformId`, `docId`, `template`);

/*
    @version 2.1
*/
ALTER TABLE `_proj_PROJECTNAME_transform_used_docs` CHANGE `template` `template` varchar(45) NOT NULL DEFAULT '';
