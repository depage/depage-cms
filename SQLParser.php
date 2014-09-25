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
    protected $categorised      = array();
    protected $hash             = false;
    protected $doubleDash       = false;
    protected $multiLine        = false;
    protected $singleQuote      = false;
    protected $doubleQuote      = false;
    protected $processedString  = '';
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
                    $this->append('string', $char);
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
                        $this->append('break', $char);
                    } else {
                        if ($char == '\'') {
                            $this->singleQuote = true;
                            $this->append('string', $char);
                        } elseif ($char == '"') {
                            $this->doubleQuote = true;
                            $this->append('string', $char);
                        } else {
                            $this->append('code', $char);
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
        // @todo is_callable & exception
        $this->replacementFunction = $replacementFunction;
    }
    /* }}} */
    /* {{{ getStatements */
    public function getStatements()
    {
        $finishedStatements = array();

        foreach($this->categorised as $statement) {
            $type = $statement['type'];

            if ($type == 'code') {
                $append = $this->replace($statement['string']);
                $append = preg_replace('/\s+/', ' ', $append);

                if (substr($this->processedString, -1) == ' ' && $append[0] == ' ') {
                    $append = ltrim($append);
                }

                $this->processedString .= $append;
            } elseif ($type == 'string') {
                $this->processedString .= $statement['string'];
            } elseif ($type == 'break') {
                $finishedStatements[]   = trim($this->processedString);
                $this->processedString  = '';
            }
        }

        $this->categorised = array();
        return $finishedStatements;
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
    /* {{{ append */
    protected function append($type, $char)
    {
        end($this->categorised);
        $index = key($this->categorised);

        if (
            $index != null && $this->categorised[$index]['type'] == $type
        ) {
            $this->categorised[$index]['string'] .= $char;
        } else {
            $this->categorised[] = array(
                'type'      => $type,
                'string'    => $char,
            );
        }
    }
    /* }}} */
    /* {{{ reset */
    public function reset()
    {
        $this->categorised      = array();
        $this->hash             = false;
        $this->doubleDash       = false;
        $this->multiLine        = false;
        $this->singleQuote      = false;
        $this->doubleQuote      = false;
        $this->processedString  = '';
    }
    /* }}} */
    /* {{{ isEndOfStatment */
    public function isEndOfStatement()
    {
        return trim($this->processedString) == '' && $this->categorised == array();
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
