<?php

namespace Depage\XmlDb;

interface XmlGetter
{
    public function docExists($doc_id_or_name);
    public function getDocXml($doc_id_or_name, $add_id_attribute = true);
    public function getDocXmlXpath($doc_id_or_name, $xpath, $add_id_attribute = true);
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
