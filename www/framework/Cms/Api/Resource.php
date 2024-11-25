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
        $publishId = filter_input(\INPUT_GET, 'publishId', \FILTER_SANITIZE_NUMBER_INT);
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
        } else {
            $body = $this->transformUrl($publishId, $uri, $lang);
        }

        $retVal = [
            'success' => true,
            'validRequest' => $validRequest,
            'lang' => $lang,
            'uri' => $uri,
            'project' => $this->projectName,
            'publishId' => $publishId,
            'publishingTargets' => $this->project->getPublishingTargets(),
            'body' => base64_encode($body),
        ];

        return $retVal;
    }
    // }}}
    // {{{ transformUrl()
    /**
     * @brief transformUrl
     *
     * @param string $url
     * @param string $lang
     *
     * @return string
     **/
    protected function transformUrl(int $publishId, string $uri, string $lang)
    {
        $this->project->setPreviewType("live");
        $xmlgetter = $this->project->getXmlGetter();

        $conf = $this->project->getPublishingTargets()[$publishId];

        $transformCache = new \Depage\Transformer\TransformCache(
            $this->pdo,
            $this->project->name,
            $conf->template_set . "-live-" . $publishId,
        );

        $transformer = \Depage\Transformer\Transformer::factory(
            "live",
            $this->project->getXmlGetter(),
            $this->project,
            $conf->template_set,
            $transformCache
        );
        $transformer->publishId = $publishId;

        $transformer->setBaseUrl(
            $this->project->getBaseUrl($publishId)
        );
        $transformer->setBaseUrlStatic(
            $this->project->getBaseUrlStatic($publishId)
        );
        $transformer->routeHtmlThroughPhp = $this->project->getProjectConfig()->routeHtmlThroughPhp;

        return $transformer->transformUrl("/" . $uri, $lang);
    }
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :

