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
    const VERSION_TAG       = 'version';
    const VERSION_DELIMITER = 'Version:';
    /* }}} */
    /* {{{ variables */
    protected $tableNames = array();
    protected $sql        = array();
    protected $statement  = '';
    protected $comment    = false;
    /* }}} */

    /* {{{ constructor */
    /**
     *
     * @return void
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    /* }}} */

    /* {{{ load */
    public function load($tableNames = array())
    {
        $this->tableNames = $tableNames;

        foreach($tableNames as $tableName) {
            $contents       = file($tableName . '.sql');
            $lastVersion    = false;
            $number         = 1;

            foreach($contents as $line) {
                $version = ($this->readVersionDelimiter($line));

                if ($version) {
                    $this->sql[$tableName][$version][$number] = $line;
                    $lastVersion = $version;
                } else {
                    $this->sql[$tableName][$lastVersion][$number] = $line;
                }
                $number++;
            }
        }
    }
    /* }}} */
    /* {{{ currentTableVersion */
    protected function currentTableVersion($tableName)
    {
        $query      = 'SELECT TABLE_COMMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = "' . $tableName . '" LIMIT 1';
        $statement  = $this->pdo->query($query);
        $statement->execute();
        $row        = $statement->fetch();

        return str_replace(self::VERSION_TAG . ' ', '', $row['TABLE_COMMENT']);
    }
    /* }}} */
    /* {{{ readVersionDelimiter */
    protected function readVersionDelimiter($line)
    {
        if (
            preg_match('/' . self::VERSION_DELIMITER . '\s+' . self::VERSION_TAG . ' (.?[0-9]*\.?[0-9]+)/', $line, $matches)
            && count($matches) == 2
        ) {
            return $matches[1];
        }

        return false;
    }
    /* }}} */
    /* {{{ update */
    public function update()
    {
        foreach($this->tableNames as $tableName) {
            $new = false;
            foreach($this->sql[$tableName] as $version => $sql) {
                if ($new) {
                    foreach($sql as $number => $line) {
                        $this->commit($line, $number);
                    }
                } else {
                    $new = ($version == $this->currentTableVersion($tableName));
                }
            }

            if (!$new) {
                foreach($this->sql[$tableName] as $sql) {
                    foreach($sql as $number => $line) {
                        $this->commit($line, $number);
                        // @todo boilerplate
                    }
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
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
