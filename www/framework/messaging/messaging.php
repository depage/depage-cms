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

// MESSAGE_STATUS {{{
/**
 * Enum class for message status
 *
 */
class MESSAGE_STATUS {
    const deleted = 0;
    const unread = 1;
    const read = 2;
}
// }}}

// messaging {{{
class messaging {
    
    protected $pdo = null;
    protected $messages_table = null;
    protected $user_table = null;
    public $options = array();
    
    private $cols = array(
        'message_id'      => \PDO::PARAM_INT,
        'sender_id'       => \PDO::PARAM_INT,
        'recipient_id'    => \PDO::PARAM_INT,
        'replyto_id'      => \PDO::PARAM_INT,
        'status'          => \PDO::PARAM_INT,
        'deleted'         => \PDO::PARAM_INT, // special case param
    );
    
    protected $defaults = array(
        'notification_subject'   => 'New mail notification',
        'notification_email'     => 'no_reply@depage.com',
    );
    
    // {{{ constructor()
    public function __construct(\db_pdo $pdo, array $options = array()) {
        $this->pdo = $pdo;
        $this->messages_table = $this->pdo->prefix . '_messages';
        $this->user_table = $this->pdo->prefix . '_auth_user';
        
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
            $this->insert($messages);
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
    private function insert(array $messages){
        $query = $this->prepareInsert();
        
        foreach($messages as &$message){
            $result = $query->execute(array(
                'sender_id'     => $message->sender_id,
                'recipient_id'  => $message->recipient_id,
                'replyto_id'    => $message->replyto_id,
                'subject'       => $message->subject,
                'content'       => $message->content,
                'type'          => $message->type,
            ));
            $message->message_id = $this->pdo->lastInsertId();
        }
        if (!$result) {
            //print_r($query->errorInfo());
            throw new \Exception('Failed to insert message.');
        }
    }
    // }}}
    
    // prepareInsert() {{{
    /**
     * Prepare Insert
     * 
     * @return object - prepared statment
     */
    private function prepareInsert(){
        return $this->pdo->prepare("INSERT INTO {$this->messages_table}
            (sender_id, recipient_id, replyto_id, subject, content, type, status, timestamp)
            VALUES (:sender_id, :recipient_id, :replyto_id, :subject, :content, :type, " . MESSAGE_STATUS::unread . ", NOW())"
        );
    }
    // }}}
    
    // get {{{
    /**
     * Get
     * 
     * Fetches messages from the database according to the provided filter params
     * 
     * @param array $params - ['status'=>0,'type'=>0,'limit'=>array(offset,max)]
     * 
     * @return array $messages - array of message object
     */
    protected function getMessages(array $params = array(), $exclude_deleted = true){
        $cmd = "SELECT 
                s.name AS 'from', 
                r.name AS 'to', 
                s.name_full AS 'fullfrom', 
                r.name_full AS 'fullto', 
                m.*
            FROM {$this->messages_table} AS m
            INNER JOIN {$this->user_table} AS r ON m.recipient_id = r.id
            INNER JOIN {$this->user_table} AS s ON m.sender_id = s.id";
        
        $where = array();
        
        if ($exclude_deleted) {
            $where[] = 'status != :deleted';
            $params['deleted'] = MESSAGE_STATUS::deleted;
        }
        
        if (isset($params['message_id'])) {
            $where[]= 'message_id = :message_id';
        }
        
        if (isset($params['recipient_id'])) {
            $where[]= 'recipient_id = :recipient_id';
        }
        
        if (isset($params['sender_id'])) {
            $where[]= 'sender_id = :sender_id';
        }
        
        if (isset($params['type'])) {
            $where[].= 'type = :type';
        }
        
        if (isset($params['status'])) {
            $where[].= 'status = :status';
        }
        
        if (count($where)){
            $cmd .= ' WHERE ' . implode(' AND ', $where);
        }
        
        if (!isset($params['message_id'])) {
            // only order if we are attempting to get multiple messages
            $cmd .= ' ORDER BY timestamp DESC';
        }
        
        if (isset($params['limit'])) {
            $cmd .= ' LIMIT :offset, :max';
        }
        
        $query = $this->pdo->prepare($cmd);
        
        $this->bindParams($query, $params);
        
        $query->execute();
        
        $messages = $query->fetchAll(\PDO::FETCH_CLASS, 'depage\messaging\message');
        
        if ($messages === false) {
            // throw new \Exception('Failed to get messages.');
        }
        return $messages;
    }
    // }}}
    
    
    // bindParams {{{
    // TODO duplicated from Entity - could inherit or share ?
    /**
     * BindParams
     * @param unknown_type $query
     * @param array $data
     * @throws \Exception
     */
    protected function bindParams (&$query, array $data){
        foreach($data as $col=>&$value) {
            if (array_key_exists($col, $this->cols)){
                if (is_array($value)){
                    throw new \Exception("Invalid value supplied for column {$col}: " . print_r($value));
                }
                $query->bindParam(":{$col}", $value, $this->cols[$col]);
            }
        }
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
    
    // getMessagesForUser {{{
    /**
     * getMessagesForUser
     * 
     * Gets messages for a given user (wraps getMessages) 
     * 
     * @param int $recipient_id - message recipient
     * @param array $params - filter
     *
     * @return array
     */
    public function getMessagesForUser($recipient_id, array $params = array()) {
        $params['recipient_id'] = $recipient_id;
        return $this->getMessages($params);
    }
    // }}}
    
    // getMessagesForUser {{{
    /**
     * getMessagesForSender
     * 
     * gets messages for a given sender (wraps getMessages)
     * 
     * @param int $recipient_id - message recipient
     * @param array $params - filter
     *
     * @return array
     */
    public function getMessagesForSender($sender_id, array $params = array()) {
        $params['sender_id'] = $sender_id;
        return $this->getMessages($params);
    }
    // }}}
    
    // getUnread {{{
    /**
     * Get Unread
     * 
     * Get unread messages (wraps getMessagesForUser)
     * 
     * @param int $recipient_id
     * @param bool $exclude deleted
     *
     * @return array
     */
    public function getUnread($recipient_id, $exclude_deleted = true){
        return $this->getMessagesForUser($recipient_id, array('status'=>1), $exclude_deleted);
    }
    // }}}
    
    // countUnread {{{
    /**
     * Count Messages
     *
     * Counts the unread messages for a given user.
     *
     * @param int $recipient_id
     * @param bool $unread only count unread
     * @param bool $exclude ignore deleted
     *
     * @return int
     */
    public function countMessages($recipient_id, $unread = false, $exclude_deleted = true){
        $query = "SELECT COUNT(message_id) AS unread
            FROM {$this->messages_table}
            WHERE recipient_id = :recipient_id";
        
        if ($unread){
            $query .= ' AND status = ' . MESSAGE_STATUS::unread;
        } else if ($exclude_deleted){
            $query .= ' AND status != ' . MESSAGE_STATUS::deleted;
        }
        
        $cmd = $this->pdo->prepare($query);
        $cmd->bindParam(":recipient_id", $recipient_id, \PDO::PARAM_INT);
        
        $cmd->execute();
        
        $result = $cmd->fetch();
        
        return $result['unread'];
    }
    // }}}
    
    // getInboxMessage {{{
    /**
     * getInboxMessage
     *
     * Gets an inbox message for a given user and message_id (wraps getMessagesForUser)
     *
     * @param int $user_id
     * @param int $message_id
     * @param bool $exclude deleted
     *
     * @return depage\messaging\message
     */
    public function getInboxMessage($user_id, $message_id, $exclude_delete = true){
         $results = $this->getMessagesForUser($user_id, array('message_id'=>$message_id), $exclude_delete);
         if (count($results)){
             return $results[0];
         }
         return false;
    }
    // }}}
    
    // getSentMessage {{{
    /**
     * getSentMessage
     *
     * Gets a sent message for a given user and message_id (wraps getMessagesForSedner)
     * 
     * @param int $sender_id
     * @param int $message_id
     * @param bool $exclude deleted
     *
     * @return depage\messaging\message
     */
    public function getSentMessage($sender_id, $message_id, $exclude_delete = true){
         $results = $this->getMessagesForSender($sender_id, array('message_id'=>$message_id), $exclude_delete);
         if (count($results)){
             return $results[0];
         }
         return false;
    }
    // }}}
    
    // {{{
    /**
     * markAsRead
     * 
     * @param unknown_type $message_id
     * 
     * @return bool
     */
    public function markAsRead($message_id){
        $query = "UPDATE {$this->messages_table}
            SET status=" . MESSAGE_STATUS::read . "
            WHERE message_id=:message_id";
        
        $cmd = $this->pdo->prepare($query);
        $cmd->bindParam(":message_id", $message_id, \PDO::PARAM_INT);
        
        $cmd->execute();
    }
    // }}}
}
// }}}
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
