<?php

use Cobalt\Notifications\NotificationManager;
use Controllers\Controller;

class Notifications extends Controller {
    function debug() {
        $notification = new \Cobalt\Notifications\Notification1_0Schema([
            'subject' => 'Hello, World',
            'body'    => "Here's some **markdown** to use for a test",
            'for'     => [
                [
                    'user' => new \MongoDB\BSON\ObjectId("62c86a1de50fc66d640f09b2"),
                    'read' => false,
                    'recieved' => new \MongoDB\BSON\UTCDateTime(1661612937423)
                ]
            ],
            'action' => [
                'params'  => [
                    '62c86a1de50fc66d640f09b2'
                ],
                'route'   => 'CoreAdmin@individual_user_management_panel',
                'path'    => null,
                'context' => 'admin'
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