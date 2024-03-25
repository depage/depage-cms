<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for imagesmagick class
 **/
class procTest extends TestCase
{
    protected $graphics;

    // {{{ setUp()
    /**
     * Prepares fresh test objects
     **/
    public function setUp():void
    {
        $this->graphics = new graphics_procTestClass(array(
            'executable' => '',
            'timeout' => 1,
        ));
    }
    // }}}

    // {{{ testProcTimeout()
    /**
     * Test conversion timeout
     **/
    public function testProcTimeout()
    {
        $this->expectException(\Depage\Graphics\Exceptions\Exception::class);

        $this->graphics->setCommand("sleep 20");
        $this->graphics->execCommand();
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
