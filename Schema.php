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

        if (empty($this->fileNames)) {
            throw new Exceptions\FileNotFoundException("No file found matching \"{$path}\"."); 
        }

        foreach ($this->fileNames as $fileName) {
            $contents       = file($fileName);
            $number         = 1;
            $parser         = new SQLParser();
            $header         = true;
            $versions       = array();
            $tableName;

            foreach ($contents as $line) {
                // @todo refactor extract tags method
                $versionTag = $this->extractTag($line, self::VERSION_TAG);
                if ($versionTag) {
                    $versions[$versionTag] = $number;
                }
                $parser->parseLine($line);

                if ($header) {
                    $tableNameTag = $this->extractTag($line, self::TABLENAME_TAG);
                    if ($tableNameTag) {
                        if (isset($tableName)) {
                            throw new Exceptions\MultipleTableNamesException("More than one tablename tags in \"{$fileName}\".");
                        } else {
                            $tableName = $tableNameTag;
                            $parser->replace($tableName, $this->replace($tableName));
                        }
                    }
                    $connectionTag = $this->extractTag($line, self::CONNECTION_TAG);
                    if ($connectionTag) {
                        $parser->replace($connectionTag, $this->replace($connectionTag));
                    }

                    if (!$parser->isEndOfStatement()) {
                        $header = false;
                        if (!isset($tableName)) {
                            throw new Exceptions\TableNameMissingException("Tablename tag missing in \"{$fileName}\".");
                        }
                        if (empty($versions)) {
                            throw new Exceptions\UnversionedCodeException("There is code without version tags in \"{$fileName}\" at line {$number}.");
                        }
                    }
                }

                $statements = $parser->getStatements();
                if ($statements) {
                    $statementBlock[$number] = $statements;
                }

                $number++;
            }

            $currentVersion = $this->currentTableVersion($this->replace($tableName));
            $keys           = array_keys($versions);
            $search         = array_search($currentVersion, $keys);

            if ($search === false) {
                $startKey = $keys[0];
            } elseif ($search == count($keys) - 1) {
                $startKey = false;
            } else {
                $startKey = $keys[$search + 1];
            }

            if ($startKey !== false) {
                $startLine = $versions[$startKey];

                foreach ($statementBlock as $lineNumber => $statements) {
                    if ($lineNumber >= $startLine) {
                        $this->execute($lineNumber, $statements);
                    }
                }
            }
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
        $version    = $row['TABLE_COMMENT'];

        if ($row && $row['TABLE_COMMENT'] == '') {
            throw new Exceptions\VersionIdentifierMissingException("Missing version identifier in table \"{$tableName}\".");
        }

        return $version;
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
    /* {{{ execute */
    protected function execute($number, $statements) {
        foreach ($statements as $statement) {
            try {
                $preparedStatement = $this->pdo->prepare($statement);
                $preparedStatement->execute();
            } catch (\PDOException $e) {
                throw new Exceptions\SQLExecutionException($e, $number, $statement);
            }
        }
    }
    /* }}} */
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
