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
	$this->text = $mailtext;
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
	$astring = "--{$this->boundary}\n" . 
	    "Content-Type: $mimetype\n" .
	    "Content-Transfer-Encoding: base64\n" .
	    "Content-Disposition: attachement;\n  filename=\"$filename\"\n\n";
	$astring .= chunk_split(base64_encode($string)) . "\n";

	$this->attachements[] = $astring;
    }
    // }}}
    // {{{ send()
    function send($recipients = null) {
	if (!is_null($recipients)) {
	    $this->recipients = $recipients;
	}
	if (is_array($recipients)) {
	    $recipient = implode(",", $recipients);
	} else {
	    $recipient = $recipients;
	}

	$headers = "From: {$this->sender}{$this->mail_header_line_ending}";
	if ($this->replyto != "") {
	    $headers .= "Reply-To: {$this->replyto}{$this->mail_header_line_ending}";
	}
	$headers .= "X-Mailer: PHP/" . phpversion() . "{$this->mail_header_line_ending}";
	if (count($this->attachements) == 0) {
	    $headers .= 
	    "Content-Type: text/plain; charset={$this->encoding}{$this->mail_header_line_ending}" . 
		"Content-transfer-encoding: 8bit";
	} else {
	    $headers .=
		"MIME-Version: 1.0{$this->mail_header_line_ending}" .
		"Content-Type: multipart/mixed; {$this->mail_header_line_ending}\tboundary=\"{$this->boundary}\"{$this->mail_header_line_ending}";
	}

	$subject = "=?{$this->encoding}?B?" . base64_encode($this->subject) . "?=";

	if (count($this->attachements) == 0) {
	    $message = wordwrap($this->text);
	} else {
	    $message = 
		"This is a MIME encapsulated multipart message - \n" .
		"please use a MIME-compliant e-mail program to open it. \n\n" .

		"Dies ist eine mehrteilige Nachricht im MIME-Format - \n" .
		"bitte verwenden Sie zum Lesen ein MIME-konformes Mailprogramm.\n\n";
	    $message .= 
		"--{$this->boundary}\n" .
		"Content-Type: text/plain; charset={$this->encoding}\n" . 
		"Content-transfer-encoding: 8bit\n\n"; 
	    $message .= wordwrap($this->text);

	    foreach ($this->attachements as $att) {
		$message .= "\n\n$att";
	    }
	    $message .= "--{$this->boundary}--\n";
	}

        return mail($recipient, $subject, $message, $headers);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
