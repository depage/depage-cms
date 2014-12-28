<?php
/**
 * @file    framework/task/task_runner.php
 *
 * depage cms task runner module
 *
 *
 * copyright (c) 2011 Lion Vollnhals [lion.vollnhals@googlemail.com]
 *
 * @author    Lion Vollnhals [lion.vollnhals@googlemail.com]
 */

namespace Depage\Tasks;

require_once(__DIR__ . "/../Depage/Runner.php");

/*
 * task runner design:
 *
 * task_runner#task reads task with respective id from table *_tasks .
 * task is locked by writing a file lock.
 * individual subtasks are read from table *_subtasks .
 * each subtask and task has a status that is updated after running it.
 * if a subtask fails then its status is set to "failed: {$error_message}",
 * the corresponding task is also set to "failed".
 *
 * general errors are signaled by throwing exceptions. for example when
 * there is no such task or when the task is already running.
 *
 * logging is done to logs/depage_task_task_runner.log .
 *
 * php's max execution timeout is anticipated, the script is automatically
 * restarted and the task will be resumed.
 * dependent subtasks will also be redone.
 * if possible the script will be re-executed by php CLI, otherwise the
 * current request will be repeated.
 */

/* TODO:
 webserver timeouts may happen before php max execution timeout:
 "Your web server can have other timeout configurations that
 may also interrupt PHP execution. Apache has a Timeout directive
 and IIS has a CGI timeout function. Both default to 300 seconds.
 See your web server documentation for specific details."

*/

class TaskRunner extends \Depage\Depage\Ui\Base
{
    // {{{ default config
    public $defaults = array(
        'env' => "development",
        'phpcli' => "",
    );
    protected $options = array();
    protected $lowPriority = true;
    // }}}

    // {{{ constructor
    public function __construct($options = NULL) {
        parent::__construct($options);

        // get database instance
        $this->pdo = new \Depage\Db\Pdo (
            $this->options->db->dsn, // dsn
            $this->options->db->user, // user
            $this->options->db->password, // password
            array(
                'prefix' => $this->options->db->prefix, // database prefix
            )
        );

        $this->force_login = false; // TODO: !$cli;
        if ($this->force_login) {
            // get auth object
            $this->auth = \Depage\Auth\Auth::factory(
                $this->pdo, // db_pdo
                $this->options->auth->realm, // auth realm
                DEPAGE_BASE, // domain
                $this->options->auth->method // method
            );
        }
    }
    // }}}

    // {{{ runNow
    public function runNow($task_id) {
        if ($this->force_login)
            $this->auth->enforce();

        $this->task = Task::load((int)$task_id, $this->pdo);
        $this->abnormal_exit = true;
        register_shutdown_function(array($this, "_atShutdown"));

        if ($this->task->lock()) {
            try {
                $this->log->log("starting task {$task_id} ({$this->task->task_name})");

                while ($subtask = $this->task->getNextSubtask()) {
                    // @todo change logging to log output of a task to one file per task
                    $subtask_name = "{$subtask->id} ({$subtask->name})";
                    $this->log->log("    starting subtask $subtask_name");

                    $status = $this->task->runSubtask($subtask);
                    if ($status === false) {
                        throw new \Exception("Parse Error or subtask returned false");
                    }

                    $this->log->log("    finished subtask $subtask_name");
                    $this->task->setSubtaskStatus($subtask, "done");
                }

                $this->log->log("finished task {$task_id} ({$this->task->task_name})");
                $this->task->setTaskStatus("done");
                $this->task->remove();
            } catch (\Exception $e) {
                $this->task->setSubtaskStatus($subtask, "failed: " . $e->getMessage());
                $this->task->setTaskStatus("failed");
                $this->log->log("ERROR: " . $e->getMessage());
            }

            $this->task->unlock();
        } else {
            $this->log->log("task {$this->task->task_name} is already running");
        }

        $this->abnormal_exit = false;
    }
    // }}}
    // {{{ run
    public function run($task_id, $lowPriority = true) {
        $this->lowPriority = $lowPriority;
        $this->task = Task::load((int)$task_id, $this->pdo);
        $this->abnormal_exit = true;

        register_shutdown_function(array($this, "_atShutdown"));
    }
    // }}}

    // {{{ _atShutdown
    public function _atShutdown() {
        if ($this->abnormal_exit) {
            // release last held lock, so that new task can grab it
            $this->task->unlock();

            $args = array(
                "dp-path" => DEPAGE_PATH,
                "conf-url" => DEPAGE_BASE,
                "task-id" => $this->task->task_id,
            );

            $this->executeInBackground(__DIR__ . "/../../", "framework/Tasks/" . basename(__FILE__), $args, $this->lowPriority);
        } else {
            $this->log->log("normal exit");
        }
    }
    // }}}

