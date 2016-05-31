<?php

/**
 * Tests for imagesmagick class
 **/
class procTest extends PHPUnit_Framework_TestCase
{
    // {{{ setUp()
    /**
     * Prepares fresh test objects
     **/
    public function setUp()
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
     *
     * @expectedException \Depage\Graphics\Exceptions\Exception
     **/
    public function testProcTimeout()
    {
        $this->graphics->setCommand("sleep 20");
        $this->graphics->execCommand();
    }
    // }}}
    // {{{ testLock()
    /**
     * Test file lock
     *
     * @todo implement test of locking
     **/
    public function testLock()
    {
    }
    // }}}
}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
