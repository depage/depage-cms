<?php

class TestBase extends PHPUnit_Framework_TestCase
{
    // {{{ setUp
    public function setUp()
    {
        $this->testDir = getcwd();

        $this->localDir = $this->createLocalTestDir();
        $this->remoteDir = $this->createRemoteTestDir();

        $this->fs = $this->createTestClass();
    }
    // }}}
    // {{{ tearDown
    public function tearDown()
    {
        $this->deleteLocalTestDir();
        $this->deleteRemoteTestDir();
    }
    // }}}
    // {{{ rmr
    protected function rmr($path)
    {
        if (is_dir($path)) {
            $scanDir = array_diff(scandir($path), array('.', '..'));

            foreach ($scanDir as $nested) {
                $this->rmr($path . '/' . $nested);
            }
            rmdir($path);
        } else if (is_file($path)) {
            unlink($path);
        }
    }
    // }}}

    // {{{ createLocalTestDir
    public function createLocalTestDir()
    {
        $this->rmr($this->testDir . '/Temp');
        mkdir($this->testDir . '/Temp');
        // @todo verify

        return $this->testDir . '/Temp';
    }
    // }}}
    // {{{ deleteLocalTestDir
    public function deleteLocalTestDir()
    {
        $this->rmr($this->localTestDir . '/Temp');
    }
    // }}}

    // {{{ createTestFile
    protected function createTestFile($path)
    {
        $testFile = fopen($path, 'w');
        fwrite($testFile, 'testString');
        fclose($testFile);
    }
    // }}}
    // {{{ confirmTestFile
    protected function confirmTestFile($path)
    {
        $contents = file($path);
        return $contents == array('testString');
    }
    // }}}

    // {{{ invokeMkdir
    protected function invokeMkdir($path)
    {
        // @todo explode recursive paths
        $this->nodes[] = array('dir', $path);
        $this->fs->mkdir($path);
    }
    // }}}
    // {{{ invokePut
    protected function invokePut($local, $remotePath)
    {
        $this->nodes[] = array('file', $remotePath);
        $this->fs->put($local, $remotePath);
    }
    // }}}
    // {{{ invokePutString
    protected function invokePutString($remotePath, $string)
    {
        $this->nodes[] = array('file', $remotePath);
        $this->fs->putString($remotePath, $string);
    }
    // }}}
}
