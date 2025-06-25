<?php

namespace Cobalt\Notifications\Classes;

use Auth\UserCRUD;
use Auth\UserPersistance;
use Cobalt\Model\Types\UserIdType;
use Cobalt\Notifications\Models\NotificationSchema;
use Cobalt\SchemaPrototypes\Compound\UserIdResult;
use DateInterval;
use DateTime;
use Exceptions\HTTP\BadRequest;
use Mail\SendMail;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Cursor;
use MongoDB\Model\BSONDocument;
use TypeError;

class NotificationManager extends \Drivers\Database {

    public function get_collection_name() {
        return app("Notifications_collection");
    }

    function get_schema_name($doc = []) {
        return $doc['class'] ?? '\\Cobalt\\Notifications\\Models\\Notification';
    }

    const QUERY_LIMIT = 20;
    const SORT_SENT = -1;
    const SORT_PRIORITY = -1;
    const QUERY_FIRST_PAGE = 0;
    const MAX_UNREAD_COUNT = 9;

    const STATE_ANY = -1;
    const STATE_UNREAD = 0;
    const STATE_READ = 1;
    const STATE_UNSEEN = 2;
    const STATE_SEEN = 3;


    public function getNotificationsForUser(?ObjectId $user = null, $onlyUnread = true, array $options = []) {
        $opts = $this->buildQueryAndOptions($user, $onlyUnread, $options);

        $q = $this->find(
            $opts['query'],
            $opts['options']
        );
        // $query += ['for.seen' => false];
        $update = $this->updateMany($opts['query'], ['$set' => ['for.$.seen' => true]]);

        // $result = $this->updateMany($opts['query'], ['$set' => ['for.$.state' => ]], $options);

        return $q;
    }

    public function getUnreadNotificationCountForUser(?ObjectId $user = null) {
        $opts = $this->buildQueryAndOptions($user, $this::STATE_UNREAD, []);
        $unseen = $this->buildQueryAndOptions($user, $this::STATE_UNSEEN, []);
        
        return [
            // "unread" => $this->countDocuments($opts['query'], ['limit' => $this::MAX_UNREAD_COUNT]),
            "unseen" => $this->countDocuments($unseen['query'], ['limit' => $this::MAX_UNREAD_COUNT]),
        ];
    }

    private function buildQueryAndOptions(?ObjectId $user, $seenStatus, array $options = []) {
        if($user === null) $user = session('_id');
        $id = new ObjectId($user);

        $query = [
            'for' => [
                '$elemMatch' => [
                    'user' => $id,
                ]
            ]
        ];

        switch((int)$seenStatus) {
            case $this::STATE_ANY:
                break;
            case $this::STATE_READ:
                $query['for']['$elemMatch']['read'] = true;
                break;
            case $this::STATE_UNREAD:
                $query['for']['$elemMatch']['read'] = false;
                break;
            case $this::STATE_SEEN:
                $query['for']['$elemMatch']['seen'] = true;
                break;
            case $this::STATE_UNSEEN:
                $query['for']['$elemMatch']['seen'] = false;
                break;
        }

        // Let's merge the submitted options with our trusted default params
        $options = array_merge([
            'limit' => $this::QUERY_LIMIT,
            'sort' => array_merge(['sent' => $this::SORT_SENT, 'priority' => $this::SORT_PRIORITY], $options['sort'] ?? []),
            'page' => $this::QUERY_FIRST_PAGE
        ], $options);

        // Let's limit the number of total documents to the minimum between the default and the submitted value
        $options['limit'] = min($options['limit'], $this::QUERY_LIMIT * 2);
        
        // Let's rebuild our options to only use our trusted params
        $trusted_options = [
            'sort' => $options['sort'],
            'limit' => $options['limit'],
            'skip' => $options['limit'] * $options['page'],
        ];
        return [
            'query' => $query,
            'options' => $trusted_options,
        ];
    }

    public function setReadState($id, ObjectId $user, bool $state) {
        $result = $this->updateOne([
            '_id' => $id,
            'for.user' => $user,
        ],[
            '$set' => [
                "for.$.read" => $state,
                "for.$.seen" => $state,
            ]
        ]);

        return $result->getModifiedCount();
    }

    public function setSeenState($id, $user, $state) {
        return $this->setReadState($id, $user, $state, "seen");
    }
    
