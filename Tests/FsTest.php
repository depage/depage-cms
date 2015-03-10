<?php

use Depage\Fs\Fs;

class FsTest extends PHPUnit_Framework_TestCase
{
    // {{{ setUp
    public function setUp()
    {
        $params = array(
            'scheme' => 'file'
        );

        $this->fs = new Depage\Fs\FsFile($params);
    }
    // }}}
    // {{{ invoke
    public function invoke($fs, $methodName, $args = null)
    {
        $reflector = new ReflectionClass($fs);
        $reflectionMethod = $reflector->getMethod($methodName);
        $reflectionMethod->setAccessible(true);
        $result = null;

        if ($args === null) {
            $result = $reflectionMethod->invoke($fs);
        } else {
            $result = $reflectionMethod->invokeArgs($fs, $args);
        }

        return $result;
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
        );

        $ftpFs = new Depage\Fs\Fs($params);
        $this->invoke($ftpFs, 'lateConnect');
        $this->assertEquals('ftp://testUser:testPass@testHost:42/path/to/file', $this->invoke($ftpFs, 'cleanUrl', array('path/to/file')));
        $this->assertEquals('ftp://testUser:testPass@testHost:42/path/to/file', $this->invoke($ftpFs, 'cleanUrl', array('/path/to/file')));

        $params['path'] = '/testSubDir';
        $ftpFsSubDir = new Depage\Fs\Fs($params);
        $this->invoke($ftpFsSubDir, 'lateConnect');
        $this->assertEquals('ftp://testUser:testPass@testHost:42/testSubDir/path/to/file', $this->invoke($ftpFsSubDir, 'cleanUrl', array('path/to/file')));
        $this->assertEquals('ftp://testUser:testPass@testHost:42/testSubDir/path/to/file', $this->invoke($ftpFsSubDir, 'cleanUrl', array('/testSubDir/path/to/file')));
    }
    // }}}
    // {{{ testParseUrl
    public function testParseUrl()
    {
        $expected = array(
            'path'=>'/path/to/file',
            'scheme'=>'file',
        );
        $this->assertEquals($expected, $this->invoke($this->fs, 'parseUrl', array('file:///path/to/file')));

        $this->assertEquals(array('path'=>'/path/to/file'), $this->invoke($this->fs, 'parseUrl', array('/path/to/file')));

        $expected = array(
            'path'      => '/path/to/file',
            'scheme'    => 'ftp',
            'user'      => 'testUser',
            'pass'      => 'testPass',
            'host'      => 'testHost',
            'port'      => '42',
        );
        $this->assertEquals($expected, $this->invoke($this->fs, 'parseUrl', array('ftp://testUser:testPass@testHost:42/path/to/file')));
    }
    // }}}
    // {{{ testParseUrlPath
    public function testParseUrlPath()
    {
        $this->assertEquals(array('path'=>''),          $this->invoke($this->fs, 'parseUrl', array('')));
        $this->assertEquals(array('path'=>'abc'),       $this->invoke($this->fs, 'parseUrl', array('abc')));
        $this->assertEquals(array('path'=>'a[bd]c'),    $this->invoke($this->fs, 'parseUrl', array('a[bd]c')));
        $this->assertEquals(array('path'=>'abc*'),      $this->invoke($this->fs, 'parseUrl', array('abc*')));
        $this->assertEquals(array('path'=>'*abc'),      $this->invoke($this->fs, 'parseUrl', array('*abc')));
        $this->assertEquals(array('path'=>'*abc*'),     $this->invoke($this->fs, 'parseUrl', array('*abc*')));
        $this->assertEquals(array('path'=>'*'),         $this->invoke($this->fs, 'parseUrl', array('*')));
        $this->assertEquals(array('path'=>'**'),        $this->invoke($this->fs, 'parseUrl', array('**')));
        $this->assertEquals(array('path'=>'abc?'),      $this->invoke($this->fs, 'parseUrl', array('abc?')));
        $this->assertEquals(array('path'=>'ab?c'),      $this->invoke($this->fs, 'parseUrl', array('ab?c')));
        $this->assertEquals(array('path'=>'?abc'),      $this->invoke($this->fs, 'parseUrl', array('?abc')));
        $this->assertEquals(array('path'=>'?abc?'),     $this->invoke($this->fs, 'parseUrl', array('?abc?')));
        $this->assertEquals(array('path'=>'?'),         $this->invoke($this->fs, 'parseUrl', array('?')));
        $this->assertEquals(array('path'=>'??'),        $this->invoke($this->fs, 'parseUrl', array('??')));
        $this->assertEquals(array('path'=>'a&b'),       $this->invoke($this->fs, 'parseUrl', array('a&b')));
        $this->assertEquals(array('path'=>'&'),         $this->invoke($this->fs, 'parseUrl', array('&')));
        $this->assertEquals(array('path'=>'&&'),        $this->invoke($this->fs, 'parseUrl', array('&&')));
    }
    // }}}
    // {{{ testCleanUrlFile
    public function testCleanUrlFile()
    {
        $this->invoke($this->fs, 'lateConnect');
        $this->assertEquals('file://' . getcwd() . '/path/to/file', $this->invoke($this->fs, 'cleanUrl', array('file://' . getcwd() . '/path/to/file')));
        $this->assertEquals('file://' . getcwd() . '/path/to/file', $this->invoke($this->fs, 'cleanUrl', array('path/to/file')));
        $this->assertEquals('file://' . getcwd() . '/path/to/file', $this->invoke($this->fs, 'cleanUrl', array(getcwd() . '/path/to/file')));
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
