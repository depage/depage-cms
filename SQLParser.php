<?php
/**
 * @file    framework/DB/SQLParser.php
 *
 * depage database module
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 * @author    Sebastian Reinhold [sebastian@bitbernd.de]
 */

namespace depage\DB;

class SQLParser
{
    /* {{{ variables */
    protected $statement    = '';
    protected $statements   = array();
    protected $hash         = false;
    protected $doubleDash   = false;
    protected $multiLine    = false;
    protected $singleQuote  = false;
    protected $doubleQuote  = false;
    /* }}} */

    /* {{{ processLine */
    public function processLine($line)
    {
        $this->hash         = false;
        $this->doubleDash   = false;

        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];
            $next = (isset($line[$i+1])) ? $line[$i+1] : '';
            $prev = (isset($line[$i-1])) ? $line[$i-1] : '';

            if (!$this->isComment()) {
                if ($this->isString()) {
                    if ($prev != '\\') {
                        if ($this->singleQuote && $char == '\'') {
                            $this->singleQuote = false;
                        } elseif ($this->doubleQuote && $char == '"') {
                            $this->doubleQuote = false;
                        }
                    }
                    $this->statement .= $char;
                } else {
                    if ($char == '#') {
                        $this->hash = true;
                    } elseif ($char == '-' && $next == '-') {
                        $this->doubleDash = true;
                    } elseif ($char == '/' && $next == '*') {
                        $this->multiLine = true;
                    } elseif ($char == ';') {
                        $this->statements[] = trim($this->statement);
                        $this->statement    = '';
                    } elseif (preg_match('/\s/', $char)) {
                        if (substr($this->statement, -1) != ' ') {
                            $this->statement .= ' ';
                        }
                    } else {
                        $this->statement .= $char;

                        if ($char == '\'') {
                            $this->singleQuote = true;
                        } elseif ($char == '"') {
                            $this->doubleQuote = true;
                        }
                    }
                }
            }
            if ($this->multiLine && !$this->isString() && $char == '/' && $prev == '*') {
                $this->multiLine = false;
            }
        }
    }
    /* }}} */
    /* {{{ getStatements */
    public function getStatements()
    {
        $returnStatements = $this->statements;
        $this->statements = array();
        return $returnStatements;
    }
    /* }}} */
    /* {{{ isComment */
    protected function isComment()
    {
        return ($this->hash || $this->doubleDash || $this->multiLine);
    }
    /* }}} */
    /* {{{ isString */
    protected function isString()
    {
        return $this->singleQuote || $this->doubleQuote;
    }
}
