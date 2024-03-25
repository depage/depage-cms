<?php

namespace Depage\Cms\Streams;

abstract class Base {
    public $context;
    protected $position = 0;
    protected $data = null;

    // {{{ registerAsStream()
    public static function registerStream($protocol, Array $parameters = [])
    {
        $class = get_called_class();
        static::$parameters = $parameters;

        if (in_array($protocol, stream_get_wrappers())) {
            stream_wrapper_unregister($protocol);
        }
        stream_wrapper_register($protocol, $class);
    }
    // }}}
    // {{{ init()
    public function init()
    {
        foreach (static::$parameters as $key => $value) {
            $this->$key = $value;
        }
    }
    // }}}

    // {{{ stream_open()
    public abstract function stream_open($path, $mode, $options, &$opened_path);
    // }}}
    // {{{ stream_read()
    public function stream_read($count)
    {
        $ret = substr($this->data, $this->position, $count);
        $this->position += $count;

        return $ret;
    }
    // }}}
    // {{{ stream_write()
    public function stream_write($data)
    {
        return 0;
    }
    // }}}
    // {{{ stream_tell()
    function stream_tell()
    {
        return $this->position;
    }
    // }}}
    // {{{ stream_seek()
function stream_seek($offset, $whence)
    {
        switch ($whence) {
            case SEEK_SET:
                if ($offset < strlen($this->data) && $offset >= 0) {
                     $this->position = $offset;
                     return true;
                } else {
                     return false;
                }
                break;

            case SEEK_CUR:
                if ($offset >= 0) {
                     $this->position += $offset;
                     return true;
                } else {
                     return false;
                }
                break;

            case SEEK_END:
                if (strlen($this->data) + $offset >= 0) {
                     $this->position = strlen($this->data) + $offset;
                     return true;
                } else {
                     return false;
                }
                break;

            default:
                return false;
        }
    }
    // }}}
    // {{{ stream_eof()
    public function stream_eof()
    {
        return $this->position >= strlen($this->data);
    }
    // }}}
    // {{{ stream_stat()
    public function stream_stat(){
        return [];
    }
    // }}}
    // {{{ stream_set_option()
    public function stream_set_option($option, $arg1, $arg2)
    {
        return false;
    }
    // }}}
    // {{{ url_stat()
    public function url_stat(){
        return [];
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
