depage-db
=======================================================

depage-db is a small wrapper around the pdo object which allows late/on demand
initializition of the database connection.

It includes a schema Class to import or update SQL database Schemata. Schemata
are described by common SQL files with update instructions in comments. SQL
files can also be templates to allow for prefixing/replacement of table
identifiers.

#Instruction tags

- @version
    - mandatory, labels the following code with a version identifier
- @tablename
    - mandatory, declares database tablename to be updated
    - marks table identifier for replacement function
- @connection
    - marks table identifiers for replacement function

#Example

```mysql
# @tablename example
# @version 0.1

CREATE TABLE example (
    uid int(10) unsigned NOT NULL DEFAULT '0',
    pid int(10) unsigned NOT NULL DEFAULT '0'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

# @version 0.2
ALTER TABLE example
    ADD COLUMN did int(10) unsigned NOT NULL DEFAULT '0' AFTER pid;
```

```php
<?php
$schema = new Schema($pdo);
$schema->setReplace(
    function ($tableName) {
        return 'prefix_' . $tableName;
    }
);
$schema->loadFile('schema.sql');
```

#License (dual)

- GPL2: <http://www.gnu.org/licenses/gpl-2.0.html>
- MIT: <http://www.opensource.org/licenses/mit-license.php>

