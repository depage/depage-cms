<?php

namespace depage\CMS\xslt;

class FuncDelegate {
    protected static $functions = array();

    // {{{ registerFunctions()
    public static function registerFunctions($proc, Array $functions = array())
    {
        $class = get_called_class();
        static::$functions = $functions;

        $names = array();
        foreach ($functions as $name => $func) {
            if (is_callable($func)) {
                $names[] = __CLASS__ . "::" . $name;
            } else {
                // @todo exception or warning?
            }
        }
        $proc->registerPHPFunctions($names);
    }
    // }}}
    // {{{ __callStatic()
    public function __callStatic($name, $parameters = array())
    {
        if (isset(static::$functions[$name]) && is_callable(static::$functions[$name])) {
            return call_user_func_array(static::$functions[$name], $parameters);
        } else {
            var_dump("not callable $name");
        }
    }
    // }}}

}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
