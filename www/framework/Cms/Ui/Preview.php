<?php
/**
 * @file    framework/cms/Ui/Preview.php
 *
 * preview ui handler
 *
 *
 * copyright (c) 2013-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Ui;

use \Depage\Html\Html;

class Preview extends \Depage\Depage\Ui\Base
{
    protected $htmlOptions = [];
    protected $basetitle = "";
    protected $previewType = "dev";
    protected $projectName = "";
    protected $template = "";
    protected $lang = "";
    public $routeThroughIndex = true;
    public $defaults = [
        'cache' => [
            'xmldb' => [
                'disposition' => "file",
                'host' => "",
            ],
        ],
    ];

    // {{{ _init
    public function _init(array $importVariables = []) {
        parent::_init($importVariables);

        if (empty($this->pdo)) {
            // get database instance
            $this->pdo = new \Depage\Db\Pdo (
                $this->options->db->dsn, // dsn
                $this->options->db->user, // user
                $this->options->db->password, // password
                [
                    'prefix' => $this->options->db->prefix, // database prefix
                ]
            );
        }

        // get cache object for xmldb
        $this->xmldbCache = \Depage\Cache\Cache::factory("xmldb", [
            'disposition' => $this->options->cache->xmldb->disposition,
            'host' => $this->options->cache->xmldb->host,
        ]);

        $this->projectName = $this->urlSubArgs[0];
        $this->project = \Depage\Cms\Project::loadByName($this->pdo, $this->xmldbCache, $this->projectName);
        $this->xmldb = $this->project->getXmlDb();

        // get auth object
        $this->auth = \depage\Auth\Auth::factory(
            $this->pdo, // db_pdo
            $this->options->auth->realm, // auth realm
            DEPAGE_BASE, // domain
            $this->options->auth->method // method
        );

        // set html-options
        $this->htmlOptions = [
            'template_path' => __DIR__ . "/../tpl/",
            'clean' => "space",
            'env' => $this->options->env,
        ];
        $this->basetitle = \Depage\Depage\Runner::getName() . " " . \Depage\Depage\Runner::getVersion();
    }
    // }}}
    // {{{ _package
    /**
     * gets a list of projects
     *
     * @return  null
     */
    public function _package($output) {
        return $output;
    }
    // }}}

    // {{{ index
    /**
     * function to route all previews through
     *
     * @return  null
     */
    public function index()
    {
        $args = func_get_args();

        // get parameters
        $this->template = array_shift($args);
        $this->timestamp = time();
        $this->previewType = array_shift($args);
        if (preg_match("/^(history)-(\d{4}-\d{2}-\d{2})-(\d{2}:\d{2}:\d{2})$/", $this->previewType, $m)) {
            $this->timestamp = strtotime("{$m[2]} {$m[3]}");
        }
        if (empty($this->previewType)) {
            $this->previewType = "pre";
        }

        $lang = array_shift($args);

        $urlPath = "/" . implode("/", $args);

        $project = \Depage\Cms\Project::loadByName($this->pdo, $this->xmldbCache, $this->projectName);
        $project->setPreviewType($this->previewType);

        if ($lang == "api" && $project->isApiAvailable()) {
            $redirector = new \Depage\Redirector\Redirector($project->getBaseUrl() . '/');
            require($project->getProjectPath() . 'lib/global/api.php');
            die();
        } else if ($lang == "sitemap.xml") {
            $sitemap = new \Depage\Http\Response();
            $sitemap
                ->setBody($project->generateSitemap())
                ->addHeader("Content-Type: text/xml; charset=UTF-8");

            return $sitemap;
        } else if ($urlPath == "/atom.xml") {
            $feed = new \Depage\Http\Response();
            $feed
                ->setBody($project->generateAtomFeed(null, $lang))
                ->addHeader("Content-Type: text/xml; charset=UTF-8");

            return $feed;
        } else if ($urlPath == "/") {
            // redirect to home
            \Depage\Depage\Runner::redirect($project->getHomeUrl());
        }

        return $this->preview($urlPath, $lang);
    }
    // }}}
    // {{{ error
    /**
     * function to show error messages
     *
     * @return  null
     */
    public function error($error, $env) {
        $content = parent::error($error, $env);

        $h = new Html("html.tpl", [
            'title' => $this->basetitle,
            'subtitle' => $output->title,
            'content' => new Html("box.tpl", [
                'id' => "error",
                'class' => "box-error",
                'content' => new Html([
                    'content' => nl2br($content),
                ]),
            ]),
        ], $this->htmlOptions);

        return $this->_package($h);
    }
    // }}}

    // {{{ preview
    /**
     * @return  null
     */
    protected function preview($urlPath, $lang)
    {
        $transformCache = null;
        $this->project->setPreviewType($this->previewType);

        if ($this->template != "newsletter") {
            $transformCache = new \Depage\Transformer\TransformCache($this->pdo, $this->projectName, $this->template . "-" . $this->previewType);
        }
        $xmlGetter = $this->project->getXmlGetter();
        $xmlGetter->timestamp = $this->timestamp;

        $transformer = \Depage\Transformer\Transformer::factory($this->previewType, $xmlGetter, $this->project, $this->template, $transformCache);
        $transformer->routeHtmlThroughPhp = true;

        $projectConfig = $this->project->getProjectConfig();
        if (isset($projectConfig->aliases)) {
            $transformer->registerAliases($projectConfig->aliases);
        }

        if ($this->template == "newsletter") {
            preg_match("/\/(_Newsletter_([a-z0-9]*))\.html/", $urlPath, $matches);
            $newsletterName = $matches[1];
            $newsletter = \Depage\Cms\Newsletter::loadByName($this->pdo, $this->project, $newsletterName);

            $html = $newsletter->transform($this->previewType, $lang);
        } else {
            $html = $transformer->display($urlPath, $lang);
        }

        return $html;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
