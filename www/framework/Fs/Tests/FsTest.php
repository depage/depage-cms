<?php

namespace Depage\Fs\Tests;

use Depage\Fs\Fs;
use Depage\Fs\Exceptions\FsException;
use Depage\Fs\Tests\TestClasses\FsTestClass;

class FsTest extends \PHPUnit_Framework_TestCase
{
    // {{{ createTestObject
    public function createTestObject($override = array())
    {
        $params = array(
            'scheme' => 'testScheme'
        );

        $newParams = array_merge($params, $override);

        return new FsTestClass($newParams);
    }
    // }}}
    // {{{ setUp
    public function setUp()
    {
        $this->fs = $this->createTestObject();
    }
    // }}}

    // {{{ testSchemeAlias
    public function testSchemeAlias()
    {
        $this->assertEquals(array('class' => 'file', 'scheme' => 'file'),       $this->fs->schemeAlias());
        $this->assertEquals(array('class' => 'file', 'scheme' => 'file'),       $this->fs->schemeAlias(''));
        $this->assertEquals(array('class' => 'file', 'scheme' => 'file'),       $this->fs->schemeAlias('file'));
        $this->assertEquals(array('class' => 'ftp', 'scheme' => 'ftp'),         $this->fs->schemeAlias('ftp'));
        $this->assertEquals(array('class' => 'ftp', 'scheme' => 'ftps'),        $this->fs->schemeAlias('ftps'));
        $this->assertEquals(array('class' => 'ssh', 'scheme' => 'ssh2.sftp'),   $this->fs->schemeAlias('ssh2.sftp'));
        $this->assertEquals(array('class' => 'ssh', 'scheme' => 'ssh2.sftp'),   $this->fs->schemeAlias('ssh'));
        $this->assertEquals(array('class' => 'ssh', 'scheme' => 'ssh2.sftp'),   $this->fs->schemeAlias('sftp'));
        $this->assertEquals(array('class' => '', 'scheme' => 'madeupscheme'),   $this->fs->schemeAlias('madeupscheme'));
    }
    // }}}

    // {{{ testCleanPath
    public function testCleanPath()
    {
        $this->assertEquals('', $this->fs->cleanPath(''));
        $this->assertEquals('', $this->fs->cleanPath('.'));
        $this->assertEquals('', $this->fs->cleanPath('./'));
        $this->assertEquals('', $this->fs->cleanPath('.//'));

        $this->assertEquals('/', $this->fs->cleanPath('/'));
        $this->assertEquals('/', $this->fs->cleanPath('//'));

        $this->assertEquals('', $this->fs->cleanPath('..'));
        $this->assertEquals('/', $this->fs->cleanPath('/..'));

        $this->assertEquals('path/to/file', $this->fs->cleanPath('path/to/file'));
        $this->assertEquals('path/file', $this->fs->cleanPath('path//file'));
        $this->assertEquals('path/file', $this->fs->cleanPath('path/./file'));
        $this->assertEquals('file', $this->fs->cleanPath('path/../file'));

        $this->assertEquals('/path/to/file', $this->fs->cleanPath('/path/to/file'));
        $this->assertEquals('/path/file', $this->fs->cleanPath('/path//file'));
        $this->assertEquals('/path/file', $this->fs->cleanPath('/path/./file'));
        $this->assertEquals('/file', $this->fs->cleanPath('/path/../file'));
        $this->assertEquals('/path/to/file', $this->fs->cleanPath('/../path/to/file'));
    }
    // }}}

    // {{{ testCleanUrl
    public function testCleanUrl()
    {
        $params = array(
            'user' => 'testUser',
            'pass' => 'testPass',
            'host' => 'testHost',
            'port' => 42,
        );

        $fs = $this->createTestObject($params);
        $fs->lateConnect();
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/path/to/file', $fs->cleanUrl('path/to/file'));
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/path/to/file', $fs->cleanUrl('/path/to/file'));

        $params['path'] = '/testSubDir';
        $fsSubDir = $this->createTestObject($params);
        $fsSubDir->lateConnect();
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/testSubDir/path/to/file', $fsSubDir->cleanUrl('path/to/file'));
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/testSubDir/path/to/file', $fsSubDir->cleanUrl('/testSubDir/path/to/file'));

        $params['path'] = '/testSubDir/';
        $fsSubDir = $this->createTestObject($params);
        $fsSubDir->lateConnect();
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/testSubDir/path/to/file', $fsSubDir->cleanUrl('path/to/file'));
        $this->assertEquals('testScheme://testUser:testPass@testHost:42/testSubDir/path/to/file', $fsSubDir->cleanUrl('/testSubDir/path/to/file'));
    }
    // }}}
    // {{{ testCleanUrlSpecialCharacters
    public function testCleanUrlSpecialCharacters()
    {
        $this->fs->lateConnect();

        $this->assertEquals('testScheme:///path',           $this->fs->cleanUrl('path'));
        $this->assertEquals('testScheme:///path/to/file',   $this->fs->cleanUrl('path/to/file'));
        $this->assertEquals('testScheme:///path/to/file',   $this->fs->cleanUrl('/path/to/file'));
        $this->assertEquals('testScheme:/// ',              $this->fs->cleanUrl(' '));
        $this->assertEquals('testScheme:///pa h/to/fi e',   $this->fs->cleanUrl('/pa h/to/fi e'));
        $this->assertEquals('testScheme:///?',              $this->fs->cleanUrl('?'));
        $this->assertEquals('testScheme:///pa?h/to/fi?e',   $this->fs->cleanUrl('/pa?h/to/fi?e'));
        $this->assertEquals('testScheme:///|',              $this->fs->cleanUrl('|'));
        $this->assertEquals('testScheme:///pa|h/to/fi|e',   $this->fs->cleanUrl('/pa|h/to/fi|e'));
        $this->assertEquals('testScheme:///<',              $this->fs->cleanUrl('<'));
        $this->assertEquals('testScheme:///>',              $this->fs->cleanUrl('>'));
        $this->assertEquals('testScheme:///pa<h/to/fi>e',   $this->fs->cleanUrl('/pa<h/to/fi>e'));
        $this->assertEquals('testScheme:///(',              $this->fs->cleanUrl('('));
        $this->assertEquals('testScheme:///)',              $this->fs->cleanUrl(')'));
        $this->assertEquals('testScheme:///pa(h/to/fi)e',   $this->fs->cleanUrl('/pa(h/to/fi)e'));
        $this->assertEquals('testScheme:///[',              $this->fs->cleanUrl('['));
        $this->assertEquals('testScheme:///]',              $this->fs->cleanUrl(']'));
        $this->assertEquals('testScheme:///pa[h/to/fi]e',   $this->fs->cleanUrl('/pa[h/to/fi]e'));
        $this->assertEquals('testScheme:///"',              $this->fs->cleanUrl('"'));
        $this->assertEquals('testScheme:///pa"h/to/fi"e',   $this->fs->cleanUrl('/pa"h/to/fi"e'));
        $this->assertEquals('testScheme:///\'',             $this->fs->cleanUrl('\''));
        $this->assertEquals('testScheme:///pa\'h/to/fi\'e', $this->fs->cleanUrl('/pa\'h/to/fi\'e'));
    }
    // }}}

    // {{{ testParseUrl
    public function testParseUrl()
    {
        $expected = array(
            'path'=>'/path/to/file',
            'scheme'=>'file',
        );
        $this->assertEquals($expected, Fs::parseUrl('file:///path/to/file'));

        $this->assertEquals(array('path'=>'/path/to/file'), Fs::parseUrl('/path/to/file'));

        $expected = array(
            'path'      => '/path/to/file',
            'scheme'    => 'testScheme',
            'user'      => 'testUser',
            'pass'      => 'testPass',
            'host'      => 'testHost',
            'port'      => '42',
        );
        $this->assertEquals($expected, Fs::parseUrl('testScheme://testUser:testPass@testHost:42/path/to/file'));
    }
    // }}}
    // {{{ testParseUrlPath
    public function testParseUrlPath()
    {
        $this->assertEquals(array('path'=>''),          Fs::parseUrl(''));
        $this->assertEquals(array('path'=>'abc'),       Fs::parseUrl('abc'));
        $this->assertEquals(array('path'=>'a[bd]c'),    Fs::parseUrl('a[bd]c'));
        $this->assertEquals(array('path'=>'abc*'),      Fs::parseUrl('abc*'));
        $this->assertEquals(array('path'=>'*abc'),      Fs::parseUrl('*abc'));
        $this->assertEquals(array('path'=>'*abc*'),     Fs::parseUrl('*abc*'));
        $this->assertEquals(array('path'=>'*'),         Fs::parseUrl('*'));
        $this->assertEquals(array('path'=>'**'),        Fs::parseUrl('**'));
        $this->assertEquals(array('path'=>'abc?'),      Fs::parseUrl('abc?'));
        $this->assertEquals(array('path'=>'ab?c'),      Fs::parseUrl('ab?c'));
        $this->assertEquals(array('path'=>'?abc'),      Fs::parseUrl('?abc'));
        $this->assertEquals(array('path'=>'?abc?'),     Fs::parseUrl('?abc?'));
        $this->assertEquals(array('path'=>'?'),         Fs::parseUrl('?'));
        $this->assertEquals(array('path'=>'??'),        Fs::parseUrl('??'));
        $this->assertEquals(array('path'=>'a&b'),       Fs::parseUrl('a&b'));
        $this->assertEquals(array('path'=>'&'),         Fs::parseUrl('&'));
        $this->assertEquals(array('path'=>'&&'),        Fs::parseUrl('&&'));
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
            'user' => 'testUser',
            'pass' => '',
            'host' => 'testHost',
        );

        $fs = $this->createTestObject($params);
        $this->assertEquals('testScheme://testUser:@testHost/', $fs->pwd());
    }
    // }}}

    // {{{ currentErrorHandler
    public function currentErrorHandler()
    {
        $handler = set_error_handler(function() {});
        restore_error_handler();
        return $handler;
    }
    // }}}
    // {{{ testErrorHandlerCommand
    public function testErrorHandlerCommand()
    {
        $initialHandler = $this->currentErrorHandler();

        $fs = $this->createTestObject();
        $this->assertSame($initialHandler, $this->currentErrorHandler());

        $fs->pwd();
        $this->assertSame($initialHandler, $this->currentErrorHandler());
    }
    // }}}
    // {{{ testErrorHandlerPrePostCommand
    public function testErrorHandlerPrePostCommand()
    {
        $initialHandler = $this->currentErrorHandler();

        $fs = $this->createTestObject();
        $this->assertSame($initialHandler, $this->currentErrorHandler());

        $fs->preCommandHook();
        $this->assertSame(array($fs, 'depageFsErrorHandler'), $this->currentErrorHandler());

        $fs->postCommandHook();
        $this->assertSame($initialHandler, $this->currentErrorHandler());
    }
    // }}}
    // {{{ testErrorHandlerAfterException
    public function testErrorHandlerAfterException()
    {
        $initialHandler = $this->currentErrorHandler();

        $fs = $this->createTestObject();
        $this->assertSame($initialHandler, $this->currentErrorHandler());

        try {
            $this->fs->cd('invalidPath');
        } catch (FsException $e) {}

        $this->assertSame($initialHandler, $this->currentErrorHandler());
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
