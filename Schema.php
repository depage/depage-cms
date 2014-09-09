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
            $handle = @fopen($tableName . '.sql', "r");
            if ($handle) {
                while (($buffer = fgets($handle, 4096)) !== false) {
                    $this->sql[$tableName][] = $buffer;
                }

                if (!feof($handle)) {
                    //TODO exception
                }

                fclose($handle);
            }
        }
    }
    /* }}} */
    /* {{{ getStartLineByVersion */
    private function getStartLineByVersion($tableName, $version)
    {
        foreach($this->sql[$tableName] as $number=>$line) {
            if (strpos($line, self::VERSION_TAG . ' ' . $version) !== false) {
                return $number;
            }
        }

        //TODO return st or exception
    }
    /* }}} */
    /* {{{ getCurrentTableVersion */
    private function getCurrentTableVersion($tableName)
    {
        $query      = 'SELECT TABLE_COMMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = "' . $tableName . '" LIMIT 1';
        $statement  = $this->pdo->query($query);
        $statement->execute();
        $row        = $statement->fetch();

        return str_replace(self::VERSION_TAG . ' ', '', $row['TABLE_COMMENT']);
    }
    /* }}} */
    /* {{{ getCandidateTableVersion */
    private function getCandidateTableVersion($tableName)
    {
        $lastVersion = false;

        foreach($this->sql[$tableName] as $line) {
            $version    = $this->readVersionDelimiter($line);
            if ($version) {
                $lastVersion = $version;
            }
        }

        return $lastVersion;
    }
    /* }}} */
    /* {{{ readVersionDelimiter */
    private function readVersionDelimiter($line) {
        $trimmedLine = trim($line);

        if (
            isset(trim($trimmedLine)[0])
            && trim($trimmedLine)[0] == '#'
            && strpos($trimmedLine, self::VERSION_DELIMITER) !== false
            && preg_match('/' . self::VERSION_TAG . ' (.?[0-9]*\.?[0-9]+)/', $trimmedLine, $matches)
            && count($matches == 2)
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
            
        }
    }
    /* }}} */
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
