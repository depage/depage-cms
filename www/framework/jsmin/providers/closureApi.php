<?php
/**
 * @file    jsmin.php
 * @brief   jsmin class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 **/

namespace depage\jsmin\providers;

/**
 * @brief Main jsmin class
 **/
class closureApi extends \depage\jsmin\jsmin {
    // {{{ variables
    var $apiUrl = "http://closure-compiler.appspot.com/compile";
    // }}}
    
    // {{{ minifySrc()
    /**
     * @brief minifies js-source
     *
     * @param $src javascript source code
     **/
    public function minifySrc($src) {
        $rq = new \depage\http\request($this->apiUrl, array(
            'js_code' => $src,
            'compilation_level' => "SIMPLE_OPTIMIZATIONS",
            'output_info' => "compiled_code",
            'output_format' => "xml",
        ));
        $data = $rq->execute();
        $xml = simplexml_load_string($data);
        if (!empty($xml->compiledCode)) {
            return $xml->compiledCode;
        } else {
            foreach ($xml->serverErrors as $error) {
                throw new exceptions\jsminException($error->error);
            }

            return false;
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
