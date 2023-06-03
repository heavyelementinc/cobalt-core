<?php

namespace Cobalt\Notifications;

use Auth\UserCRUD;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use MongoDB\BSON\ObjectId;

class PushNotifications {
    var $app;
    var $vapid_keys = null;
    var $_id = null;
    var string $ua_push_types = 'notifications.push.types';

    var $valid = [
        'contact_form_new'    => [
            'required_permission' => 'Contact_form_submissions_access',
            // 'required_settings' => 
            'label' => "Contact form: New contact form submissions",
            'default' => true,
        ],
        'contact_form_unread' => [
            'required_permission' => 'Contact_form_submissions_access',
            'label' => "Contact form: Daily unread contact reminders",
            'default' => false,
        ],
    ];

    function __construct() {
        global $app;
        $this->app = $app;
        // if(is_null($this->app)) $this->app = new \Cobalt\Settings\Manager();
        $this->fetch_vapid_keys();
    }

    function update_vapid_keys($keys = null) {
        if(!$keys) $keys = VAPID::createVapidKeys();
        $result = $this->app->updateOne(
            ['_id' => $this->_id],
            [
                '$set' => [
                    'meta.type' => 'VAPID',
                    'keyset' => $keys,
                ]
            ],
            ['upsert' => true]
        );

        return $result->getModifiedCount();
    }

    function fetch_vapid_keys($error = false) {
        $this->vapid_keys = $this->app->findOne(['meta.type' => 'VAPID']);
        $this->_id = $this->vapid_keys->_id ?? new ObjectId();

        if($this->vapid_keys === null) {
            if($error) throw new \Exception("Could not establish VAPID keys!");
            $this->update_vapid_keys();
            $this->fetch_vapid_keys(true);
        }

        return $this->vapid_keys;
    }

    function push($subject, $message, $recipient_classes = [], $data = []) {
        $recipients = $this->fetch_recipients($recipient_classes);
        $auth = [
            "VAPID" => [
                'subject'    => $_SERVER['SERVER_NAME'],// ?? app("domain_name"),
                'publicKey'  => $this->vapid_keys->keyset->publicKey,
                'privateKey' => $this->vapid_keys->keyset->privateKey
            ]
        ];
        $webPush = new WebPush($auth);
        $webPush->setReuseVAPIDHeaders(true);

        foreach($recipients as $user) {
            foreach($user->__dataset['notifications']['push']['keys'] as $r) {
                $json = json_encode([
                    'subject' => view_from_string($subject, ['user' => $user]),
                    'message' => view_from_string($message, ['user' => $user]),
                    'origin'  => app("domain_name"),
                    'badge'   => '/favicon.ico',
                    'icon'    => app("logo.thumb.filename"),
                    'data'    => $data
                ]);
                $webPush->queueNotification(Subscription::create([
                        'endpoint' => $r['endpoint'],
                        'publicKey'=> $this->vapid_keys->keyset->publicKey,
                        'keys' => [
                            'p256dh' => $r['keys']['p256dh'],
                            'auth'   => $r['keys']['auth']
                        ],
                        'contentEncoding' => 'aesgcm'
                    ]),
                    $json
                );
            }
        }

        foreach($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            if($report->isSuccess()) {
                log_item("[Push Notify] Message sent successfully for {$endpoint}");

            } else {
                log_item("[Push Notify] Message failed to send for subscription {$endpoint}: {$report->getReason()}");
            }
        }
    }

    function fetch_recipients($classes) {
        $crud = new UserCRUD();
        if ($classes === "root") $query = ['groups' => 'root'];
        else if ($classes instanceof ObjectId) {
            $query = ['_id' => $classes];
        } else if(is_array($classes)) {
            // $arr = array_fill_keys($classes,true);
            $query = [];
            foreach($classes as $name){
                $query[] = ["$this->ua_push_types.$name" => true];
            }
            // $query = [$this->ua_push_types => $arr];
        } else {
            throw new BadRequest("Failed to find valid recipients");
        }
        
        $users = $crud->findAllAsSchema(['$or' => $query]);

        return $users;
    }

    function render_push_opt_in_form_values($user) {
        $form_items = "";
        $push_settings = $user->{$this->ua_push_types} ?? [];
        foreach($this->valid as $type => $meta) {
            if(!$this->is_elligible($user, $type)) continue;
            $value = "false";
            if(in_array($type, $push_settings)) $value = json_encode($push_settings[$type]);
            $form_items .= "
            <li>
                <switch-container>
                    <input-switch name='$type' checked='$value'></input-switch><label>$meta[label]</label>
                </switch-container>
            </li>";
        }
        if(!$form_items) return "<li>Your user account is ineligible to receive any of the currently supported push notifications.</li>";
        return $form_items;
    }

    function is_elligible($user, $type) {
        return (has_permission($this->valid[$type]['required_permission'], null, $user, false));
    }

    

    final function enrollPushKeys($userId, $key_data) {
        $ua = new UserCRUD();
        if($userId instanceof ObjectId === false) $userId = new ObjectId($userId);
        $user = $ua->findOne(['_id' => $userId]);
        if(!$user) throw new NotFound("Invalid resource");
        $valid = ['endpoint', 'expirationTime', 'keys'];
        $validated = [];
        foreach($valid as $key) {
            if(!key_exists($key, $key_data)) throw new BadRequest("Your enrollment request is missing a required key: `$key`");
            $validated[$key] = $key_data[$key];
        }
        $result = $ua->updateOne(['_id' => $userId],[
            '$addToSet' => ['notifications.push.keys' => $validated]
        ]);
        return $result->getModifiedCount();
    }

    final function revokePushKeys($userId, $key_data) {
        $ua = new UserCRUD();
        if($userId instanceof ObjectId === false) $userId = new ObjectId($userId);
        $user = $ua->findOne(['_id' => $userId]);
        if(!$user) throw new NotFound("Invalid resource");
        
        $modCount = 0;
        foreach($user->notifications->push->keys as $keyset) {
            if($keyset->auth !== $key_data['auth']) continue;
            $result = $ua->updateOne(['_id' => $userId], [
                '$pull' => ['notifications.push.keys' => $keyset]
            ]);
            $modCount += $result->getModifiedCount();
        }

        return $modCount;
    }
}
