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
    
    // {{{ parseMailParts()
    public function parseMailParts($eml) {
        $mailparse = mailparse_msg_create();
        mailparse_msg_parse($mailparse, $eml);

        $structure = mailparse_msg_get_structure($mailparse);
        $parts = array();

        foreach ($structure as $partId) {
            $part = mailparse_msg_get_part($mailparse, $partId);
            $parts[$partId] = mailparse_msg_get_part_data($part);
        }

        return $parts;
    }
    // }}}
    // {{{ getPartBody()
    public function getPartBody($eml, $part) {
        $parts = $this->parseMailParts($eml);

        $start = $parts[$part]['starting-pos-body'];
        $end = $parts[$part]['ending-pos-body'];
        $body = substr($eml, $start, $end - $start);

        return quoted_printable_decode($body);
    }
    // }}}
    // {{{ getPartAttachment()
    public function getPartAttachment($eml, $part) {
        $parts = $this->parseMailParts($eml);

        $start = $parts[$part]['starting-pos-body'];
        $end = $parts[$part]['ending-pos-body'];
        $body = substr($eml, $start, $end - $start);

        return base64_decode($body);
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

        $parts = $this->parseMailParts($this->mail->getEml());

        $this->assertRegExp("/^CC: cc@domain.com$/m", $this->mail->getHeaders());
        $this->assertEquals("cc@domain.com", $parts[1]['headers']['cc']);
    }
    // }}}
    // {{{ testBCC()
    public function testBCC() {
        $this->mail->setBCC("bcc@domain.com");

        $parts = $this->parseMailParts($this->mail->getEml());

        $this->assertRegExp("/^BCC: bcc@domain.com$/m", $this->mail->getHeaders());
        $this->assertEquals("bcc@domain.com", $parts[1]['headers']['bcc']);
    }
    // }}}
    // {{{ testReplyTo()
    public function testReplyTo() {
        $this->mail->setReplyTo("reply@domain.com");

        $parts = $this->parseMailParts($this->mail->getEml());

        $this->assertRegExp("/^Reply-To: reply@domain.com$/m", $this->mail->getHeaders());
        $this->assertEquals("reply@domain.com", $parts[1]['headers']['reply-to']);
    }
    // }}}
    // {{{ testPlainText()
    public function testPlainText() {
        $this->mail->setText("This is the text with a text line longer than the maximum text width of 75 characters\nSpecial Chars: äöüß");

        $body = $this->getPartBody($this->mail->getEml(), '1');

        $this->assertEquals("This is the text with a text line longer than the maximum text width of 75\ncharacters\nSpecial Chars: äöüß\n", $body);
    }
    // }}}
    // {{{ testHtmlText
    public function testHtmlText() {
        $this->mail->setHtmlText("<p>This is the text with a text line longer than the maximum text width of 75 characters</p>\n<p>Special Chars: äöüß</p>");

        $eml = $this->mail->getEml();
        $parts = $this->parseMailParts($eml);

        $plainText = $this->getPartBody($eml, '1.1');
        $htmlText = $this->getPartBody($eml, '1.2');

        $this->assertEquals("text/plain; charset=\"UTF-8\"", $parts['1.1']['headers']['content-type']);
        $this->assertEquals("This is the text with a text line longer than the maximum text width of 75\ncharacters\nSpecial Chars: äöüß\n", $plainText);

        $this->assertEquals("text/html; charset=\"UTF-8\"", $parts['1.2']['headers']['content-type']);
        $this->assertEquals("<p>This is the text with a text line longer than the maximum text width of\n75 characters</p>\n<p>Special Chars: äöüß</p>", $htmlText);
    }
    // }}}
    // {{{ testAttachString
    public function testAttachString() {
        $this->mail->attachStr("Special Chars: äöüß", "application/octet_stream");

        $eml = $this->mail->getEml();
        $parts = $this->parseMailParts($eml);

        $attachment = $this->getPartAttachment($eml, '1.2');

        $this->assertEquals("application/octet_stream", $parts['1.2']['headers']['content-type']);
        $this->assertEquals("Special Chars: äöüß", $attachment);
    }
    // }}}
    // {{{ testAttachFile
    public function testAttachFile() {
        $filename = __FILE__;

        $this->mail->attachFile($filename);

        $eml = $this->mail->getEml();
        $parts = $this->parseMailParts($eml);

        $attachment = $this->getPartAttachment($eml, '1.2');

        $this->assertEquals("application/octet_stream", $parts['1.2']['headers']['content-type']);
        $this->assertEquals("attachement; filename=\"" . basename($filename) . "\"", $parts['1.2']['headers']['content-disposition']);
        $this->assertEquals(file_get_contents($filename), $attachment);
    }
    // }}}
    // {{{ testSend
    public function testSend() {
        /*
        rename_function('mail', 'mail_orig');
        rename_function('mail_mock', 'mail');
        
        $this->mail->setSubject("Subject");

        $results = $this->mail->send();

        $this->assertSame('foo@example.com', $results[0]);
        $this->assertSame('Default Title', $results[1]);
        $this->assertSame('Default Message', $results[2]);
        $this->assertSame('Default Message', $results[3]);
         */
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
