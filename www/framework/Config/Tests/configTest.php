<?php

require "../Config.php";

class configTest extends PHPUnit_Framework_TestCase
{
    // {{{ setUp()
    /**
     * @brief setUp
     *
     * @param mixed
     * @return void
     **/
    public function setUp()
    {
        $this->config = new \Depage\Config\Config();
        $this->testConfig = [
            '*' => [
                'globalKeyNumber' => 1,
                'globalKeyString' => "global value",
                'globalKeyArray1' => [
                    'key1' => "bla",
                    'key2' => "blub",
                ],
                'globalKeyArray2' => [
                    'key1' => "bla",
                    'key2' => "blub",
                ],
            ],
            '*/path/' => [
                'globalKeyString' => "overridden wildcard path",
                'globalKeyArray2' => [
                    'key1' => "bla2",
                    'key2' => "blub2",
                ],
            ],
            '*/path?/' => [
                'globalKeyString' => "overridden character wildcard",
            ],
            '*/**/path/' => [
                'globalKeyString' => "overridden deep wildcard",
            ],
            'domain.com/path/' => [
                'globalKeyString' => "overridden domain path",
                'globalKeyArray2' => [
                    'key1' => "bla3",
                    'key2' => "blub3",
                ],
            ],
        ];
    }
    // }}}

    // {{{ testGlobalValuesNumber()
    public function testGlobalValuesNumber()
    {
        $this->config->setConfigForUrl($this->testConfig, "randomDomain.com");

        $this->assertEquals(1, $this->config->globalKeyNumber);
    }
    // }}}
    // {{{ testGlobalValuesString()
    public function testGlobalValuesString()
    {
        $this->config->setConfigForUrl($this->testConfig, "randomDomain.com");

        $this->assertEquals("global value", $this->config->globalKeyString);
    }
    // }}}
    // {{{ testGlobalValuesArray()
    public function testGlobalValuesArray()
    {
        $this->config->setConfigForUrl($this->testConfig, "randomDomain.com");

        $this->assertEquals("bla", $this->config->globalKeyArray1->key1);
        $this->assertEquals("blub", $this->config->globalKeyArray1->key2);
        $this->assertEquals("bla", $this->config->globalKeyArray2->key1);
        $this->assertEquals("blub", $this->config->globalKeyArray2->key2);
    }
    // }}}

    // {{{ testWildcardUrlWithPathString()
    public function testWildcardUrlWithPathString()
    {
        $this->config->setConfigForUrl($this->testConfig, "randomDomain.com/path/");

        $this->assertEquals("overridden wildcard path", $this->config->globalKeyString);
    }
    // }}}
    // {{{ testWildcardUrlWithPathArray()
    public function testWildcardUrlWithPathArray()
    {
        $this->config->setConfigForUrl($this->testConfig, "randomDomain.com/path/");

        $this->assertEquals("bla", $this->config->globalKeyArray1->key1);
        $this->assertEquals("bla2", $this->config->globalKeyArray2->key1);
    }
    // }}}
    // {{{ testWildcardUrlWithPathAndWildcarCharacterString()
    public function testWildcardUrlWithPathAndWildcarCharacterString()
    {
        $this->config->setConfigForUrl($this->testConfig, "randomDomain.com/path2/");

        $this->assertEquals("overridden character wildcard", $this->config->globalKeyString);
    }
    // }}}

    // {{{ testDeepWildcardString()
    public function testDeepWildcardString()
    {
        $this->config->setConfigForUrl($this->testConfig, "randomDomain.com/path1/path2/path3/path/bla/");

        $this->assertEquals("overridden deep wildcard", $this->config->globalKeyString);
    }
    // }}}
    // {{{ testDomainPathString()
    public function testDomainPathString()
    {
        $this->config->setConfigForUrl($this->testConfig, "domain.com/path/");

        $this->assertEquals("overridden domain path", $this->config->globalKeyString);
    }
    // }}}
    // {{{ testDomainPathArray()
    public function testDomainPathArray()
    {
        $this->config->setConfigForUrl($this->testConfig, "domain.com/path/");

        $this->assertEquals("bla", $this->config->globalKeyArray1->key1);
        $this->assertEquals("bla3", $this->config->globalKeyArray2->key1);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
