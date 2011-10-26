<?php
/**
 * @file    mail.php
 *
 * mail module
 *
 *
 * copyright (c) 2006-2011 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace depage\mail;

class mail {
    var $sender;
    var $recipients;
    var $subject;
    var $text;
    var $htmlText;
    var $attachements = array();

    // {{{ constructor()
    function __construct($sender, $replyto = "", $recipients = "") {
        $this->sender = $sender;
        $this->replyto = $replyto;
        $this->recipients = $recipients;
        $this->boundary = "=====PHP" . md5(date("r"));
        $this->mail_header_line_ending = "\n";
        $this->encoding = "UTF-8";
    }
    // }}}
    
    // {{{ setSubject()
    function setSubject($subject) {
        $this->subject = $subject;
    }
    // }}}
    // {{{ setText()
    function setText($mailtext) {
        $mailtext = $this->normalizeLineEndings($mailtext);

        $this->text = $mailtext;
    }
    // }}}
    // {{{ setHtmlText()
    function setHtmlText($mailtext) {
        $mailtext = $this->normalizeLineEndings($mailtext);

        $this->htmlText = $mailtext;
        $this->text = strip_tags($this->htmlText);
    }
    // }}}
    
    // {{{ attachFile()
    function attachFile($filename, $mimetype = "application/octet_stream") {
        $fstring = file_get_contents($filename);

        $this->attachStr($fstring, $mimetype, basename($filename));
    }
    // }}}
    // {{{ attachStr()
    function attachStr($string, $mimetype, $filename = "") {
        $astring = "--{$this->boundary}{$this->mail_header_line_ending}" . 
            "Content-type: $mimetype{$this->mail_header_line_ending}" .
            "Content-transfer-encoding: base64{$this->mail_header_line_ending}" .
            "Content-disposition: attachement;{$this->mail_header_line_ending}  filename=\"$filename\"{$this->mail_header_line_ending}{$this->mail_header_line_ending}";
        $astring .= chunk_split(base64_encode($string)) . "{$this->mail_header_line_ending}";

        $this->attachements[] = $astring;
    }
    // }}}
    // {{{ attachHtml()
    function attachHtml($string) {
        $string = str_replace("<title></title>", "<title>" . htmlspecialchars($this->subject) . "</title>", $string);

        $astring = "--{$this->boundary}{$this->mail_header_line_ending}" . 
            "Content-type: text/html; charset=\"{$this->encoding}\"{$this->mail_header_line_ending}" .
            "Content-Transfer-encoding: quoted-printable{$this->mail_header_line_ending}{$this->mail_header_line_ending}";
        $astring .= $this->quotedPrintableEncode($this->wordwrap($string)) . "{$this->mail_header_line_ending}";

        $this->attachements[] = $astring;
    }
    // }}}
    
    // {{{ send()
    function send($recipients = null) {
        $headers = "";
        $message = "";
        $subject = "";

        if (!is_null($recipients)) {
            $this->recipients = $recipients;
        }
        if (is_array($recipients)) {
            $recipient = implode(",", $recipients);
        } else {
            $recipient = $recipients;
        }

        if ($this->htmlText != "") {
            $this->attachHtml($this->htmlText);
        }

        $headers .= "From: {$this->sender}{$this->mail_header_line_ending}";
        if ($this->replyto != "") {
            $headers .= "Reply-To: {$this->replyto}{$this->mail_header_line_ending}";
        }
        $headers .= "X-Mailer: PHP/" . phpversion() . "{$this->mail_header_line_ending}";
        if (count($this->attachements) == 0) {
            $headers .= 
            "Content-type: text/plain; charset={$this->encoding}{$this->mail_header_line_ending}" . 
            "Content-transfer-encoding: quoted-printable";
        } else {
            $headers .=
            "MIME-Version: 1.0{$this->mail_header_line_ending}" .
            // @todo add to boundaries (mixed/alternative) depending on attachements
            //"Content-Type: multipart/mixed; {$this->mail_header_line_ending}\tboundary=\"{$this->boundary}\"{$this->mail_header_line_ending}";
            "Content-Type: multipart/alternative; {$this->mail_header_line_ending}\tboundary=\"{$this->boundary}\"{$this->mail_header_line_ending}";
        }

        $subject .= "=?{$this->encoding}?B?" . base64_encode($this->subject) . "?=";

        if (count($this->attachements) == 0) {
            $message .= $this->quotedPrintableEncode($this->wordwrap($this->text));
        } else {
            $message .= 
                "This is a MIME encapsulated multipart message - {$this->mail_header_line_ending}" .
                "please use a MIME-compliant e-mail program to open it. {$this->mail_header_line_ending}{$this->mail_header_line_ending}" .

                "Dies ist eine mehrteilige Nachricht im MIME-Format - {$this->mail_header_line_ending}" .
                "bitte verwenden Sie zum Lesen ein MIME-konformes Mailprogramm.{$this->mail_header_line_ending}{$this->mail_header_line_ending}";
            $message .= 
                "--{$this->boundary}{$this->mail_header_line_ending}" .
                "Content-type: text/plain; charset=\"{$this->encoding}\"{$this->mail_header_line_ending}" . 
                "Content-transfer-encoding: quoted-printable{$this->mail_header_line_ending}{$this->mail_header_line_ending}"; 
            $message .= $this->quotedPrintableEncode($this->wordwrap($this->text));

            foreach ($this->attachements as $att) {
                $message .= "{$this->mail_header_line_ending}{$this->mail_header_line_ending}$att";
            }
            $message .= "--{$this->boundary}--{$this->mail_header_line_ending}";
        }

        return mail($recipient, $subject, $message, $headers);
    }
    // }}}
    
    // {{{ wordwrap()
    /**
     * Word wrap
     *
     * @param  string  $string
     * @param  integer $width
     * @param  string  $break
     * @param  boolean $cut
     * @param  string  $charset
     * @return string
     */
    function wordwrap($string, $width = 75, $break = "\n", $cut = false, $charset = 'utf-8') {
        $stringWidth = iconv_strlen($string, $charset);
        $breakWidth  = iconv_strlen($break, $charset);

        if (strlen($string) === 0) {
            return '';
        } elseif ($breakWidth === null) {
            throw new Exception('Break string cannot be empty');
        } elseif ($width === 0 && $cut) {
            throw new Exception('Can\'t force cut when width is zero');
        }

        $result    = '';
        $lastStart = $lastSpace = 0;

        for ($current = 0; $current < $stringWidth; $current++) {
            $char = iconv_substr($string, $current, 1, $charset);

            if ($breakWidth === 1) {
                $possibleBreak = $char;
            } else {
                $possibleBreak = iconv_substr($string, $current, $breakWidth, $charset);
            }

            if ($possibleBreak === $break) {
                $result    .= iconv_substr($string, $lastStart, $current - $lastStart + $breakWidth, $charset);
                $current   += $breakWidth - 1;
                $lastStart  = $lastSpace = $current + 1;
            } elseif ($char === ' ') {
                if ($current - $lastStart >= $width) {
                    $result    .= iconv_substr($string, $lastStart, $current - $lastStart, $charset) . $break;
                    $lastStart  = $current + 1;
                }

                $lastSpace = $current;
            } elseif ($current - $lastStart >= $width && $cut && $lastStart >= $lastSpace) {
                $result    .= iconv_substr($string, $lastStart, $current - $lastStart, $charset) . $break;
                $lastStart  = $lastSpace = $current;
            } elseif ($current - $lastStart >= $width && $lastStart < $lastSpace) {
                $result    .= iconv_substr($string, $lastStart, $lastSpace - $lastStart, $charset) . $break;
                $lastStart  = $lastSpace = $lastSpace + 1;
            }
        }

        if ($lastStart !== $current) {
            $result .= iconv_substr($string, $lastStart, $current - $lastStart, $charset);
        }

        return $result;
    }
    // }}}
    // {{{ quotedPrintableEncode()
    function quotedPrintableEncode($string) {
        $string = $this->normalizeLineEndings(quoted_printable_encode($string));

        return $string;
    }
    // }}}
    // {{{ normalizeLineEndings()
    function normalizeLineEndings($string) {
        $string = str_replace(array("\r\n", "\r"), $this->mail_header_line_ending, $string);

        return $string;
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
