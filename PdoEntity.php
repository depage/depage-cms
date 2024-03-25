<?php
/**
 * @file    PdoEntity.php
 *
 * PdoEntity
 *
 * copyright (c) 2006-2017 Frank Hellenkamp [jonas@depage.net]
 */
namespace Depage\Entity;

abstract class PdoEntity extends Entity
{
    /**
     * @brief pdo object for database access
     **/
    protected $pdo = null;

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $pdo, $user
     * @return void
     **/
    public function __construct(\Depage\Db\Pdo $pdo)
    {
        parent::__construct($pdo);

        $this->pdo = $pdo;
    }
    // }}}

    // abstract functions
    // {{{ loadBy()
    /**
     * @brief loadBy
     *
     * @param mixed $
     * @return void
     **/
    static public function loadBy($pdo, Array $search, Array $order = []) {
    }
    // }}}
    // {{{ save()
    /**
     * @brief save
     *
     * @param mixed
     * @return void
     **/
    abstract public function save();
    // }}}

    // empty overridable class functions
    // {{{ onLoad()
    /**
     * @brief onLoad
     *
     * @param mixed
     * @return void
     **/
    protected function onLoad()
    {
    }
    // }}}
    // {{{ onSave()
    /**
     * @brief onSave
     *
     * @param mixed
     * @return void
     **/
    protected function onSave()
    {
    }
    // }}}

    // helpers
    // {{{ sqlConditionFor()
    /**
     * @brief sqlConditionFor
     *
     * @param mixed $name, $values
     * @return void
     **/
    protected static function sqlConditionFor($name, $values, &$params)
    {
        $escapedName = str_replace(".", "_", $name);
        if (!is_array($values)) {
            $params[$escapedName] = $values;
            return "$name = :$escapedName";
        } else {
            $where = "$name IN (";
            foreach ($values as $key => $val) {
                $params["$escapedName$key"] = $val;
                $where .= ":$escapedName$key,";
            }
            return rtrim($where, ",") . ")";
        }
    }
    // }}}
    // {{{ dateTimestamp()
    /**
     * @brief
     *
     * @param mixed $timestamp = null
     * @return void
     **/
    public static function dateTimestamp($timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
    // }}}
    // {{{ escapeLike()
    /**
     * @brief escapeLike
     *
     * @param mixed $s, $e
     * @return void
     **/
    static public function escapeLike($s, $e)
    {
        return str_replace([$e, '_', '%'], ["{$e}{$e}", "{$e}_", "{$e}%"], $s);
    }
    // }}}

    // {{{ __sleep()
    /**
     * allows Depage\Db\Pdo-object to be serialized
     */
    public function __sleep()
    {
        return array(
            'pdo',
            'initialized',
            'data',
            'types',
            'dirty',
        );
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
