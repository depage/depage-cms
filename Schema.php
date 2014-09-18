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
    protected $tableNames = array();
    protected $sql        = array();
    protected $statement  = '';
    protected $comment    = false;
    /* }}} */

    /* {{{ constructor */
    /**
     * @return void
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    /* }}} */

    /* {{{ load */
    public function load($path)
    {
        $fileNames = glob($path);

        foreach($fileNames as $fileName) {
            $contents           = file($fileName);
            $lastVersion        = 0;
            $number             = 1;

            $tableName          = $this->extractTag($contents, self::TABLENAME_TAG);
            $this->tableNames[] = $tableName;

            foreach($contents as $line) {
                $version = ($this->extractTag($line, self::VERSION_TAG));

                if ($version) {
                    $this->sql[$tableName][$version][$number] = $line;
                    $lastVersion = $version;
                } elseif ($lastVersion) {
                    $this->sql[$tableName][$lastVersion][$number] = $line;
                }
                $number++;
            }
        }
    }
    /* }}} */
    /* {{{ extractTag */
    protected function extractTag($content, $tag)
    {
        if (!is_array($content)) {
            $contentArray = array($content);
        } else {
            $contentArray = $content;
        }

        foreach($contentArray as $line) {
            if (
                preg_match('/(#|--|\/\*)\s+' . $tag . '\s+(.+)/', $line, $matches)
                && count($matches) == 3
            ) {
                return trim($matches[2]); // @todo do trimming in regex
            }
        }

        return false;
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
    /* {{{ update */
    public function update()
    {
        foreach($this->tableNames as $tableName) {
            $currentVersion = $this->currentTableVersion($tableName);
            $new            = (!array_key_exists($currentVersion, $this->sql[$tableName]));

            foreach($this->sql[$tableName] as $version => $sql) {
                if ($new) {
                    foreach($sql as $number => $line) {
                        $this->commit($line, $number);
                    }
                } else {
                    $new = ($version == $this->currentTableVersion($tableName));
                }
            }
        }
    }
    /* }}} */
    /* {{{ commit */
    protected function commit($line, $number)
    {
        $skipQuotes = '"[^"]*"(*SKIP)(*F)|\'[^\']*\'(*SKIP)(*F)';

        if ($this->comment) {
            if (preg_match('/' . $skipQuotes . '|\*\//', $line)) {
                $line = preg_replace('/' . $skipQuotes . '|^.*\*\//', '', $line);
                $this->comment = false;

                $this->commit($line, $number);
            }
        } else {
            $line = preg_replace('/' . $skipQuotes . '|#.*$|--.*$|\/\*.*\*\//', '', $line);

            if (preg_match('/' . $skipQuotes . '|\/\*/', $line)) {
                $line = preg_replace('/' . $skipQuotes . '|\/\*.*$/', '', $line);
                $this->comment = true;
            }

            $queue = preg_split('/' . $skipQuotes . '|(;)/', $line, 0, PREG_SPLIT_DELIM_CAPTURE);

            foreach($queue as $element) {
                if ($element == ';') {
                    $this->execute(preg_replace('/\s+/', ' ', trim($this->statement)), $number);
                    $this->statement = '';
                } else {
                    $this->statement .= $element . ' ';
                }
            }
        }
    }
    /* }}} */
    /* {{{ execute */
    protected function execute($statement, $lineNumber) {
        $preparedStatement = $this->pdo->prepare($statement);
        $preparedStatement->execute();
    }
    /* }}} */
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