    // private
    // {{{ executeInBackground()
    /**
    * executes another php script in background
    *
    * script is executed as background task
    * and function returns immediately to current script.
    *
    * @public
    *
    * @param    $path (string)
    * @param    $script (string)
    * @param    $args (string)
    * @param    $start_low_priority (bool)
    */
    private function executeInBackground($path, $script, $args = array(), $start_low_priority = false) {
        $path_phpcli = $this->getPhpExecutable();

        if ($path_phpcli && is_executable($path_phpcli)) {
            $param = "";
            foreach ($args as $key => $value) {
                $param .= " --$key " . escapeshellarg($value);
            }

            // call script in background through cli executable
            // this is the finest, because cli scripts has generally no timeout
            // but unfortunately not available in all cases/platforms
            if (file_exists($path . $script) || $path == '') {

                chdir($path);
                $prio_param = "";
                if (substr(php_uname(), 0, 7) == 'Windows') {
                    if ($start_low_priority) {
                        $prio_param = "/belownormal";
                    }
                    $fp = popen("start \"php subTask\" /min $prio_param \"" . str_replace("/", "\\", $path_phpcli) . "\" -f $script $args", "r");
                    usleep(500);
                    pclose($fp);
                } else {
                    if ($start_low_priority) {
                        $prio_param = "nice -n 19";
                    }
                    $fp = popen("$prio_param \"$path_phpcli\" -f $script -- $param > /dev/null &", "r");
                    usleep(500);
                    pclose($fp);
                }
            }
        // should only be called if original request was not by cli
        } else {
            // call script through http
            $host = $_SERVER['HTTP_HOST'];
            if ($host == "") {
                $host = $_SERVER['SERVER_NAME'];
            }
            if ($host == "") {
                $host = $_SERVER['SERVER_ADDR'];
            }
            if ($host == "") {
                $host = "localhost";
            }
            //$url = "http://{$host}{$conf->path_base}framework/{$script}?arg=" . urlencode($args);
            // TODO: fix url to adjust according to handlers
            $url = "http://{$host}{$_SERVER['REQUEST_URI']}";

            if (is_callable('curl_init')) {
                // call script through curl-interface
                $fp = curl_init($url);

                curl_setopt($fp, CURLOPT_HEADER, false);
                // hack for "non-blocking" -> has always a timout of 1 second
                curl_setopt($fp, CURLOPT_TIMEOUT, 1);
                curl_setopt($fp, CURLOPT_RETURNTRANSFER, true);

                curl_exec($fp);
                curl_close($fp);
            // TODO: does not work:
            } else if (is_callable('fsockopen')) {
                // call script though fsockopen-interface
                $urlinfo = parse_url($url);

                if (!isset($urlinfo['port'])) {
                    $urlinfo['port'] = $_SERVER['SERVER_PORT'];
                }
                if (!isset($urlinfo['port'])) {
                    $urlinfo['port'] = 80;
                }

                $header .= "GET {$urlinfo['path']}?{$urlinfo['query']} HTTP/1.0\r\n";
                $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
                $header .= "Content-Length: 0\r\n\r\n";

                $fp = fsockopen ($urlinfo['host'], $urlinfo['port'], $errno, $errstr, 30);
                if ($fp) {
                    fputs ($fp, $header);
                    fclose($fp);
                } else {
                    $log->add_entry("could not execute '$script' by '$url'\n$errorno - $errstr");
                }
            // TODO: probably cannot work at all:
            } else {
                // call script through fopen -> this is ugly because it's blocking until
                // called script is finished or parent script has timed out
                $fp = fopen($url, 'r');
                stream_set_blocking($fp, 0); // @todo test non-blocking

                if ($fp) {
                    fclose($fp);
                } else {
                    $log->add_entry("could not execute '$script' by '$url'\n$errorno - $errstr");
                }
            }
        }
    }
    // }}}
    // {{{ getPhpExecutable()
    private function getPhpExecutable() {
        if ($this->options->phpcli != "") {
            return $this->options->phpcli;
        }
        // only some shells set this variable
        if (isset($_SERVER["_"])) {
            $exe = $_SERVER["_"];
        }
        if (empty($exe) || strpos($exe, "php") === false) {
            $exe = $this->getPhpExecutableFromPath();
        }

        return $exe;
    }
    // }}}
    // {{{ getPhpExecutableFromPath()
    // see http://stackoverflow.com/questions/3889486/how-to-get-the-path-of-the-php-bin-from-php/3889630#3889630
    private function getPhpExecutableFromPath() {
        $paths = explode(PATH_SEPARATOR, getenv('PATH'));
        foreach ($paths as $path) {
            $php_executable = $path . DIRECTORY_SEPARATOR . "php" . (isset($_SERVER["WINDIR"]) ? ".exe" : "");
            if (file_exists($php_executable) && is_file($php_executable)) {
                return $php_executable;
            }
        }
        return FALSE; // not found
    }
    // }}}
}

// run task if called from cli
if (php_sapi_name() == 'cli') {
    $dp = new \Depage\Depage\Runner();

    // test getopt without "standard"-options
    $options = getopt("h", array(
        "task-id:",
        "dp-path:",
        "conf-url:",
    ));

    $task_runner = new TaskRunner($dp->conf);
    $task_runner->runNow($options['task-id'], true);
}

/* TODO:
 *  - how to handle errors?
 *  - restart failed task!? wird erstmal nicht gebraucht!
 *  - was ist wenn ein einzelner Subtaks Zeitlimit überschreitet?
 */
/* vim:set ft=php sw=4 sts=4 fdm=marker : */

