<?php

/**
 * @file    Response.php
 * @brief   http response class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 **/

namespace Depage\Http;

class Response
{
    /**
     * @brief headers
     **/
    protected $headers = [];

    /**
     * @brief body
     **/
    protected $body = "";

    /**
     * @brief info
     **/
    protected $info = [];

    /**
     * @brief isRedirect
     **/
    protected $isRedirect = false;

    /**
     * @brief redirectUrl
     **/
    protected $redirectUrl = "";

    /**
     * @brief lastModified
     **/
    protected $lastModified;

    /**
     * @brief contentType
     **/
    protected $contentType = "";

    /**
     * @brief charset
     **/
    protected $charset = "";

    /**
     * @brief httpCode
     **/
    protected $httpCode = "";

    /**
     * @brief httpMessage
     **/
    protected $httpMessage = "";

    /**
     * @brief fiels
     **/
    protected static $fields = [
        "headers",
        "body",
        "info",
        "contentType",
        "charset",
        "httpCode",
        "httpMessage",
        "isRedirect",
        "redirectUrl",
        "lastModified",
    ];

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $body
     * @param mixed $headers
     * @param mixed $info
     * @return void
     **/
    public function __construct($body = "", $headers = [], $info = [])
    {
        $this->lastModified = new \DateTimeImmutable("now");
        $this->setBody($body);
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
     * @param string|array $body
     * @return void
     **/
    public function setBody(string|array $body = ""): self
    {
        if (is_array($body)) {
            $this->body = implode('', $body);
        } else {
            $this->body = (string) $body;
        }

        return $this;
    }
    // }}}
    // {{{ getJson()
    /**
     * @brief getJson
     *
     * @return mixed
     **/
    public function getJson(): mixed
    {
        $data = json_decode((string) $this->body, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \Exception('Unable to parse response body into JSON: ' . json_last_error());
        }

        return $data === null ? [] : $data;
    }
    // }}}
    // {{{ getXml()
    /**
     * @brief getXml
     *
     * @return DOMDocument
     **/
    public function getXml(): \DOMDocument
    {
        $useErrors = libxml_use_internal_errors(true);

        $doc = new \DOMDocument("1.0", "UTF-8");
        if (!$doc->loadHTML($this->body)) {
            throw new \Exception('Unable to parse response body into XML: ' . libxml_get_last_error());
        }

        libxml_use_internal_errors($useErrors);

        return $doc;
    }
    // }}}
    // {{{ addHeader()
    /**
     * @brief addHeader
     *
     * @param string $headerLine
     * @return self
     **/
    public function addHeader(string $headerLine): self
    {
        if (empty($headerLine)) {
            return $this;
        }
        $this->headers[] = $headerLine;

        list($key, $value) = array_replace(["", ""], explode(": ", $headerLine));

        $key = strtolower($key);

        if (substr($key, 0, 4) == "http") {
            $data = explode(' ', $headerLine, 3);
            $this->httpCode = $data[1];
            $this->httpMessage = $data[2] ?? "";
        } elseif ($key == "content-type") {
            preg_match('/([\w\/+]+)(;\s+charset=(\S+))?/i', $value, $matches);
            if (isset($matches[1])) {
                $this->contentType = $matches[1];
            }
            if (isset($matches[3])) {
                $this->charset = $matches[3];
            }
        } elseif ($key == "location") {
            $this->isRedirect = true;
            $this->redirectUrl = $value;
        } elseif ($key == "last-modified") {
            $this->lastModified = new \DateTimeImmutable($value);
        }

        return $this;
    }
    // }}}
    // {{{ sendHeaders()
    /**
     * @brief sendHeaders
     *
     * @param mixed
     * @return void
     **/
    public function sendHeaders(): void
    {
        foreach ($this->headers as $header) {
            header($header);
        }
    }
    // }}}
    // {{{ getStatus()
    /**
     * @brief getStatus
     *
     * @return object
     **/
    public function getStatus(): object
    {
        $matches = [];
        preg_match('|HTTP/[\d\.]+\s+(\d+)(\s+.*)?|', $this->headers[0] ?? '', $matches);

        return (object) [
            'code' => $matches[1] ?? '',
            'message' => $matches[2] ?? '',
        ];
    }
    // }}}
    // {{{ getHeader()
    /**
     * @brief getHeader
     *
     * @param string search
     * @return void
     **/
    public function getHeader(string $search): string
    {
        foreach ($this->headers as $header) {
            list($key, $value) = array_replace(["", ""], explode(": ", $header));
            if (strtolower($key) == strtolower($search)) {
                return trim($value);
            }
        }

        return "";
    }
    // }}}

    // {{{ __get()
    /**
     * @brief __get
     *
     * @param string $key
     * @return mixed
     **/
    public function __get(string $key): mixed
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
     * @param szring $name
     * @param array $arguments
     * @return mixed
     **/
    public function __call(string $name, array $arguments): mixed
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
     * @return bool
     **/
    public function isRedirect(): bool
    {
        return $this->isRedirect;
    }
    // }}}

    // {{{ __toString()
    /**
     * @brief __toString
     *
     * @return string
     **/
    public function __toString(): string
    {
        return (string) $this->body;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
