<?php

namespace Cobalt\Notifications;

use Exceptions\HTTP\BadRequest;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use stdClass;

class NotificationManager extends \Drivers\Database {

    public function get_collection_name() {
        return app("Notifications_collection");
    }

    function get_schema_name($doc = []) {
        return $doc['class'] ?? '\\Cobalt\\Notifications\\Notification1_0Schema';
    }

    const QUERY_LIMIT = 20;

    public function getNotificationsForUser($user = null, $onlyUnread = true) {
        if($user === null) $user = session('_id');
        $id = new ObjectId($user);

        $query = [
            'for.user.id' => $id,
        ];

        if($onlyUnread === true) {
            $query['for.user.$.read'] = true;
        }

        $options = [
            'sort' => ['sent' => -1, 'priority' => -1],
            'limit' => $_GET['limit'] ?? $this::QUERY_LIMIT,
            'skip' => ($_GET['limit'] ?? $this::QUERY_LIMIT) * (int)$_GET['page']
        ];

        $q = $this->findAllAsSchema(
            $query,
            $options
        );

        $result = $this->updateMany($query, ['$set' => ['for.user.$.seen' => true]], $options);

        return $q;
    }

    public function getUnreadNotificationCountForUser($user = null) {
        if($user === null) $user = session('_id');
        if($user instanceof ObjectId) $id = $user;
        else $id = new ObjectId($user['_id']);

        $query = [
            'for.user.id' => $id,
            'for.user.read' => false,
        ];

        $unseen = [
            'for.user.id' => $id,
            'for.user.seen' => false,
        ];
        
        return [
            "unread" => $this->count($query,  ['limit' => $this::QUERY_LIMIT]),
            "unseen" => $this->count($unseen, ['limit' => $this::QUERY_LIMIT]),
        ];
    }

    public function setReadState($id, $user, $state, $field = "read") {
        $possibleStates = [
            'read' => true,
            'unread' => false,
        ];
        
        if(!key_exists($state, $possibleStates)) throw new BadRequest("Invalid state");
        
        $result = $this->updateOne([
            '_id' => $id,
            'for.user.id' => $user,
        ],[
            '$set' => [
                "for.user.$.$field" => $possibleStates[$state]
            ]
        ]);

        return $result->getModifiedCount();
    }

    public function setSeenState($id, $user, $state) {
        return $this->setReadState($id, $user, $state, "seen");
    }
    
    public function sendNotification($content) {

        $id = $content['_id'] ?? null;
        unset($content['_id']);

        $allowUpsert = true;
        if($id !== null) $allowUpsert = false;

        $schema = $this->get_schema_name($content);
        
        $normalizer = new $schema();

        $this->addresseeDataStructure($content);
        
        $content['from'] = $content['from'] ?? session('_id');
        $content['type']  = $normalizer->{'type'};
        $content['ip']    = $normalizer->{'ip'};
        $content['token'] = $normalizer->{'token'};
        // $content['action'] = $content['action'] ?? $this->deriveAction($validated, $normalizer);
        
        // Execute validation
        $validated = $normalizer->__validate($content);

        $validated['sent']  = new UTCDateTime();
        
        $addToSet['for.user'] = ['$each' => $validated['for.user']->__dataset];
        unset($validated['for.user']);

        $result = $this->updateOne(
            ['_id' => new ObjectId($id)],
            [
                '$set' => $validated,
                '$addToSet' => $addToSet
            ],
            ['upsert' => $allowUpsert]
        );

        $id = $result->getUpsertedId();
        
        // if(app("Notifications_enable_push_notifications")) {
        //     $this->dispatchPushNotifications($id);
        // }
        
        return $result->getModifiedCount();
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
        return view($notificationData->getTemplate(),['ntfy' => $notificationData]);
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
