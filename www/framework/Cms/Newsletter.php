<?php
/**
 * @file    framework/cms/cms_project.php
 *
 * depage cms project module
 *
 *
 * copyright (c) 2016 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms;

class Newsletter
{
    /**
     * @brief document
     **/
    public $document = null;
    // dp_proj_{$projectName}_newsletter_subscribers

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $
     * @return void
     **/
    protected function __construct($pdo, $project, $name)
    {
        $this->project = $project;
        $this->name = $name;
        $this->pdo = $pdo;

        $this->tableSubscribers = $this->pdo->prefix . "_proj_" . $this->project->name . "_newsletter_subscribers";
        $this->tableSent = $this->pdo->prefix . "_proj_" . $this->project->name . "_newsletter_sent";
    }
    // }}}
    // {{{ loadAll()
    /**
     * @brief loadAll
     *
     * @return void
     **/
    static public function loadAll($pdo, $project)
    {
        $newsletters = [];
        $xmldb = $project->getXmlDb();

        $docs = $xmldb->getDocuments("", "Depage\\Cms\\XmlDocTypes\\Newsletter");
        $self = get_called_class();

        foreach ($docs as $doc) {
            $newsletter = new $self($pdo, $project, $doc->getDocInfo()->name);
            $newsletter->setDocument($doc);
            $newsletters[] = $newsletter;
        }

        return $newsletters;
    }
    // }}}
    // {{{ loadByName()
    /**
     * @brief loadByName
     *
     * @param mixed $
     * @return void
     **/
    public static function loadByName($pdo, $project, $name)
    {
        $xmldb = $project->getXmlDb();

        $docs = $xmldb->getDocuments($name, "Depage\\Cms\\XmlDocTypes\\Newsletter");
        $self = get_called_class();

        foreach ($docs as $doc) {
            $newsletter = new $self($pdo, $project, $name);
            $newsletter->setDocument($doc);

            return $newsletter;
        }

        throw new \Exception("Newsletter not found");
    }
    // }}}
    // {{{ create()
    /**
     * @brief create
     *
     * @param mixed $
     * @return void
     **/
    public static function create($pdo, $project)
    {
        $xmldb = $project->getXmlDb();

        $doc = $xmldb->createDoc('Depage\Cms\XmlDocTypes\Newsletter');

        $self = get_called_class();
        $newsletter = new $self($pdo, $project);
        $newsletter->setDocument($doc);

        $xml = new \DOMDocument();
        $xml->load(__DIR__ . "/XmlDocTypes/NewsletterXml/newsletter.xml");

        $doc->save($xml);

        return $newsletter;
    }
    // }}}

    // {{{ setDocument()
    /**
     * @brief setDocument
     *
     * @param mixed $
     * @return void
     **/
    protected function setDocument($doc)
    {
        $this->document = $doc;
        $this->name = $doc->getDocInfo()->name;
    }
    // }}}

    // {{{ getNewsletterCanditates()
    /**
     * @brief getCandidates
     *
     * @param mixed
     * @return void
     **/
    public function getCandidates($xpath = "//sec:news")
    {
        // @todo update candidates depending on:
        // - if they are active in current newsletter
        // - if they are active in an older newsletter
        $candidates = [];
        $xmldb = $this->project->getXmlDb();
        $pages = $this->project->getPages();

        foreach ($pages as $page) {
            if ($page->released == true) {
                $ids = $xmldb->getNodeIdsByXpath($xpath, $page->id);
                if (count($ids) > 0) {
                    $candidates[$page->name] = $page;
                }
            }
        }

        return $candidates;
    }
    // }}}
    // {{{ setNewsletterPages()
    /**
     * @brief setNewsletterPages
     *
     * @param mixed $pages = []
     * @return void
     **/
    public function setNewsletterPages($pages = [], $xml = null)
    {
        if (is_null($xml)) {
            $xml = $this->getXml();
        } else if ($xml->nodeType == \XML_ELEMENT_NODE) {
            $xml = $xml->ownerDocument;
        }
        $xpath = new \DOMXPath($xml);
        $xpResult = $xpath->query("//sec:news");

        foreach ($xpResult as $node) {
            $node->parentNode->removeChild($node);
        }

        foreach ($pages as $page) {
            $newNode = $xml->createElement("sec:news");
            $newNode->setAttribute("db:docref", $page->name);

            $xml->documentElement->appendChild($newNode);
        }

        $this->document->save($xml);
    }
    // }}}

    // {{{ getPreviewUrl()
    /**
     * @brief getPreviewUrl
     *
     * @return void
     **/
    public function getPreviewUrl($lang = "de")
    {
        return DEPAGE_BASE . "project/{$this->project->name}/preview/newsletter/pre/$lang/{$this->name}.html";
    }
    // }}}
    // {{{ getXml()
    /**
     * @brief getXml
     *
     * @return void
     **/
    public function getXml()
    {
        return $this->document->getXml();
    }
    // }}}
    // {{{ getSubject()
    /**
     * @brief getSubject
     *
     * @param mixed $lang
     * @return void
     **/
    public function getSubject($lang)
    {
        if (!empty($lang)) {
            $nodes = $this->document->getNodeIdsByXpath("//pg:meta/pg:linkdesc[@lang = '$lang']");
        } else {
            $nodes = $this->document->getNodeIdsByXpath("//pg:meta/pg:linkdesc");
        }

        if (count($nodes) > 0) {
            return $this->document->getAttribute($nodes[0], "value");
        } else {
            return "";
        }
    }
    // }}}
    // {{{ getTitle()
    /**
     * @brief getTitle
     *
     * @param mixed $lang
     * @return void
     **/
    public function getTitle($lang)
    {
        if (!empty($lang)) {
            $nodes = $this->document->getNodeIdsByXpath("//pg:meta/pg:title[@lang = '$lang']");
        } else {
            $nodes = $this->document->getNodeIdsByXpath("//pg:meta/pg:title");
        }

        if (count($nodes) > 0) {
            return $this->document->getAttribute($nodes[0], "value");
        } else {
            return "";
        }
    }
    // }}}
    // {{{ getSubscriberCategories()
    /**
     * @brief getSubscriberCategories
     *
     * @param mixed
     * @return void
     **/
    public function getSubscriberCategories()
    {
        $query = $this->pdo->prepare(
            "SELECT category
            FROM
                {$this->tableSubscribers} AS subscribers
            GROUP BY
                category
            ORDER BY
                category ASC
            "
        );

        $query->execute();
        $categories = $query->fetchAll(\PDO::FETCH_COLUMN);

        return $categories;
    }
    // }}}
    // {{{ getSubscribers()
    /**
     * @brief getSubscribers
     *
     * @param mixed $category
     * @return void
     **/
    public function getSubscribers($category)
    {
        $query = $this->pdo->prepare(
            "SELECT lang, email
            FROM
                {$this->tableSubscribers} AS subscribers
            WHERE
                category = :category
            "
        );

        $query->execute([
            'category' => $category,
        ]);
        $subscribers = $query->fetchAll(\PDO::FETCH_COLUMN | \PDO::FETCH_GROUP);

        return $subscribers;
    }
    // }}}

    // {{{ transform()
    /**
     * @brief transform
     *
     * @param mixed $
     * @return void
     **/
    public function transform($previewType, $lang)
    {
        $transformer = \Depage\Transformer\Transformer::factory($previewType, $this->project->getXmlGetter(), $this->project->name, "newsletter");

        // @todo set baseUrl from publishing target
        $transformer->baseUrl = DEPAGE_BASE . "project/{$this->project->name}/preview/html/live/";
        $transformer->useAbsolutePaths = true;

        $html = $transformer->transformDoc("", $this->document->getDocId(), $lang);

        return $html;
    }
    // }}}
    // {{{ sendToSubscribers()
    /**
     * @brief sendToSubscribers
     *
     * @param mixed $param
     * @return void
     **/
    public function sendToSubscribers($from, $category)
    {
        $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $this->name, $this->project->name);

        $subscribers = $this->getSubscribers($category);

        foreach ($subscribers as $lang => $to) {
            $html = $this->transform("live", $lang);

            $mail = new \Depage\Mail\Mail("info@depage.net");
            $mail->setSubject($this->getSubject($lang))
                ->setHtmlText($html);

            $mail->sendLater($task, $to, true);
        }
    }
    // }}}
    // {{{ sendLater()
    /**
     * @brief sendLater
     *
     * @param mixed $previewType, $
     * @return void
     **/
    public function sendLater($from, $to, $lang)
    {
        $html = $this->transform("live", $lang);

        $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $this->name, $this->project->name);

        $mail = new \Depage\Mail\Mail("info@depage.net");
        $mail->setSubject($this->getSubject($lang))
            ->setHtmlText($html);

        $mail->sendLater($task, $to, true);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
