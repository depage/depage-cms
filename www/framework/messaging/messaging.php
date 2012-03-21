<?php
/**
 * @file    messaging.php
 *
 * messaging module
 *
 * copyright (c) 2006-2011 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author Ben Wallis [benedict_wallis@yahoo.co.uk]
 */

namespace depage\messaging;

use depage\mail\mail;

class messaging {
    
    protected $pdo = null;
    protected $messages_table = null;
    
    protected $defaults = array(
        'notification_subject' => 'New mail notification',
        'notification_email'   => 'no_reply@depage.com', 
    );
    
    // {{{ constructor()
    public function __construct(\db_pdo $pdo, $table_prefix = 'dp', array $options = array()) {
        $this->pdo = $pdo;
        $this->messages_table = $table_prefix . '_messages';
        
        $conf = new \config($options);
        $this->options = $conf->getFromDefaults($this->defaults);
    }
    // }}}
    
    // {{{ send()
    /**
     * Send
     * 
     * Sends the messages by inserting them to the database.
     * 
     * @param array or message object - $messages
     * @param bool $notify - also send email notifications to recipients
     * 
     * @return boolean
     */
    public function send($messages, $notify = false) {
        
        if ($messages instanceof message ) {
            $messages = array($messages);
        }
        
        foreach($messages as $message){
            $this->insert($message);
            if ($notify) {
                $this->sendNotification($message);
            }
        }
        
        return true;
    }
    // }}}
    
    // {{{ insert()
    /**
     * Insert
     * 
     * Inserts the message into the database.
     * 
     * @param message $message
     * 
     * @return boolean
     */
    private function insert(message $message){
        $query = $this->pdo->prepare("INSERT INTO {$this->messages_table}
            (sender_id, recipient_id, replyto_id, subject, content, type, status, timestamp)
            VALUES (:sender_id, :recipient_id, :replyto_id, :subject, :content, :type, 0, NOW())"
        );
        
        $result = $query->execute(array(
            'sender_id'     => $message->sender_id,
            'recipient_id'  => $message->recipient_id,
            'replyto_id'    => $message->replyto_id,
            'subject'       => $message->subject,
            'content'       => $message->content,
            'type'          => $message->type
        ));
        
        if (!$result) {
            print_r($query->errorInfo());
            throw new \Exception('Failed to insert message.');
        }
    }
    // }}}
    
    // get {{{
    /**
     * Get
     * 
     * Fetches messages from the database according to the provided filter params
     * 
     * @param int $recipient_id 
     * @param array $params - ['status'=>0,'type'=>0,'limit'=>array(offset,max)]
     * 
     * @return array $messages - array of message object
     */
    public function getMessagesForUser($recipient_id, array $params = array()){
        $cmd = "SELECT *
            FROM {$this->messages_table}
            WHERE recipient_id = :recipient_id";
        
        if (isset($params['status'])) {
            $cmd .= ', status = :status';
        }
        
        if (isset($params['type'])) {
            $cmd .= ', type = :type';
        }
        
        $cmd .= ' ORDER BY timestamp DESC';
        
        if (isset($params['limit'])) {
            $cmd .= ' LIMIT :offset, :max';
        }
        
        $query = $this->pdo->prepare($cmd);
        
        $query->bindParam(':recipient_id', $recipient_id, \PDO::PARAM_INT);
        
        if (isset($params['status'])) {
            $query->bindParam(':status', $params['status'], \PDO::PARAM_INT);
        }
        
        if (isset($params['type'])) {
            $query->bindParam(':type', $params['type'], \PDO::PARAM_INT);
        }
        
        if (isset($params['limit'])) {
            $query->bindParam(':offset', $params['limit'][0], \PDO::PARAM_INT);
            $query->bindParam(':max', $params['limit'][1], \PDO::PARAM_INT);
        }
        
        $query->execute();
        
        $messages = $query->fetchAll(\PDO::FETCH_CLASS, 'depage\messaging\message');
        
        if ($messages === false) {
            print_r($cmd);
            var_dump($exec);
            print_r($query->errorInfo());
            throw new \Exception('Failed to get messages.');
        }
        return $messages;
    }
    // }}}
    
    // sendNotifications {{{
    /**
     * Send Notification
     * 
     * Sends message notifications by email.
     * 
     * @param message $message
     * 
     * @return boolean
     */
    private function sendNotification(message $message){
        $user = \auth_user::get_by_id($this->pdo, $message->recipient_id);
        if (!empty($user)) {
            $mail = new mail($this->defaults['notification_email']);
            $mail->setSubject($this->defaults['notification_subject']);
            $mail->setText($message->content);
            return $mail->send($user->fullname . ' <' . $user->email . '>');
        }
        return false;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
