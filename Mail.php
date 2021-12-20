<?php
/**
 * @file    Mail.php
 * @brief   simple mail generator and sender
 *
 * copyright (c) 2006-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

// {{{ documentation
/**
 * @mainpage
 *
 * @intro
 * @image html icon_depage-forms.png
 * @htmlinclude main-intro.html
 * @endintro
 *
 * @section Usage
 *
 * @endsection
 *
 * @htmlinclude main-extended.html
 **/
// }}}
//
namespace Depage\Mail;

/**
 * @brief A simple mail generator and sender
 *
 * depage::mail::mail is a simple class to generate emails with text- and/or
 * html-content with the simple ability to add various attachments. It takes
 * care of mail-boundaries automatically and sends the mail through the native
 * mail() function.
 *
 * It also wordwraps text automatically, and tries to generate a plain-text
 * version of an html-text when no plain-text is provided.
 *
 * @code
 * <?php
 *      $mail = new Depage\Mail\Mail("sender@domain.com");
 *
 *      $mail->setSubject("new mail subject")
 *           ->setText("This will be the text inside of the mail")
 *           ->attachFile("path/to/filename.pdf");
 *
 *      $mail->send("recipient@domain.com");
 * @endcode
 */
class Mail
{
    protected $version = "2.0.0";
    protected $sender;
    protected $recipients;
    protected $cc;
    protected $bcc;
    protected $replyto;
    protected $returnPath;
    protected $subject;
    protected $text;
    protected $htmlText;
    protected $trackerImage;
    protected $dontShowEmail = true;
    protected $attachements = array();
    protected $boundary;
    protected $encoding = "UTF-8";
    protected $eol = PHP_EOL;
    protected $mailFunction = "mail";

    // {{{ constructor()
    /**
     * @brief construct a new mail object
     *
     * @param string $sender email of the sender
     */
    public function __construct($sender)
    {
        $this->sender = $sender;
        $this->returnPath = $sender;
        $this->boundary = "depage-att=" . hash("sha1", date("r") . mt_rand()) . "=";
        $this->boundary2 = "depage-mail=" . hash("sha1", date("r") . mt_rand()) . "=";
    }
    // }}}

    // {{{ setSubject()
    /**
     * @brief Sets the mails subject.
     *
     * @param  string $subject new subject
     * @return object returns the mail object (for chaining)
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }
    // }}}
    // {{{ setRecipients()
    /**
     * @brief Sets the recipients of the mail.
     *
     * Recipients can either be set as a string or as an array of strings. All
     * strings can also be comma separated emails.
     *
     * You also can use all valid email notations:
     *
     * - recipient@domain.com
     * - recipient1@domain.com, recipient2@domain.com
     * - Displayname <recipient@domain.com>
     *
     * @param  string|array $recipients new recipients
     * @return object       returns the mail object (for chaining)
     */
    public function setRecipients($recipients)
    {
        $this->recipients = $recipients;

        return $this;
    }
    // }}}
    // {{{ setCC()
    /**
     * @brief Sets the CC recipients of the mail.
     *
     * @param  string|array $recipients new recipients
     * @return object       returns the mail object (for chaining)
     */
    public function setCC($recipients)
    {
        $this->cc = $recipients;

        return $this;
    }
    // }}}
    // {{{ setBCC()
    /**
     * @brief Sets the BCC recipients of the mail.
     *
     * @param  string|array $recipients new recipients
     * @return object       returns the mail object (for chaining)
     */
    public function setBCC($recipients)
    {
        $this->bcc = $recipients;

        return $this;
    }
    // }}}
    // {{{ setReplyTo()
    /**
     * @brief Sets the reply-to header
     *
     * @param  string $subject new reply-to address
     * @return object returns the mail object (for chaining)
     */
    public function setReplyTo($email)
    {
        $this->replyto = $email;

        return $this;
    }
    // }}}
    // {{{ setReturnPath()
    /**
     * @brief Sets the reply-to header
     *
     * @param  string $subject new reply-to address
     * @return object returns the mail object (for chaining)
     */
    public function setReturnPath($email)
    {
        $this->returnPath = $email;

        return $this;
    }
    // }}}
    // {{{ setText()
    /**
     * @brief Sets the content of the mail as plain text.
     *
     * @param  string $mailtext new mail content
     * @return object returns the mail object (for chaining)
     */
    public function setText($mailtext)
    {
        $mailtext = $this->normalizeLineEndings($mailtext);

        $this->text = $mailtext;

        return $this;
    }
    // }}}
    // {{{ setHtmlText()
    /**
     * @brief Sets the content of the mail as html text.
     *
     * It also sets the plaintext-content of the message by stripping out any
     * tags but leaving the whitespace.
     *
     * @param  string $mailtext new mail html-content
     * @return object returns the mail object (for chaining)
     */
    public function setHtmlText($mailtext)
    {
        // @todo add option to insert/replace tracking image
        $mailtext = $this->normalizeLineEndings($mailtext);

        $this->htmlText = $mailtext;
        $this->text = $this->stripTags($mailtext);

        return $this;
    }
    // }}}
    // {{{ setTrackerImage()
    /**
     * @brief setTrackerImage
     *
     * @param mixed $
     * @return void
     **/
    public function setTrackerImage($url)
    {
        if (!empty($url)) {
            $this->trackerImage = $url;
        }
    }
    // }}}

