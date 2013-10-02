<?php

require_once('../mail.php');

use depage\mail\mail;

// {{{ mailTestClass
/**
 * Input is abstract, so we need this test class to instantiate it.
 **/
class mailTestClass extends mail {
    // needed for testSetAutofocus
    /*
    public function getAutofocus() {
        return $this->autofocus;
    }
     */
}
// }}}

/**
 * General tests for the input class.
 **/
class mailTest extends PHPUnit_Framework_TestCase {
    // {{{ setUp()
    public function setUp() {
        $this->mail     = new mail("sender@domain.com");
    }
    // }}}

    // {{{ testSubject()
    public function testSubject() {
        $this->mail->setSubject("my new subject: äöüß");

        $this->assertEquals("=?UTF-8?B?bXkgbmV3IHN1YmplY3Q6IMOkw7bDvMOf?=", $this->mail->getSubject());
    }
    // }}}
    // {{{ testRecients()
    public function testRecients() {
        $this->mail->setRecipients("recipient1@domain.com");

        $this->assertEquals("recipient1@domain.com", $this->mail->getRecipients());
    }
    // }}}
    // {{{ testRecientsArray()
    public function testRecientsArray() {
        $this->mail->setRecipients(array(
            "recipient1@domain.com",
            "recipient2@domain.com",
        ));

        $this->assertEquals("recipient1@domain.com,recipient2@domain.com", $this->mail->getRecipients());
    }
    // }}}
    // {{{ testCC()
    public function testCC() {
        $this->mail->setCC("cc@domain.com");

        $this->assertRegExp("/^CC: cc@domain.com$/m", $this->mail->getHeaders());
    }
    // }}}
    // {{{ testBCC()
    public function testBCC() {
        $this->mail->setBCC("bcc@domain.com");

        $this->assertRegExp("/^BCC: bcc@domain.com$/m", $this->mail->getHeaders());
    }
    // }}}
    // {{{ testReplyTo()
    public function testReplyTo() {
        $this->mail->setReplyTo("reply@domain.com");

        $this->assertRegExp("/^Reply-To: reply@domain.com$/m", $this->mail->getHeaders());
    }
    // }}}
    // {{{ testPlainText()
    public function testPlainText() {
        $this->mail->setText("This is the text with a text line longer than the maximum text width of 75 characters\näöüß");

        $this->assertEquals("This is the text with a text line longer than the maximum text width of 75=\n=0Acharacters=0A=C3=A4=C3=B6=C3=BC=C3=9F", $this->mail->getBody());
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
