<?php
/**
 * @file    PdoEntity.php
 *
 * Entity
 *
 * Abstract class provides a base for building model objects.
 *
 * Inheriting classes provide table name and column information and override getters and setters.
 *
 * Provides generic CRUD functions for models.
 *
 * copyright (c) 2006-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author Ben Wallis [benedict_wallis@yahoo.co.uk]
 */
namespace Depage\Entity;

abstract class Entity {

    // {{{ variables
    /**
     * PDO
     *
     * @var \Depage\Db\Pdo
     */
    protected $pdo = null;

    /**
     * Table Name
     *
     * @var string
     */
    protected static $table_name = null;

    /**
     * Cols
     *
     * Array of table cols indexed on the column name.
     * Values provide the PDO data type for binding to markers.
     *
     * @var array
     */
    protected static $cols = array();

    /**
     * Primary Keys
     *
     * Primary keys for the table used to build UPDATE and INSERT statements.
     *
     * @var array
     */
    protected static $primary = array();

    /**
     * Data Array
     *
     * The data array accessed via the magic get / set functions.
     *
     * @var array
     */
    protected $data = array();

    /**
     * Dirty Data
     *
     * This array tracks which properties are dirty for saving.
     * Bool array value indicates column state.
     *
     * @var array
     */
    protected $dirty = array();

    /**
     * Insert Ignore
     *
     * Flag sets ignore duplicates on INSERT
     *
     * @var bool
     */
    protected $insert_ignore = false;
    // }}}

