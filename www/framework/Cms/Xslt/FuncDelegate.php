<?php

namespace Depage\Cms\Xslt;

/*
 * Allows to register php functions with xsl processor to be called inside of the
 * context of a given object instead of just statically.
 */
class FuncDelegate {
    protected static $functions = [];

    // {{{ resetFunctions
    /**
     * reset
     *
     * resets all registered functions
     *
     * @access public
     * @return void
     */
    public static function resetFunctions() {
        self::$functions = [];
    }
    // }}}
    // {{{ registerFunctions()
    public static function registerFunctions($proc, Array $functions = array())
    {
        $class = get_called_class();
        static::$functions += $functions;

        $names = [];
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
    public static function __callStatic($name, $parameters = [])
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
