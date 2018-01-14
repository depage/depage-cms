<?php

require __DIR__ . "/../Config.php";

class BaseClassWithDefaults
{
    public $defaults = [
        'globalKeyString' => "base class value",
        'globalKeyArray' => [
            'key1' => "bla",
        ],
    ];
}

class ChildClassWithDefaults extends BaseClassWithDefaults
{
    public $defaults = [
        'globalKeyString' => "child class value",
        'globalKeyNumber' => 1,
    ];
}

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

    // {{{ testSetConfigWithArray()
    public function testSetConfigWithArray()
    {
        $this->config->setConfig([
            "key1" => "bla",
            "key2" => [
                "sub" => [
                    "subsub" => "blum",
                ],
            ],
        ]);

        $this->assertEquals("bla", $this->config->key1);
        $this->assertEquals("bla", $this->config["key1"]);

        $this->assertEquals("blum", $this->config->key2->sub->subsub);
        $this->assertEquals("blum", $this->config["key2"]["sub"]["subsub"]);
    }
    // }}}
    // {{{ testSetConfigWithConfig()
    public function testSetConfigWithConfig()
    {
        $conf = new \Depage\Config\Config();
        $conf->setConfig([
            "key1" => "bla",
            "key2" => [
                "sub" => [
                    "subsub" => "blum",
                ],
            ],
        ]);
        $this->config->setConfig($conf);

        $this->assertEquals("bla", $this->config->key1);
        $this->assertEquals("bla", $this->config["key1"]);

        $this->assertEquals("blum", $this->config->key2->sub->subsub);
        $this->assertEquals("blum", $this->config["key2"]["sub"]["subsub"]);
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

    // {{{ testGetDefaultFromBaseClass()
    public function testGetDefaultFromBaseClass()
    {
        $class = new BaseClassWithDefaults();
        $values = $this->config->getDefaultsFromClass($class);

        $this->assertEquals("base class value", $values->globalKeyString);
        $this->assertEquals("bla", $values->globalKeyArray->key1);
    }
    // }}}
    // {{{ testGetDefaultFromChildClass()
    public function testGetDefaultFromChildClass()
    {
        $class = new ChildClassWithDefaults();
        $values = $this->config->getDefaultsFromClass($class);

        $this->assertEquals("child class value", $values->globalKeyString);
        $this->assertEquals(1, $values->globalKeyNumber);
        $this->assertEquals("bla", $values->globalKeyArray->key1);
    }
    // }}}

    // {{{ testConstructor()
    public function testConstructor()
    {
        $config = new \Depage\Config\Config([
            'key' => "value",
        ]);

        $this->assertEquals("value", $config->key);
    }
    // }}}

    // {{{ testToArray()
    public function testToArray()
    {
        $array = [
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
        ];
        $this->config->setConfig($array);
        $this->assertEquals($array, $this->config->toArray());
    }
    // }}}

    // {{{ testReadOnlyObject()
    public function testReadOnlyObject()
    {
        $array = [
            'key' => "value",
        ];
        $this->config->setConfig($array);

        $this->config->key = "new value";
        $this->assertEquals("value", $this->config->key);
    }
    // }}}
    // {{{ testReadOnlyArray()
    public function testReadOnlyArray()
    {
        $array = [
            'key' => "value",
        ];
        $this->config->setConfig($array);

        $this->config["key"] = "new value 2";
        $this->assertEquals("value", $this->config->key);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
