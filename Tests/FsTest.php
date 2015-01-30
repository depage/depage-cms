<?php

use Depage\Fs\Fs;

class FsTest extends PHPUnit_Framework_TestCase
{
    // {{{ setUp
    public function setUp()
    {
        $this->fs = new FsFileTestClass($params);
    }
    // }}}

    // {{{ testCleanUrl
    public function testCleanUrl()
    {
        $params = array(
            'scheme' => 'ftp',
            'user' => 'testUser',
            'pass' => 'testPass',
            'host' => 'testHost',
            'port' => 42,
            'path' => '',
        );

        $ftpFs = new FsTestClass($params);
        $this->assertEquals('ftp://testUser:testPass@testHost:42/path/to/file', $ftpFs->cleanUrl('path/to/file'));
        $this->assertEquals('ftp://testUser:testPass@testHost:42/path/to/file', $ftpFs->cleanUrl('/path/to/file'));

        $params['path'] = '/testSubDir';
        $ftpFsSubDir = new FsTestClass($params);
        $this->assertEquals('ftp://testUser:testPass@testHost:42/testSubDir/path/to/file', $ftpFsSubDir->cleanUrl('path/to/file'));
        $this->assertEquals('ftp://testUser:testPass@testHost:42/testSubDir/path/to/file', $ftpFsSubDir->cleanUrl('/testSubDir/path/to/file'));
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
            'scheme'    => 'ftp',
            'user'      => 'testUser',
            'pass'      => 'testPass',
            'host'      => 'testHost',
            'port'      => '42',
        );
        $this->assertEquals($expected, $this->fs->parseUrl('ftp://testUser:testPass@testHost:42/path/to/file'));
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
        $this->assertEquals('file://' . getcwd() . '/path/to/file', $this->fs->cleanUrl('file://' . getcwd() . '/path/to/file'));
        $this->assertEquals('file://' . getcwd() . '/path/to/file', $this->fs->cleanUrl('path/to/file'));
        $this->assertEquals('file://' . getcwd() . '/path/to/file', $this->fs->cleanUrl(getcwd() . '/path/to/file'));
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
