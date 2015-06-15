<?php
/**
 * @file    framework/Cms/Ui/Main.php
 *
 * depage cms ui module
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Ui;

use \Depage\Html\Html;

class Main extends Base {
    protected $autoEnforceAuth = false;

    // {{{ _getSubHandler
    static function _getSubHandler() {
        return array(
            'project/*' => '\Depage\Cms\Ui\Project',
            'user/*' => '\Depage\Cms\Ui\User',
            'project/*/preview' => '\Depage\Cms\Ui\Preview',
            'project/*/flash' => '\Depage\Cms\Ui\Flash',
            //'project/*/tree/*' => '\Depage\Cms\Ui\Tree',
            //'project/*/tree/*/fallback' => '\Depage\Cms\Ui\SocketFallback',
            //'project/*/edit/*' => '\Depage\Cms\Ui\Edit',
        );
    }
    // }}}

    // {{{ index
    /**
     * default function to call if no function is given in handler
     *
     * @return  null
     */
    public function index() {
        if ($this->auth->enforceLazy()) {
            // logged in
            $h = new Html(array(
                'content' => new Html("home.tpl", array(
                    'content' => array(
                        $this->projects(),
                        $this->users("current"),
                        $this->tasks(),
                    ),
                )),
            ), $this->htmlOptions);
        } else {
            // not logged in
            $h = new Html(array(
                'content' => new Html("welcome.tpl", array(
                    'title' => "Welcome to\n depage::cms ",
                    'login' => "Login",
                    'login_link' => "login/",
                )),
            ), $this->htmlOptions);
        }

        return $h;
    }
    // }}}

    // {{{ login
    public function login() {
        if ($this->auth->enforce()) {
            // logged in
            \Depage\Depage\Runner::redirect(DEPAGE_BASE);
        } else {
            // not logged in
            $form = new \Depage\HtmlForm\HtmlForm("login", array(
                'label' => _("Login"),
                'validator' => function($form, $values) {
                    return (bool) $this->auth->login($values['name'], $values['pass']);
                },
            ));

            // define formdata
            $form->addText("name", array(
                'label' => 'Name',
                'required' => true,
            ));

            $form->addPassword("pass", array(
                'label' => 'Passwort',
                'required' => true,
            ));

            $form->process();

            if ($form->valid) {
                $form->clearSession();
            } else {
                $error = "";
                if (!$form->isEmpty()) {
                    $error = "<p class=\"error\">false/unknown username password combination</p>";
                }

                $h = new Html("box.tpl", array(
                    'icon' => "framework/cms/images/icon_login.gif",
                    'class' => "box-login",
                    'title' => "Login",
                    'content' => array(
                        $error,
                        $form,
                    ),
                ), $this->htmlOptions);

                return $h;
            }
        }
    }
    // }}}
    // {{{ logout
    public function logout($action = null) {
        //if ($action[0] == "now") {
            $this->auth->enforceLogout();
        //}

        $h = new Html("box.tpl", array(
            'class' => "box-logout",
            'title' => "Bye bye!",
            'content' => new Html("logout.tpl", array(
                'content' => "Thank you for using depage::cms. ",
                'relogin1' => "You can relogin ",
                'relogin2' => "here",
                'relogin_link' => "login/",
            )),
        ), $this->htmlOptions);

        return $h;
    }
    // }}}

    // {{{ projects
    /**
     * gets a list of projects
     *
     * @return  null
     */
    public function projects() {
        // get data
        $projects = \Depage\Cms\Project::loadAll($this->pdo, $this->cache);

        // construct template
        $h = new Html("box.tpl", array(
            'class' => "box-projects",
            'title' => "Projects",
            'content' => new Html("projectlist.tpl", array(
                'projects' => $projects,
            )),
        ), $this->htmlOptions);

        return $h;
    }
    // }}}
    // {{{ tasks
    /**
     * gets a list of projects
     *
     * @return  null
     */
    public function tasks($taskId = null) {
        // handle tasks deletion form
        $taskForm = new \Depage\HtmlForm\HtmlForm("delete-task", array(
            'label' => _("Remove"),
            'successUrl' => DEPAGE_BASE,
            'class' => "action-form",
        ));
        $taskForm->addHidden("taskId");

        $taskForm->process();
        if ($taskForm->valid) {
            $task = \Depage\Tasks\Task::load($this->pdo, $taskForm->getValues()['taskId']);
            $task->remove();

            $taskForm->clearSession();
        }

        // get data
        if (!empty($taskId)) {
            // load specific task
            $tasks = array();
            $task = \Depage\Tasks\Task::load($this->pdo, $taskId);

            if ($task) {
                $taskrunner = new \Depage\Tasks\TaskRunner($this->options);
                $taskrunner->run($task->taskId);

                $tasks[] = $task;
            }
        } else {
            // load all tasks
            $tasks = \Depage\Tasks\Task::loadAll($this->pdo);
        }

        foreach ($tasks as $task) {
            if ($task) {
                $taskrunner = new \Depage\Tasks\TaskRunner($this->options);
                $taskrunner->run($task->taskId);
            }
        }

        // construct template
        $h = new Html("box.tpl", array(
            'id' => "box-tasks",
            'class' => "box-tasks",
            'title' => "Tasks",
            'updateUrl' => "tasks/",
            'content' => new Html("taskProgress.tpl", array(
                'tasks' => $tasks,
                'taskForm' => $taskForm,
            )),
        ), $this->htmlOptions);

        return $h;
    }
    // }}}
    // {{{ task
    /**
     * gets a list of projects
     *
     * @return  null
     */
    public function task($taskId) {
        return $this->tasks($taskId);
    }
    // }}}

    // {{{ users
    /**
     * gets a list of loggedin users
     *
     * @return  null
     */
    public function users($current = null) {
        $this->auth->enforce();

        $showCurrent = $current === "current";

        if ($showCurrent) {
            $users = \Depage\Auth\User::loadActive($this->pdo);
        } else {
            $users = \Depage\Auth\User::loadAll($this->pdo);
        }

        $h = new Html("box.tpl", array(
            'id' => "box-users",
            'class' => "box-users",
            'title' => "Users",
            'updateUrl' => "users/$current/",
            'content' => new Html("userlist.tpl", array(
                'title' => $this->basetitle,
                'users' => $users,
            )),
        ), $this->htmlOptions);

        return $h;
    }
    // }}}
    // {{{ test_task()
    /**
     * @brief test_task
     *
     * @param mixed
     * @return void
     **/
    public function test_task()
    {
        $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, "Test Task");
        $sleepMin = 0;
        $sleepMax = 10 * 1000000;

        for ($i = 0; $i < 5; $i++) {
            $dep1 = $task->addSubtask("init $i", "echo(\"init $i\n\"); usleep(rand($sleepMin, $sleepMax));");

            for ($j = 0; $j < 5; $j++) {
                $dep2 = $task->addSubtask("dep2 $i/$j", "echo(\"dep $i/$j\n\"); usleep(rand($sleepMin, $sleepMax));", $dep1);

                for ($k = 0; $k < 10; $k++) {
                    $task->addSubtask("testing $i/$j/$k", "echo(\"testing $i/$j/$k\n\"); usleep(rand($sleepMin, $sleepMax));", $dep2);
                }
            }
        }
        //$task->addSubtask("testing error", "throw new \Exception(\"ahhhh!\");");

        \Depage\Depage\Runner::redirect(DEPAGE_BASE);
    }
    // }}}

    // {{{ setup()
    /**
     * @brief adds base schemata
     *
     * @return void
     **/
    public function setup()
    {
        // add/update schema for authentication
        \Depage\Auth\Auth::updateSchema($this->pdo);

        $this->auth->enforce();

        // add/update schema for tasks
        \Depage\Tasks\Task::updateSchema($this->pdo);

        // add/update schema for project structures
        \Depage\Cms\Project::updateSchema($this->pdo);

        $projects = \Depage\Cms\Project::loadAll($this->pdo, $this->cache);

        foreach ($projects as $project) {
            $project->updateProjectSchema();
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
