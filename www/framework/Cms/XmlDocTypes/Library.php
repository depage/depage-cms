<?php

namespace Depage\Cms\XmlDocTypes;

// TODO configure

class Library extends Base {
    // @todo check uniqure names with folder updates!!!
    use Traits\UniqueNames;

    const XML_TEMPLATE_DIR = __DIR__ . '/LibraryXml/';

    // {{{ constructor
    public function __construct($xmlDb, $document) {
        parent::__construct($xmlDb, $document);

        $doctypePage = new \Depage\Cms\XmlDocTypes\Page($this->xmlDb, null);

        // list of elements that may created by a user
        $this->availableNodes = [
            'proj:folder' => (object) [
                'name' => _("Folder"),
                'newName' => _("Untitled"),
                'icon' => "",
            ],
        ];

        foreach ($this->availableNodes as $nodeName => &$node) {
            $node->id = $nodeName;
            $node->nodeName = $nodeName;
        }

        // list of valid parents given by nodename
        $this->validParents = [
            'proj:folder' => [
                'proj:folder',
                'proj:library',
            ],
        ];
    }
    // }}}

    // {{{ onAddNode
    /**
     * On Add Node
     *
     * @param \DomNode $node
     * @param $target_id
     * @param $target_pos
     * @param $extras
     * @return null
     */
    public function onAddNode(\DomNode $node, $targetId, $targetPos, $extras) {
        if (is_string($extras)) {
            $node->setAttribute("name", $extras);
        }
        $path = $this->getPathById($targetId, $node->getAttribute("name"));
        $this->fs()->mkdir($path);

        return true;
    }
    // }}}
    // {{{ onMoveNode()
    /**
     * @brief onMoveNode
     *
     * @param mixed $param
     * @return void
     **/
    public function onMoveNode($nodeId, $oldParentId)
    {
        $name = $this->document->getAttribute($nodeId, "name");
        $srcPath = $this->getPathById($oldParentId, $name);
        $targetPath = $this->getPathById($nodeId);

        if ($srcPath != $targetPath) {
            $success = $this->fs()->mv($srcPath, $targetPath);
            $this->clearGraphicsCache($srcPath);
        }

        return true;
    }
    // }}}
    // {{{ onCopyNode
    /**
     * On Copy Node
     *
     * @param \DomElement $node
     * @param $target_id
     * @param $target_pos
     * @return null
     */
    public function onCopyNode($node_id, $copy_id)
    {
        // @todo disable copying folders?
        return true;
    }
    // }}}
    // {{{ onDeleteNode()
    /**
     * On Delete Node
     *
     * Deletes an xmlDb document by the given id.
     *
     * @param $doc_id
     * @return boolean
     */
    public function onDeleteNode($nodeId, $parentId)
    {
        $path = $this->getPathById($nodeId);
        if (empty($path)) {
            return true;
        }

        $fl = new \Depage\Cms\FileLibrary($this->project->getPdo(), $this->project);
        $xpath = new \DomXpath($this->document->getSubdocByNodeId($nodeId));
        $list = $xpath->query("//proj:folder");
        foreach ($list as $item) {
            $fl->deleteDataForFolder((int) $item->attributes->getNamedItem('id')->nodeValue);
        }

        try {
            $this->moveToTrash($path);
        } catch (\Exception $e) {
        }

        return true;
    }
    // }}}
    // {{{ onSetAttribute
    /**
     * On Delete Node
     *
     * @param $node_id
     * @param $parent_id
     * @return bool
     */
    public function onSetAttribute($nodeId, $attrName, $oldVal, $newVal) {
        parent::onSetAttribute($nodeId, $attrName, $oldVal, $newVal);

        if ($attrName == "name") {
            $parentId = $this->document->getParentIdById($nodeId);
            $srcPath = $this->getPathById($parentId, $oldVal);
            $targetPath = $this->getPathById($parentId, $newVal);

            $this->fs()->mv($srcPath, $targetPath);
            $this->clearGraphicsCache($srcPath);
        }

        return true;
    }
    // }}}

    // {{{ testDocument
    public function testDocument($node) {
        $changed = $this->testUniqueNames($node, "//proj:*");

        $xmlnav = new \Depage\Cms\XmlNav();

        // add parent url if $node is not root node
        list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);
        $xmlnav->addUrlAttributes($node, $this->getParentUrl($node));

