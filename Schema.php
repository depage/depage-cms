<?php
/**
 * @file    framework/DB/Schema.php
 *
 * depage database module
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 * @author    Sebastian Reinhold [sebastian@bitbernd.de]
 */

namespace depage\DB;

class Schema
{
    /* {{{ constants */
    const TABLENAME_TAG     = '@tablename';
    const CONNECTION_TAG    = '@connection';
    const VERSION_TAG       = '@version';
    /* }}} */
    /* {{{ variables */
    protected $tableNames       = array();
    protected $connections      = array();
    protected $fileNames        = array();
    protected $sql              = array();
    protected $replaceFunction  = array();
    /* }}} */

    /* {{{ constructor */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    /* }}} */

    /* {{{ load */
    public function load($path)
    {
        $this->fileNames = glob($path);

        if (empty($this->fileNames))
            throw new Exceptions\FileNotFoundException("No file found matching \"{$path}\"."); 

        foreach($this->fileNames as $fileName) {
            $contents       = file($fileName);
            $lastVersion    = 0;
            $number         = 1;

            foreach($contents as $line) {
                $version = $this->extractTag($line, self::VERSION_TAG);
                if ($version) {
                    $this->sql[$fileName][$version][$number] = $line;
                    $lastVersion = $version;
                } elseif ($lastVersion) {
                    $this->sql[$fileName][$lastVersion][$number] = $line;
                }

                $tableNameTag = $this->extractTag($line, self::TABLENAME_TAG);
                if ($tableNameTag) {
                    if (isset($this->tableNames[$fileName])) {
                        throw new Exceptions\MultipleTableNamesException("More than one tablename tags in \"{$fileName}\".");
                    } else {
                        $this->tableNames[$fileName] = $tableNameTag;
                    }
                }

                $connectionTag = $this->extractTag($line, self::CONNECTION_TAG);
                if ($connectionTag) {
                    $this->connections[$fileName][] = $connectionTag;
                }

                $number++;
            }

            if (empty($this->tableNames[$fileName]))
                throw new Exceptions\TableNameMissingException("Tablename tag missing in \"{$fileName}\".");
        }
    }
    /* }}} */
    /* {{{ extractTag */
    protected function extractTag($line, $tag)
    {
        $match = false;

        if (
            preg_match('/(#|--|\/\*)\s+' . $tag . '\s+(\S.*\S)\s*$/', $line, $matches)
            && count($matches) == 3
        ) {
            $match = $matches[2];
        }

        return $match;
    }
    /* }}} */
    /* {{{ currentTableVersion */
    protected function currentTableVersion($tableName)
    {
        $query      = 'SELECT TABLE_COMMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = "' . $tableName . '" LIMIT 1';
        $statement  = $this->pdo->query($query);
        $statement->execute();
        $row        = $statement->fetch();

        return $row['TABLE_COMMENT'];
    }
    /* }}} */
    /* {{{ setReplace */
    public function setReplace($replaceFunction) {
        $this->replaceFunction = $replaceFunction;
    }
    /* }}} */
    /* {{{ replace */
    protected function replace($tableName) {
        if (is_callable($this->replaceFunction)) {
            $tableName = call_user_func($this->replaceFunction, $tableName);
        }

        return $tableName;
    }
    /* }}} */
    /* {{{ update */
    public function update()
    {
        foreach($this->fileNames as $fileName) {
            $tableName      = $this->tableNames[$fileName];
            $currentVersion = $this->currentTableVersion($this->replace($tableName));
            $new            = (!array_key_exists($currentVersion, $this->sql[$fileName]));
            $block          = array();

            // @todo refactor -> extractNewCode
            foreach($this->sql[$fileName] as $version => $sql) {
                if ($new) {
                    foreach($sql as $number => $line) {
                        $block[$number] = $line;
                    }
                } else {
                    $new = ($version == $currentVersion);
                }
            }

            $names      = (isset($this->connections[$fileName])) ? $this->connections[$fileName] : array();
            $names[]    = $tableName;
            $parser     = new SQLParser();

            foreach($names as $name) {
                $dictionary[$name] = $this->replace($name);
            }
            $parser->replace(array_keys($dictionary), $dictionary);

            foreach($parser->parse($block) as $number => $statements) {
                $this->execute($number, $statements);
            }
        }
    }
    /* }}} */
    /* {{{ execute */
    protected function execute($number, $statements) {
        foreach($statements as $statement) {
            $preparedStatement = $this->pdo->prepare($statement);
            $preparedStatement->execute();
        }
    }
    /* }}} */
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
