<?php
/**
 * @file    message.php
 *
 * messaging module
 *
 * copyright (c) 2006-2011 Frank Hellenkamp [jonas@depage.net]
 *
 * @author Ben Wallis [benedict_wallis@yahoo.co.uk]
 */

namespace Depage\Messaging;

class Message
{
    /**
     * Message ID.
     *
     * @var int
     */
    public $message_id = null;

    /**
     * User ID of message sender.
     *
     * @var int
     */
    public $sender_id = null;

    /**
     * User ID of message recipient.
     *
     * @var int
     */
    public $recipient_id = null;

    /**
     * ID of message that this message is in reply to.
     *
     * @var int
     */
    public $replyto_id = null;

    /**
     * Message subject.
     *
     * @var int
     */
    public $subject = '';

    /**
     * Message content.
     *
     * @var int
     */
    public $content = '';

    /**
     * Message type enum
     *
     * @var int
     */
    public $type = 0;

    /**
     * Message status enum
     *
     * @var int
     */
    public $status = 0;

    /**
     * Message timestamp.
     *
     * @var int
     */
    public $timestamp = null;


    // {{{ constructor()
    /**
     * Construct the message object
     *
     * Dynamic constructor for messages
     *
     * @param array - message properties
     *
     * @return void
     */
    public function __construct() {
        $args  = func_get_args();
        if (count($args)){
            $params = $args[0];
            foreach($params as $var => $value){
                if(property_exists($this, $var)) {
                    $this->$var = $value;
                }
            }
        }
    }
    // }}}

}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
