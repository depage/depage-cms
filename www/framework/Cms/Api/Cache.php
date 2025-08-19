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
        $success = $this->project->clearTransformCache();

        // clear xmldb cache
        if (!empty($this->xmldbCache)) {
            $this->xmldbCache->delete($this->pdo->prefix . '_proj_' . $this->project->name . '_xmldocs/');
        }

        $retVal = [
            'success' => $success,
        ];

        return $retVal;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :

