<?php

namespace Depage\XmlDb\Tests;

class MockDoctypeHandler
{
    public function testDocument($xmlDoc)
    {
        return true;
    }

    public function onDeleteNode($id)
    {
    }
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
