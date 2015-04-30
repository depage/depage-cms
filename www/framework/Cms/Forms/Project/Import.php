<?php

namespace Depage\Cms\Forms\Project;

/**
 * brief Project
 * Class Project
 */
class Import extends Base
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

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
