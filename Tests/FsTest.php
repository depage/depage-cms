<?php

use Depage\Fs\Fs;
use Depage\Fs\FsTestClass;
use Depage\Fs\FsFileTestClass;

class FsTest extends PHPUnit_Framework_TestCase
{
    // {{{ setUp
    public function setUp()
    {
        $params = array(
            'scheme' => 'testScheme'
        );

        $this->fs = new FsTestClass($params);
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

        $fs = new FsTestClass($params);
        $fs->lateConnect();
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/path/to/file', $fs->cleanUrl('path/to/file'));
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/path/to/file', $fs->cleanUrl('/path/to/file'));

        $params['path'] = '/testSubDir';
        $fsSubDir = new FsTestClass($params);
        $fsSubDir->lateConnect();
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/testSubDir/path/to/file', $fsSubDir->cleanUrl('path/to/file'));
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/testSubDir/path/to/file', $fsSubDir->cleanUrl('/testSubDir/path/to/file'));

        $params['path'] = '/testSubDir/';
        $fsSubDir = new FsTestClass($params);
        $fsSubDir->lateConnect();
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/testSubDir/path/to/file', $fsSubDir->cleanUrl('path/to/file'));
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/testSubDir/path/to/file', $fsSubDir->cleanUrl('/testSubDir/path/to/file'));
    }
    // }}}
    // {{{ testCleanUrlSpecialCharacters
    public function testCleanUrlSpecialCharacters()
    {
        $params = array('scheme' => 'testScheme');

        $fs = new FsTestClass($params);
        $fs->lateConnect();

        $this->assertEquals('testScheme:///path',           $fs->cleanUrl('path'));
        $this->assertEquals('testScheme:///path/to/file',   $fs->cleanUrl('path/to/file'));
        $this->assertEquals('testScheme:///path/to/file',   $fs->cleanUrl('/path/to/file'));
        $this->assertEquals('testScheme:/// ',              $fs->cleanUrl(' '));
        $this->assertEquals('testScheme:///pa h/to/fi e',   $fs->cleanUrl('/pa h/to/fi e'));
        $this->assertEquals('testScheme:///?',              $fs->cleanUrl('?'));
        $this->assertEquals('testScheme:///pa?h/to/fi?e',   $fs->cleanUrl('/pa?h/to/fi?e'));
        $this->assertEquals('testScheme:///|',              $fs->cleanUrl('|'));
        $this->assertEquals('testScheme:///pa|h/to/fi|e',   $fs->cleanUrl('/pa|h/to/fi|e'));
        $this->assertEquals('testScheme:///<',              $fs->cleanUrl('<'));
        $this->assertEquals('testScheme:///>',              $fs->cleanUrl('>'));
        $this->assertEquals('testScheme:///pa<h/to/fi>e',   $fs->cleanUrl('/pa<h/to/fi>e'));
        $this->assertEquals('testScheme:///(',              $fs->cleanUrl('('));
        $this->assertEquals('testScheme:///)',              $fs->cleanUrl(')'));
        $this->assertEquals('testScheme:///pa(h/to/fi)e',   $fs->cleanUrl('/pa(h/to/fi)e'));
        $this->assertEquals('testScheme:///[',              $fs->cleanUrl('['));
        $this->assertEquals('testScheme:///]',              $fs->cleanUrl(']'));
        $this->assertEquals('testScheme:///pa[h/to/fi]e',   $fs->cleanUrl('/pa[h/to/fi]e'));
        $this->assertEquals('testScheme:///"',              $fs->cleanUrl('"'));
        $this->assertEquals('testScheme:///pa"h/to/fi"e',   $fs->cleanUrl('/pa"h/to/fi"e'));
        $this->assertEquals('testScheme:///\'',             $fs->cleanUrl('\''));
        $this->assertEquals('testScheme:///pa\'h/to/fi\'e', $fs->cleanUrl('/pa\'h/to/fi\'e'));
    }
    // }}}
    // {{{ testParseUrl
    public function testParseUrl()
    {
        $expected = array(
            'path'=>'/path/to/file',
            'scheme'=>'file',
        );
        $this->assertEquals($expected, $this->fs->parseUrl('file:///path/to/file'));

        $this->assertEquals(array('path'=>'/path/to/file'), $this->fs->parseUrl('/path/to/file'));

        $expected = array(
            'path'      => '/path/to/file',
            'scheme'    => 'testScheme',
            'user'      => 'testUser',
            'pass'      => 'testPass',
            'host'      => 'testHost',
            'port'      => '42',
        );
        $this->assertEquals($expected, $this->fs->parseUrl('testScheme://testUser:testPass@testHost:42/path/to/file'));
    }
    // }}}
    // {{{ testParseUrlPath
    public function testParseUrlPath()
    {
        $this->assertEquals(array('path'=>''),          $this->fs->parseUrl(''));
        $this->assertEquals(array('path'=>'abc'),       $this->fs->parseUrl('abc'));
        $this->assertEquals(array('path'=>'a[bd]c'),    $this->fs->parseUrl('a[bd]c'));
        $this->assertEquals(array('path'=>'abc*'),      $this->fs->parseUrl('abc*'));
        $this->assertEquals(array('path'=>'*abc'),      $this->fs->parseUrl('*abc'));
        $this->assertEquals(array('path'=>'*abc*'),     $this->fs->parseUrl('*abc*'));
        $this->assertEquals(array('path'=>'*'),         $this->fs->parseUrl('*'));
        $this->assertEquals(array('path'=>'**'),        $this->fs->parseUrl('**'));
        $this->assertEquals(array('path'=>'abc?'),      $this->fs->parseUrl('abc?'));
        $this->assertEquals(array('path'=>'ab?c'),      $this->fs->parseUrl('ab?c'));
        $this->assertEquals(array('path'=>'?abc'),      $this->fs->parseUrl('?abc'));
        $this->assertEquals(array('path'=>'?abc?'),     $this->fs->parseUrl('?abc?'));
        $this->assertEquals(array('path'=>'?'),         $this->fs->parseUrl('?'));
        $this->assertEquals(array('path'=>'??'),        $this->fs->parseUrl('??'));
        $this->assertEquals(array('path'=>'a&b'),       $this->fs->parseUrl('a&b'));
        $this->assertEquals(array('path'=>'&'),         $this->fs->parseUrl('&'));
        $this->assertEquals(array('path'=>'&&'),        $this->fs->parseUrl('&&'));
    }
    // }}}
    // {{{ testCleanUrlFile
    public function testCleanUrlFile()
    {
        $fs = new FsFileTestClass(array('scheme' => 'file'));
        $fs->lateConnect();

        $this->assertEquals('file://' . getcwd() . '/path/to/file', $fs->cleanUrl('file://' . getcwd() . '/path/to/file'));
        $this->assertEquals('file://' . getcwd() . '/path/to/file', $fs->cleanUrl('path/to/file'));
        $this->assertEquals('file://' . getcwd() . '/path/to/file', $fs->cleanUrl(getcwd() . '/path/to/file'));
    }
    // }}}
    // {{{ testExtractFileName
    public function testExtractFileName()
    {
        $this->assertEquals('filename.extension', $this->fs->extractFileName('scheme://path/to/filename.extension'));
        $this->assertEquals('filename.extension', $this->fs->extractFileName('path/to/filename.extension'));
        $this->assertEquals('filename.extension', $this->fs->extractFileName('/filename.extension'));
        $this->assertEquals('filename.extension', $this->fs->extractFileName('filename.extension'));

        $this->assertEquals('filename', $this->fs->extractFileName('scheme://path/to/filename'));
        $this->assertEquals('filename', $this->fs->extractFileName('path/to/filename'));
        $this->assertEquals('filename', $this->fs->extractFileName('/filename'));
        $this->assertEquals('filename', $this->fs->extractFileName('filename'));

        $this->assertEquals('filename.stuff.extension', $this->fs->extractFileName('scheme://path/to/filename.stuff.extension'));
        $this->assertEquals('filename.stuff.extension', $this->fs->extractFileName('path/to/filename.stuff.extension'));
        $this->assertEquals('filename.stuff.extension', $this->fs->extractFileName('/filename.stuff.extension'));
        $this->assertEquals('filename.stuff.extension', $this->fs->extractFileName('filename.stuff.extension'));

        $this->assertEquals('filename.', $this->fs->extractFileName('scheme://path/to/filename.'));
        $this->assertEquals('filename.', $this->fs->extractFileName('path/to/filename.'));
        $this->assertEquals('filename.', $this->fs->extractFileName('/filename.'));
        $this->assertEquals('filename.', $this->fs->extractFileName('filename.'));

        $this->assertEquals('.extension', $this->fs->extractFileName('scheme://path/to/.extension'));
        $this->assertEquals('.extension', $this->fs->extractFileName('path/to/.extension'));
        $this->assertEquals('.extension', $this->fs->extractFileName('/.extension'));
        $this->assertEquals('.extension', $this->fs->extractFileName('.extension'));
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
