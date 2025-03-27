<?php

namespace Cobalt\Notifications\Classes;

use Cobalt\Notifications\Models\NotificationSchema;
use Exceptions\HTTP\BadRequest;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

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
    
    public function sendNotification(NotificationSchema $note) {
        $id = $note->_id ?? null;

        $allowUpsert = true;
        // if($id !== null) $allowUpsert = false;
        if(!isset($note->type)) $note->type = 0;
        
        $note->ip   = $_SERVER['X-FORWARDED-FOR'] ?? $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['REMOTE_ADDR'];
        $note->sent = new UTCDateTime();
        
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
        // cobalt_log('sendNotification', 'Notification sent');
        
        // if(app("Notifications_enable_push_notifications")) {
        //     $this->dispatchPushNotifications($id);
        // }
        
        return $upserted_id || $id;
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
        $ntfy = $this->findOneAsSchema(['_id' => $id]);
        if(!$ntfy) return;

        $users = [];
        foreach($ntfy->{'for.users'} as $u) {
            $users[] = $u->id;
        }

        $p = new PushNotifications();
        $p->push('New Notification', $ntfy->body, );
    }
}
