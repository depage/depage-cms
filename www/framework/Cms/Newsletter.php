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
    protected $document = null;
    // dp_proj_{$projectName}_subscribers
    // dp_proj_{$projectName}_subscriber_groups

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $
     * @return void
     **/
    protected function __construct($project, $name)
    {
        $this->project = $project;
        $this->name = $name;
    }
    // }}}
    // {{{ loadAll()
    /**
     * @brief loadAll
     *
     * @return void
     **/
    static public function loadAll($project)
    {
        $newsletters = [];
        $xmldb = $project->getXmlDb();

        $docs = $xmldb->getDocuments("", "Depage\\Cms\\XmlDocTypes\\Newsletter");
        $self = get_called_class();

        foreach ($docs as $doc) {
            $newsletter = new $self($project, $doc->getDocInfo()->name);
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
    public static function loadByName($project, $name)
    {
        $xmldb = $project->getXmlDb();

        $docs = $xmldb->getDocuments($name, "Depage\\Cms\\XmlDocTypes\\Newsletter");
        $self = get_called_class();

        foreach ($docs as $doc) {
            $newsletter = new $self($project, $name);
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
    public static function create($project)
    {
        $xmldb = $project->getXmlDb();

        $doc = $xmldb->createDoc('Depage\Cms\XmlDocTypes\Newsletter');

        $self = get_called_class();
        $newsletter = new $self($project);
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

    // {{{ getPreviewPath()
    /**
     * @brief getPreviewPath
     *
     * @return void
     **/
    public function getPreviewPath()
    {
        $lang = "de";
        //return DEPAGE_BASE . "project/{$this->project->name}/preview/newsletter/pre/$lang/{$this->name}.html";
        return DEPAGE_BASE . "project/{$this->project->name}/preview/newsletter/dev/$lang/{$this->name}.html";
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
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
