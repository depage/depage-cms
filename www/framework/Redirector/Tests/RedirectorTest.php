<?php

require(__DIR__ . "/../Redirector.php");

use Depage\Redirector\Redirector;

class RedirectorTest extends PHPUnit_Framework_TestCase
{
    // {{{ setUp()
    /**
     * @brief setUp
     *
     * @param mixed
     * @return void
     **/
    protected function setUp()
    {
        parent::setUp();

        $this->redirector = new Redirector();

        $this->redirector
            ->setLanguages(array(
                "en",
                "de",
                "fr",
            ))
            ->setPages(array(
                "/home.html",
                "/contact.html",
                "/contact/imprint.html",
            ));
    }
    // }}}

    // {{{ testGetLanguageByBrowser()
    /**
     * @brief testGetLanguageByBrowser
     *
     * @return void
     **/
    public function testGetLanguageByBrowser()
    {
        $lang = $this->redirector->getLanguageByBrowser("de,en-US;q=0.7,en;q=0.3");

        $this->assertEquals("de", $lang);
    }
    // }}}
    // {{{ testGetLanguageByBrowserFallback()
    /**
     * @brief testGetLanguageByBrowserFallback
     *
     * @return void
     **/
    public function testGetLanguageByBrowserFallback()
    {
        $lang = $this->redirector->getLanguageByBrowser("pl,zh;q=0.7");

        $this->assertEquals("en", $lang);
    }
    // }}}

    // {{{ testGetAlternativePage()
    /**
     * @brief testGetAlternativePage
     *
     * @param mixed
     * @return void
     **/
    public function testGetAlternativePage()
    {
        $this->redirector
            ->setPages(array(
                "/home.html",
                "/contact.html",
                "/contact/imprint.html",
            ));

        $alternative = $this->redirector->getAlternativePage("/contact/imprint2.html");

        $this->assertEquals("/contact/imprint.html", $alternative);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