    // {{{ attachFile()
    /**
     * @brief Attaches a file to a message.
     *
     * @param  string $filename path to filename to attach
     * @param  string $mimetype optional mimetype of the attachment. Defaults to "application/octet_stream"
     * @return object returns the mail object (for chaining)
     */
    public function attachFile($filename, $mimetype = "application/octet_stream")
    {
        $fstring = file_get_contents($filename);

        $this->attachStr($fstring, $mimetype, basename($filename));
    }
    // }}}
    // {{{ attachStr()
    /**
     * @brief Attaches a string as a file to a message.
     *
     * @param  string $filename path to filename to attach
     * @param  string $mimetype optional mimetype of the attachment. Defaults to "application/octet_stream"
     * @param  string $filename filename to use as a name for the attachment
     * @return object returns the mail object (for chaining)
     */
    public function attachStr($string, $mimetype, $filename = "")
    {
        $astring = "--{$this->boundary}{$this->eol}" .
            "Content-type: $mimetype{$this->eol}" .
            "Content-transfer-encoding: base64{$this->eol}" .
            "Content-disposition: attachement;{$this->eol} filename=\"$filename\"{$this->eol}{$this->eol}";
        $astring .= chunk_split(base64_encode($string), 76, $this->eol) . "{$this->eol}";

        $this->attachements[] = $astring;

        return $this;
    }
    // }}}

