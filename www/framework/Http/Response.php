<?php
/**
 * @file    response.php
 * @brief   http response class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 **/

namespace Depage\Http;

class Response {
    /**
     * @brief headers
     **/
    protected $headers = array();

    /**
     * @brief body
     **/
    protected $body = "";

    /**
     * @brief info
     **/
    protected $info = array();

    /**
     * @brief isRedirect
     **/
    public $isRedirect = false;

    /**
     * @brief redirectUrl
     **/
    protected $redirectUrl = "";

    // {{{ __construct()
    public function __construct($headers = "", $body = "", $info = array()) {
        $this->body = $body;
        $this->info = $info;

        if (!is_array($headers)) {
            $headers = explode("\r\n", $headers);
        }

        foreach ($headers as $header) {
            $this->addHeader($header);
        }
    }
    // }}}
    // {{{ setBody()
    /**
     * @brief setBody
     *
     * @param mixed $$
     * @return void
     **/
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }
    // }}}
    // {{{ addHeader()
    /**
     * @brief addHeader
     *
     * @param mixed $
     * @return void
     **/
    public function addHeader($headerLine)
    {
        if (empty($headerLine)) {
            return;
        }
        $this->headers[] = $headerLine;

        list($key, $value) = array_replace(array("", ""), explode(": ", $headerLine));

        if (substr($key, 0, 4) == "HTTP") {
            $data = explode(' ', $headerLine, 3);
            $this->httpCode = $data[1];
            $this->httpMessage = $data[2];
        } else if ($key == "Content-Type") {
            preg_match('/([\w\/+]+)(;\s+charset=(\S+))?/i', $value, $matches );
            if (isset($matches[1])) {
                $this->contentType = $matches[1];
            }
            if (isset($matches[3])) {
                $this->charset = $matches[3];
            }
        } else if ($key == "Location") {
            $this->isRedirect = true;
            $this->redirectUrl = $value;
        }

        return $this;
    }
    // }}}
    // {{{ __get()
    /**
     * @brief __get
     *
     * @param mixed $
     * @return void
     **/
    public function __get($key)
    {
        if (in_array(array(
            "headers",
            "body",
            "info",
            "contentType",
            "charset",
            "httpCode",
            "httpMessage",
            "isRedirect",
            "redirectUrl",
        ))) {
            return $this->$key;
        }
    }
    // }}}

    // {{{ __toString()
    public function __toString() {
        return (string) $this->body;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
