<?php

namespace Depage\Cms\Auth;

/**
 * brief CmsUser
 * Class CmsUser
 */
class CmsUser extends \Depage\Auth\User
{
    protected $level = 4;

    // {{{ getLevel()
    /**
     * @brief getLevel
     *
     * @param mixed
     * @return void
     **/
    protected function getLevel()
    {

    }
    // }}}
    // {{{ setLevel()
    /**
     * @brief setLevel
     *
     * @param mixed
     * @return void
     **/
    protected function setLevel($value)
    {
        // read only
    }
    // }}}

    // user rights:
    // {{{ canEditAllUsers()
    /**
     * @brief canEditAllUsers
     *
     * @return bool true of false if the user can edit all projects
     **/
    public function canEditAllUsers()
    {
        return $this->level == 1;
    }
    // }}}
    // {{{ canEditAllProjects()
    /**
     * @brief canEditAllProjects
     *
     * @return bool true of false if the user can edit all projects
     **/
    public function canEditAllProjects()
    {
        return $this->level == 1;
    }
    // }}}
    // {{{ canEditProject()
    /**
     * @brief canEditProject
     *
     * @param mixed $projectName
     * @return void
     **/
    public function canEditProject($projectName)
    {
        return \Depage\Cms\Project::loadByUser($this->pdo, null, $this, $projectName)[0] == true;
    }
    // }}}
    // {{{ canAddProjects()
    /**
     * @brief canAddProjects
     *
     * @return bool true of false if the user can edit all projects
     **/
    public function canAddProjects()
    {
        return $this->canEditAllProjects();
    }
    // }}}
    // {{{ canEditTemplates()
    /**
     * @brief canEditTemplates
     *
     * @return bool true of false if the user can edit templates and template specific variables
     **/
    public function canEditTemplates()
    {
        return $this->level <= 2;
    }
    // }}}
    // {{{ canPublishProject()
    /**
     * @brief canPublishProject
     *
     * @param mixed $param
     * @return void
     **/
    public function canPublishProject()
    {
        return $this->level <= 4;
    }
    // }}}
    // {{{ canEditNewsletter()
    /**
     * @brief canEditNewsletter
     *
     * @param mixed
     * @return void
     **/
    public function canEditNewsletter()
    {
        return $this->level <= 4;
    }
    // }}}
    // {{{ canSendNewsletter()
    /**
     * @brief canSendNewsletter
     *
     * @param mixed
     * @return void
     **/
    public function canSendNewsletter()
    {
        return $this->level <= 3;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
