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
    // {{{ variables
    protected $categorised      = array();
    protected $hash             = false;
    protected $doubleDash       = false;
    protected $multiLine        = false;
    protected $singleQuote      = false;
    protected $doubleQuote      = false;
    protected $parsedString     = '';
    // }}}

    // {{{ parseLine
    public function parseLine($line)
    {
        $categorised    = $this->categorise($line);
        $tidied         = $this->tidy($categorised);

        return $tidied;
    }
    // }}}
    // {{{ categorise
    public function categorise($line)
    {
        $this->categorised  = array();
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
            } else {
                $this->append('comment', $char);

                if ($this->multiLine && !$this->isString() && $char == '/' && $prev == '*') {
                    $this->multiLine = false;
                }
            }
        }

        return $this->categorised;
    }
    // }}}
    // {{{ tidy
    public function tidy($categorised = array())
    {
        $finished = array();

        foreach ($categorised as $statement) {
            $type = $statement['type'];

            if ($type == 'code') {
                $append = preg_replace('/\s+/', ' ', $statement['string']);

                if (substr($this->parsedString, -1) == ' ' && $append[0] == ' ') {
                    $append = ltrim($append);
                }

                $this->parsedString .= $append;
            } elseif ($type == 'string') {
                $this->parsedString .= $statement['string'];
            } elseif ($type == 'break') {
                $finished[]         = trim($this->parsedString);
                $this->parsedString = '';
            }
        }

        return $finished;
    }
    // }}}
    // {{{ append
    protected function append($type, $char)
    {
        end($this->categorised);
        $index = key($this->categorised);

        if (
            $index !== null && $this->categorised[$index]['type'] == $type
        ) {
            $this->categorised[$index]['string'] .= $char;
        } else {
            $this->categorised[] = array(
                'type'      => $type,
                'string'    => $char,
            );
        }
    }
    // }}}
    // {{{ isComment
    protected function isComment()
    {
        return ($this->hash || $this->doubleDash || $this->multiLine);
    }
    // }}}
    // {{{ isString
    protected function isString()
    {
        return $this->singleQuote || $this->doubleQuote;
    }
    // }}}
    // {{{ isEndOfStatment
    public function isEndOfStatement()
    {
        return (trim($this->parsedString) == '');
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
