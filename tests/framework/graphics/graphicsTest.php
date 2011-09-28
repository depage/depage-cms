<?php

require_once('../../../www/framework/depage/depage.php');

class graphicsTest extends PHPUnit_Framework_TestCase {
    public function testFactory() {
        $graphics = graphics::factory();
        $this->assertTrue($graphics instanceof graphics_gd);
        $graphics = graphics::factory(array('extension'=>'gd'));
        $this->assertTrue($graphics instanceof graphics_gd);
        $graphics = graphics::factory(array('extension'=>'foobar'));
        $this->assertTrue($graphics instanceof graphics_gd);
        $graphics = graphics::factory(array('extension'=>'imagemagick', 'executable'=>'bin'));
        $this->assertTrue($graphics instanceof graphics_imagemagick);
        $graphics = graphics::factory(array('extension'=>'im', 'executable'=>'bin'));
        $this->assertTrue($graphics instanceof graphics_imagemagick);
        $graphics = graphics::factory(array('extension'=>'graphicsmagick', 'executable'=>'bin'));
        $this->assertTrue($graphics instanceof graphics_graphicsmagick);
        $graphics = graphics::factory(array('extension'=>'gm', 'executable'=>'bin'));
        $this->assertTrue($graphics instanceof graphics_graphicsmagick);
    }
}
