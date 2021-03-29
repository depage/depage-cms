<?php
/**
 * @file    FileLibrary.php
 *
 * description
 *
 * copyright (c) 2021 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms;

/**
 * @brief FileLibrary
 * Class FileLibrary
 */
class FileLibrary
{
    /**
     * @brief pdo
     **/
    protected $pdo = null;

    /**
     * @brief root
     **/
    protected $project = null;

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed
     * @return void
     **/
    public function __construct($pdo, $project)
    {
        $this->pdo = $pdo;
        $this->project = $project;

        $this->fs = \Depage\Fs\Fs::factory($this->project->getProjectPath() . "lib/");
    }
    // }}}

    // {{{ syncLibraryTree()
    /**
     * @brief syncLibraryTree
     *
     * @param mixed
     * @return void
     **/
    public function syncLibraryTree($selectedPath)
    {
        $xmldb = $this->project->getXmlDb();
        $doc = $xmldb->getDoc("files");
        if (!$doc) {
            $doc = $xmldb->createDoc('Depage\Cms\XmlDocTypes\Library', "files");

            $xml = new \Depage\Xml\Document();
            $xml->load(__DIR__ . "/../XmlDocTypes/LibraryXml/library.xml");

            $nodeId = $doc->save($xml);
        }
        $xml = $doc->getXml();

        $this->syncFolder($doc, $xml->documentElement, "");

        if (!empty($selectedPath)) {
            $selectedPath = trim($selectedPath, '/');
            $dirs = explode('/', $selectedPath);
            $xpath = new \DOMXPath($xml);
            $xpath->registerNamespace("proj", "http://cms.depagecms.net/ns/project");

            $query = "/proj:library";
            foreach ($dirs as $dir) {
                $query .= "/proj:folder[@name = '" . htmlentities($dir) . "']";
            }
            $query .= "/@db:id";

            $result = $xpath->evaluate($query);
            if ($result->length == 1) {
                return $result->item(0)->nodeValue;
            } else {
                return false;
            }
        }
    }
    // }}}
    // {{{ syncFolder()
    /**
     * @brief syncFolder
     *
     * @param mixed $path, $folderNode
     * @return void
     **/
    protected function syncFolder($doc, $folderNode, $path = "")
    {
        $pattern = trim($path . "/*", '/');
        $dirs = $this->fs->lsDir($pattern);
        array_walk($dirs, function(&$dir) {
            $dir = pathinfo($dir, \PATHINFO_FILENAME);

        });
        $dirsById = [];
        $nodesById = [];

        // check if folder exists
        foreach($folderNode->childNodes as $node) {
            $name = $node->getAttribute("name");
            $id = $doc->getNodeId($node);
            $index = array_search($name, $dirs);

            if ($index === false) {
                // folder does not exist anymore
                $doc->deleteNode($doc->getNodeId($node));
            } else {
                // folder exists
                array_splice($dirs, $index, 1);
                $dirsById[$id] = $name;
                $nodesById[$id] = $node;
            }
        }

        // add unindexed folders
        foreach($dirs as $dir) {
            $parentId = $doc->getNodeId($folderNode);
            $node = $folderNode->ownerDocument->createElementNS ("http://cms.depagecms.net/ns/project", "proj:folder");
            $id = $doc->addNode($node, $parentId, -1, $dir);
            $node->setAttribute("name", $dir);
            $node->setAttributeNS("http://cms.depagecms.net/ns/database", "db:id", $id);

            $dirsById[$id] = $dir;
            $nodesById[$id] = $node;

            $folderNode->appendChild($node);
        }

        // index next folder level
        foreach($dirsById as $id => $dir) {
            $this->syncFolder($doc, $nodesById[$id], $path . '/' . $dir);
        }
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
