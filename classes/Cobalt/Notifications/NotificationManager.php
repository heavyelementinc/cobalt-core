<?php

namespace Cobalt\Notifications;

use MongoDB\BSON\ObjectId;

class NotificationManager extends \Drivers\Database {

    public function get_collection_name() {
        return app("Notifications_collection");
    }

    function get_schema_name($doc = []) {
        return $doc['class'] ?? '\\Cobalt\\Notifications\\Notification1_0Schema.php';
    }

    const QUERY_LIMIT = 20;

    public function getNotificationsForUser($user = null, $onlyUnread = true) {
        if($user === null) $user = session('_id');
        $id = new ObjectId($user['_id']);

        $query = [
            'for.user' => $id,
        ];

        if($onlyUnread === true) {
            $query['for.read'] = ['$ne' => $id];
        }

        $query = $this->ntfy->findAllAsSchema(
            $query,
            [
                'sort' => ['sent' => -1, 'priority' => -1],
                'limit' => $_GET['limit'] ?? $this::QUERY_LIMIT,
                'skip' => ($_GET['limit'] ?? $this::QUERY_LIMIT) * (int)$_GET['page']
            ]
        );

        return $query;
    }

    public function getUnreadNotificationCountForUser($user = null) {
        if($user === null) $user = session('_id');
        $id = new ObjectId($user['_id']);
        $query = [
            'for.user' => $id,
            'for.read' => ['$ne' => $id]
        ];
        return $this->ntfy->count($query);
    }

    public function sendNotification($content) {
        $schema = $this->get_schema_name($content);
        $normalizer = new $schema();
        $validated = $normalizer->__validate($content);
        $id = $content['_id'] ?? null;
        unset($content['_id']);

        // TODO: Separate out any fields that need to be other operators
        $result = $this->updateOne(
            ['_id' => new ObjectId($id)],
            [
                '$set' => $validated
            ],
            ['upsert' => 1]
        );
        return $result->getUpdatedCount();
    }

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
    
}
