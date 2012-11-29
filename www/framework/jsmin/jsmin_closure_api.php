<?php
/**
 * @file    jsmin.php
 * @brief   jsmin class
 *
 * @author  Frank Hellenkamp <jonas@depage.net>
 **/

namespace depage\jsmin;

/**
 * @brief Main jsmin class
 **/
class jsmin_closure_api extends jsmin {
    // {{{ variables
    var $apiUrl = "http://closure-compiler.appspot.com/compile";
    // }}}
    // {{{ factory()
    /**
     * @brief   jsmin object factory
     * 
     * Generates minify object
     *
     * @param   $options (array) jsmin processing parameters
     * @return  (object) jsmin object
     **/
    public static function factory($options = array()) {
        $extension = (isset($options['extension'])) ? $options['extension'] : 'closure-api';

        if ( $extension == 'closure-api' ) {
        }
        return new jsmin_closure_api($options);
    }
    // }}}
    // {{{ __construct()
    /**
     * @brief jsmin class constructor
     *
     * @param $options (array) image processing parameters
     **/
    public function __construct($options = array()) {
        parent::__construct($options);
    }
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
