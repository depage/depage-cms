<?php
/**
 * @file    framework/cms/ui_base.php
 *
 * base class for cms-ui modules
 *
 *
 * copyright (c) 2011-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace DepageLegacy;

class Import
{
    protected $pdo;
    protected $cache;
    protected $xmldb;

    protected $projectName;
    protected $pageIds = array();

    protected $xmlImport;
    protected $xmlSettings;
    protected $xmlNavigation;

    // {{{ constructor
    public function __construct($name, $pdo, $cache)
    {
        $this->projectName = $name;

        $this->pdo = $pdo;
        $this->cache = $cache;
        $this->xmldb = new \depage\xmldb\xmldb("{$this->pdo->prefix}_proj_{$this->projectName}", $this->pdo, $this->cache);

    }
    // }}}
    // {{{ importProject()
    public function importProject($xmlFile)
    {
        $this->loadBackup($xmlFile);

        $this->extractNavigation();
        $this->extractPagedata();
        $this->extractSettings();

        return $this->xmlNavigation;
        //return $this->xmlImport;
    }
    // }}}
    
    // {{{ loadBackup()
    public function loadBackup($xmlFile)
    {
        $this->xmlImport = new \depage\xml\Document();
        $this->xmlImport->load($xmlFile);
    }
    // }}}
    // {{{ extractNavigation()
    public function extractNavigation()
    {
        $xpath = new \DOMXPath($this->xmlImport);
        $nodelist = $xpath->query("//proj:pages_struct");

        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $this->xmlNavigation = new \depage\xml\Document();
            $node = $this->xmlNavigation->importNode($nodelist->item($i), true);
            $this->xmlNavigation->appendChild($node);
        }

        $doc = $this->xmldb->getDoc("pages");
        if (!$doc) {
            $doc = $this->xmldb->createDoc("pages", "depage\cms\xmldoctypes\pages");
        }

        $doc->save($this->xmlNavigation);
    }
    // }}}
    // {{{ extractPagedata()
    public function extractPagedata()
    {
        $xpath = new \DOMXPath($this->xmlNavigation);
        $nodelist = $xpath->query("//*[@db:ref]");

        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            var_dump($nodelist->item($i));
        }
        die();
    }
    // }}}
    // {{{ extractSettings()
    public function extractSettings()
    {
        $xpath = new \DOMXPath($this->xmlImport);
        $nodelist = $xpath->query("//proj:settings");

        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $this->xmlSettings = new \depage\xml\Document();
            $node = $this->xmlSettings->importNode($nodelist->item($i), true);
            $this->xmlSettings->appendChild($node);
        }

        $doc = $this->xmldb->getDoc("settings");
        if (!$doc) {
            // @todo update doctype
            $doc = $this->xmldb->createDoc("settings", "depage\xmldb\xmldoctypes\base");
        }

        $doc->save($this->xmlSettings);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
