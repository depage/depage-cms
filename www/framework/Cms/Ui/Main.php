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
            'project/*/flash' => '\Depage\Cms\Ui\Flash',
            'project/*/newsletter/*' => '\Depage\Cms\Ui\Newsletter',
            'project/*/tree/*' => '\Depage\Cms\Ui\Tree',
            //'project/*/tree/*/fallback' => '\Depage\Cms\Ui\SocketFallback',
            //'project/*/edit/*' => '\Depage\Cms\Ui\Edit',
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
                    $this->users("current"),
                    $this->tasks(),
                ],
            ], $this->htmlOptions);
        } else {
            // not logged in
            $h = new Html("welcome.tpl", [
                'title' => _("Welcome to\n depage::cms"),
                'login' => "Login",
                'login_link' => "login/",
            ], $this->htmlOptions);
        }

        return $h;
    }
    // }}}

    // {{{ login
    public function login() {
        if ($this->auth->enforce()) {
            // logged in
            if (!empty($_GET['redirectTo'])) {
                \Depage\Depage\Runner::redirect($_GET['redirectTo']);
            } else {
                \Depage\Depage\Runner::redirect(DEPAGE_BASE);
            }
        } else {
            // not logged in
            $form = new \Depage\HtmlForm\HtmlForm("login", [
                'label' => _("Login"),
                'validator' => function($form, $values) {
                    return (bool) $this->auth->login($values['name'], $values['pass']);
                },
            ]);

            // define formdata
            $form->addText("name", [
                'label' => 'Name',
                'required' => true,
                'autofocus' => true,
            ]);

            $form->addPassword("pass", [
                'label' => 'Passwort',
                'required' => true,
            ]);

            $form->process();

            if ($form->valid) {
                $form->clearSession();
            } else {
                $error = "";
                if (!$form->isEmpty()) {
                    $error = "<p class=\"error\">false/unknown username password combination</p>";
                }

                $h = new Html("box.tpl", [
                    'icon' => "framework/cms/images/icon_login.gif",
                    'class' => "box-login",
                    'title' => "Login",
                    'liveHelp' => _("Login"),
                    'content' => [
                        $error,
                        $form,
                    ],
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

        $h = new Html("box.tpl", [
            'class' => "box-logout",
            'title' => "Bye bye!",
            'content' => new Html("logout.tpl", [
                'content' => "Thank you for using depage::cms. ",
                'relogin1' => "You can relogin ",
                'relogin2' => "here",
                'relogin_link' => "login/",
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
                $this->users("current"),
                $this->tasks(),
                $this->notifications(),
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

        // handle tasks deletion form
        $taskForm = new \Depage\HtmlForm\HtmlForm("delete-task", [
            'label' => _("Remove"),
            'successUrl' => DEPAGE_BASE,
            'class' => "action-form",
        ]);
        $taskForm->addHidden("taskId");

        $taskForm->process();
        if ($taskForm->valid) {
            if ($task = \Depage\Tasks\Task::load($this->pdo, $taskForm->getValues()['taskId'])) {
                $task->remove();
            }

            $taskForm->clearSession();
        }

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

        foreach ($tasks as $task) {
            if ($task) {
                $taskrunner = new \Depage\Tasks\TaskRunner($this->options);
                //$taskrunner->run($task->taskId);
            }
        }

        // construct template
        $h = new Html("box.tpl", [
            'id' => "box-tasks",
            'class' => "box-tasks",
            'title' => _("Tasks"),
            'updateUrl' => "tasks/",
            'liveHelp' => _("Shows the currently running background tasks"),
            'content' => new Html("taskProgress.tpl", [
                'tasks' => $tasks,
                'taskForm' => $taskForm,
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
        $sleepMax = 10000;

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

        $task->begin();

        \Depage\Depage\Runner::redirect(DEPAGE_BASE);
    }
    // }}}

    // {{{ notifications
    /**
     * gets notifications as javascript
     *
     * @return  null
     */
    public function notifications() {
        $nm = Notification::loadByTag($this->pdo, "mail.%");
        foreach($nm as $n) {
            if (!empty($n->uid)) {
                $to = \Depage\Auth\User::loadById($this->pdo, $n->uid)->email;

                $url = parse_url(DEPAGE_BASE);

                $subject = $url['host'] . " . " . $n->title;
                $text = "";
                $text .= sprintf(_("You received a new notification from %s:"), $url['host']) . "\n\n";
                $text .= $n->message . "\n\n";

                if (!empty($n->options["link"])) {
                    $text .= $n->options["link"] . "\n\n";
                }

                $text .= "--\n";
                $text .= _("Your faithful servant on") . "\n";
                $text .= DEPAGE_BASE . "\n";

                $mail = new \Depage\Mail\Mail("notifications@depage.net");
                $mail
                    ->setSubject($subject)
                    ->setText($text)
                    ->send($to);
            }
        }

        $nn = Notification::loadBySid($this->pdo, $this->authUser->sid, "depage.%");

        // construct template
        $h = new Html("Notifications.tpl", [
            'notifications' => $nn,
        ], $this->htmlOptions);

        // delete notifications
        foreach (array_merge($nm, $nn) as $n) {
            $n->delete();
        }

        return $h;
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
     * @todo move this in own class
     *
     * @param mixed $
     * @return void
     **/
    public function api($projectName, $type, $action)
    {
        $retVal = [
            'success' => false,
        ];
        if ($type == "newsletter") {
            try {
                $project = \Depage\Cms\Project::loadByName($this->pdo, $this->xmldbCache, $projectName);
                $newsletter = \Depage\Cms\Newsletter::loadByName($this->pdo, $project, $name);

                $values = json_decode(file_get_contents("php://input"));

                if ($values && $action == "subscribe") {
                    $retVal['success'] = $newsletter->subscribe($values->email, $values->firstname, $values->lastname, $values->description, $values->lang, $values->category);
                } else if ($values && $action == "unsubscribe") {
                    $retVal['success'] = $newsletter->unsubscribe($values->email, $values->lang, $values->category);
                }
            } catch (\Exception $e) {
                $retVal['error'] = $e->getMessage();
            }
        }
        if ($type == "cache") {
            try {
                $project = \Depage\Cms\Project::loadByName($this->pdo, $this->xmldbCache, $projectName);

                if ($action == "clear") {
                    $retVal['success'] = $project->clearTransformCache();
                }
            } catch (\Exception $e) {
                $retVal['error'] = $e->getMessage();
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
            ]);
        }
    }
    // }}}

    // {{{ test()
    /**
     * @brief test
     *
     * @param mixed $param
     * @return void
     **/
    public function test($param)
    {
        $indexer = new \Depage\Search\Indexer($this->pdo);

        $indexer->index("http://localhost/depage-cms/project/dsve/preview/html/pre/de/news.html");
        $indexer->index("http://localhost/depage-cms/project/dsve/preview/html/pre/de/news/2016/06/eu-kommission-fuehrt-konsultation-zur-dienstleistungsfreiheit-durch.html");
        $indexer->index("http://localhost/depage-cms/project/dsve/preview/html/pre/de/ueber-uns/dsvae.html");

        $indexer->index("https://screen-pitch.com/en/");
        $indexer->index("http://violeta-mikic.de/de/violeta-mikic.html");

        $indexer->index("http://localhost/depage-cms/project/depage/preview/html/live/en/blog/2013/10/depage-forms-html5-form-validation-part-2.html");
        die();
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