    // {{{ __constructor()
    /**
     * Constructor
     *
     * @param \Depage\Db\Pdo $pdo
     * @param array $data  - data to build the object from
     * @param bool $clean - set as clean or dirty
     * @param bool $insert_ignore
     *
     * @return void
     */
    public function __construct(\depage\DB\PDO $pdo = null, array $data = array(), $clean = true, $insert_ignore = null){
        $this->pdo = $pdo;

        foreach ($data AS $key => $value) {
            if (!in_array($key, static::$primary)) {
                // call through getter/setter for normal data keys
                $this->$key = $value;
            } else {
                // set directly for primaries
                $this->data[$key] = $value;
            }
        }
        $this->dirty = array_fill_keys(array_keys($data), $clean);
        $this->insert_ignore = $insert_ignore === null ? $this->insert_ignore : $insert_ignore;
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
    public function __get($key) {
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
    public function __set($key, $val) {
        $setter = "set" . ucfirst($key);
        if (is_callable(array($this, $setter))) {
            return $this->$setter($val);
        }
        if (array_key_exists($key, static::$cols)) {
            // add value if property exists and is not primary
            if (!in_array($key, static::$primary)) {
                // TODO set false on PDO  class fetch instance
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
    public function __isset($key) {
        return (isset($this->data[$key]));
    }
    // }}}

    // {{{ load()
    /**
     * Load
     *
     * Static factory to load the database entities.
     *
     * @param depage\DB\PDO $pdo - depage pdo class
     * @param array $params - array(key=>value) to build the WHERE clause on.
     *
     * @return object
     */
    protected static function load(\depage\DB\PDO $pdo, $params, $orderBy = ""){
        $query = "SELECT * FROM {$pdo->prefix}_" . static::$table_name;

        $where = array();
        foreach($params as $key=>&$val) {
            $where[] = "{$key}=:{$key}";
        }

        if (count($where)) {
            $query .= " WHERE " . join(' AND ', $where);
        }
        if (!empty($orderBy)) {
            $query .= " ORDER BY $orderBy";
        }

        return self::fetchEntities($pdo, $query, $params);
    }
    // }}}

    // {{{ fetchArray()
    /**
     * Fetch Array
     *
     * Prepares the query, binds the params, executes and fetches assoc array.
     *
     * @param \depage\DB\PDO $pdo
     * @param string $query
     * @param array $params
     *
     * @return array $results
     */
    protected static function fetchArray(\depage\DB\PDO $pdo, $query, array $params = array(), array $types = array()){
        $cmd = $pdo->prepare($query);
        self::bindParams($cmd, $params, $types);
        try {
            $cmd->execute();
        } catch(\exception $ex) {
            // TODO exception handling
            // var_dump($ex, $pdo, $query, $params);
            //debug_print_backtrace();
            throw ($ex);
        }
        $cmd->setFetchMode(\PDO::FETCH_ASSOC);
        $results = $cmd->fetchAll();
        return $results;
    }
    // }}}

    // {{{ fetchEntities()
    /**
     * Fetch Entities
     *
     * Wraps fetchArray returning results as instantiations of the called class.
     *
     * @param array $results
     *
     * @return array
     */
    protected static function fetchEntities(\depage\DB\PDO $pdo, $query, array $params = array(), array $types = array(), $class = null) {
        $results = self::fetchArray($pdo, $query, $params, $types);
        $class = empty($class) ? get_called_class() : $class;
        foreach($results as &$result) {
            $result = new $class($pdo, $result);
        }
        return $results;
    }
    // }}}

    // {{{ fetchEntity()
    /**
     * Fetch Entity
     *
     * Used for queries returning a single entity
     *
     * @param \depage\DB\PDO $pdo
     * @param string $query
     * @param array $params
     *
     * @return entity
     */
    protected static function fetchEntity(\depage\DB\PDO $pdo, $query, array $params = array(), array $types = array(), $class = null) {
        $results = self::fetchEntities($pdo, $query, $params, $types, $class);
        if (count($results)){
            return $results[0];
        }
        return false;
    }
    // }}}

    // {{{ fetchCount()
    /**
     * Fetch Count
     *
     * Wraps count fetches for pagination.
     *
     * NB SELECT must contain count column alias.
     *
     * @param string $query SELECT COUNT(*) AS count
     * @param array $params
     * @param array $types
     */
    protected static function fetchCount($pdo, $query, $params, $types) {
        $cmd = $pdo->prepare($query);
        self::bindParams($cmd, $params, $types);
        $cmd->execute();
        $result = $cmd->fetch();
        return $result['count'];
    }
    // }}}

    // {{{ bindParams()
    /**
     * Bind Params
     *
     * @param $cmd
     * @param array $params
     * @param array $types optional array of types to bind - defaults from static::$cols
     *
     * @return void
     */
    protected static function bindParams(&$cmd, array $params, array $types = array()) {
        $types = empty($types) ? static::$cols : $types;
        foreach($params as $key=>&$val){
            /* DEBUG
            if (!isset($types[$key])) {
                var_dump($types);
                debug_print_backtrace();
            }
            */
            $cmd->bindParam(":{$key}", $val, $types[$key]);
        }
    }
    // }}}

    // {{{ save()
    /**
     * Save
     *
     * Saves the dirty array to the database.
     *
     * Optionally takes an array to add to the object properties before saving.
     *
     * INSERT if primary key is dirty
     * UPDATE otherwise
     *
     * @param array $data - optional data values to save
     *
     * @return object
     */
    public function save(array $data = array()){
        foreach($data as $key=>&$val) {
            $this->$key=$val;
        }
        if(!in_array(true,$this->dirty)){
            return true;
        }
        list($primary) = static::$primary;
        $results = array();

        if(isset($this->dirty[$primary]) && $this->dirty[$primary] !== true) {
            $results = $this->update();
        } else {
            $results = $this->insert();
        }
        // set clean
        $this->dirty = array_fill_keys(array_keys($this->dirty), false);
        return $results;
    }
    // }}}

    // {{{ insert
    /**
     * Insert
     *
     * Builds an INSERT statement from the dirty array.
     *
     * @return int  - last insert ID
     */
    protected function insert() {
        $dirty = array_keys($this->dirty, true);
        $query = "INSERT";
        if($this->insert_ignore) {
            $query .= " IGNORE";
        }
        $query .= " INTO {$this->pdo->prefix}_" . static::$table_name . "
            (" . join(',',$dirty) . ")
            VALUES (:" . join(',:', $dirty) . ")";
        $params = array_intersect_key($this->data,  array_flip($dirty));
        $cmd = $this->pdo->prepare($query);
        static::bindParams($cmd, $params);
        $result = $cmd->execute();
        if (count($this->primary) === 1) {
            return $this->pdo->lastInsertId();
        }
        return $result;
    }
    // }}}

    // {{{ update
    /**
     * Update
     *
     * Builds an UPDATE statement from the dirty array
     * using the primary key columns for the WHERE.
     *
     * @return bool
     */
    protected function update() {
        $params = array();
        $update_keys = array_keys($this->dirty,true);
        foreach($update_keys as &$key) {
            $params[$key] = $this->data[$key];
            $key = "{$key}=:{$key}";
        }
        $where = array();
        foreach(static::$primary as &$key) {
            $where[] = "{$key}=:{$key}";
            $params[$key] = $this->$key;
        }

        $query = "UPDATE {$this->pdo->prefix}_" . static::$table_name . " SET " . join(',', $update_keys);
        if (count($where)) {
            $query .= " WHERE " . join(' AND ', $where);
        }
        $cmd = $this->pdo->prepare($query);
        static::bindParams($cmd, $params);
        return $cmd->execute();
    }
    // }}}

    // {{{ delete
    /**
     * Delete
     *
     * @return bool
     */
    protected function delete() {
        $where = array();
        foreach(static::$primary as &$key) {
            $where[] = "{$key}=:{$key}";
            $params[$key] = $this->$key;
        }

        $query = "DELETE FROM {$this->pdo->prefix}_" . static::$table_name;
        if (count($where)) {
            $query .= " WHERE " . join(' AND ', $where);
            $cmd = $this->pdo->prepare($query);
            static::bindParams($cmd, $params);
            return $cmd->execute();
        } else {
            return false;
        }
    }
    // }}}

    // {{{ addLimit
    /**
     * addLimit
     *
     * Appends and binds a SQL LIMIT clause
     *
     * @param string $query
     * @param array $params
     * @param array $types
     * @param int $page
     * @param int $size
     */
    protected static function addLimit(&$query, &$params, &$types, $page, $size) {
        $page = (int)$page - 1; // LIMIT is zero indexed
        $size = (int)$size;

        $query .= "
            LIMIT :page, :size";
        $params['page'] = $page * $size;
        $types['page'] = \PDO::PARAM_INT;
        $params['size'] = $size;
        $types['size'] = \PDO::PARAM_INT;
    }
    // }}}

    // Escape Like {{{
    /**
     * Escape Like
     *
     * Esed to escape user params applied to a LIKE clause
     *
     * TODO public static for user model call - could be refactored as protected if user inherited this class
     *
     * @param string $s
     * @param string $e
     *
     * @param string - LIKE escaped.
     */
    public static function escapeLike($s, $e){
        return str_replace(array($e, '_', '%'), array("{$e}{$e}", "{$e}_", "{$e}%"), $s);
    }
    // }}}

    // {{{ mapAssoc
    /**
     * Map Assoc
     *
     * Helper utility function for sorting arrays on object id
     *
     * TODO use collections
     *
     * @param $arr
     * @param $key
     *
     * @return array
     */
    public static function mapAssoc($arr, $key) {
        $mapped = array();
        foreach($arr as &$obj){
            $mapped[$obj->$key] = $obj;
        }
        return $mapped;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
