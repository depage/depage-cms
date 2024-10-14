<?php
/**
 * @file    Json.php
 *
 * description
 *
 * copyright (c) 2020 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Api;

/**
 * @brief Json
 * Class Json
 */
abstract class Json extends \Depage\Cms\Ui\Base
{
    protected $autoEnforceAuth = false;

    public $projectName = "";
    public $project = null;
    public $pdo = null;
    public $xmldbCache = null;

    // {{{ _init
    public function _init(array $importVariables = []) {
        $this->projectName = $this->urlSubArgs[0];

        parent::_init($importVariables);

        if ($this->projectName == "-") {
            return;
        }

        if (isset($this->project)) {
            return;
        }

        if (empty($this->projectName)) {
            throw new \Depage\Cms\Exceptions\Project("no project given");
        } else {
            $this->project = \Depage\Cms\Project::loadByName($this->pdo, $this->xmldbCache, $this->projectName);
        }

        if (!$this->project) {
            throw new \Depage\Cms\Exceptions\Project("not allowed");
        }
    }
    // }}}
    // {{{ _package()
    /**
     * @brief _package
     *
     * @param mixed $param
     * @return void
     **/
    protected function _package($output)
    {
        return new \Depage\Json\Json($output);
    }
    // }}}

    // {{{ notfound
    /**
     * function to call if action/function is not defined
     *
     * @return  null
     */
    public function notfound($function = "")
    {
        header('HTTP/1.1 404 Not Found');

        return [
            'success' => false,
            'error' => "not found",
            'message' => $function,
        ];
    }
    // }}}
    // {{{ notallowed
    /**
     * function to call if action/function is not defined
     *
     * @return  null
     */
    public function notallowed($message = "")
    {
        return [
            'success' => false,
            'error' => "not allowd",
            'message' => $message,
        ];
    }
    // }}}
    // {{{ error
    /**
     * function to show error messages
     *
     * @return  null
     */
    public function error($error, $env)
    {
        return [
            'success' => false,
            'error' => "not found",
            'message' => $error,
        ];
    }
    // }}}

    // {{{ parseJsonParams()
    /**
     * @brief parseJsonParams
     *
     * @param mixed
     * @return void
     **/
    protected function parseJsonParams()
    {
        return json_decode(file_get_contents("php://input"));
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
