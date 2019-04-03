<?php
/**
 * @file    jsmin.php
 * @brief   jsmin class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 **/

namespace Depage\JsMin\Providers;

/**
 * @brief Main jsmin class
 **/
class ClosureLocal extends \Depage\JsMin\JsMin {
    // {{{ variables
    var $java = "/usr/bin/java";
    var $jar = "/Users/Shared/coding/Closure/compiler.jar";
    // }}}

    // {{{ minifySrc()
    /**
     * @brief minifies js-source
     *
     * @param $src javascript source code
     **/
    public function minifySrc($src) {

        $compiler = "{$this->java} -jar {$this->jar} ";
        $descriptorspec = array(
            0 => array("pipe", "r"), // stdin is a pipe that the child will read from
            1 => array("pipe", "w"), // stdout is a pipe that the child will write to
            2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
        );

        $process = proc_open($compiler, $descriptorspec, $pipes);

        if (is_resource($process)) {
            // write to stdin
            fwrite($pipes[0], $src);
            fclose($pipes[0]);

            // read from stdout
            $result = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // close process
            $success = proc_close($process);

            // @todo add error handler when there are errors while minimizing
        }
        if ($success == 0) {
            return $result;
        } else {
            return false;
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
