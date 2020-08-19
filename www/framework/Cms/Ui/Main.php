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
use \Depage\Notifications\Notification;

class Main extends Base {
    protected $autoEnforceAuth = false;

    // {{{ _getSubHandler
    static function _getSubHandler() {
        return [
            'project/*' => '\Depage\Cms\Ui\Project',
            'user/*' => '\Depage\Cms\Ui\User',
            'project/*/preview' => '\Depage\Cms\Ui\Preview',
            'project/*/newsletter/*' => '\Depage\Cms\Ui\Newsletter',
            'project/*/tree/*' => '\Depage\Cms\Ui\Tree',
            'project/*/tree/*/fallback' => '\Depage\Cms\Ui\SocketFallback',
            'project/*/doc-properties/*/*' => '\Depage\Cms\Ui\DocProperties',
            'project/*/library' => '\Depage\Cms\Ui\FileLibrary',
            'project/*/colors' => '\Depage\Cms\Ui\ColorSchemes',
            'api/*/newsletter' => '\Depage\Cms\Api\Newsletter',
            'api/*/user' => '\Depage\Cms\Api\User',
            'api/*/cache' => '\Depage\Cms\Api\Cache',
            //'api/*/project' => '\Depage\Cms\Api\Project',
        ];
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
            $h = new Html("home.tpl", [
                'content' => [
                    $this->projects(),
                    $this->tasks(),
                    $this->users("current"),
                    $this->news(),
                    $this->help(),
                ],
                'helpUrl' => $this->helpUrl,
            ], $this->htmlOptions);
        } else {
            $form = new \Depage\Cms\Forms\Login("login");

            // not logged in
            $h = new Html("welcome.tpl", [
                'loginForm' => $form,
                'content' => [
                    $this->news(),
                    $this->help(),
                ],
            ], $this->htmlOptions);
        }

