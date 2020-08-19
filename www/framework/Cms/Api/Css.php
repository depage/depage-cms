<?php
/**
 * @file    Css.php
 *
 * description
 *
 * copyright (c) 2020 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Api;

/**
 * @brief Css
 * Class Css
 */
class Css extends Json
{
    protected $autoEnforceAuth = false;

    // {{{ generate()
    /**
     * @brief generate
     *
     * @return object
     **/
    public function generate()
    {
        $values = $this->parseJsonParams();

        $retVal = [
            'success' => $this->project->generateCss(),
        ];

        return $retVal;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :

