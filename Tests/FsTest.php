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
    // {{{ invokeCleanUrl
    public function invokeCleanUrl($url)
    {
        return $this->invoke($this->fs, 'cleanUrl', array($url));
    }
    // }}}
    // {{{ invokeParseUrl
    public function invokeParseUrl($url)
    {
        return $this->invoke($this->fs, 'parseUrl', array($url));
    }
    // }}}
    // {{{ invokeExtractFileName
    public function invokeExtractFileName($path)
    {
        return $this->invoke($this->fs, 'extractFileName', array($path));
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
        $this->assertEquals($expected, $this->invokeParseUrl('file:///path/to/file'));

        $this->assertEquals(array('path'=>'/path/to/file'), $this->invokeParseUrl('/path/to/file'));

        $expected = array(
            'path'      => '/path/to/file',
            'scheme'    => 'ftp',
            'user'      => 'testUser',
            'pass'      => 'testPass',
            'host'      => 'testHost',
            'port'      => '42',
        );
        $this->assertEquals($expected, $this->invokeParseUrl('ftp://testUser:testPass@testHost:42/path/to/file'));
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
        $this->invoke($this->fs, 'lateConnect');
        $this->assertEquals('file://' . getcwd() . '/path/to/file', $this->invokeCleanUrl('file://' . getcwd() . '/path/to/file'));
        $this->assertEquals('file://' . getcwd() . '/path/to/file', $this->invokeCleanUrl('path/to/file'));
        $this->assertEquals('file://' . getcwd() . '/path/to/file', $this->invokeCleanUrl(getcwd() . '/path/to/file'));
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
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
