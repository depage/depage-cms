<?php

namespace Depage\Cms\Forms;

/**
 * brief Project
 * Class Project
 */
class Import extends \Depage\Htmlform\Htmlform
{
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $name, $params
     * @return void
     **/
    public function __construct($name, $params = array())
    {
        $params['submitLabel'] = _("Import Now");

        $params['cancelUrl'] = DEPAGE_BASE;
        $params['cancelLabel'] = _("Cancel");

        parent::__construct($name, $params);

        $this->addHidden("action", array(
            'defaultValue' => "import",
        ));

    }
    // }}}
}

