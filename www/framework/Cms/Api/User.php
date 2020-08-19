<?php
/**
 * @file    User.php
 *
 * description
 *
 * copyright (c) 2020 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Api;

/**
 * @brief User
 * Class User
 */
class User extends Json
{
    protected $autoEnforceAuth = false;

    // {{{ status()
    /**
     * @brief status
     *
     * @return object
     **/
    public function status()
    {
        $values = $this->parseJsonParams();

        $retVal = [
            'success' => true,
            'loggedin' => false,
        ];

        $user = $this->auth->enforceLazy();
        if ($user) {
            $retVal['loggedin'] = true;
            $retVal['user'] = $user->name;
        }

        return $retVal;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :

