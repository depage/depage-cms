<?php

use Depage\Fs\Fs;

class FsTest extends PHPUnit_Framework_TestCase
{
    // {{{ testCleanUrlFtp
    public function testCleanUrlFtp()
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
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
