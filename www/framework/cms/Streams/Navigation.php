<?php

namespace depage\cms\Streams;

class Navigation {
    protected $position = 0;
    protected $data = null;
    protected static $parameters;

    // {{{ registerAsStream()
    public static function registerStream(\String $protocol, Array $parameters)
    {
        $class = get_called_class();
        self::$parameters = $parameters;

        if (in_array($class, stream_get_wrappers())) {
            stream_wrapper_unregister($protocol);
        }
        stream_wrapper_register($protocol, $class);
    }
    // }}}
    // {{{ init()
    public function init()
    {
        foreach (self::$parameters as $key => $value) {
            $this->$key = $value;
        }
    }
    // }}}
    
    // {{{ stream_open()
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->init();

        $url = parse_url($path);
        $this->data = $this->xmldb->getDocXml("pages");

        return true;
    }
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
    // {{{ stream_eof()
    public function stream_eof()
    {
        return true;
    }
    // }}}
    // {{{ url_stat()
    public function url_stat(){
        return array();     
        
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
