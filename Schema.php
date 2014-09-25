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
    const TABLENAME_TAG = '@tablename';
    const VERSION_TAG   = '@version';
    /* }}} */
    /* {{{ variables */
    protected $tableNames   = array();
    protected $fileNames    = array();
    protected $sql          = array();
    protected $parser;
    /* }}} */

    /* {{{ constructor */
    public function __construct($pdo)
    {
        $this->pdo      = $pdo;
        $this->parser   = new SQLParser;
    }
    /* }}} */

    /* {{{ load */
    public function load($path)
    {
        $this->fileNames = glob($path);
        // @todo complain when fileNames is empty
        // @todo complain when tablename tag is missing

        foreach($this->fileNames as $fileName) {
            $contents           = file($fileName);
            $lastVersion        = 0;
            $number             = 1;

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
                    $this->tableNames[$fileName][] = $tableNameTag;
                }

                $number++;
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

        return $row['TABLE_COMMENT'];
    }
    /* }}} */
    /* {{{ setReplacement */
    public function setReplacement($replacementFunction) {
        $parser->setReplacement($this->replacementFunction);
    }
    /* }}} */
    /* {{{ update */
    public function update()
    {
        foreach($this->fileNames as $fileName) {
            foreach($this->tableNames[$fileName] as $tableName) {
                $currentVersion = $this->currentTableVersion($tableName);
                $new            = (!array_key_exists($currentVersion, $this->sql[$fileName]));

                foreach($this->sql[$fileName] as $version => $sql) {
                    if ($new) {
                        foreach($sql as $number => $line) {
                            $this->parser->processLine($line);

                            foreach($this->parser->getStatements() as $statement) {
                                $this->execute($statement);
                            }
                        }
                    } else {
                        $new = ($version == $currentVersion);
                    }
                }
            }
        }
    }
    /* }}} */
    /* {{{ execute */
    protected function execute($statement) {
        $preparedStatement = $this->pdo->prepare($statement);
        $preparedStatement->execute();
    }
    /* }}} */
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
