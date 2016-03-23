<?php
/**
 * @file    framework/cms/ui_base.php
 *
 * base class for cms-ui modules
 *
 *
 * copyright (c) 2011-2012 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace depage\Cms\Rpc;

class Message{
    // {{{ variables
    public $contentType = "text/xml";
    public $charset = "UTF-8";
    public $funcs = array();
    public $return = array();
    // }}}

    // {{{ constructor
    /**
     * constructor, sets function handling object
     *
     * @public
     *
     * @param    $funcObj (object)
     */
    function __construct($funcObj = null) {
        $this->funcObj = &$funcObj;
    }
    // }}}
    // {{{ create()
    /**
     * creates rpc-message with given function object
     *
     * @public
     *
     * @param    $funcs (func-object | array of func-objects)
     *
     * @return    $xmlMsgData (string)
     */
    static function create($funcs) {
        $msg = new Message();

        $msg->funcs = $funcs;

        return $msg;
    }
    // }}}
    // {{{ __toString()
    /**
     * creates rpc-message with given function object
     *
     * @public
     *
     * @param    $funcs (func-object | array of func-objects)
     *
     * @return    $xmlMsgData (string)
     */
    function __toString() {
        $funcs = $this->funcs;

        $data = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
        $data .= "<rpc:msg";

        $data .= " xmlns:rpc=\"http://cms.depagecms.net/ns/rpc\"";

        $data .= ">";
        if (is_array($funcs)) {
            foreach ($funcs as $func) {
                $data .= $func;
            }
        } else if (is_object($funcs) || is_string($funcs)) {
            $data .= $funcs;
        }
        $data .= "</rpc:msg>";

        return $data;
    }
    // }}}
    // {{{ parse()
    /**
     * parses rpc-xml-message
     *
     * @public
     *
     * @param    $xmldata (string)
     *
     * @return    $func_objects (array)
     */
    function parse($xmldata){
        $funcs = Array();

        $error = false;

        $xmlobj = new \Depage\Xml\Document();
        if (!$xmlobj->loadXML($xmldata)) {
            if ($xmldata != '') {
                trigger_error("error in rpc:msg message:\n'$xmldata'\n");
            }
        } else {
            $xpath = new \DOMXPath($xmlobj);

            $nodelist = $xpath->query("/rpc:msg/rpc:func");

            for ($i = 0; $i < $nodelist->length; $i++){
                $func = $nodelist->item($i)->getAttribute('name');

                if (method_exists($this->funcObj, $func)) {
                    $paramList = $xpath->query("./rpc:param", $nodelist->item($i));
                    $args = Array();
                    for ($j = 0; $j < $paramList->length; $j++) {
                        $paramNode = $paramList->item($j);
                        if ($paramNode->hasChildNodes()){
                            $argnode = $paramNode->firstChild;

                            if ($paramNode->childNodes->length > 1 || $argnode->nodeType == \XML_ELEMENT_NODE) {
                                $args[$paramNode->getAttribute('name')] = array();
                                while($argnode !== null) {
                                    if ($argnode->nodeType == \XML_ELEMENT_NODE) {
                                        $args[$paramNode->getAttribute('name')][] = $argnode;
                                    }

                                    $argnode = $argnode->nextSibling;
                                }
                            } else {
                                $args[$paramNode->getAttribute('name')] = $xmlobj->saveXML($argnode, false);
                            }
                        }
                    }

                    $pos = count($funcs);
                    $funcs[$pos] = new Func($func, $args);
                    $funcs[$pos]->set_func_obj($this->funcObj);
                } else {
                    $log = new \Depage\Log\Log();
                    $log->log("function undefined: $func");
                    $log->log($xmldata);
                }
            }

            return $funcs;
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
