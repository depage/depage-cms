<?php
/**
 * @file    Library.php
 *
 * description
 *
 * copyright (c) 2021 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Api;

/**
 * @brief Library
 * Class Library
 */
class Library extends Json
{
    protected $autoEnforceAuth = false;

    // {{{ status()
    /**
     * @brief status
     *
     * @return object
     **/
    public function sync()
    {
        $values = $this->parseJsonParams();

        $fl = new \Depage\Cms\FileLibrary($this->pdo, $this->project);

        $retVal = [
            'success' => $fl->syncLibrary(),
        ];

        return $retVal;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :

