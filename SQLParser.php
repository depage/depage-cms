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
    protected $statements   = array();
    protected $hash         = false;
    protected $doubleDash   = false;
    protected $multiLine    = false;
    protected $singleQuote  = false;
    protected $doubleQuote  = false;
    protected $string       = '';
    protected $replacementFunction;
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
                    $this->addTo('strings', $char);
                    if ($prev != '\\') {
                        if ($this->singleQuote && $char == '\'') {
                            $this->singleQuote = false;
                        } elseif ($this->doubleQuote && $char == '"') {
                            $this->doubleQuote = false;
                        }
                    }
                } else {
                    if ($char == '#') {
                        $this->hash = true;
                    } elseif ($char == '-' && $next == '-') {
                        $this->doubleDash = true;
                    } elseif ($char == '/' && $next == '*') {
                        $this->multiLine = true;
                    } elseif ($char == ';') {
                        $this->addTo('breaks', $char);
                    } else {
                        if ($char == '\'') {
                            $this->singleQuote = true;
                            $this->addTo('strings', $char);
                        } elseif ($char == '"') {
                            $this->doubleQuote = true;
                            $this->addTo('strings', $char);
                        } else {
                            $this->addTo('code', $char);
                        }
                    }
                }
            } elseif ($this->multiLine && !$this->isString() && $char == '/' && $prev == '*') {
                $this->multiLine = false;
            }
        }
    }
    /* }}} */
    /* {{{ setReplacement */
    public function setReplacement($replacementFunction) {
        $this->replacementFunction = $replacementFunction;
    }
    /* }}} */
    /* {{{ getStatements */
    public function getStatements()
    {
        $filtered = array();

        foreach($this->statements as $statement) {
            $type = $statement['type'];

            if ($type == 'code') {
                $append = preg_replace('/\s+/', ' ', $statement['string']);
                $append = $this->replace($append);

                if (substr($this->string, -1) == ' ' && $append[0] == ' ') {
                    $append = ltrim($append);
                }

                $this->string .= $append;
            } elseif ($type == 'strings') {
                $this->string .= $statement['string'];
            } elseif ($type == 'breaks') {
                $filtered[]     = trim($this->string);
                $this->string   = '';
            }
        }

        $this->statements = array();
        return $filtered;
    }
    /* }}} */
    /* {{{ replace */
    protected function replace($string)
    {
        if ($this->replacementFunction != null) {
            $string = call_user_func($this->replacementFunction, $string);
        }

        return $string;
    }
    /* }}} */
    /* {{{ addTo */
    protected function addTo($type, $char)
    {
        end($this->statements);
        $index = key($this->statements);

        if (
            $index != null && $this->statements[$index]['type'] == $type
        ) {
            $this->statements[$index]['string'] .= $char;
        } else {
            $this->statements[] = array(
                'type'      => $type,
                'string'    => $char,
            );
        }
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
    /* }}} */
}
