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
    protected $publishId = null;
    public $defaults = [
        'graphics' => [
            'extension'     => 'gd',
            'executable'    => '',
            'background'    => 'transparent',
            'optimize'      => false,
        ],
    ];
    protected function __construct($options = NULL) {
        parent::__construct($options);

        $this->conf = new \Depage\Config\Config($options);
        $this->options = $this->conf->getDefaultsFromClass($this);
    }
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
            header("HTTP/1.1 401 Unauthorized");

            return [
                'success' => false,
                'validRequest' => $validRequest,
            ];
        } else if ($lang == "lib") {
            $body = $this->libFile($publishId, $uri, $lang);
        } else if ($uri == "sitemap.xml") {
            $body = $this->project->generateSitemap($publishId, $lang);
        } else if ($uri == "atom.xml") {
            $body = $this->project->generateAtomFeed($publishId, $lang);
        } else if (isset($this->project->getLanguages()[$lang])) {
            try {
                $body = $this->transformUrl($publishId, $uri, $lang);
            } catch (\Exception $e) {
                $body = "";
            }
        }

        if (empty($body)) {
            header("HTTP/1.1 404 Not Found");

            return [
                'success' => !empty($body),
                'validRequest' => $validRequest,
                'lang' => $lang,
                'uri' => $uri,
                'project' => $this->projectName,
                'publishId' => $publishId,
            ];
        }

        $this->markAsPublished($publishId, $lang, $uri, $body);

        echo($body);
        die();
    }
    // }}}
    // {{{ markAsPublished()
    /**
     * @brief markAsPublished
     *
     * @param string $lang
     * @param string $uri
     * @param string $body
     *
     * @return void
     **/
    protected function markAsPublished(int $publishId, string $lang, string $uri, string $body):void
    {
        $hash = hash("sha256", $body);
        $conf = $this->project->getPublishingTargets()[$publishId];

        $publishPdo = clone $this->pdo;
        $publishPdo->prefix = $this->pdo->prefix . "_proj_" . $this->project->name;

        $fs = \Depage\Fs\Fs::factory($conf->output_folder, [
            'user' => $conf->output_user,
            'pass' => $conf->output_pass,
        ]);

        $publisher = new \Depage\Publisher\Publisher($publishPdo, $fs, $publishId);
        $publisher->markFileAsPublished($lang . "/" . $uri, $hash);

    }
    // }}}
    // {{{ libFile()
    /**
     * @brief libFile
     *
     * @param string $uri
     *
     * @return object
     **/
    protected function libFile(int $publishId, string $uri, string $lang)
    {
        $body = "";
        $projectPath = $this->project->getProjectPath();
        $path = $projectPath . "/lib/" . $uri;
        $useImgUrl = preg_match("/(.*\.(jpg|jpeg|gif|png|webp|eps|tif|tiff|pdf|svg))\.([^\\\]*)\.(jpg|jpeg|gif|png|webp)/i", $uri);

        if ($useImgUrl) {
            $path = $projectPath . "lib/cache/graphics/lib/" . $uri;
            $options = $this->options->graphics;
            $baseUrl = $this->project->getBaseUrl($publishId);

            $imgurl =  new \Depage\Graphics\Imgurl([
                'extension' => $options->extension,
                'executable' => $options->executable,
                'optimize' => $options->optimize,
                'baseUrl' => $baseUrl,
                'cachePath' => $projectPath . "lib/cache/graphics/",
                'relPath' => $projectPath,
            ]);
            $imgurl->render($baseUrl . "lib/" . $uri);
        }
        if (file_exists($path)) {
            $body = file_get_contents($path);
        }

        return $body;
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
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :

