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
    protected $search       = '';
    protected $replace      = '';
    protected $string       = '';
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
    /* {{{ replaceSQL */
    public function replaceSQL($search, $replace) {
        $this->search   = $search;
        $this->replace  = $replace;
    }
    /* }}} */
    /* {{{ getStatements */
    public function getStatements()
    {
        $filtered = array();

        foreach($this->statements as $statement) {
            switch ($statement['type']) {
                case 'code':
                    $append = preg_replace('/\s+/', ' ', $statement['string']);
                    if ($this->search != '')
                        $append = preg_replace('/' . $this->search . '/', $this->replace, $append);
                    if (substr($this->string, -1) == ' ' && substr($append, 0, 1) == ' ')
                        $append = ltrim($append);
                    $this->string .= $append;
                break;
                case 'strings':
                    $this->string .= $statement['string'];
                break;
                case 'breaks':
                    $filtered[]     = trim($this->string);
                    $this->string   = '';
                break;
            }
        }

        $this->statements = array();
        return $filtered;
    }
    /* }}} */
    /* {{{ addTo */
    protected function addTo($type, $char)
    {
        $end = array_pop($this->statements);

        if ($end['type'] == $type) {
            $this->statements[] = array(
                'type'      => $type,
                'string'    => $end['string'] . $char,
            );
        } else {
            $this->statements[] = $end;
            $this->statements[] = array(
                'type'      => $type,
                'string'    => $char,
            );
        }
        reset($this->statements);
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
