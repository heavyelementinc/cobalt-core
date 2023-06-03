<?php

use Cobalt\Notifications\NotificationManager;
use Cobalt\Notifications\PushNotifications;
use Controllers\Controller;
use MongoDB\BSON\ObjectId;

class Notifications extends Controller {
    
    function __construct() {
        $this->ntfy = new NotificationManager();
    }
    
    function getUserNotifications() {
        return $this->ntfy->getNotificationsForUser();
    }

    function sendNotification() {
        return $this->ntfy->sendNotification($_POST);
    }

    function pushNotification($recipient = null) {
        if(!$recipient || $recipient === "root") $recipient = 'root';
        else $recipient = new ObjectId($recipient);

        $push = new PushNotifications();
        $push->push('Test Subject', 'Hello {{user.fname}}, this is a test of your push notifications.', $recipient, ['details' => "Here's a secret message from uncharted space"]);
        echo "\"Test\"";
        exit;
    }

    function debug() {
        $notification = new \Cobalt\Notifications\Notification1_0Schema([
            '_id' => new ObjectId(),
            'version' => '1.0',
            'subject' => 'Hello, World',
            'body'    => "Here's some **markdown** to use for a test",
            'sent'    => strtotime("-1 day") * 1000,
            'from'    => session()["_id"],
            'for'     => [
                [
                    'user' => "8888888888888888",
                    'read' => false,
                    'recieved' => new \MongoDB\BSON\UTCDateTime(1661612937423)
                ],
                [
                    'user' => session()["_id"],
                    'read' => false,
                    'recieved' => new \MongoDB\BSON\UTCDateTime(1661612937423)
                ]
            ],
            'action' => [
                // 'path'    => "/",
                'route'   => 'CoreAdmin@individual_user_management_panel',
                'params'  => [
                    '62c86a1de50fc66d640f09b2'
                ],
            ]
        ]);

        $ntfy = new NotificationManager();

        add_vars([
            'title' => "Notifications Debug",
            'notifications' => $ntfy->renderNotification($notification)
        ]);

        set_template("debug/notifications.html");
    }
}
