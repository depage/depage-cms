<?php
namespace depage\DB\Exceptions;

class SQLExecutionException extends \Exception
{
    public function __construct($pdoException, $lineNumber, $statement) {
        $message = 'Database error in statement "' . $statement . '" ending in line ' . $lineNumber . "\n";
        $message .= 'PDO message: ' . preg_replace('/ at line [0-9]+$/', '', $pdoException->errorInfo[2]) . "\n";

        parent::__construct($message);
    }
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
