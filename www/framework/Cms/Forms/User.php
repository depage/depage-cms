<?php

namespace Depage\Cms\Forms;

/**
 * brief Project
 * Class Project
 */
class User extends \Depage\Htmlform\Htmlform
{
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $name, $params
     * @return void
     **/
    public function __construct($name, $params)
    {
        $this->user = $params['user'];

        $params['cancelUrl'] = DEPAGE_BASE;
        $params['cancelLabel'] = _("Cancel");

        parent::__construct($name, $params);

        $this->addText("name", array(
            "label" => _("Username"),
            "required" => "true",
            "validator" => "/[-a-zA-Z0-9_]+/",
            "disabled" => $this->user->id !== null,
        ));
        $this->addText("fullname", array(
            "label" => _("Display Name"),
            "required" => "true",
        ));
        $this->addEmail("email", array(
            "label" => _("Email"),
        ));

        $this->populate($this->user);
    }
    // }}}
}

