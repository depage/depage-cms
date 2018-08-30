<?php
/**
 * @file    framework/Cms/Ui/DocProperties.php
 *
 * depage cms edit module
 *
 *
 * copyright (c) 2011-2018 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\UI;

use \Depage\Html\Html;

class DocProperties extends Base
{
    // {{{ variables
    /**
     * @brief projectName
     **/
    protected $projectName = "";

    /**
     * @brief docRef
     **/
    protected $docRef = null;

    /**
     * @brief nodeId
     **/
    protected $nodeId = null;

    /**
     * @brief project
     **/
    protected $project = null;

    /**
     * @brief xmldb
     **/
    protected $xmldb = null;

    /**
     * @brief languages
     **/
    protected $languages = [];

    /**
     * @brief form
     **/
    protected $form = null;

    /**
     * @brief fs
     **/
    protected $fs = null;
    // }}}

    // {{{ _init()
    public function _init(array $importVariables = []) {
        parent::_init($importVariables);

        if (!empty($this->urlSubArgs[0])) {
            $this->projectName = $this->urlSubArgs[0];
        }
        if (!empty($this->urlSubArgs[1])) {
            $this->docRef = $this->urlSubArgs[1];
        }
        if (!empty($this->urlSubArgs[2])) {
            $this->nodeId = $this->urlSubArgs[2];
        }

        $this->project = \Depage\Cms\Project::loadByUser($this->pdo, $this->xmldbCache, $this->authUser, $this->projectName)[0];
        $this->xmldb = $this->project->getXmlDb($this->authUser->id);

        $this->languages = array_keys($this->project->getLanguages());
    }
    // }}}
    // {{{ package
    /**
     * gets a list of projects
     *
     * @return  null
     */
    public function package($output) {
        // pack into base-html if output is html-object
        if (!isset($_REQUEST['ajax']) && is_object($output) && is_a($output, "Depage\Html\Html")) {
            // pack into body html
            $output = new Html("html.tpl", [
                'title' => $this->basetitle,
                'subtitle' => $output->title,
                'content' => $output,
            ], $this->htmlOptions);
        }

        return $output;
    }
    // }}}

    // {{{ index
    /**
     * default function to call if no function is given in handler
     *
     * @return  null
     */
    public function index() {
        $this->auth->enforce();

        $h = "";
        $doc = $this->xmldb->getDocByNodeId($this->nodeId);
        $xml = $doc->getSubdocByNodeId($this->nodeId);

        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace("db", "http://cms.depagecms.net/ns/database");

        $node = $xml->documentElement;
        $hashOld = $doc->hashDomNode($node);

        $this->form = new \Depage\Cms\Forms\XmlForm("xmldata_{$this->nodeId}", [
            'jsAutosave' => true,
            'dataNode' => $node,
            'class' => "labels-on-top",
        ]);

        if ($node->getAttribute("icon")) {
            //$this->form->addHtml("<p>Icon: " . $node->getAttribute("icon") . "</p>");
        }

        if (in_array($node->prefix, ['pg', 'sec', 'edit'])) {
            // only for page data content
            $this->addPgRelease();
        }

        if ($callback = $this->getCallbackForNode($node)) {
            $this->$callback($node);
        }
        foreach($node->childNodes as $n) {
            if ($callback = $this->getCallbackForNode($n)) {
                $this->$callback($n);
            }
        }
        $this->form->setDefaultValuesXml();

        $this->form->process();

        if ($this->form->validateAutosave()) {
            // @todo check if content has changed
            $released = $doc->isReleased();
            $node = $this->form->getValuesXml();
            $hashNew = $doc->hashDomNode($node);

            if ($hashOld !== $hashNew) {
                $doc->saveNode($node);

                $prefix = $this->pdo->prefix . "_proj_" . $this->projectName;
                $deltaUpdates = new \Depage\WebSocket\JsTree\DeltaUpdates($prefix, $this->pdo, $this->xmldb, $doc->getDocId(), $this->projectName, 0);
                $parentId = $doc->getParentIdById($this->nodeId);
                $deltaUpdates->recordChange($parentId);

                if ($released) {
                    // get pageId correctly
                    $pageInfo = $this->project->getPages($this->docRef)[0];
                    $pageDoc = $this->xmldb->getDoc("pages");
                    $deltaUpdates = new \Depage\WebSocket\JsTree\DeltaUpdates($prefix, $this->pdo, $this->xmldb, $pageDoc->getDocId(), $this->projectName, 0);
                    $parentId = $pageDoc->getParentIdById($pageInfo->pageId);
                    $deltaUpdates->recordChange($parentId);
                }
            }

            $this->form->clearSession(false);
        }

        // @todo clean unsed session?

        $h .= $this->form;
        //$h .= htmlentities($xml->saveXML($node));

        $output = new Html([
            'title' => "edit",
            'content' => $h,
        ], $this->htmlOptions);

        return $output;
    }
    // }}}

    // {{{ thumbnail()
    /**
     * @brief thumbnail
     *
     * @param mixed $file
     * @return void
     **/
    public function thumbnail($file)
    {
        if ($_GET['ajax'] == "true") {
            $file = rawurldecode($file);
        }
        return new Html("thumbnail.tpl", [
            'file' => $file,
            'project' => $this->project,
        ], $this->htmlOptions);
    }
    // }}}

    // {{{ getCallbackForNode()
    /**
     * @brief getCallbackForNode
     *
     * @param mixed $node
     * @return void
     **/
    protected function getCallbackForNode($node)
    {
        $f = str_replace(":", "_", $node->nodeName);
        $parts = explode("_", $f);

        for ($i = 0; $i < count($parts); $i++) {
            $parts[$i] = ucfirst($parts[$i]);
        }
        $callback = "add" . implode($parts);

        if ($callback == "addEditPlainSource" && $this->nodeId != $node->getAttribute("db:id")) {
            return false;
        }

        if (is_callable([$this, $callback])) {
            return $callback;
        }

        if ($node->prefix != "sec" && $node->prefix != "proj") {
            //echo $callback . "<br>";
        }

        return false;
    }
    // }}}
    // {{{ getLabelForNode()
    /**
     * @brief getLabelForNode
     *
     * @param mixed $node
     * @return void
     **/
    protected function getLabelForNode($node, $fallback = "")
    {
        $label = $node->getAttribute("name");
        if (empty($label)) {
            $label = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "name");
        }
        if (empty($label)) {
            $label = $fallback;
        }

        return $label;
    }
    // }}}
    // {{{ getLangFieldset()
    /**
     * @brief getLangFieldset
     *
     * @param mixed $node, $label
     * @return void
     **/
    protected function getLangFieldset($node, $label, $class = "")
    {
        $lang = $node->getAttribute("lang");

        if ($lang == $this->languages[0] || $lang == "") {
            $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

            $this->fs = $this->form->addFieldset("xmledit-$nodeId-lang-fs", [
                'label' => $label,
                'class' => "doc-property-fieldset $class",
            ]);
        }

        return $this->fs;
    }
    // }}}
    // {{{ getForceSize()
    /**
     * @brief getForceSize
     *
     * @param mixed $node
     * @return void
     **/
    protected function getForceSize($node)
    {
        $forceSize = $node->getAttribute("force_size");
        $forceWidth = $node->getAttribute("force_width") ?? "X";
        $forceHeight = $node->getAttribute("force_height") ?? "X";

        if (empty($forceSize)) {
            $forceSize = $forceWidth . "x" . $forceHeight;
        }
        if ($forceSize == "XxX") {
            $forceSize = "";
        }

        return $forceSize;
    }
    // }}}

    // {{{ addPgRelease()
    /**
     * @brief addPgRelease
     *
     * @param mixed $node
     * @return void
     **/
    protected function addPgRelease()
    {
        $pageInfo = $this->project->getPages($this->docRef)[0];
        $lastchangeUser = \Depage\Auth\User::loadById($this->pdo, $pageInfo->lastchangeUid);
        $dateFormatter = new \Depage\Formatters\DateNatural();
        //var_dump($pageInfo);

        $fs = $this->form->addFieldset("xmledit-{$this->docRef}-lastchange-fs", [
            'label' => _("Last Change"),
            'class' => "doc-property-fieldset doc-property-meta",
            'dataAttr' => [
                'docref' => $this->docRef,
            ],
        ]);
        $fs->addHtml(sprintf(
            _("<p>%s by %s</p>"),
            $dateFormatter->format($pageInfo->lastchange, true),
            htmlspecialchars($lastchangeUser->fullname ?? _("unknown user"))
        ));
        if ($this->authUser->canPublishProject()) {
            $releaseTitle = _("Release Page");
        } else {
            $releaseTitle = _("Request Page Release");
        }
        $class = $pageInfo->released ? "disabled" : "";
        $fs->addHtml("<p><a class=\"button release $class\">{$releaseTitle}</a></p>");

    }
    // }}}
    // {{{ addPgMeta()
    /**
     * @brief addPgMeta
     *
     * @param mixed $node
     * @return void
     **/
    protected function addPgMeta($node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");
        $pageInfo = $this->project->getPages($this->docRef)[0];

        $list = ['' => _("Default")] + $this->project->getColorschemes();
        $fs = $this->form->addFieldset("xmledit-$nodeId-colorscheme-fs", [
            'label' => _("Colorscheme"),
            'class' => "doc-property-fieldset",
        ]);
        $fs->addSingle("colorscheme-$nodeId", [
            'label' => "",
            'list' => $list,
            'skin' => "select",
            'dataInfo' => "//*[@db:id = '$nodeId']/@colorscheme",
        ]);

        $navs = $this->project->getNavigations();
        $defaults = [];
        foreach ($navs as $key => $val) {
            if ($pageInfo->nav[$key] == 'true') {
                $defaults[] = $key;
            }
        }
        $fs = $this->form->addFieldset("xmledit-$nodeId-navigation-fs", [
            'label' => _("Navigation"),
            'class' => "doc-property-fieldset",
        ]);
        $fs->addMultiple("xmledit-$nodeId-navigation", [
            'label' => "",
            'list' => $navs,
            'class' => 'page-navigations',
            'defaultValue' => $defaults,
            'dataAttr' => [
                'pageId' => $pageInfo->pageId,
            ],
        ]);

        $tags = $this->project->getTags();
        $defaults = [];
        foreach ($tags as $key => $val) {
            if ($pageInfo->tags[$key] == 'true') {
                $defaults[] = $key;
            }
        }
        if (count($tags) > 0) {
            $fs = $this->form->addFieldset("xmledit-$nodeId-tags-fs", [
                'label' => _("Tags"),
                'class' => "doc-property-fieldset",
            ]);
            $fs->addMultiple("xmledit-$nodeId-tags", [
                'label' => "",
                //'skin' => "tags",
                'list' => $tags,
                'class' => 'page-tags',
                'defaultValue' => $defaults,
                'dataAttr' => [
                    'pageId' => $pageInfo->pageId,
                ],
            ]);
        }

        $fs = $this->form->addFieldset("xmledit-$nodeId-pagetype-fs", [
            'label' => _("Pagetype"),
            'class' => "doc-property-fieldset",
        ]);
        $fs->addSingle("xmledit-$nodeId-pagetype", [
            'label' => "",
            'skin' => "select",
            'class' => 'page-type',
            'list' => [
                'html' => _("html"),
                'text' => _("text"),
                'php' => _("php"),
            ],
            'defaultValue' => $pageInfo->fileType,
            'dataAttr' => [
                'pageId' => $pageInfo->pageId,
            ],
        ]);

    }
    // }}}
    // {{{ addPgTitle()
    /**
     * @brief addPgTitle
     *
     * @param mixed $node
     * @return void
     **/
    protected function addPgTitle($node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $fs = $this->getLangFieldset($node, $this->getLabelForNode($node, _("Title")));
        $fs->addText("xmledit-$nodeId", [
            'label' => $node->getAttribute("lang"),
            'lang' => $node->getAttribute("lang"),
            'dataInfo' => "//*[@db:id = '$nodeId']/@value",
        ]);
    }
    // }}}
    // {{{ addPgLinkdesc()
    /**
     * @brief addPgLinkdesc
     *
     * @param mixed $node
     * @return void
     **/
    protected function addPgLinkdesc($node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $fs = $this->getLangFieldset($node, $this->getLabelForNode($node, _("Linkinfo")));
        $fs->addText("xmledit-$nodeId", [
            'label' => $node->getAttribute("lang"),
            'lang' => $node->getAttribute("lang"),
            'dataInfo' => "//*[@db:id = '$nodeId']/@value",
        ]);
    }
    // }}}
    // {{{ addPgDesc()
    /**
     * @brief addPgDesc
     *
     * @param mixed $
     * @return void
     **/
    protected function addPgDesc($node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $fs = $this->getLangFieldset($node, $this->getLabelForNode($node, _("Description")));
        $fs->addRichtext("xmledit-$nodeId", [
            'label' => $node->getAttribute("lang"),
            'lang' => $node->getAttribute("lang"),
            'dataInfo' => "//*[@db:id = '$nodeId']",
            'autogrow' => true,
            'allowedTags' => [
                "p",
                "br",
            ],
        ]);
    }
    // }}}

    // {{{ addEditTextSingleline()
    /**
     * @brief addEditTextSingleline
     *
     * @param mixed $node
     * @return void
     **/
    protected function addEditTextSingleline($node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $fs = $this->getLangFieldset($node, $this->getLabelForNode($node, _("Text")));
        $fs->addText("xmledit-$nodeId", [
            'label' => $node->getAttribute("lang"),
            'lang' => $node->getAttribute("lang"),
            'dataInfo' => "//*[@db:id = '$nodeId']/@value",
        ]);
    }
    // }}}
    // {{{ addEditTextHeadline()
    /**
     * @brief addEditTextHeadline
     *
     * @param mixed $
     * @return void
     **/
    protected function addEditTextHeadline($node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $fs = $this->getLangFieldset($node, $this->getLabelForNode($node, _("Headline")));

        $fs->addRichtext("xmledit-$nodeId", [
            'label' => $node->getAttribute("lang"),
            'dataInfo' => "//*[@db:id = '$nodeId']",
            'lang' => $node->getAttribute("lang"),
            'autogrow' => true,
            'allowedTags' => [
                "p",
                "br",
            ],
        ]);
    }
    // }}}
    // {{{ addEditTextFormatted()
    /**
     * @brief addEditTextFormatted
     *
     * @param mixed $
     * @return void
     **/
    protected function addEditTextFormatted($node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $fs = $this->getLangFieldset($node, $this->getLabelForNode($node, _("Text")));

        // @todo add lang attribute for spelling hint
        $fs->addRichtext("xmledit-$nodeId", [
            'label' => $node->getAttribute("lang"),
            'autogrow' => true,
            'lang' => $node->getAttribute("lang"),
            'dataInfo' => "//*[@db:id = '$nodeId']",
            'allowedTags' => [
                // inline elements
                "a",
                "b",
                "strong",
                "i",
                "em",
                "small",

                // block elements
                "p",
                "br",
                "ul",
                "ol",
                "li",
            ],
        ]);
    }
    // }}}
    // {{{ addEditRichtext()
    /**
     * @brief addEditRichtext
     *
     * @param mixed $
     * @return void
     **/
    protected function addEditRichtext($node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $fs = $this->getLangFieldset($node, $this->getLabelForNode($node, _("Text")));

        // @todo add lang attribute for spelling hint
        $fs->addRichtext("xmledit-$nodeId", [
            'label' => $node->getAttribute("lang"),
            'autogrow' => true,
            'lang' => $node->getAttribute("lang"),
            'dataInfo' => "//*[@db:id = '$nodeId']",
            'allowedTags' => [
                // inline elements
                "a",
                "b",
                "strong",
                "i",
                "em",
                "small",

                // block elements
                "p",
                "br",
                "h1",
                "h2",
                "ul",
                "ol",
                "li",
            ],
        ]);
    }
    // }}}
    // {{{ addEditType()
    /**
     * @brief addEditType
     *
     * @param mixed $node
     * @return void
     **/
    protected function addEditType($node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");
        $options = $node->getAttribute("options");
        $variables = $this->project->getVariables();

        $options = preg_replace_callback("/%var_([^%]*)%/", function($matches) use ($variables) {
            return $variables[$matches[1]];
        }, $options);

        $list = [];
        foreach (explode(",", $options) as $val) {
            $list[$val] = $val;
        }

        $class = "edit-type";
        $skin = "radio";

        if (count($list) > 6) {
            $class = "";
            $skin = "select";
        }

        $fs = $this->getLangFieldset($node, $this->getLabelForNode($node, _("Type")));
        $fs->addSingle("xmledit-$nodeId", [
            'label' => $node->getAttribute("lang"),
            'list' => $list,
            'class' => $class,
            'skin' => $skin,
            'dataInfo' => "//*[@db:id = '$nodeId']/@value",
        ]);
    }
    // }}}
    // {{{ addEditDate()
    /**
     * @brief addEditDate
     *
     * @param mixed $node
     * @return void
     **/
    protected function addEditDate($node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $fs = $this->getLangFieldset($node, $this->getLabelForNode($node, _("Date")));
        $fs->addDate("xmledit-$nodeId", [
            'label' => $node->getAttribute("lang"),
            'dataInfo' => "//*[@db:id = '$nodeId']/@value",
        ]);
    }
    // }}}
    // {{{ addEditA()
    /**
     * @brief addEditA
     *
     * @param mixed $node
     * @return void
     **/
    protected function addEditA($node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $fs = $this->getLangFieldset($node, $this->getLabelForNode($node, _("Link")), "edit-a");

        $lang = $node->getAttribute("lang");
        $fs->addText("xmledit-$nodeId-title", [
            'label' => !empty($lang) ? $lang : _("Title"),
            'placeholder' => _("Link title"),
            'dataInfo' => "//*[@db:id = '$nodeId']",
        ]);

        $fs->addText("xmledit-$nodeId-href", [
            'label' => _("href"),
            'class' => "edit-href",
            'placeholder' => _("http://domain.com"),
            'dataInfo' => "//*[@db:id = '$nodeId']/@href",
        ]);

        // @todo leave only one target setting for multiple links
        $fs->addSingle("xmledit-$nodeId-target", [
            'label' => $this->getLabelForNode($node, _("Target")),
            'list' => [
                '' => _("Default"),
                '_blank' => _("New Window"),
            ],
            'skin' => "radio",
            'class' => "edit-type edit-target",
            'dataInfo' => "//*[@db:id = '$nodeId']/@target",
        ]);
    }
    // }}}
    // {{{ addEditAudio()
    /**
     * @brief addEditAudio
     *
     * @param mixed $node
     * @return void
     **/
    protected function addEditAudio($node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $f = $this->form->addFieldset("xmledit-$nodeId", [
            'label' => $this->getLabelForNode($node, _("Audio")),
            'class' => "edit-audio",
        ]);
        $f->addText("xmledit-$nodeId-src", [
            'label' => $this->getLabelForNode($node, _("src")),
            'class' => "edit-src",
            'dataAttr' => [
                'accept' => ".mp3,.m4a,.ogg,.wav,.flac",
            ],
            'dataInfo' => "//*[@db:id = '$nodeId']/@src",
        ]);
    }
    // }}}
    // {{{ addEditVideo()
    /**
     * @brief addEditVideo
     *
     * @param mixed $node
     * @return void
     **/
    protected function addEditVideo($node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $f = $this->form->addFieldset("xmledit-$nodeId", [
            'label' => $this->getLabelForNode($node, _("Video")),
            'class' => "edit-video",
        ]);
        $f->addText("xmledit-$nodeId-src", [
            'label' => $this->getLabelForNode($node, _("src")),
            'class' => "edit-src",
            'dataAttr' => [
                'accept' => ".mp4,.m4v,.ogv,.webm",
            ],
            'dataInfo' => "//*[@db:id = '$nodeId']/@src",
        ]);
    }
    // }}}
    // {{{ addEditImg()
    /**
     * @brief addEditImg
     *
     * @param mixed $node
     * @return void
     **/
    protected function addEditImg($node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $fs = $this->getLangFieldset($node, $this->getLabelForNode($node, _("Image")), "edit-img");

        $fs->addHtml($this->thumbnail($node->getAttribute("src")));


        $lang = $node->getAttribute("lang");
        $fs->addText("xmledit-$nodeId-img", [
            'label' => !empty($lang) ? $lang : _("src"),
            'class' => "edit-src",
            'dataAttr' => [
                'accept' => ".jpg,.jpeg,.png,.gif,.svg,.pdf",
                'forceSize' => $this->getForceSize($node),
            ],
            'dataInfo' => "//*[@db:id = '$nodeId']/@src",
        ]);

        $fs->addText("xmledit-$nodeId-title", [
            'label' => _("title"),
            'placeholder' => _("Image title"),
            'dataInfo' => "//*[@db:id = '$nodeId']/@title",
        ]);

        $fs->addText("xmledit-$nodeId-alt", [
            'label' => _("alt"),
            'placeholder' => _("Image description"),
            'dataInfo' => "//*[@db:id = '$nodeId']/@alt",
        ]);
        if ($node->hasAttribute("href") || $node->hasAttribute("href_id")) {
            $fs->addText("xmledit-$nodeId-href", [
                'label' => _("href"),
                'class' => "edit-href",
                'placeholder' => _("http://domain.com"),
                'dataInfo' => "//*[@db:id = '$nodeId']/@href",
            ]);
        }
    }
    // }}}
    // {{{ addEditTable()
    /**
     * @brief addEditTable
     *
     * @param mixed $param
     * @return void
     **/
    protected function addEditTable($node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $fs = $this->getLangFieldset($node, $this->getLabelForNode($node, _("Table")));
        $fs->addRichtext("xmledit-$nodeId", [
            'label' => $node->getAttribute("lang"),
            'lang' => $node->getAttribute("lang"),
            'dataInfo' => "//*[@db:id = '$nodeId']",
            'class' => "edit-table",
            'lang' => $node->getAttribute("lang"),
            'autogrow' => true,
            'allowedTags' => [
                "table",
                "tbody",
                "tr",
                "td",
                "p",
                "br",
                "b",
                "strong",
                "i",
                "em",
                "small",
                "a",
            ],
        ]);
    }
    // }}}
    // {{{ addEditPlainSource()
    /**
     * @brief addEditPlainSource
     *
     * @param mixed $param
     * @return void
     **/
    protected function addEditPlainSource($node)
    {
        if (!$this->authUser->canEditTemplates()) {
            return $this->addNotAllowed();
        }
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $fs = $this->getLangFieldset($node, $this->getLabelForNode($node, _("Source")));

        $fs->addTextarea("xmledit-$nodeId", [
            'label' => "",
            'dataInfo' => "//*[@db:id = '$nodeId']",
            'autogrow' => true,
            'class' => "edit-source"
        ]);
    }
    // }}}
    // {{{ addNotAllowed()
    /**
     * @brief addNotAllowed
     *
     * @return void
     **/
    protected function addNotAllowed()
    {
        $this->form->addHtml("<p class=\"error\">");
            $this->form->addHtml(htmlentities(_("Not allowed to edit this element.")));
        $this->form->addHtml("</p>");
    }
    // }}}

    // {{{ addColor()
    /**
     * @brief addColor
     *
     * @param mixed
     * @return void
     **/
    protected function addColor($node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $this->form->addText("xmledit-$nodeId", [
            'label' => $node->getAttribute("name"),
            'dataInfo' => "//*[@db:id = '$nodeId']/@value",
        ]);
    }
    // }}}
}
/* vim:set ft=php sts=4 fdm=marker et : */
