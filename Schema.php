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
    private $tableNames = array();
    private $sql        = array();
    /* }}} */

    /* {{{ constructor */
    /**
     *
     * @return void
     */
    public function __construct($pdo, $tableNames)
    {
        $this->tableNames   = $tableNames;
        $this->pdo          = $pdo;
    }
    /* }}} */

    /* {{{ load */
    public function load()
    {
        foreach($this->tableNames as $tableName) {
            $this->sql[$tableName] = file($tableName . '.sql');
        }
    }
    /* }}} */
    /* {{{ startLineByVersion */
    private function startLineByVersion($tableName, $version)
    {
        foreach($this->sql[$tableName] as $number=>$line) {
            if (strpos($line, self::VERSION_TAG . ' ' . $version) !== false) {
                return $number;
            }
        }

        // @todo return st or exception
    }
    /* }}} */
    /* {{{ currentTableVersion */
    private function currentTableVersion($tableName)
    {
        $query      = 'SELECT TABLE_COMMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = "' . $tableName . '" LIMIT 1';
        $statement  = $this->pdo->query($query);
        $statement->execute();
        $row        = $statement->fetch();

        return str_replace(self::VERSION_TAG . ' ', '', $row['TABLE_COMMENT']);
    }
    /* }}} */
    /* {{{ candidateTableVersion */
    private function candidateTableVersion($tableName)
    {
        $lastVersion = false;

        foreach($this->sql[$tableName] as $line) {
            $version = $this->readVersionDelimiter($line);
            if ($version) {
                $lastVersion = $version;
            }
        }

        return $lastVersion;
    }
    /* }}} */
    /* {{{ readVersionDelimiter */
    private function readVersionDelimiter($line)
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
            if ($this->currentTableVersion($tableName) < $this->candidateTableVersion($tableName)) {
                $this->executeUpdate($tableName);
            }
        }
    }
    /* }}} */
    /* {{{ executeUpdate */
    public function executeUpdate($tableName)
    {
        $startLine  = $this->startLineByVersion($tableName, $this->currentTableVersion($tableName));
        $endLine    = count($this->sql[$tableName]);

        $sqlString = '';
        for ($i = $startLine; $i < $endLine; $i++) {
            // @todo remove comments # and --
            $sqlString .= preg_replace('/#.*$/', '', $this->sql[$tableName][$i]);
        }

        $sqlString  = trim($sqlString);
        $sqlString  = preg_replace('/\s+/', ' ', $sqlString) . "\n";
        $sqlArray   = explode(';', $sqlString);

        foreach($sqlArray as $query) {
            echo trim($query) . "\n";
        }
    }
    /* }}} */
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
