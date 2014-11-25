<?php
/**
 * @file    framework/DB/SqlParser.php
 *
 * depage database module
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 * @author    Sebastian Reinhold [sebastian@bitbernd.de]
 */

namespace Depage\Db;

class SqlParser
{
    // {{{ variables
    protected $split        = array();
    protected $hash         = false;
    protected $doubleDash   = false;
    protected $multiLine    = false;
    protected $singleQuote  = false;
    protected $doubleQuote  = false;
    protected $parsedString = '';
    // }}}

    // {{{ parse
    public function parse($line)
    {
        $split  = $this->split($line);
        $tidied = $this->tidy($split);

        return $tidied;
    }
    // }}}
    // {{{ split
    public function split($line)
    {
        $this->split        = array();
        $this->hash         = false;
        $this->doubleDash   = false;

        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];
            $next = (isset($line[$i+1])) ? $line[$i+1] : '';
            $prev = (isset($line[$i-1])) ? $line[$i-1] : '';

            if ($this->isComment()) {
                $this->append('comment', $char);
                if ($this->multiLine && $char == '/' && $prev == '*') {
                    $this->multiLine = false;
                }
            } else {
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
                    } elseif ($char == '\'') {
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
        }

        return $this->split;
    }
    // }}}
    // {{{ tidy
    public function tidy($split = array())
    {
        $finished = array();

        foreach ($split as $statement) {
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
        end($this->split);
        $index = key($this->split);

        if (
            $index !== null && $this->split[$index]['type'] == $type
        ) {
            $this->split[$index]['string'] .= $char;
        } else {
            $this->split[] = array(
                'type'      => $type,
                'string'    => $char,
            );
        }
    }
    // }}}
    // {{{ isEndOfStatment
    public function isEndOfStatement()
    {
        return (trim($this->parsedString) == '');
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
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
