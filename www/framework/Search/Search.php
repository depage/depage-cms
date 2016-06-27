<?php

namespace Depage\Search;

/**
 * brief Search
 * Class Search
 */
class Search
{
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed
     * @return void
     **/
    public function __construct($db)
    {
        $this->db = new Providers\Pdo($db);
    }
    // }}}

    // {{{ query()
    /**
     * @brief query
     *
     * @param mixed $
     * @return void
     **/
    public function query($search, $start = 0, $count = 20)
    {
        $results = $this->db->query($search, $start, $count);

        return $results;
    }
    // }}}
}


/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
