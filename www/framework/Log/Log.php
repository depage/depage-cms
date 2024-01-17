<?php
/**
 * @file    framework/log/log.php
 *
 * depage log module
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Log;

class Log
{
    // {{{ default config
    protected $defaults = array(
        'file' => "",
        'mail' => "",
    );
    protected $conf;
    protected $options;
    // }}}

    // {{{ constructor
    /**
     * automatically loads classes from the framework or the private modules
     *
     * @param   $options (array) named options for base class
     *
     * @return  null
     */
    public function __construct($options = NULL) {
        $conf = new \Depage\Config\Config($options);
        $this->options = $conf->getFromDefaults($this->defaults);

        if (empty($this->options->file)) {
            $this->options->setConfig(['file' => DEPAGE_PATH . "logs/depage.log"]);
        }
        $dir = dirname($this->options->file);

        if (!is_writable($dir) && !mkdir($dir, 0777, true)) {
            $this->options->file = "";
            $this->log("Could not create/write log to '$dir'");
        }
    }
    // }}}
    // {{{ getMessage
    /**
     * get log message based on given data
     *
     * @param   $arg (var) text, array or object to log
     *
     * @return  null
     */
    public function getMessage($arg) {
        $message = "";

        if (gettype($arg) != 'string') {
            ob_start();

            if (is_object($arg)) {
                $obj = $arg;

                if ($obj instanceof \DOMDocument) {
                    echo "XPath: {$obj->getNodePath()}\n".$obj->saveXML($obj);
                } else if ($obj instanceof \DOMElement) {
                    echo "XPath: {$obj->getNodePath()}\n".$obj->ownerDocument->saveXML($obj);
                } else if ($obj instanceof \DOMAttr) {
                    echo "XPath: {$obj->getNodePath()}\n".$obj->ownerDocument->saveXML($obj);
                } else if ($obj instanceof \DOMNodeList) {
                    for ($i = 0; $i < $obj->length; $i++) {
                        echo "Item #$i, XPath: {$obj->item($i)->getNodePath()}\n".  "{$obj->item($i)->ownerDocument->saveXML($obj->item($i))}\n";
                    }
                } else {
                    print_r($obj);
                }
            } else {
                print_r($arg);
            }

            $message .= ob_get_contents();
            ob_end_clean();
        } else {
            $message .= $arg;
        }

        $message = str_replace("\n", "\n    ", rtrim($message, "\n"));

        return $message;
    }
    // }}}
    // {{{ log
    /**
     * log a message
     *
     * @param   $arg (var) text, array or object to log
     * @param   $type (string) type of the log message
     *
     * @return  null
     */
    public function log($arg, $type = "debug") {
        $message = $this->getMessage($arg);
        $date = date("c");

        if ($this->options->file != "") {
            if (php_sapi_name() == 'cli') {
                fwrite(STDERR, "$message\n");
            } else {
                error_log("[$date] [$type] $message\n", 3, $this->options->file);
            }
        } else {
            error_log("[$date] [$type] $message\n");
        }
    }
    // }}}
    // {{{ backtrace
    /**
     * logs a backtrace of current call
     *
     * @param   $type (string) type of the log message
     *
     * @return  null
     */
    public function backtrace($type = "debug") {
        ob_start();

        debug_print_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);

        $message = ob_get_contents();
        ob_end_clean();

        $this->log($message, $type);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