    public function sendNotification(NotificationSchema $note, bool $push_notify = true) {
        $id = $note->_id ?? null;

        $allowUpsert = true;
        // if($id !== null) $allowUpsert = false;
        if(!isset($note->type)) $note->type = 0;
        
        $note->ip   = $_SERVER['X-FORWARDED-FOR'] ?? $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['REMOTE_ADDR'];
        $note->sent = new UTCDateTime();
        
        if(count($note['for']) < 1) {
            throw new TypeError("At least one recipient must be specified");
        }

        foreach($note['for'] as $u){ 
            if(!is_array($u)) throw new TypeError("Recipient must conform to the correct datastructure!");
            if(!key_exists('user', $u) || !key_exists('seen',$u) || !key_exists('read', $u) || !key_exists('modified', $u)) {
                throw new TypeError("Recipient datastructure is missing required fields");
            }
        }

        // $addToSet['for'] = ['$each' => $note->for];
        // unset($validated['for']);

        $result = $this->updateOne(
            ['_id' => new ObjectId($id)],
            [
                '$set' => $note,
                // '$addToSet' => $addToSet
            ],
            ['upsert' => $allowUpsert]
        );

        $upserted_id = $result->getUpsertedId();
        $recipientCount = count($note['for']);
        cobalt_log('NtfyManager', 'Notification from '.$note->from.' sent to '.$recipientCount." recipient".plural($recipientCount));
        
        if(__APP_SETTINGS__["Notifications_enable_push_notifications"] && $push_notify) {
            $this->dispatchPushNotifications($id);
        }
        
        return $upserted_id || $id;
    }

    static function getAddresseesByPermission(string|array $permissions, bool $state = true, ?array $options = null) {
        $crud = new UserCRUD();
        $users = $crud->getUsersByPermission($permissions, $state, array_merge([
            'limit' => 50,
            'projection' => ['_id' => 1]
        ],$options ?? []));
        return static::convertUserResultsToRecipientUserStructure($users);
    }

    static function convertUserResultsToRecipientUserStructure(array|Cursor $users) {
        $result = [];
        foreach($users as $u) {
            $id = null;
            if($u instanceof UserPersistance || $u instanceof BSONDocument) {
                $id = $u->_id;
            } else if ($u instanceof ObjectId) {
                $id = $u;
            }
            if($id === null) continue;
            $result[] = [
                'user' => $id,
                "seen" => false,
                "read" => false,
                'modified' => new UTCDateTime()
            ];
        }
        return $result;
    }

    public function addresseeDataStructure(&$content) {        
        $user_id = $content['for.user'];
        $content['for.user'] = [];

        foreach($user_id as $user) {
            $content['for.user'][] = [
                'id' => $user,
                'seen' => false,
                'read' => false,
            ];
        }
    }

    // public function deriveAction($content, $schema) {
        
    //     return $schema->get_action($content ?? [
    //         'path' => ,
    //     ]);
    //     // return new stdClass();
    // }

    public function renderNotification($notificationData) {
        return view($notificationData->template,['ntfy' => $notificationData]);
    }

    public function updateRecipientMeta($notificationId, $user = null, $meta) {
        if($user === null) $user = session('_id');
        $mutant = [];
        
        foreach($meta as $key => $value) {
            $mutant['for.$.' . $key] = $value;
        }

        $result = $this->updateOne(
            [
                '_id' => $this->__id($notificationId),
                'for.user' => $this->__id($user)
            ], [
                '$set' => $mutant
            ]
        );

        return $result;
    }
    
    public function addRecipient($notificationId, $user = null) {
        if($user === null) $user = session('_id');

    }

    public function removeRecipient($notificationId, $user = null) {
        if($user === null) $user = session('_id');

    }

    public function readNotificationByRouteLiteral(?string $route, ?ObjectId $user_id) {
        if(!$route) return;
        if($user_id === null) $user_id = session()['_id'];
        $result = $this->updateOne(
            [
                'action.href' => $route,
                'for.user' => $user_id,
            ], [
                '$set' => [
                    'for.$.read' => true,
                    'for.$.seen' => true
                ]
            ]
        );
        return $result->getModifiedCount();
    }