    // {{{ getSubject()
    /**
     * @brief Gets the mail subject as an encoded string.
     *
     * @return string $subject encoded subject
     */
    public function getSubject()
    {
        $subject = "=?{$this->encoding}?B?" . base64_encode($this->subject) . "?=";

        return $subject;
    }
    // }}}
    // {{{ getRecipients()
    /**
     * @brief Gets the mail recipients as a comma separated list.
     *
     * @return string $recipients all recipients (comma separated)
     */
    public function getRecipients()
    {
        return $this->normalizeRecipients($this->recipients);
    }
    // }}}
    // {{{ getHeaders()
    /**
     * @brief Gets the mail headers.
     *
     * @return string $headers the mail headers
     */
    public function getHeaders()
    {
        $headers = "";

        $headers .= "From: {$this->sender}{$this->eol}";
        if ($this->replyto != "") {
            $headers .= "Reply-To: {$this->replyto}{$this->eol}";
        }
        if ($this->returnPath != "") {
            $headers .= "Return-Path: {$this->returnPath}{$this->eol}";
        }
        if ($this->cc != "") {
            $headers .= "CC: " . $this->normalizeRecipients($this->cc) . $this->eol;
        }
        if ($this->bcc != "") {
            $headers .= "BCC: " . $this->normalizeRecipients($this->bcc) . $this->eol;
        }

        $headers .= "X-Mailer: depage-mail ({$this->getVersion()}){$this->eol}";

        if (count($this->attachements) == 0 && empty($this->htmlText)) {
            $headers .=
                "Content-type: text/plain; charset={$this->encoding}{$this->eol}" .
                "Content-transfer-encoding: quoted-printable";
        } else {
            $headers .=
                "MIME-Version: 1.0{$this->eol}" .
                "Content-Type: multipart/mixed; {$this->eol}\tboundary=\"{$this->boundary}\"{$this->eol}";
        }

        return $headers;
    }
    // }}}
    // {{{ getBody()
    /**
     * @brief Gets the message mail body including all attachments.
     *
     * @return string $message the mail body
     */
    public function getBody()
    {
        $message = "";

        if (count($this->attachements) == 0 && empty($this->htmlText)) {
            $message .= $this->quotedPrintableEncode($this->wordwrap($this->text)) . $this->eol;
        } else {
            $message .=
                _("This is a MIME encapsulated multipart message.") . $this->eol .
                _("Please use a MIME-compliant e-mail program to open it.") . $this->eol . $this->eol;

            $message .=
                "--{$this->boundary}{$this->eol}" .
                "Content-Type: multipart/alternative; {$this->eol}" .
                "\tboundary=\"{$this->boundary2}\"{$this->eol}{$this->eol}";

            $message .=
                "--{$this->boundary2}{$this->eol}" .
                "Content-type: text/plain; charset=\"{$this->encoding}\"{$this->eol}" .
                "Content-transfer-encoding: quoted-printable{$this->eol}{$this->eol}";
            $message .= $this->quotedPrintableEncode($this->wordwrap($this->text));

            if (!empty($this->htmlText)) {
                $htmlText = str_replace("<title></title>", "<title>" . htmlspecialchars($this->subject) . "</title>", $this->htmlText);

                if (!empty($this->trackerImage)) {
                    $htmlText = str_replace("</body>", $this->getTracker() . "</body>", $htmlText);
                }

                $message .= "{$this->eol}{$this->eol}";
                $message .= "--{$this->boundary2}{$this->eol}" .
                    "Content-type: text/html; charset=\"{$this->encoding}\"{$this->eol}" .
                    "Content-Transfer-encoding: quoted-printable{$this->eol}{$this->eol}";
                $message .= $this->quotedPrintableEncode($this->wordwrap($htmlText)) . $this->eol;
            }
            $message .= "--{$this->boundary2}--{$this->eol}";

            foreach ($this->attachements as $att) {
                $message .= "{$this->eol}{$this->eol}$att";
            }
            $message .= "--{$this->boundary}--{$this->eol}";
        }

        return $message;
    }
    // }}}
    // {{{ getTracker()
    /**
     * @brief getTracker
     *
     * @param mixed
     * @return void
     **/
    public function getTracker()
    {
        $html = "";

        $html .= "<table border=\"0\"><tr><td style=\"color: #ffffff;\" width=\"100%\">";
        $html .= "<img src=\"{$this->trackerImage}\" alt=\"-\" width=\"100\" height=\"10\">";
        $html .= "</td></tr></table>";

        return $html;
    }
    // }}}
    // {{{ getEml()
    /**
     * @brief Gets the whole message in EML format
     *
     * @return string $message whole message
     */
    public function getEml()
    {
        $message = "";

        $message .= "To: " . $this->getRecipients() . $this->eol;
        $message .= "Subject: " . $this->getSubject(). $this->eol;
        $message .= $this->getHeaders() . $this->eol . $this->eol;
        $message .= $this->getBody();

        return $message;
    }
    // }}}
    // {{{ getVersion()
    /**
     * @brief Gets the Version number of depage-mail
     *
     * @return string $version version number
     */
    public function getVersion()
    {
        return $this->version;
    }
    // }}}

    // {{{ send()
    /**
     * @brief Sends the mail out to all recipients.
     *
     * @param  string|array $recipients new recipients
     * @return bool         true on success, false on error
     */
    public function send($recipients = null, $trackerImage = null)
    {
        if (!is_null($recipients)) {
            $this->setRecipients($recipients);
        }
        $this->setTrackerImage($trackerImage);

        $success = call_user_func($this->mailFunction, $this->getRecipients(), $this->getSubject(), $this->getBody(), $this->getHeaders());

        return $success;
    }
    // }}}
    // {{{ sendLater()
    /**
     * @brief sendLater
     *
     * @param mixed $
     * @return void
     **/
    public function sendLater(\Depage\Tasks\Task $task, $recipients = null, $trackerImage = null)
    {
        if (!is_null($recipients)) {
            $this->setRecipients($recipients);
        }

        $recipients = array_unique(explode(",", $this->getRecipients()));
        $this->setRecipients(null);

        foreach($recipients as $i => $to) {
            if ($this->dontShowEmail) {
                $title = "sending mail " . ($i + 1);
            } else {
                $title = "sending mail to $to";
            }
            if (!empty($to)) {
                $task->addSubtask($title, "%s->send(%s, %s);", [
                    $this,
                    $to,
                    $trackerImage,
                ]);
            }
        }

        $task->begin();
    }
    // }}}

