<?php

require(__DIR__ . "/task.php");

/*

get first task from task list
grab file lock
run task
remove task from task list
release file lock


when time limit reached then
    start over
    resume last task

==============================

webserver timeouts may happen before php max execution timeout:
"Your web server can have other timeout configurations that
 may also interrupt PHP execution. Apache has a Timeout directive
 and IIS has a CGI timeout function. Both default to 300 seconds.
 See your web server documentation for specific details."

*/

$abnormal_exit = true;
register_shutdown_function("at_shutdown");

// TODO:
set_time_limit(2);
error_reporting(E_ERROR);



$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cli') {
    $task_name = $argv[1];
} else {
    $task_name = $_REQUEST["task"];
}

// TODO:
$table_prefix = "";
$pdo = null;
$task = new \depage\task\task($task_name, $table_prefix, $pdo);
if ($task->lock()) {
    echo "running task $task_name\n";

    while ($subtask = $task->get_next_subtask()) {
        $subtask_name = $task_name . "_" . $subtask->id;

        echo "running subtask $subtask_name\n";
        eval($subtask->php);

        $task->set_subtask_status($subtask, "done");
    }

    $task->unlock();
} else {
    echo "task $task_name is already running\n";
}

$abnormal_exit = false;


function at_shutdown() {
    if ($abnormal_exit) {
        echo "abnormal exit: restarting ...\n";

        // release last held lock, so that new task can grab it
        global $task;
        $task->unlock();

        global $task_name;
        execInBackground("", basename($_SERVER['PHP_SELF']), $task_name);
    } else {
        echo "normal exit\n";
    }
}

    // {{{ execInBackground()
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
    function execInBackground($path, $script, $args = '', $start_low_priority = false) {
        $_this = new stdClass;
        $_this->path_phpcli = "/opt/local/bin/php";
        global $conf;
        global $log;

        if ($_this->path_phpcli != "" && is_executable($_this->path_phpcli)) {
            // call script in background through cli executable
            // this is the finest, because cli scripts has generally no timeout
            // but unfortunately not available in all cases/platforms
            if (file_exists($path . $script) || $path == '') {
                chdir($path);
                if (substr(php_uname(), 0, 7) == 'Windows') {
                    if ($start_low_priority) {
                        $prio_param = "/belownormal";
                    }
                    pclose(popen("start \"php subTask\" /min $prio_param \"" . str_replace("/", "\\", $_this->path_phpcli) . "\" -f $script " . escapeshellarg($args), "r"));    
                } else {
                    if ($start_low_priority) {
                        $prio_param = "nice -10";
                    }
                    //exec("$prio_param \"$_this->path_phpcli\" -f $script " . escapeshellarg($args) . " > /dev/null &");    
                    //pclose(popen("$prio_param \"$_this->path_phpcli\" -f $script " . escapeshellarg($args) . " > /dev/null &", "r"));    
                    pclose(popen("$prio_param \"$_this->path_phpcli\" -f $script " . escapeshellarg($args) . ">> /tmp/output &", "r"));    
                }
            }
        } else {
            // call script through http
            $path = pathinfo($_SERVER['REQUEST_URI']);
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
            $url = "http://{$host}{$conf->path_base}framework/{$script}?arg=" . urlencode($args);

            if (is_callable('curl_init')) {
                // call script through curl-interface
                $fp = curl_init($url);

                curl_setopt($fp, CURLOPT_HEADER, false);
                // hack for "non-blocking" -> has always a timout of 1 second
                curl_setopt($fp, CURLOPT_TIMEOUT, 1);

                curl_exec($fp);
                curl_close($fp);
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
            } else {
                // call script through fopen -> this is ugly because it's blocking until 
                // called script is finished or parent script has timed out
                $fp = fopen($url, 'r');
                if ($fp) {
                    fclose($fp);
                } else {
                    $log->add_entry("could not execute '$script' by '$url'\n$errorno - $errstr");
                }
            }
        }
    }
    // }}}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
