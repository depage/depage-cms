<?php
/**
 * @file    Cache.php
 *
 * description
 *
 * copyright (c) 2020 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Api;

/**
 * @brief Cache
 * Class Cache
 */
class Cache extends Json
{
    protected $autoEnforceAuth = false;

    // {{{ clear()
    /**
     * @brief clear
     *
     * @return object
     **/
    public function clear()
    {
        $values = $this->parseJsonParams();

        $folders = [
            '/global/',
            '/global/css/',
            '/global/js/',
            '/global/assets/',
        ];

        $fl = new \Depage\Cms\FileLibrary($this->pdo, $this->project);
        foreach ($folders as $folder) {
            $fl->syncFiles($folder);
        }

        $retVal = [
            'success' => $this->project->clearTransformCache(),
        ];

        return $retVal;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :

