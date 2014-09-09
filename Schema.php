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
    const VERSION_DELIMITER = 'version ';
    /* }}} */
    /* {{{ variables */
    private $fileNames  = array();
    private $sql        = array();
    /* }}} */

    /* {{{ constructor */
    /**
     *
     * @return void
     */
    public function __construct($pdo, $fileNames)
    {
        $this->fileNames    = $fileNames;
        $this->pdo          = $pdo;

    }
    /* }}} */

    /* {{{ load */
    public function load()
    {
        foreach($this->fileNames as $fileName) {
            $handle = @fopen($fileName, "r");
            if ($handle) {
                while (($buffer = fgets($handle, 4096)) !== false) {
                    $this->sql[] = $buffer;
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
    private function getStartLineByVersion($version)
    {
        foreach($this->sql as $number=>$line) {
            if (strpos($line, self::VERSION_DELIMITER . $version) !== false) { //TODO search string is hard coded
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

        return str_replace(self::VERSION_DELIMITER, '', $row['TABLE_COMMENT']);
    }
    /* }}} */
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