        return $h;
    }
    // }}}

    // {{{ login
    public function login() {
        if ($this->auth->enforce()) {
            // logged in
            $redirectTo = DEPAGE_BASE;
            if (!empty($_GET['redirectTo']) && substr($_GET['redirectTo'], 0, strlen(DEPAGE_BASE)) == DEPAGE_BASE) {
                $redirectTo = $_GET['redirectTo'];
            }
            \Depage\Depage\Runner::redirect($redirectTo);
        } else {
            // not logged in
            $form = new \Depage\Cms\Forms\Login("login", [
                'validator' => function($form, $values) {
                    return (bool) $this->auth->login($values['name'], $values['pass']);
                },
            ]);

            $form->process();

            if ($form->valid) {
                $form->clearSession();
            } else {
                $message = "";
                if (!$form->isEmpty()) {
                    $message = "<p class=\"error\">false/unknown username password combination</p>";
                }
                if (isset($_GET['loggedOut'])) {
                    $message = "<p class=\"message\">" . _("Your current session expired. Please login again.") . "</p>";
                }
                $form->addHtml($message);

                $h = new Html("scrollable.tpl", [
                    'class' => "top",
                    'content' => new Html("box.tpl", [
                        'icon' => "framework/cms/images/icon_login.gif",
                        'class' => "box-login",
                        'title' => "Login",
                        'liveHelp' => _("Login"),
                        'content' => [
                            $form,
                        ],
                    ]),
                ], $this->htmlOptions);

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

        $h = new Html("scrollable.tpl", [
            'class' => "top",
            'content' => new Html("box.tpl", [
                'class' => "box-logout",
                'title' => "Bye bye!",
                'content' => new Html("logout.tpl", [
                    'content' => "Thank you for using depage::cms. ",
                    'relogin1' => "You can relogin ",
                    'relogin2' => "here",
                    'relogin_link' => "login/",
                ]),
            ]),
        ], $this->htmlOptions);

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
        $this->user = $this->auth->enforce();

        // get data
        $projects = \Depage\Cms\Project::loadByUser($this->pdo, $this->xmldbCache, $this->user);
        $projectGroups = \Depage\Cms\ProjectGroup::loadAll($this->pdo);

        // construct template
        $h = new Html("box.tpl", [
            'class' => "box-projects",
            'title' => _("Projects"),
            'liveHelp' => _("Edit, preview or changed settings for your projects"),
            'content' => new Html("projectlist.tpl", [
                'user' => $this->user,
                'projects' => $projects,
                'projectGroups' => $projectGroups,
            ]),
        ], $this->htmlOptions);

        return $h;
    }
    // }}}

    // {{{ overview()
    /**
     * @brief overview
     *
     * @return void
     **/
    public function overview()
    {
        if ($this->auth->enforceLazy()) {
            $content = [
                $this->tasks(),
                $this->users("current"),
            ];

            return $content;
        }
    }
    // }}}

    // {{{ tasks
    /**
     * gets a list of projects
     *
     * @return  null
     */
    public function tasks($taskId = null) {
        $this->user = $this->auth->enforce();

        // get data
        if (!empty($taskId)) {
            // load specific task
            $tasks = [];
            $task = \Depage\Tasks\Task::load($this->pdo, $taskId);

            if ($task) {
                $tasks[] = $task;
            }
        } else {
            // load all tasks
            $tasks = \Depage\Tasks\Task::loadAll($this->pdo);
        }

        // filter tasks by user
        $projects = \Depage\Cms\Project::loadByUser($this->pdo, $this->xmldbCache, $this->user);
        $tasks = array_filter($tasks, function($task) use ($projects) {
            foreach ($projects as $project) {
                if ($project->name == null || $project->name == $task->projectName) {
                    return true;
                }
            }

            return false;
        });

        // construct template
        $h = new Html("box.tpl", [
            'class' => "box-tasks",
            'title' => _("Tasks"),
            'updateUrl' => "tasks/",
            'liveHelp' => _("Shows the currently running background tasks"),
            'content' => new Html("taskProgress.tpl", [
                'tasks' => $tasks,
            ]),
        ], $this->htmlOptions);

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
        $this->user = $this->auth->enforce();

        $showCurrent = $current === "current";

        if ($showCurrent) {
            $users = \Depage\Auth\User::loadActive($this->pdo);
            $updateUrl = "users/current/";
        } else {
            $users = \Depage\Auth\User::loadAll($this->pdo);
            $updateUrl = "";
        }

        // filter users by user
        $projects = \Depage\Cms\Project::loadByUser($this->pdo, $this->xmldbCache, $this->user);
        $user = $this->user;

        $users = array_filter($users, function($u) use ($projects, $user) {
            if ($u->id == $user->id) {
                return true;
            }
            if ($user->canEditAllUsers()) {
                return true;
            }
            $userProjects = \Depage\Cms\Project::loadByUser($this->pdo, $this->xmldbCache, $u);
            $shared = array_intersect($projects, $userProjects);
            if (count($shared) > 0) {
                return true;
            }

            return false;
        });

        $h = new Html("box.tpl", [
            'id' => $showCurrent ? "box-users" : "",
            'class' => "box-users",
            'title' => _("Users"),
            'updateUrl' => $updateUrl,
            'liveHelp' => _("Shows the users that are currently logged in"),
            'content' => new Html("userlist.tpl", [
                'title' => $this->basetitle,
                'users' => $users,
                'showCurrent' => $showCurrent,
            ]),
        ], $this->htmlOptions);

        if (!$showCurrent && !isset($_GET['ajax'])) {
            $h = new Html("scrollable.tpl", [
                'content' => $h,
            ], $this->htmlOptions);
        }

        return $h;
    }
    // }}}

    // {{{ news
    /**
     * overview of depage-related ews
     *
     * @return  null
     */
    public function news() {
        // construct template
        $h = new Html("box.tpl", [
            'class' => "box-news",
            'title' => _("News"),
            'liveHelp' => _("Shows news and version info"),
            'content' => new Html("news.tpl", [
            ]),
        ], $this->htmlOptions);

        return $h;
    }
    // }}}

    // {{{ track()
    /**
     * @brief track
     *
     * @param mixed $
     * @return void
     **/
    public function track($projectName, $type, $name, $hash)
    {
        if ($type == "newsletter") {
            try {
                $project = \Depage\Cms\Project::loadByName($this->pdo, $this->xmldbCache, $projectName);
                $newsletter = \Depage\Cms\Newsletter::loadByName($this->pdo, $project, $name);

                $newsletter->track($hash);
            } catch (\Exception $e) {
            }
        }

        $im = imagecreate(100, 10);
        $color = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $color);

        header('Content-Type: image/png');

        imagepng($im);
        imagedestroy($im);
    }
    // }}}
    // {{{ api()
    /**
     * @brief api
     *
     * @todo move this in own class or classes
     *
     * @param mixed $
     * @return void
     **/
    public function api($projectName, $type, $action)
    {
        $retVal = [
            'success' => false,
        ];
        try {
            if ($projectName != "-") {
                $project = \Depage\Cms\Project::loadByName($this->pdo, $this->xmldbCache, $projectName);
            }
        } catch (\Exception $e) {
            $retVal['error'] = $e->getMessage();
            return new \Depage\Json\Json($retVal);
        }

        if ($type == "css") {
            if ($action == "generate") {
                $retVal['success'] = $project->generateCss();
            }
        }
        if ($type == "project") {
            $url = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_STRING);

            if ($action == "pageId" && !empty($url)) {
                $xmlGetter = $project->getXmlGetter();

                $transformer = \Depage\Transformer\Transformer::factory("dev", $xmlGetter, $projectName, "html");
                $transformer->routeHtmlThroughPhp = true;
                list($retVal['pageId'],, $retVal['urlPath']) = $transformer->getPageIdFor($url);

                $retVal['success'] = true;
            }
        }
        if ($type == "task") {
            $taskToDelete = filter_input(INPUT_POST, 'taskId', FILTER_SANITIZE_NUMBER_INT);
            if ($action == "delete") {
                if ($task = \Depage\Tasks\Task::load($this->pdo, $taskToDelete)) {
                    $retVal['success'] = $task->remove();
                }
            }
        }

        return new \Depage\Json\Json($retVal);
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

        \Depage\Tasks\Task::updateSchema($this->pdo);
        \Depage\Cms\Project::updateSchema($this->pdo);
        \Depage\Notifications\Notification::updateSchema($this->pdo);

        $projects = \Depage\Cms\Project::loadAll($this->pdo, $this->xmldbCache);

        foreach ($projects as $project) {
            $project->updateProjectSchema();
        }

        return "updated";
    }
    // }}}
    // {{{ info()
    /**
     * @brief displays php info
     *
     * @return void
     **/
    public function info()
    {
        if ($this->auth->enforceLazy()) {
            $info = new \Depage\Php\Info();
            return new Html("about.tpl", [
                "info" => $info->getInfo(),
            ], $this->htmlOptions);
        }
    }
    // }}}
    // {{{ help()
    /**
     * @brief displays inline help
     *
     * @return void
     **/
    public function help()
    {
        return new Html("helpLink.tpl", [
            'helpUrl' => $this->helpUrl,
        ], $this->htmlOptions);
    }
    // }}}

    // {{{ search()
    /**
     * @brief search
     *
     * @param mixed
     * @return void
     **/
    public function search()
    {
        $search = new \Depage\Search\Search($this->pdo);
        $results = $search->query($_GET['q']);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
