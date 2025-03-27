<?php

namespace Cobalt\Notifications;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONDocument;

class NotificationOld implements MongoDB\BSON\Persistable {
    /**
     * @var string $subject - The subject of this notification. Unused?
     */
    private string $subject;
    /**
     * @var string $body - The body content. Stored as markdown.
     */
    private string $body;

    private string $class = '\\Cobalt\\Notifications\\Notification1_0Schema';
    private string $type;
    
    private bool $system_message;

    /* ===== AUTOMATICALLY SET FIELDS ===== */
    private UTCDateTime $sent;
    private ?ObjectId $from;
    private string $ip;
    private string $token;

    function __construct(?BSONDocument $doc = null) {
        if($doc) $this->fromStorage($doc);
    }
    
    function fromStorage(BSONDocument $document):Notification {
        
        return $this;
    }

    function toStorable():array {
        return [

        ];
    }

    function addRecipient($username_or_email, $id = null) {
        if($id) {
            
        }
    }

    private function getUser($username)

    function set_body($body) {
        $this->body = $body;
    }

    function get_body() {
        return $this->body;
    }

    function set_subject($sub) {
        $this->subject = $sub;
    }

    function get_subject(){
        return $this->subject;
    }

    function set_class($class) {
        if()
        $this->class = $class;
    }
}