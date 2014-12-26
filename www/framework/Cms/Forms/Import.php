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
    public function __construct($name, $params)
    {
        $groups = array();
        foreach($params['projectGroups'] as $g) {
            $groups[$g->id] = $g->name;
        }
        $this->project = $params['project'];

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