    // {{{ wordwrap()
    /**
     * @brief Word wraps the text content
     *
     * @param  string  $string   text to wrao
     * @param  integer $width    text width to wrap after, defaults to 75
     * @param  boolean $forceCut force the textbreak, even whan a word is longer the the text-width
     * @return string  wordwrapped text
     */
    protected function wordwrap($string, $width = 75, $forceCut = false)
    {
        $stringWidth = mb_strlen($string, $this->encoding);
        $breakWidth  = mb_strlen($this->eol, $this->encoding);

        if (strlen($string) === 0) {
            return '';
        } elseif ($width < 1 && $forceCut) {
            // Disable forceCut when width is lower than 1
            $forceCut = false;
        }

        $result    = '';
        $lastStart = $lastSpace = 0;

        for ($current = 0; $current < $stringWidth; $current++) {
            $char = mb_substr($string, $current, 1, $this->encoding);

            if ($breakWidth === 1) {
                $possibleBreak = $char;
            } else {
                $possibleBreak = mb_substr($string, $current, $breakWidth, $this->encoding);
            }

            if ($possibleBreak === $this->eol) {
                $result    .= mb_substr($string, $lastStart, $current - $lastStart + $breakWidth, $this->encoding);
                $current   += $breakWidth - 1;
                $lastStart  = $lastSpace = $current + 1;
            } elseif ($char === ' ') {
                if ($current - $lastStart >= $width) {
                    $result    .= mb_substr($string, $lastStart, $current - $lastStart, $this->encoding) . $this->eol;
                    $lastStart  = $current + 1;
                }

                $lastSpace = $current;
            } elseif ($current - $lastStart >= $width && $forceCut && $lastStart >= $lastSpace) {
                $result    .= mb_substr($string, $lastStart, $current - $lastStart, $this->encoding) . $this->eol;
                $lastStart  = $lastSpace = $current;
            } elseif ($current - $lastStart >= $width && $lastStart < $lastSpace) {
                $result    .= mb_substr($string, $lastStart, $lastSpace - $lastStart, $this->encoding) . $this->eol;
                $lastStart  = $lastSpace = $lastSpace + 1;
            }
        }

        if ($lastStart !== $current) {
            $result .= mb_substr($string, $lastStart, $current - $lastStart, $this->encoding);
        }

        return $result;
    }
    // }}}
    // {{{ stripTags()
    /**
     * @brief Strips tags from the html content.
     *
     * @param  string $string html-markup
     * @return string text with tags removed
     */
    protected function stripTags($string)
    {
        // insert html links as text
        // @todo only do this for links with specific class or attribute
        $stripped = preg_replace_callback(array(
            '@<a[^>]*?href="([^"]*)"[^>]*?>(.*)</a>@iu',
        ), function($m) {
            if ($m[1] == $m[2]) {
                return "{$m[2]}";
            } else if (substr($m[1], 0, 7) == "mailto:") {
                return substr($m[1], 7);
            } else {
                return "{$m[2]} [{$m[1]}]";
            }
        }, $string);

        // replace images with alt-text
        $stripped = preg_replace(array(
            '@<img[^>]*?alt="([^"]*)"[^>]*?>@iu',
        ), '${1}', $stripped);

        // Remove invisible/unwanted content
        $stripped = preg_replace(array(
            '@<title[^>]*?>.*?</title>@siu',
            '@<style[^>]*?>.*?</style>@siu',
            '@<script[^>]*?.*?</script>@siu',
            '@<object[^>]*?.*?</object>@siu',
            '@<embed[^>]*?.*?</embed>@siu',
            '@<applet[^>]*?.*?</applet>@siu',
            '@<noframes[^>]*?.*?</noframes>@siu',
            '@<noembed[^>]*?.*?</noembed>@siu',
        ), '', $stripped);

        $stripped = strip_tags($stripped);

        // remove duplicate newlines with just 2
        $stripped = trim(preg_replace("/(\r?\n){2,}/", "\n\n", $stripped));

        return $stripped;
    }
    // }}}
    // {{{ quotedPrintableEncode()
    /**
     * @brief encodes text a quoted-printable and normalizes line endings.
     *
     * @param  string $string text to be encoded
     * @return string encoded string
     */
    protected function quotedPrintableEncode($string)
    {
        $string = $this->normalizeLineEndings(quoted_printable_encode($string));

        return $string;
    }
    // }}}
    // {{{ normalizeLineEndings()
    /**
     * @brief Normalizes line endings to current eol.
     *
     * @param  string $string text to be normalized
     * @return string normalized string
     */
    protected function normalizeLineEndings($string)
    {
        // replace with \n first
        $string = str_replace(array("\r\n", "\r", "\n"), "\n", $string);

        if ($this->eol !== "\n") {
            // replace with real eol afterwords
            $string = str_replace("\n", $this->eol, $string);
        }

        return $string;
    }
    // }}}
    // {{{ normalizeRecipients()
    /**
     * @brief Normalize recipients from array to a list of comma separated emails
     *
     * @todo validate emails
     *
     * @param  string|array $recipients new recipients
     * @return string       $recipients all recipients (comma separated)
     */
    protected function normalizeRecipients($recipients)
    {
        if (is_array($recipients)) {
            $recipients = implode(",", $recipients);
        }

        return trim($recipients);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
