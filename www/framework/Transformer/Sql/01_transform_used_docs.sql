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
