<?php
/**
 * @file    Entity.php
 *
 * Entity
 *
 * Abstract class provides a base for building model objects.
 *
 * Inheriting classes provide table name and column information and override getters and setters.
 *
 * copyright (c) 2003-2014 Frank Hellenkamp [jonas@depagecms.net]
 */
namespace Depage\Entity;

abstract class Entity
{
    // {{{ variables
    /**
     * Fields
     *
     * Array of table fields indexed on the column name.
     * Values provide the PDO data type for binding to markers.
     *
     * @var array
     */
    protected static $fields = array();

    /**
     * @brief initialized
     **/
    protected $initialized = false;

    /**
     * Data Array
     *
     * The data array accessed via the magic get / set functions.
     *
     * @var array
     */
    protected $data = array();

    /**
     * Types Array
     *
     * The Types of the fields. Optional, add to enable strict type testing when
     * setting fields
     *
     * @var array
     */
    protected $types = array();

    /**
     * Dirty Data
     *
     * This array tracks which properties are dirty for saving.
     * Bool array value indicates column state.
     *
     * @var array
     */
    protected $dirty = array();
    // }}}

    // {{{ __constructor()
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        if (count($this->data) === 0) {
            // new empty object with no data -> set defaults
            foreach (static::$fields as $key => $value) {
                $this->data[$key] = $value;
            }

            $this->dirty = array_fill_keys(array_keys(static::$fields), true);
        } else {
            // object initiated through pdo fetch, so data is already set
            $this->dirty = array_fill_keys(array_keys(static::$fields), false);
        }

        $this->initialized = true;
    }
    // }}}

    // {{{ __get()
    /**
     * Get
     *
     * Gets the propery from the data array if it exists.
     *
     * @param string $property
     *
     * @return mixed
     */
    public function __get($key)
    {
        $getter = "get" . ucfirst($key);
        if (is_callable(array($this, $getter))) {
            return $this->$getter();
        }
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
    }
    // }}}

    // {{{ __set()
    /**
     * Set
     *
     * Sets the data and dirty arrays if the data property exists and the data has changed.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function __set($key, $val)
    {
        $setter = "set" . ucfirst($key);
        if (is_callable(array($this, $setter))) {
            return $this->$setter($val);
        }
        if (array_key_exists($key, static::$fields)) {
            // add value if property exists and is not primary
            if (!in_array($key, static::$primary) || !$this->initialized) {
                $this->dirty[$key] = (isset($this->dirty[$key]) && $this->dirty[$key] == true) || (
                    (isset($this->data[$key]) && $this->data[$key] != $val)
                    || !isset($this->data[$key])
                );
                $this->data[$key] = $val;
            }

            return true;
        }
        return false;
    }
    // }}}

    // {{{ __isset()
    /**
     * IsSet
     *
     * Checks that the property exists.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __isset($key)
    {
        return (isset($this->data[$key]));
    }
    // }}}

    // {{{ getFields()
    /**
     * @brief get field names that are defined in schema
     *
     * @param string $prefix = ""
     * @return array of field names
     **/
    protected static function getFields($prefix = "")
    {
        $fields = array_keys(static::$fields);

        if ($prefix !== "") {
            $fields = array_map(function($val) use ($prefix) {
                return $prefix . "." . $val;
            }, $fields);
        }

        return $fields;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
