<?php

require(__DIR__ . "/../Redirector.php");
require(__DIR__ . "/../Result.php");

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
            ->setBaseUrl("http://domain.com")
            ->setLanguages(array(
                "en",
                "de",
                "fr",
            ))
            ->setPages(array(
                "/home.html",
                "/contact.html",
                "/contact/imprint.html",
                "/sub1/sub2/sub3/content.html",
                "/sub1/sub2/sub3/content2.html",
            ))
            ->setAliases(array(
                "/office.html" => "/contact.html",
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
        $alternative = $this->redirector->getAlternativePage("/contact/imprint2.html");

        $this->assertFalse($alternative->isFallback());
        $this->assertEquals("/contact/imprint.html", $alternative);
    }
    // }}}
    // {{{ testGetAlternativePageFallback()
    /**
     * @brief testGetAlternativePageFallback
     *
     * @param mixed
     * @return void
     **/
    public function testGetAlternativePageFallback()
    {
        $alternative = $this->redirector->getAlternativePage("/non/existant/page.html");

        $this->assertTrue($alternative->isFallback());
        $this->assertEquals("/home.html", $alternative);
    }
    // }}}
    // {{{ testAliases()
    /**
     * @brief testAliases
     *
     * @param mixed
     * @return void
     **/
    public function testAliases()
    {
        $alternative = $this->redirector->getAlternativePage("/office.html");

        $this->assertFalse($alternative->isFallback());
        $this->assertEquals("/contact.html", $alternative);
    }
    // }}}
    // {{{ testGetIndexPage()
    /**
     * @brief testGetIndexPage
     *
     * @param mixed
     * @return void
     **/
    public function testGetIndexPage()
    {
        $index = $this->redirector->getIndexPage();

        $this->assertTrue($index->isFallback());
        $this->assertEquals("/home.html", $index);
    }
    // }}}

    // {{{ testRedirectToAlternativePage()
    /**
     * @brief testRedirectToAlternativePage
     *
     * @param mixed
     * @return void
     *
     * @runInSeparateProcess
     **/
    public function testRedirectToAlternativePage()
    {
        $this->redirector->redirectToAlternativePage("/contact/imprint2.html", "de,en-US;q=0.7,en;q=0.3");

        $headers = xdebug_get_headers();

        $this->assertEquals("Location: /de/contact/imprint.html", $headers[0]);
    }
    // }}}
    // {{{ testRedirectToAlternativePageBaseUrl()
    /**
     * @brief testRedirectToAlternativePageBaseUrl
     *
     * @param mixed
     * @return void
     *
     * @runInSeparateProcess
     **/
    public function testRedirectToAlternativePageBaseUrl()
    {
        $this->redirector->setBaseUrl("http://domain.com/path/");
        $this->redirector->redirectToAlternativePage("/path/contact/imprint2.html", "de,en-US;q=0.7,en;q=0.3");

        $headers = xdebug_get_headers();

        $this->assertEquals("Location: /path/de/contact/imprint.html", $headers[0]);
    }
    // }}}
    // {{{ testRedirectToAlternativePageLangUri()
    /**
     * @brief testRedirectToAlternativePageLangUri
     *
     * @param mixed
     * @return void
     *
     * @runInSeparateProcess
     **/
    public function testRedirectToAlternativePageLangUri()
    {
        $this->redirector->redirectToAlternativePage("/en/contact/imprint2.html", "de,en-US;q=0.7,en;q=0.3");

        $headers = xdebug_get_headers();

        $this->assertEquals("Location: /en/contact/imprint.html", $headers[0]);
    }
    // }}}
    // {{{ testRedirectToIndex()
    /**
     * @brief testRedirectToIndex
     *
     * @return void
     *
     * @runInSeparateProcess
     **/
    public function testRedirectToIndex()
    {
        $this->redirector->redirectToIndex("/", "de,en-US;q=0.7,en;q=0.3");

        $headers = xdebug_get_headers();

        $this->assertEquals("Location: /de/home.html", $headers[0]);
    }
    // }}}
    // {{{ testRedirectToIndexBaseUrl()
    /**
     * @brief testRedirectToIndexBaseUrl
     *
     * @return void
     *
     * @runInSeparateProcess
     **/
    public function testRedirectToIndexBaseUrl()
    {
        $this->redirector->setBaseUrl("http://domain.com/path/");
        $this->redirector->redirectToIndex("/", "de,en-US;q=0.7,en;q=0.3");

        $headers = xdebug_get_headers();

        $this->assertEquals("Location: /path/de/home.html", $headers[0]);
    }
    // }}}
    // {{{ testRedirectToIndexLangUri()
    /**
     * @brief testRedirectToIndexLangUri
     *
     * @return void
     *
     * @runInSeparateProcess
     **/
    public function testRedirectToIndexLangUri()
    {
        $this->redirector->redirectToIndex("/en/", "de,en-US;q=0.7,en;q=0.3");

        $headers = xdebug_get_headers();

        $this->assertEquals("Location: /en/home.html", $headers[0]);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
