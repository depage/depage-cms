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

    // {{{ __construct()
    protected function __construct($options = null)
    {
        parent::__construct($options);

        $this->conf = new \Depage\Config\Config($options);
        $this->options = $this->conf->getDefaultsFromClass($this);
    }
    // }}}

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
        $apiKey = $this->project->getProjectConfig()->apiKey;
        $publishId = filter_input(\INPUT_GET, 'publishId', \FILTER_SANITIZE_NUMBER_INT);
        $uri = implode("/", $args);
        $body = "";

        $validRequest = $_SERVER['HTTP_X_AUTHORIZATION'] == $apiKey;

        if (!$validRequest) {
            header("HTTP/1.1 401 Unauthorized");

            return [
                'success' => false,
                'validRequest' => $validRequest,
            ];
        } elseif ($lang == "lib") {
            $body = $this->libFile($publishId, $uri, $lang);
        } elseif ($lang == "sitemap.xml") {
            $body = $this->project->generateSitemap($publishId);
        } elseif ($uri == "sitemap.xml") {
            $body = $this->project->generateSitemap($publishId, $lang);
        } elseif ($uri == "atom.xml") {
            $body = $this->project->generateAtomFeed($publishId, $lang);
        } elseif ($lang == "robots.txt") {
            $body = $this->project->generateRobotsTxt($publishId);
        } elseif ($lang == "humans.txt") {
            $body = $this->project->generateHumansTxt($publishId);
        } elseif ($lang == "security.txt") {
            $body = $this->project->generateSecurityTxt($publishId);
        } elseif (isset($this->project->getLanguages()[$lang])) {
            try {
                $body = $this->transformUrl($publishId, $uri, $lang);
            } catch (\Exception $e) {
                error_log($e->getMessage());
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

        $this->markAsPublished($publishId, $lang . "/" . $uri, hash("sha256", $body));

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
    protected function markAsPublished(int $publishId, string $path, string $hash): void
    {
        $conf = $this->project->getPublishingTargets()[$publishId];

        $publishPdo = clone $this->pdo;
        $publishPdo->prefix = $this->pdo->prefix . "_proj_" . $this->project->name;

        $fs = \Depage\Fs\Fs::factory($conf->output_folder, [
            'user' => $conf->output_user,
            'pass' => $conf->output_pass,
        ]);

        $publisher = new \Depage\Publisher\Publisher($publishPdo, $fs, $publishId);
        $publisher->markFileAsPublished($path, $hash);

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
        $targetPath = "lib/" . $uri;
        $path = $projectPath . "/" . $targetPath;
        $useImgUrl = preg_match("/(.*\.(jpg|jpeg|gif|png|webp|eps|tif|tiff|pdf|svg))\.([^\\\]*)\.(jpg|jpeg|gif|png|webp)/i", $uri);

        if ($useImgUrl) {
            $targetPath = "lib/cache/graphics/lib/" . $uri;
            $path = $projectPath . "/" . $targetPath;
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
            $this->markAsPublished($publishId, $targetPath, hash_file("sha256", $path));

            readfile($path);
            die();
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