    private function getUnreadQuery($user) {
        return [
            'for.user' => $user,
            'for.read' => false
        ];
    }

    private function getReadQuery($user) {
        return [
            'for.user' => $user,
            'for.read' => true
        ];
    }
    

    private function dispatchPushNotifications($id) {
        $ntfy = $this->findOne(['_id' => $id]);
        if(!$ntfy) return;

        $users = [];
        foreach($ntfy->{'for.users'} as $u) {
            $users[] = $u->id;
        }

        $p = new PushNotifications();
        $p->push('New Notification', $ntfy->body, );
    }

    public function process_notification_queue($user = null) {
        if(!app("Mail_username") || !app("Mail_password") || !app("Mail_smtp_host")) {
            cobalt_log("process_notification_queue", "Username, password, or SMTP host was falsy. Aborting.");
            return;
        }
        $limit = new DateTime();
        $limit->add(DateInterval::createFromDateString(app("Notifications_process_queue_notes_newer_than")));
        $query = [
            'for' => [
                '$elemMatch' => [
                    'flags' => [
                        '$bitsAllClear' => NotificationSchema::NOTIFICATION_EMAIL_SENT
                    ],
                ]
            ],
            // 'sent' => ['$gte' => new UTCDateTime($limit)]
        ];
        // if($user) {
        //     if(is_string($user)) $user = new ObjectId($user);
        //     if($user instanceof UserIdResult) $user = $user->value;
        //     if($user instanceof UserIdType) $user = $user->value;
        //     if($user instanceof ObjectId) $query['for]['$elemMatch']['user'] = $user;
        // }
        $count = $this->count($query);
        print("".fmt($count, "i")." notification".plural($count)." match parameters\n");
        $notifications = $this->find($query);

        // Create a list of notes that will be sent to the user's email address
        $recipients = [];
        $user_ids = [];
        // Build our list of notifications
        foreach($notifications as $note) {
            foreach($note->for as $user) {
                if($user->flags & NotificationSchema::NOTIFICATION_EMAIL_SENT) continue;
                $id = (string)$user->user;
                $user_ids[] = $user->user;
                $this->queue_create_recipient($recipients, $id);
                $recipients[$id]['notes'] .= view($note->template->getValue(), ['ntfy' => $note,'tag' => 'a',]);
                $recipients[$id]['ids_to_update'][] = $note->_id;
            }
            print("Created queue for ".$user->user."\n");
        }
        $uc = new UserCRUD();
        $users = $uc->find(['_id' => ['$in' => $user_ids]], ['projection' => ['uname' => 1, 'fname' => 1,'lname' => 1, 'email' => 1]]);
        foreach($users as $u) {
            $this->send_notification_email($recipients[(string)$u->_id], $u);
        }
        print("Finished email queue.");
        return;
    }
    private function queue_create_recipient(&$recipients, $user) {
        if(key_exists($user, $recipients)) return;
        $recipients[$user] = [
            'notes' => '',
            'ids_to_update' => []
        ];
    }

    private function send_notification_email(array $queue, $u) {
        if(!isset($u->email)) {
            say("$u->uname does not have an email address. Skipping.","e");
            return;
        }
        print("Sending email to user: `$u->uname`\n");
        $mail = new SendMail();
        // $mail->set_from("");
        // $mail->set_vars()
        // $queue = $recipients[(string)$u->_id];
        $note_count = count($queue['ids_to_update']);
        
        $mail->set_body(view("Cobalt/Notifications/templates/admin/email.php",[
            'note_count' => $note_count,
            'notes' => $queue['notes'],
            'user' => $u,
        ]));
        $mail->send($u->email, "You have $note_count new unread notification".plural($note_count)." at ".__APP_SETTINGS__["app_name"]);
        $updated_result = $this->updateMany(
            [
                '_id' => [
                    '$in' => $queue['ids_to_update']
                ],
                'for' => [
                    '$elemMatch' => [
                        'user' => $u->_id
                    ]
                ]
            ],
            [
                '$bit' => [
                    'for.$.flags' => ['or' => NotificationSchema::NOTIFICATION_EMAIL_SENT]
                ]
            ]
        );
        $modCt = $updated_result->getModifiedCount();
        print("Updated ". fmt($modCt, "i") . " notification state".plural($modCt)." for `$u->uname`.\n");
    }
}
