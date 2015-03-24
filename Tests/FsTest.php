<?php

use Depage\Fs\Fs;

class FsTest extends PHPUnit_Framework_TestCase
{
    // {{{ setUp
    public function setUp()
    {
        $params = array(
            'scheme' => 'testScheme'
        );

        $this->fs = new Depage\Fs\Fs($params);
    }
    // }}}
    // {{{ invokeParseUrl
    public function invokeParseUrl($url)
    {
        return invoke($this->fs, 'parseUrl', array($url));
    }
    // }}}
    // {{{ invokeExtractFileName
    public function invokeExtractFileName($path)
    {
        return invoke($this->fs, 'extractFileName', array($path));
    }
    // }}}

    // {{{ testCleanUrl
    public function testCleanUrl()
    {
        $params = array(
            'scheme' => 'testScheme',
            'user' => 'testUser',
            'pass' => 'testPass',
            'host' => 'testHost',
            'port' => 42,
        );

        $fs = new Depage\Fs\Fs($params);
        invoke($fs, 'lateConnect');
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/path/to/file', invoke($fs, 'cleanUrl', array('path/to/file')));
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/path/to/file', invoke($fs, 'cleanUrl', array('/path/to/file')));

        $params['path'] = '/testSubDir';
        $fsSubDir = new Depage\Fs\Fs($params);
        invoke($fsSubDir, 'lateConnect');
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/testSubDir/path/to/file', invoke($fsSubDir, 'cleanUrl', array('path/to/file')));
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/testSubDir/path/to/file', invoke($fsSubDir, 'cleanUrl', array('/testSubDir/path/to/file')));

        $params['path'] = '/testSubDir/';
        $fsSubDir = new Depage\Fs\Fs($params);
        invoke($fsSubDir, 'lateConnect');
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/testSubDir/path/to/file', invoke($fsSubDir, 'cleanUrl', array('path/to/file')));
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/testSubDir/path/to/file', invoke($fsSubDir, 'cleanUrl', array('/testSubDir/path/to/file')));
    }
    // }}}
    // {{{ testCleanUrlSpecialCharacters
    public function testCleanUrlSpecialCharacters()
    {
        $params = array('scheme' => 'testScheme');

        $fs = new Depage\Fs\Fs($params);
        invoke($fs, 'lateConnect');
        $this->assertEquals('testScheme:///path',           invoke($fs, 'cleanUrl', array('path')));
        $this->assertEquals('testScheme:///path/to/file',   invoke($fs, 'cleanUrl', array('path/to/file')));
        $this->assertEquals('testScheme:///path/to/file',   invoke($fs, 'cleanUrl', array('/path/to/file')));
        $this->assertEquals('testScheme:/// ',              invoke($fs, 'cleanUrl', array(' ')));
        $this->assertEquals('testScheme:///pa h/to/fi e',   invoke($fs, 'cleanUrl', array('/pa h/to/fi e')));
        $this->assertEquals('testScheme:///?',              invoke($fs, 'cleanUrl', array('?')));
        $this->assertEquals('testScheme:///pa?h/to/fi?e',   invoke($fs, 'cleanUrl', array('/pa?h/to/fi?e')));
        $this->assertEquals('testScheme:///|',              invoke($fs, 'cleanUrl', array('|')));
        $this->assertEquals('testScheme:///pa|h/to/fi|e',   invoke($fs, 'cleanUrl', array('/pa|h/to/fi|e')));
        $this->assertEquals('testScheme:///<',              invoke($fs, 'cleanUrl', array('<')));
        $this->assertEquals('testScheme:///>',              invoke($fs, 'cleanUrl', array('>')));
        $this->assertEquals('testScheme:///pa<h/to/fi>e',   invoke($fs, 'cleanUrl', array('/pa<h/to/fi>e')));
        $this->assertEquals('testScheme:///(',              invoke($fs, 'cleanUrl', array('(')));
        $this->assertEquals('testScheme:///)',              invoke($fs, 'cleanUrl', array(')')));
        $this->assertEquals('testScheme:///pa(h/to/fi)e',   invoke($fs, 'cleanUrl', array('/pa(h/to/fi)e')));
        $this->assertEquals('testScheme:///[',              invoke($fs, 'cleanUrl', array('[')));
        $this->assertEquals('testScheme:///]',              invoke($fs, 'cleanUrl', array(']')));
        $this->assertEquals('testScheme:///pa[h/to/fi]e',   invoke($fs, 'cleanUrl', array('/pa[h/to/fi]e')));
        $this->assertEquals('testScheme:///"',              invoke($fs, 'cleanUrl', array('"')));
        $this->assertEquals('testScheme:///pa"h/to/fi"e',   invoke($fs, 'cleanUrl', array('/pa"h/to/fi"e')));
        $this->assertEquals('testScheme:///\'',             invoke($fs, 'cleanUrl', array('\'')));
        $this->assertEquals('testScheme:///pa\'h/to/fi\'e', invoke($fs, 'cleanUrl', array('/pa\'h/to/fi\'e')));
    }
    // }}}
    // {{{ testParseUrl
    public function testParseUrl()
    {
        $expected = array(
            'path'=>'/path/to/file',
            'scheme'=>'file',
        );
        $this->assertEquals($expected, $this->invokeParseUrl('file:///path/to/file'));

        $this->assertEquals(array('path'=>'/path/to/file'), $this->invokeParseUrl('/path/to/file'));

        $expected = array(
            'path'      => '/path/to/file',
            'scheme'    => 'testScheme',
            'user'      => 'testUser',
            'pass'      => 'testPass',
            'host'      => 'testHost',
            'port'      => '42',
        );
        $this->assertEquals($expected, $this->invokeParseUrl('testScheme://testUser:testPass@testHost:42/path/to/file'));
    }
    // }}}
    // {{{ testParseUrlPath
    public function testParseUrlPath()
    {
        $this->assertEquals(array('path'=>''),          $this->invokeParseUrl(''));
        $this->assertEquals(array('path'=>'abc'),       $this->invokeParseUrl('abc'));
        $this->assertEquals(array('path'=>'a[bd]c'),    $this->invokeParseUrl('a[bd]c'));
        $this->assertEquals(array('path'=>'abc*'),      $this->invokeParseUrl('abc*'));
        $this->assertEquals(array('path'=>'*abc'),      $this->invokeParseUrl('*abc'));
        $this->assertEquals(array('path'=>'*abc*'),     $this->invokeParseUrl('*abc*'));
        $this->assertEquals(array('path'=>'*'),         $this->invokeParseUrl('*'));
        $this->assertEquals(array('path'=>'**'),        $this->invokeParseUrl('**'));
        $this->assertEquals(array('path'=>'abc?'),      $this->invokeParseUrl('abc?'));
        $this->assertEquals(array('path'=>'ab?c'),      $this->invokeParseUrl('ab?c'));
        $this->assertEquals(array('path'=>'?abc'),      $this->invokeParseUrl('?abc'));
        $this->assertEquals(array('path'=>'?abc?'),     $this->invokeParseUrl('?abc?'));
        $this->assertEquals(array('path'=>'?'),         $this->invokeParseUrl('?'));
        $this->assertEquals(array('path'=>'??'),        $this->invokeParseUrl('??'));
        $this->assertEquals(array('path'=>'a&b'),       $this->invokeParseUrl('a&b'));
        $this->assertEquals(array('path'=>'&'),         $this->invokeParseUrl('&'));
        $this->assertEquals(array('path'=>'&&'),        $this->invokeParseUrl('&&'));
    }
    // }}}
    // {{{ testCleanUrlFile
    public function testCleanUrlFile()
    {
        $fs = new Depage\Fs\FsFile(array('scheme' => 'file'));
        invoke($fs, 'lateConnect');
        $this->assertEquals('file://' . getcwd() . '/path/to/file', invoke($fs, 'cleanUrl', array('file://' . getcwd() . '/path/to/file')));
        $this->assertEquals('file://' . getcwd() . '/path/to/file', invoke($fs, 'cleanUrl', array('path/to/file')));
        $this->assertEquals('file://' . getcwd() . '/path/to/file', invoke($fs, 'cleanUrl', array(getcwd() . '/path/to/file')));
    }
    // }}}
    // {{{ testExtractFileName
    public function testExtractFileName()
    {
        $this->assertEquals('filename.extension', $this->invokeExtractFileName('scheme://path/to/filename.extension'));
        $this->assertEquals('filename.extension', $this->invokeExtractFileName('path/to/filename.extension'));
        $this->assertEquals('filename.extension', $this->invokeExtractFileName('/filename.extension'));
        $this->assertEquals('filename.extension', $this->invokeExtractFileName('filename.extension'));

        $this->assertEquals('filename', $this->invokeExtractFileName('scheme://path/to/filename'));
        $this->assertEquals('filename', $this->invokeExtractFileName('path/to/filename'));
        $this->assertEquals('filename', $this->invokeExtractFileName('/filename'));
        $this->assertEquals('filename', $this->invokeExtractFileName('filename'));

        $this->assertEquals('filename.stuff.extension', $this->invokeExtractFileName('scheme://path/to/filename.stuff.extension'));
        $this->assertEquals('filename.stuff.extension', $this->invokeExtractFileName('path/to/filename.stuff.extension'));
        $this->assertEquals('filename.stuff.extension', $this->invokeExtractFileName('/filename.stuff.extension'));
        $this->assertEquals('filename.stuff.extension', $this->invokeExtractFileName('filename.stuff.extension'));

        $this->assertEquals('filename.', $this->invokeExtractFileName('scheme://path/to/filename.'));
        $this->assertEquals('filename.', $this->invokeExtractFileName('path/to/filename.'));
        $this->assertEquals('filename.', $this->invokeExtractFileName('/filename.'));
        $this->assertEquals('filename.', $this->invokeExtractFileName('filename.'));

        $this->assertEquals('.extension', $this->invokeExtractFileName('scheme://path/to/.extension'));
        $this->assertEquals('.extension', $this->invokeExtractFileName('path/to/.extension'));
        $this->assertEquals('.extension', $this->invokeExtractFileName('/.extension'));
        $this->assertEquals('.extension', $this->invokeExtractFileName('.extension'));
    }
    // }}}
    // {{{ testEmptyPassword
    public function testEmptyPassword()
    {
        $params = array(
            'scheme' => 'testScheme',
            'user' => 'testUser',
            'pass' => '',
            'host' => 'testHost',
        );

        $fs = new Depage\Fs\Fs($params);
        $this->assertEquals('testScheme://testUser:@testHost/', $fs->pwd());
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
