<?php


// {{{ execute_in_background()
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
function execute_in_background($path, $script, $args = '', $start_low_priority = false) {
    $path_phpcli = get_php_executable();

    global $conf;
    global $log;

    if ($path_phpcli && is_executable($path_phpcli)) {
        // call script in background through cli executable
        // this is the finest, because cli scripts has generally no timeout
        // but unfortunately not available in all cases/platforms
        if (file_exists($path . $script) || $path == '') {
            chdir($path);
            if (substr(php_uname(), 0, 7) == 'Windows') {
                if ($start_low_priority) {
                    $prio_param = "/belownormal";
                }
                pclose(popen("start \"php subTask\" /min $prio_param \"" . str_replace("/", "\\", $path_phpcli) . "\" -f $script " . escapeshellarg($args), "r"));    
            } else {
                if ($start_low_priority) {
                    $prio_param = "nice -10";
                }
                //exec("$prio_param \"$path_phpcli\" -f $script " . escapeshellarg($args) . " > /dev/null &");    
                pclose(popen("$prio_param \"$path_phpcli\" -f $script " . escapeshellarg($args) . " > /dev/null &", "r"));    
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
        // TODO: frank fragen ob korrekt
        $url = "http://{$host}{$_SERVER['REQUEST_URI']}";
        
        if (is_callable('curl_init')) {
            // call script through curl-interface
            $fp = curl_init($url);

            curl_setopt($fp, CURLOPT_HEADER, false);
            // hack for "non-blocking" -> has always a timout of 1 second
            curl_setopt($fp, CURLOPT_TIMEOUT, 1);

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
            if ($fp) {
                fclose($fp);
            } else {
                $log->add_entry("could not execute '$script' by '$url'\n$errorno - $errstr");
            }
        }
    }
}
// }}}


// {{{ get_php_executable
// see http://stackoverflow.com/questions/3889486/how-to-get-the-path-of-the-php-bin-from-php/3889630#3889630
function get_php_executable() {
    // only some shells set this variable
    $exe = $_SERVER["_"];
    if (empty($exe) || strpos($exe, "php") === false) {
        $exe = get_php_executable_from_path();
    }
    
    return $exe;
}
// }}}

// {{{ get_php_executable_from_path
// see http://stackoverflow.com/questions/3889486/how-to-get-the-path-of-the-php-bin-from-php/3889630#3889630
function get_php_executable_from_path() {
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

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
