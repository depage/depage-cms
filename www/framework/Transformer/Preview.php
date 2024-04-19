<?php

namespace Depage\Transformer;

class Preview extends Transformer
{
    protected $previewType = "pre";
    protected $profiling = false;

    // {{{ addXsltIncludes()
    protected function addXsltIncludes($doc, $files)
    {
        $root = $doc->documentElement;
        foreach ($files as $file) {
            $tpl = new \Depage\Xml\Document();
            $tpl->resolveExternals = true;
            $tpl->load($file);

            // @todo check if and how to copy all entities from source documents
            /*
            $entities = $tpl->doctype->entities;
            foreach ($entities as $entity) {
                var_dump($entity);
            }
            die();
             */

            foreach ($tpl->documentElement->childNodes as $node) {
                $root->appendChild($doc->importNode($node, true));
            }
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
