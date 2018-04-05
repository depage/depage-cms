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
    // {{{ _init()
    public function _init(array $importVariables = []) {
        parent::_init($importVariables);

        if (!empty($this->urlSubArgs[0])) {
            $this->projectName = $this->urlSubArgs[0];
        }
        if (!empty($this->urlSubArgs[1])) {
            $this->nodeId = $this->urlSubArgs[1];
        }

        $this->project = \Depage\Cms\Project::loadByName($this->pdo, $this->xmldbCache, $this->projectName);
        $this->xmldb = $this->project->getXmlDb($this->authUser->id);
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
        $xml = $doc->getXml();

        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace("db", "http://cms.depagecms.net/ns/database");

        list($node) = $xpath->query("//*[@db:id = '{$this->nodeId}']");

        $form = new \Depage\Cms\Forms\XmlForm("xmldata_{$this->nodeId}", [
            'jsAutosave' => true,
            'dataNode' => $node,
            'class' => "labels-on-top",
        ]);

        if ($callback = $this->getCallbackForNode($node)) {
            $this->$callback($form, $node);
        }
        foreach($node->childNodes as $n) {
            if ($callback = $this->getCallbackForNode($n)) {
                $this->$callback($form, $n);
            }
        }
        $form->setDefaultValuesXml();

        $form->process();

        if ($form->validateAutosave()) {
            $node = $form->getValuesXml();
            $doc->saveNode($node);

            $form->clearSession(false);
        }

        // @todo clean unsed session?

        $h .= $form;
        $h .= htmlentities($xml->saveXML($node));

        $output = new Html([
            'title' => "edit",
            'content' => $h,
        ], $this->htmlOptions);

        return $output;
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

        if (is_callable([$this, $callback])) {
            return $callback;
        }

        if ($node->prefix != "sec") {
            echo $callback . "<br>";
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

        $lang = $node->getAttribute("lang");
        if ($lang) {
            $label .= " " . $lang;
        }

        return $label;
    }
    // }}}

    // {{{ addPgMeta()
    /**
     * @brief addPgMeta
     *
     * @param mixed $form, $node
     * @return void
     **/
    protected function addPgMeta($form, $node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $list = ['' => _("Default")] + $this->project->getColorschemes();
        $form->addSingle("colorscheme-$nodeId", [
            'label' => _("Colorscheme"),
            'list' => $list,
            'skin' => "select",
            'dataInfo' => "//*[@db:id = '$nodeId']/@colorscheme",
        ]);
    }
    // }}}
    // {{{ addPgTitle()
    /**
     * @brief addPgTitle
     *
     * @param mixed $form, $node
     * @return void
     **/
    protected function addPgTitle($form, $node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $form->addText("xmledit-$nodeId", [
            'label' => $this->getLabelForNode($node, _("Title")),
            'dataInfo' => "//*[@db:id = '$nodeId']/@value",
        ]);
    }
    // }}}
    // {{{ addPgLinkdesc()
    /**
     * @brief addPgTitle
     *
     * @param mixed $form, $node
     * @return void
     **/
    protected function addPgLinkdesc($form, $node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $form->addText("xmledit-$nodeId", [
            'label' => $this->getLabelForNode($node, _("Linkinfo")),
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
    protected function addPgDesc($form, $node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $form->addRichtext("xmledit-$nodeId", [
            'label' => $this->getLabelForNode($node, _("Description")),
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
     * @param mixed $form, $node
     * @return void
     **/
    protected function addEditTextSingleline($form, $node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $form->addText("xmledit-$nodeId", [
            'label' => $this->getLabelForNode($node, _("Text")),
            'dataInfo' => "//*[@db:id = '$nodeId']/@value",
            'lang' => $node->getAttribute("lang"),
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
    protected function addEditTextHeadline($form, $node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $form->addRichtext("xmledit-$nodeId", [
            'label' => $this->getLabelForNode($node, _("Headline")),
            'dataInfo' => "//*[@db:id = '$nodeId']",
            'class' => "labels-on-top",
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
    protected function addEditTextFormatted($form, $node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        // @todo add lang attribute for spelling hint
        $form->addRichtext("xmledit-$nodeId", [
            'label' => $this->getLabelForNode($node, _("Text")),
            'autogrow' => true,
            'class' => "labels-on-top",
            'lang' => $node->getAttribute("lang"),
            'dataInfo' => "//*[@db:id = '$nodeId']",
        ]);
    }
    // }}}
    // {{{ addEditType()
    /**
     * @brief addEditType
     *
     * @param mixed $form, $node
     * @return void
     **/
    protected function addEditType($form, $node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");
        $options = $node->getAttribute("options");
        $project = $this->getProject($this->projectName);
        $variables = $project->getVariables();

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

        $form->addSingle("xmledit-$nodeId", [
            'label' => $this->getLabelForNode($node, _("Type")),
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
     * @param mixed $form, $node
     * @return void
     **/
    protected function addEditDate($form, $node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $form->addDate("xmledit-$nodeId", [
            'label' => $this->getLabelForNode($node, _("Date")),
            'dataInfo' => "//*[@db:id = '$nodeId']/@value",
        ]);
    }
    // }}}
    // {{{ addEditA()
    /**
     * @brief addEditA
     *
     * @param mixed $form, $node
     * @return void
     **/
    protected function addEditA($form, $node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $f = $form->addFieldset("xmledit-$nodeId", [
            'label' => $this->getLabelForNode($node, _("Link")),
            'class' => "edit-img",
        ]);
        $f->addText("xmledit-$nodeId-href", [
            'label' => $this->getLabelForNode($node, _("href")),
            'dataInfo' => "//*[@db:id = '$nodeId']/@href",
        ]);
        $f->addText("xmledit-$nodeId-alt", [
            'label' => $this->getLabelForNode($node, _("Alt text")),
            'dataInfo' => "//*[@db:id = '$nodeId']/@alt",
        ]);
        $f->addText("xmledit-$nodeId-title", [
            'label' => $this->getLabelForNode($node, _("Title")),
            'dataInfo' => "//*[@db:id = '$nodeId']/@title",
        ]);
        $f->addText("xmledit-$nodeId-target", [
            'label' => $this->getLabelForNode($node, _("Target")),
            'dataInfo' => "//*[@db:id = '$nodeId']/@target",
        ]);
    }
    // }}}
    // {{{ addEditAudio()
    /**
     * @brief addEditAudio
     *
     * @param mixed $form, $node
     * @return void
     **/
    protected function addEditAudio($form, $node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $f = $form->addFieldset("xmledit-$nodeId", [
            'label' => $this->getLabelForNode($node, _("Audio")),
            'class' => "edit-audio",
        ]);
        $f->addText("xmledit-$nodeId-src", [
            'label' => $this->getLabelForNode($node, _("src")),
            'dataInfo' => "//*[@db:id = '$nodeId']/@src",
        ]);
    }
    // }}}
    // {{{ addEditImg()
    /**
     * @brief addEditImg
     *
     * @param mixed $form, $node
     * @return void
     **/
    protected function addEditImg($form, $node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $f = $form->addFieldset("xmledit-$nodeId", [
            'label' => $this->getLabelForNode($node, _("Image")),
            'class' => "edit-img",
        ]);

        // add image preview
        $imgSrc = $node->getAttribute("src");
        $thumbSrc = str_replace("libref://", "projects/{$this->project->name}/lib/", $imgSrc);
        $ext = pathinfo($imgSrc, \PATHINFO_EXTENSION);
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif'])) {
            $thumbSrc = htmlentities($thumbSrc . ".thumb-120x120.png");
        }
        $f->addHtml("<div class=\"thumb\"><img src=\"$thumbSrc\"></div>");

        $f->addText("xmledit-$nodeId-img", [
            'label' => _("Image Source"),
            'dataInfo' => "//*[@db:id = '$nodeId']/@src",
        ]);
        if ($node->hasAttribute("alt")) {
            $f->addText("xmledit-$nodeId-alt", [
                'label' => _("Alt text"),
                'dataInfo' => "//*[@db:id = '$nodeId']/@alt",
            ]);
        }
        if ($node->hasAttribute("title")) {
            $f->addText("xmledit-$nodeId-title", [
                'label' => _("Title"),
                'dataInfo' => "//*[@db:id = '$nodeId']/@title",
            ]);
        }
        if ($node->hasAttribute("href")) {
            $f->addText("xmledit-$nodeId-href", [
                'label' => _("href"),
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
    protected function addEditTable($form, $node)
    {
        $nodeId = $node->getAttributeNs("http://cms.depagecms.net/ns/database", "id");

        $form->addRichtext("xmledit-$nodeId", [
            'label' => $this->getLabelForNode($node, _("Table")),
            'dataInfo' => "//*[@db:id = '$nodeId']",
            'class' => "labels-on-top edit-table",
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
                "ul",
                "ol",
                "li",
            ],
        ]);
    }
    // }}}
}
/* vim:set ft=php sts=4 fdm=marker et : */
