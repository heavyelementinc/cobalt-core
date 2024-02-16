<?php

use Auth\UserCRUD;
use Cobalt\Notifications\NotificationManager;
use Cobalt\Notifications\Notification;
use Cobalt\Notifications\NotificationOld;

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
        ]
        // 'get' => [
        //     'description' => "[uname [bool:status]] List notifications for user",
        //     'context_required' => false
        // ]
    ];

    function test() {
        $note = new Notification();
        $note->subject = "This is a test";
        $result = array_map(fn ($a) => $a->_id, (new UserCRUD())->getRootUsers());;
        $note->for = $result;
        $note->from = null;
        $note->body = "This is a message from the CLI: \"Hello World\"";
        // $note->setFrom();

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
        return;
    }
}