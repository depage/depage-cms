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
class ClosureApi extends \Depage\JsMin\JsMin {
    // {{{ variables
    var $apiUrl = "https://closure-compiler.appspot.com/compile";
    // }}}

    // {{{ minifySrc()
    /**
     * @brief minifies js-source
     *
     * @param $src javascript source code
     **/
    public function minifySrc($src) {
        $rq = new \Depage\Http\Request($this->apiUrl);
        $rq->setPostData(array(
            'js_code' => $src,
            'compilation_level' => "SIMPLE_OPTIMIZATIONS",
            'output_info' => "compiled_code",
            'output_format' => "xml",
        ));
        $data = $rq->execute();
        $xml = simplexml_load_string($data);

        // @todo add error handler when there are errors while minimizing
        if (!empty($xml->compiledCode)) {
            return $xml->compiledCode;
        } else {
            foreach ($xml->serverErrors as $error) {
                throw new \Depage\JsMin\Exceptions\JsMinException($error->error);
            }

            return false;
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
