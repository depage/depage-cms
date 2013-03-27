<?php
/**
 * @file    framework/cms/ui_edit.php
 *
 * depage cms edit module
 *
 *
 * copyright (c) 2011 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace depage\cms;

use \html;

class ui_edit extends ui_base {
    // {{{ _init()
    public function _init(array $importVariables = array()) {
        parent::_init($importVariables);

        if (!empty($this->urlSubArgs[0])) {
            $this->projectName = $this->urlSubArgs[0];
        }
        if (!empty($this->urlSubArgs[1])) {
            $this->docName = $this->urlSubArgs[1];
        }

        // get cache instance
        $cache = \depage\cache\cache::factory("xmldb", array(
            //'disposition' => "memory",
            //'disposition' => "uncached",
            'host' => "twins.local",
        ));

        // get xmldb instance
        $this->prefix = $this->pdo->prefix . "_proj_" . $this->projectName;
        $this->xmldb = new \depage\xmldb\xmldb($this->prefix, $this->pdo, $cache, array(
            "edit:text_headline",
            "edit:text_formatted",
        ));
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
        if (!isset($_REQUEST['ajax']) && is_object($output) && is_a($output, "html")) {
            // pack into body html
            $output = new html("html.tpl", array(
                'title' => $this->basetitle,
                'subtitle' => $output->title,
                'content' => $output,
            ), $this->html_options);
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

        $docName = $this->docName;

        // {{{ $pagedataXml
        $pagedataXml = '<?xml version="1.0" encoding="UTF-8"?>
<pg:page_data xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:backup="http://cms.depagecms.net/ns/backup" xmlns:h="h" version="1.0" >
        <pg:meta db:invalid="del,move,name,dupl" db:name="tree_name_metatags" colorscheme="" lastchange_UTC="2011/05/30 14:10:38" lastchange_uid="1" >
          <pg:title lang="en" value="depage.net: us / the office / why we collaborate" />
          <pg:title lang="de" value="depage.net: wir / das büro / kollaborationen" />
          <pg:linkdesc lang="en" value="depage" />
          <pg:linkdesc lang="de" value="depage" />
          <pg:desc lang="en" >We are a small office in the heart of Berlin-Kreuzberg focussing mainly on concept, design, development, animation, visualization, CMS and Office.</pg:desc>
          <pg:desc lang="de" >Wir sind ein kleines Büro im Herzen von Kreuzberg mit den Schwerpunkten Konzept, Gestaltung, Entwicklung, Animation, Visualization, CMS und Office.</pg:desc>
        </pg:meta>
        <sec:intro name="Intro" >
          <edit:text_headline lang="en"  db:id="1">
            <p >Us, the office and why we collaborate.</p>
          </edit:text_headline>
          <edit:text_headline lang="de" db:id="2">
            <p >Wir, das Büro und warum wir Kollaborateure sind.</p>
          </edit:text_headline>
          <sec:text name="Headline" >
            <edit:text_headline lang="en" db:id="3">
              <p >I / You / We / You / They</p>
              <p />
            </edit:text_headline>
            <edit:text_headline lang="de" db:id="4">
              <p >Ich / Du / Wir / Ihr / Sie</p>
            </edit:text_headline>
          </sec:text>
          <sec:text name="Textblock" >
            <edit:text_formatted lang="en" db:id="4">
              <p >We are a small office in the heart of Berlin-Kreuzberg focussing mainly on <a href="pageref:30552" target="" >concept</a>, <a href="pageref:11382" target="" >design</a>, <a href="pageref:30585" target="" >development</a>, <a href="pageref:30582" target="" >animation, visualization</a>, <a href="pageref:30238" target="" >CMS</a> and <a href="pageref:30414" target="" >Office</a>.</p>
            </edit:text_formatted>
            <edit:text_formatted lang="de" db:id="6">
              <p >Wir sind ein kleines Büro im Herzen von Kreuzberg mit den Schwerpunkten <a href="pageref:30552" target="" >Konzept</a>, <a href="pageref:11382" target="" >Gestaltung</a>, <a href="pageref:30585" target="" >Entwicklung</a>, <a href="" target="" >Animation, Visualization</a>, <a href="" target="" >CMS</a> und <a href="pageref:30414" target="" >Office</a>.</p>
            </edit:text_formatted>
          </sec:text>
        </sec:intro>
      </pg:page_data>';
        // }}}

        $doc = $this->xmldb->getDocXml($docName);

        $xsl = new \DOMDocument();
        $xsl->load(DEPAGE_FM_PATH . "xslt/cms_htmlform_edit.xsl", LIBXML_NOCDATA);

        $xslt = new \XSLTProcessor();
        $xslt->importStylesheet($xsl);

        $forms = array();

        $php = $xslt->transformToXML($doc);
        
        // add form elements based on xml
        eval("?>$php");

        $h = "";
        foreach ($forms as $form) {
            $form->process();

            if (!$form->isEmpty() && $form->validate()) {
                $values = $form->getValues();

                /*
                $value = $values['value'];
                $xpath = new \DOMXPath($value);
                $nodelist = $xpath->query("//body/*");
                 */
                $nodelist = $values['value']->getBodyNodes();

                $savexml = $this->xmldb->getDocXml($docName);
                $rootnode = $savexml->documentElement;

                for ($i = $rootnode->childNodes->length - 1; $i >= 0; $i--) {
                    $rootnode->removeChild($rootnode->childNodes->item($i));
                }

                foreach($nodelist as $node) {
                    // copy all nodes inside the body tag
                    $newnode = $savexml->importNode($node, true);
                    $rootnode->appendChild($newnode);
                }

                if($doc = $this->xmldb->getDoc($docName)){
                    $doc->saveNode($savexml);
                }

                $form->clearSession();
            }
            $h .= $form->__toString();
        }

        $output = new html(array(
            'title' => "edit",
            'content' => $h,
        ), $this->html_options);

        return $output;
        //return $h;
        //return $php;
    }
    // }}}
}
/* vim:set ft=php sts=4 fdm=marker et : */
