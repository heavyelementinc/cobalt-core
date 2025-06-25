<?php

use Auth\UserCRUD;
use Cobalt\Notifications\Classes\NotificationManager;
use Cobalt\Notifications\Models\NotificationSchema;
use Cobalt\Notifications\Notification;
use Cobalt\Notifications\NotificationOld;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use MongoDB\BSON\UTCDateTime;

/**
 * @todo Do not display help items that require environment context if in pre-env
 */
class Notify {

    public $help_documentation = [
        'test' => [
            'description' => "Send a test notification",
            'context_required' => true,
        ],
        'count' => [
            'description' => 'uname - get the notification count for this user',
            'context_required' => true
        ],
        'email' => [
            'description' => 'send notification as email',
            'context_required' => true
        ]
        // 'get' => [
        //     'description' => "[uname [bool:status]] List notifications for user",
        //     'context_required' => false
        // ]
    ];

    function test() {
        $raw = [
            'from' => null,
            'for' => array_map(fn ($a) => ['user' => $a->_id, 'seen' => false, 'read' => false, 'modified' => new UTCDateTime()], (new UserCRUD())->getRootUsers()),
            'subject' => 'This is a test',
            'body' => "This is a message from the CLI: \"Hello World\"",
            'action' => [
                'href' => '/admin',
                'route' => '',
                'params' => ''
            ],
            'type' => 0,
            'sent' => '127.0.0.1'
        ];

        $note = new NotificationSchema();
        $note->bsonUnserialize($raw);
        say("Sending ". fmt(count($raw['for']),"i")." notification" . plural(count($raw['for'])));
        $noteman = new NotificationManager();
        return $noteman->sendNotification($note);
    }

    function count($uname) {
        $crud = new UserCRUD();
        $user = $crud->getUserByUnameOrEmail($uname);
        if(!$user) throw new \Exception("`$uname` is invalid username");
        $noteman = new NotificationManager();
        $count = $noteman->getUnreadNotificationCountForUser($user->_id);
        say(fmt($uname, "i"));
        say("=========================");
        say(fmt($count["unseen"],"i") . " unseen notifications");
        say(fmt($count["unread"],"i") . " unread notifications");
        // say(fmt($count['']));
        return;
    }

    function email($username = null) {
        $nm = new NotificationManager();
        $nm->process_notification_queue($username);
        return "Email queue complete";
    }
}