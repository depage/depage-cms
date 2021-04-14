<?php
/**
 * @file    Library.php
 *
 * description
 *
 * copyright (c) 2021 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Api;

/**
 * @brief Library
 * Class Library
 */
class Library extends Json
{
    protected $autoEnforceAuth = false;
    protected $fl = null;

    // {{{ _init()
    /**
     * @brief _init
     *
     * @param mixed $param
     * @return void
     **/
    public function _init(array $importVariables = [])
    {
        parent::_init($importVariables);

        $this->fl = new \Depage\Cms\FileLibrary($this->pdo, $this->project);
    }
    // }}}

    // {{{ status()
    /**
     * @brief status
     *
     * @return object
     **/
    public function sync()
    {
        $values = $this->parseJsonParams();

        $retVal = [
            'success' => $this->fl->syncLibrary(),
        ];

        return $retVal;
    }
    // }}}
    // {{{ setImageCenter()
    /**
     * @brief setImageCenter
     *
     * @return boolean
     **/
    public function set_image_center()
    {
        $success = false;

        if ($this->auth->enforceLazy()) {
            $fileId = filter_input(INPUT_POST, 'fileId', FILTER_VALIDATE_INT);
            $centerX = filter_input(INPUT_POST, 'centerX', FILTER_VALIDATE_INT);
            $centerY = filter_input(INPUT_POST, 'centerY', FILTER_VALIDATE_INT);

            $success = $this->fl->setImageCenter($fileId, $centerX, $centerY);
        }

        return [
            'success' => $success,
        ];
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :

