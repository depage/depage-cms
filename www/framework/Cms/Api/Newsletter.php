<?php
/**
 * @file    Newsletter.php
 *
 * description
 *
 * copyright (c) 2020 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Api;

/**
 * @brief Newsletter
 * Class Newsletter
 */
class Newsletter extends Json
{
    protected $autoEnforceAuth = false;

    // {{{ _init
    public function _init(array $importVariables = []) {
        parent::_init($importVariables);

        $this->projectName = $this->urlSubArgs[0];

        if (empty($this->projectName)) {
            throw new \Depage\Cms\Exceptions\Project("no project given");
        } else {
            $this->project = \Depage\Cms\Project::loadByName($this->pdo, null, $this->projectName);
        }

        if (!$this->project) {
            throw new \Depage\Cms\Exceptions\Project("not allowed");
        }

        $this->newsletter = new \Depage\Cms\Newsletter($this->pdo, $this->project, "");
    }
    // }}}

    // {{{ subscribe()
    /**
     * @brief subscribe
     *
     * @return object
     **/
    public function subscribe()
    {
        $values = $this->parseJsonParams();

        return [
            'validation' => $this->newsletter->subscribe(
                $values->email,
                $values->firstname,
                $values->lastname,
                $values->description,
                $values->lang,
                $values->category
            ),
            'success' => true,
        ];
    }
    // }}}
    // {{{ is_subscriber()
    /**
     * @brief is_subscriber
     *
     * @return object
     **/
    public function is_subscriber()
    {
        $values = $this->parseJsonParams();

        return [
            'success' => $this->newsletter->isSubscriber(
                $values->email,
                $values->lang,
                $values->category
            ),
        ];
    }
    // }}}
    // {{{ confirm()
    /**
     * @brief confirm
     *
     * @return object
     **/
    public function confirm()
    {
        $values = $this->parseJsonParams();

        return [
            'subscriber' => $this->newsletter->confirm(
                $values->validation
            ),
            'success' => true,
        ];
    }
    // }}}
    // {{{ unsubscribe()
    /**
     * @brief unsubscribe
     *
     * @return object
     **/
    public function unsubscribe()
    {
        $values = $this->parseJsonParams();

        return [
            'success' => $this->newsletter->unsubscribe(
                $values->email,
                $values->lang,
                $values->category
            ),
        ];
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
