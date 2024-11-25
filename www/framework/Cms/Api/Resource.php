<?php
/**
 * @file    Project.php
 *
 * description
 *
 * copyright (c) 2020 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Api;

/**
 * @brief Project
 * Class Project
 */
class Resource extends Json
{
    // {{{ get()
    /**
     * @brief get
     *
     * @param string $uri
     *
     * @return object
     **/
    public function get(string $lang, string ...$args)
    {
        // @todo check auth per api key
        $apikey = sha1("testkey");
        $uri = implode("/", $args);
        $body = "";

        $validRequest = $_SERVER['HTTP_X_AUTHORIZATION'] == $apikey;

        if (!$validRequest) {
            $response = new \Depage\Http\Response("Unauthorized", [
                "HTTP/1.1 401 Unauthorized"
            ]);

            return $response;
        }

        if ($lang == "lib") {
            $path = $this->project->getProjectPath() . "/lib/" . $uri;

            if (file_exists($path)) {
                $body = file_get_contents($path);
                //readfile($path);
            }
        }

        $retVal = [
            'success' => true,
            'validRequest' => $validRequest,
            'lang' => $lang,
            'uri' => $uri,
            'project' => $this->projectName,
            'body' => base64_encode($body),
        ];

        return $retVal;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :

