<?php

use Depage\Fs\Fs;

class FactoryTest extends PHPUnit_Framework_TestCase
{
    // {{{ testFsFile
    public function testFsFile()
    {
        $cases = array(
            '',
            'file://',
            'file:///path/to/file',
            'path/to/file',
            'bogusScheme://path/to/file',
        );

        $reflector = new ReflectionClass('Depage\Fs\FsFile');
        $urlProperty = $reflector->getProperty('url');
        $urlProperty->setAccessible(true);

        foreach ($cases as $case) {
            $fs = Fs::factory($case);

            $this->assertInstanceOf('Depage\Fs\FsFile', $fs);

            $url = $urlProperty->getValue($fs);
            $scheme = $url['scheme'];
            $this->assertEquals('file', $scheme);
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
