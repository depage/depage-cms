<?php
/**
 * @file    framework/Db/Schema.php
 *
 * depage database module
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 * @author    Sebastian Reinhold [sebastian@bitbernd.de]
 */

namespace Depage\Db;

class Schema
{
    // {{{ constants
    const TABLENAME_TAG     = '@tablename';
    const CONNECTION_TAG    = '@connection';
    const VERSION_TAG       = '@version';
    // }}}
    // {{{ variables
    protected $replaceFunction = array();
    protected $updateData = array();
    protected $dryRun;
    // }}}

    // {{{ constructor
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    // }}}

    // {{{ loadGlob
    public function loadGlob($path)
    {
        $fileNames = glob($path);
        if (empty($fileNames)) {
            trigger_error('No file found matching "' . $path . '".', E_USER_WARNING);
        }
        sort($fileNames);

        foreach ($fileNames as $fileName) {
            $this->loadFile($fileName);
        }

        return $this;
    }
    // }}}
    // {{{ loadFile
    public function loadFile($fileName)
    {
        if (!is_readable($fileName)) {
            throw new Exceptions\SchemaException('File "' . $fileName . '" doesn\'t exist or isn\'t readable.');
        }

        $parser         = new SqlParser();
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
                        throw new Exceptions\SchemaException('More than one tablename tags in "' . $fileName . '".');
                    } else {
                        $tableName = $tag[self::TABLENAME_TAG];
                        $dictionary[$tableName] = $this->replace($tableName);
                    }
                }

                if ($tag[self::CONNECTION_TAG]) {
                    $dictionary[$tag[self::CONNECTION_TAG]] = $this->replace($tag[self::CONNECTION_TAG]);
                }

                if (!$parser->isEndOfStatement()) {
                    $header = false;
                    if (!isset($tableName)) {
                        throw new Exceptions\SchemaException('Tablename tag missing in "' . $fileName . '".');
                    }
                    if (empty($versions)) {
                        throw new Exceptions\SchemaException('There is code without version tags in "' . $fileName . '" at line ' . $number . '.');
                    }
                }
            }

            $this->checkDictionary($dictionary);
            $replaced = $this->replaceIdentifiers($dictionary, $split);
            $statements = $parser->tidy($replaced);

            if ($statements) {
                $statementBlock[$number] = $statements;
            }
        }

        if(!$parser->isEndOfStatement()) {
            throw new Exceptions\SchemaException('Incomplete statement at the end of "' . $fileName . '".');
        }

        $this->updateData[] = array(
            'tableName' => $this->replace($tableName),
            'statementBlock' => $statementBlock,
            'versions' => $versions
        );

        return $this;
    }
    // }}}
    // {{{ dryRun
    public function dryRun()
    {
        $this->dryRun = true;
        $this->history = array();
        $this->run();
        return $this->history;
    }
    // }}}
    // {{{ update
    public function update()
    {
        $this->dryRun = false;
        $this->run();
    }
    // }}}
    // {{{ run
    protected function run()
    {
        foreach($this->updateData as $dataSet) {
            extract($dataSet);
            $keys = array_keys($versions);

            if ($this->tableExists($tableName)) {
                $currentVersion = $this->currentTableVersion($tableName);
                $search = array_search($currentVersion, $keys);

                if ($search == count($keys) - 1) {
                    $startKey = false;
                } elseif ($search === false) {
                    $startKey = false;
                    trigger_error('Current table version (' . $currentVersion . ') not in schema file.', E_USER_WARNING);
                } else {
                    $startKey = $keys[$search + 1];
                }
            } else {
                $startKey = $keys[0];
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

        $this->updateData = array();
    }
    // }}}
    // {{{ execute
    protected function execute($number, $statements)
    {
        foreach ($statements as $statement) {
            if ($this->dryRun) {
                $this->history[] = $statement;
            } else {
                try {
                    $preparedStatement = $this->pdo->prepare($statement);
                    $preparedStatement->execute();
                } catch (\PDOException $e) {
                    if (class_exists('\ReflectionClass', false)) {
                        $PDOExceptionReflection = new \ReflectionClass('PDOException');
                        $line = $PDOExceptionReflection->getProperty('line');
                        $message = $PDOExceptionReflection->getProperty('message');

                        $line->setAccessible(true);
                        $line->setValue($e, $number);
                        $line->setAccessible(false);
                        $message->setAccessible(true);
                        $message->setValue($e, preg_replace('/ at line [0-9]+$/', ' at line ' . $number, $message->getValue($e)));
                        $message->setAccessible(false);
                    }
                    throw $e;
                }
            }
        }
    }
    // }}}

    // {{{ tableExists
    protected function tableExists($tableName)
    {
        $exists = false;

        try {
            $this->pdo->query('SELECT 1 FROM ' . $tableName);
            $exists = true;
        } catch (\PDOException $e) {
            // only catch "table doesn't exist" exception
            if (!preg_match("/SQLSTATE\\[42S02\\]/", $e->getMessage())) {
                throw $e;
            }
        }

        return $exists;
    }
    // }}}
    // {{{ currentTableVersion
    protected function currentTableVersion($tableName)
    {
        try {
            $query = 'SELECT TABLE_COMMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = "' . $tableName . '" LIMIT 1';
            $statement = $this->pdo->query($query);
            $statement->execute();
            $row = $statement->fetch();

            if ($row['TABLE_COMMENT'] == '') {
                throw new Exceptions\SchemaException('Missing version identifier in table "' . $tableName . '".');
            }

            $version = $row['TABLE_COMMENT'];
        } catch (\PDOException $e) {
            $query = 'SHOW CREATE TABLE ' . $tableName;
            $statement = $this->pdo->query($query);
            $statement->execute();
            $row = $statement->fetch();

            if (!preg_match('/COMMENT=\'(.*)\'/', $row[1], $matches)) {
                throw new Exceptions\SchemaException('Missing version identifier in table "' . $tableName . '".');
            }

            $version = array_pop($matches);
        }

        return $version;
    }
    // }}}
    // {{{ updateTableVersion
    protected function updateTableVersion($tableName, $version)
    {
        $statement = 'ALTER TABLE ' . $tableName . ' COMMENT \'' . $version . '\'';
        $this->execute(null, array($statement));
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

        $values = array_values($comments);
        $values = array_shift($values);
        $comment = $values['string'] ?? "";

        foreach ($tags as $tag) {
            if (
                count($comments) == 1
                && preg_match('/' . $tag . '\s+(\S.*\S)\s*$/', $comment, $matches)
                && count($matches) == 2
            ) {
                // @todo get rid of '*/' in preg_match
                $matchedTags[$tag] = preg_replace('/\s*\*\/\s*$/', '', $matches[1]);
            } else {
                $matchedTags[$tag] = false;
            }
        }

        return $matchedTags;
    }
    // }}}
    // {{{ checkDictionary
    protected function checkDictionary($dictionary)
    {
        $tags = array_keys($dictionary);
        while ($tags) {
            $current = array_pop($tags);

            foreach ($tags as $test) {
                if (
                    strpos($current, $test) !== false ||
                    strpos($test, $current) !== false
                ) {
                    throw new Exceptions\SchemaException('Tags cannot be substrings of each other ("' . $current . '", "' . $test . '").');
                }
            }
        }
    }
    // }}}
    // {{{ setReplace
    public function setReplace($replaceFunction)
    {
        $this->replaceFunction = $replaceFunction;

        return $this;
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
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