        return $changed;
    }
    // }}}
    // {{{ testDocumentForHistory
    public function testDocumentForHistory($xml) {
        parent::testDocumentForHistory($xml);

        $xmlnav = new \Depage\Cms\XmlNav();
        $xmlnav->addUrlAttributes($xml);
    }
    // }}}

    // {{{ fs()
    /**
     * @brief fs
     *
     * @param mixed
     * @return void
     **/
    protected function fs()
    {
        return \Depage\Fs\Fs::factory($this->project->getProjectPath() . "lib/");
    }
    // }}}
    // {{{ moveToTrash()
    /**
     * @brief moveToTrash
     *
     * @param mixed $path
     * @return void
     **/
    public function moveToTrash($path)
    {
        // @todo check path for validity ".." and if file exists
        $trashPath = $this->project->getProjectPath() . "trash/";
        $srcPath = $this->project->getProjectPath() . "lib/" . $path;
        $targetPath = $trashPath . $path;
        $parentPath = dirname($targetPath);

        if (!file_exists($srcPath)) {
            return;
        }
        if (!is_dir($trashPath)) {
            mkdir($trashPath, 0777, true);
        }
        if (file_exists($targetPath) || is_dir($targetPath)) {
            $targetPath = $this->renameExistingTrashTarget($targetPath);
        }
        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0777, true);
        }
        rename($srcPath, $targetPath);

        $this->clearGraphicsCache($path);
    }
    // }}}
    // {{{ renameExistingTrashTarget()
    /**
     * @brief renameExistingTrashTarget
     *
     * @param mixed $
     * @return void
     **/
    protected function renameExistingTrashTarget($targetPath)
    {
        $pathinfo = pathinfo($targetPath);
        $date = date("_Y-m-d_h-i-s");

        $targetPath = $pathinfo['dirname'] . "/" . $pathinfo['filename'] . $date;
        if (isset($pathinfo['extension'])) {
            $targetPath .= "." . $pathinfo['extension'];
        }

        return $targetPath;
    }
    // }}}
    // {{{ clearGraphicsCache()
    /**
     * @brief clearGraphicsCache
     *
     * @param mixed $path
     * @return void
     **/
    protected function clearGraphicsCache($path)
    {
        $cachePath = $this->project->getProjectPath() . "lib/cache/";
        if (is_dir($cachePath)) {
            // remove thumbnails from cache inside of project if available
            $cache = \Depage\Cache\Cache::factory("graphics", [
                'cachepath' => $cachePath,
            ]);
            $cache->delete("lib/" . $path);
        }

        // remove thumbnails from global graphics cache
        $cache = \Depage\Cache\Cache::factory("graphics");
        $cache->delete("projects/" . $this->project->name . "/lib/" . $path);
    }
    // }}}
    // {{{ getPathById()
    /**
     * @brief getPathById
     *
     * @param mixed $
     * @return void
     **/
    protected function getPathById($nodeId, $added = "")
    {
        $url = $this->document->getAttribute($nodeId, 'name');
        if (!empty($added)) {
            $url .= "/$added";
        }

        while (($nodeId = $this->document->getParentIdById($nodeId)) != null) {
            $url = $this->document->getAttribute($nodeId, 'name') . "/" . $url;
        }

        return trim($url, '/');
    }
    // }}}
    // {{{ getParentUrl()
    /**
     * @brief getParentUrl
     *
     * @param mixed $
     * @return void
     **/
    protected function getParentUrl($node)
    {
        $url = "";

        $nodeId = (int) $node->getAttributeNS("http://cms.depagecms.net/ns/database", "id");
        $parentId = $this->document->getParentIdById($nodeId);
        $url = $this->getPathById($parentId);

        if (!empty($url)) {
            $url = "/$url/";
        }
        if ($node->nodeName == "proj:folder" && empty($url)) {
            $url = "/";
        }

        return $url;
    }
    // }}}

    // {{{ loadXmlTemplate()
    /**
     * Load XML Template
     *
     * @param $template
     * @return \DOMDocument
     */
    private function loadXmlTemplate($template) {
        $doc = new \DOMDocument();
        $doc->load(self::XML_TEMPLATE_DIR . $template);
        return $doc;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
