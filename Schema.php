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
    // {{{ constants
    const TABLENAME_TAG     = '@tablename';
    const CONNECTION_TAG    = '@connection';
    const VERSION_TAG       = '@version';
    // }}}
    // {{{ variables
    protected $replaceFunction  = array();
    // }}}

    // {{{ constructor
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    // }}}

    // {{{ getFileNames
    protected function getFileNames($path)
    {
        $fileNames = glob($path);

        if (empty($fileNames)) {
            throw new Exceptions\FileNotFoundException("No file found matching \"{$path}\"."); 
        }
        sort($fileNames);

        return $fileNames;
    }
    // }}}
    // {{{ load
    public function load($path)
    {
        foreach ($this->getFileNames($path) as $fileName) {
            $parser         = new SQLParser();
            $header         = true;
            $versions       = array();
            $dictionary     = array();
            $tableName;

            foreach (file($fileName) as $key => $line) {
                $number = $key + 1;
                $split  = $parser->split($line);
                $tag    = $this->extractTag($split);

                if ($tag[self::VERSION_TAG]) {
                    $versions[$tag[self::VERSION_TAG]] = $number;
                }

                if ($header) {
                    if ($tag[self::TABLENAME_TAG]) {
                        if (isset($tableName)) {
                            throw new Exceptions\MultipleTableNamesException("More than one tablename tags in \"{$fileName}\".");
                        } else {
                            $tableName              = $tag[self::TABLENAME_TAG];
                            $dictionary[$tableName] = $this->replace($tableName);
                        }
                    }

                    if ($tag[self::CONNECTION_TAG]) {
                        $dictionary[$tag[self::CONNECTION_TAG]] = $this->replace($tag[self::CONNECTION_TAG]);
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

                $replaced   = $this->replaceIdentifiers($dictionary, $split);
                $statements = $parser->tidy($replaced);

                if ($statements) {
                    $statementBlock[$number] = $statements;
                }
            }

            if(!$parser->isEndOfStatement()) {
                throw new Exceptions\SyntaxErrorException("Incomplete statement at the end of \"{$fileName}\".");
            }
            $this->update($this->replace($tableName), $statementBlock, $versions);
        }
    }
    // }}}
    // {{{ update
    protected function update($tableName, $statementBlock, $versions)
    {
        $currentVersion = $this->currentTableVersion($tableName);
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

            $lastVersion = $keys[count($keys) - 1];
            $this->updateTableVersion($tableName, $lastVersion);
        }
    }
    // }}}
    // {{{ extractTag
    protected function extractTag($split = array())
    {
        $tags = array(
            self::VERSION_TAG,
            self::TABLENAME_TAG,
            self::CONNECTION_TAG,
        );

        $comments       = array_filter($split, function ($v) { return $v['type'] == 'comment'; });
        $matchedTags    = array();

        foreach ($tags as $tag) {
            if (
                count($comments) == 1
                && preg_match('/' . $tag . '\s+(\S.*\S)\s*$/', $comments[0]['string'], $matches)
                && count($matches) == 2
            ) {
                $matchedTags[$tag] = $matches[1];
            } else {
                $matchedTags[$tag] = false;
            }
        }

        return $matchedTags;
    }
    // }}}
    // {{{ currentTableVersion
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
    // }}}
    // {{{ setReplace
    public function setReplace($replaceFunction)
    {
        $this->replaceFunction = $replaceFunction;
    }
    // }}}
    // {{{ replace
    protected function replace($tableName)
    {
        if (is_callable($this->replaceFunction)) {
            $tableName = call_user_func($this->replaceFunction, $tableName);
        }

        return $tableName;
    }
    // }}}
    // {{{ replaceIdentifiers
    protected function replaceIdentifiers($dictionary, $split = array())
    {
        $replaced = array_map(
            function ($v) use ($dictionary)
            {
                if ($v['type'] == 'code') {
                    $element = array(
                        'type'      => 'code',
                        'string'    => str_replace(array_keys($dictionary), $dictionary, $v['string']),
                    );
                } else {
                    $element = $v;
                }

                return $element;
            },
            $split
        );

        return $replaced;
    }
    // }}}
    // {{{ execute
    protected function execute($number, $statements)
    {
        foreach ($statements as $statement) {
            try {
                $preparedStatement = $this->pdo->prepare($statement);
                $preparedStatement->execute();
            } catch (\PDOException $e) {
                throw new Exceptions\SQLExecutionException($e, $number, $statement);
            }

        }
    }
    // }}}
    // {{{ updateTableVersion
    protected function updateTableVersion($tableName, $version)
    {
        $statement = 'ALTER TABLE ' . $tableName . ' COMMENT \'' . $version . '\'';

        $preparedStatement = $this->pdo->prepare($statement);
        $preparedStatement->execute();
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
