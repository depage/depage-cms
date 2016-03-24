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
    protected $isRedirect = false;

    /**
     * @brief redirectUrl
     **/
    protected $redirectUrl = "";

    /**
     * @brief fiels
     **/
    protected static $fields = array(
        "headers",
        "body",
        "info",
        "contentType",
        "charset",
        "httpCode",
        "httpMessage",
        "redirectUrl",
    );

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
    // {{{ getJson()
    /**
     * @brief getJson
     *
     * @param mixed $param
     * @return void
     **/
    public function getJson()
    {
        $data = json_decode((string) $this->body, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            var_dump($data);
            throw new \Exception('Unable to parse response body into JSON: ' . json_last_error());
        }

        return $data === null ? array() : $data;
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
        if (in_array($key, static::$fields)) {
            return $this->$key;
        }
    }
    // }}}
    // {{{ __call()
    /**
     * @brief __get
     *
     * @param mixed $
     * @return void
     **/
    public function __call($name, $arguments)
    {
        $prefix = substr($name, 0, 3);
        $key = lcfirst(substr($name, 3));

        if ($prefix == "get" && in_array($key, static::$fields)) {
            return $this->$key;
        }
    }
    // }}}
    // {{{ isRedirect()
    /**
     * @brief isRedirect
     *
     * @param mixed
     * @return void
     **/
    public function isRedirect()
    {
        return $this->isRedirect;
    }
    // }}}

    // {{{ __toString()
    public function __toString() {
        return (string) $this->body;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
