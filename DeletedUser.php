<?php
/**
 * @file    DeletedUser.php
 *
 * description
 *
 * copyright (c) 2021 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Auth;

/**
 * @brief DeletedUser
 * Class DeletedUser
 */
class DeletedUser extends User
{
    // {{{ getDisabled()
    /**
     * @brief getDisabled
     *
     * @param mixed
     * @return void
     **/
    public function getDisabled()
    {
        return true;
    }
    // }}}

    // {{{ getName()
    /**
     * @brief getName
     *
     * @param mixed
     * @return void
     **/
    protected function getName()
    {
        return "DeletedUser";

    }
    // }}}
    // {{{ getEmail()
    /**
     * @brief getEmail
     *
     * @param mixed
     * @return void
     **/
    protected function getEmail()
    {
        return "";
    }
    // }}}
    // {{{ getFullname()
    /**
     * @brief getFullname
     *
     * @param mixed
     * @return void
     **/
    protected function getFullname()
    {
        return _("Deleted User");
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
