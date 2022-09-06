<?php

namespace Cobalt\Notifications;

class NotificationManager extends \Drivers\Database {

    public function get_collection_name() {
        return "cobaltNotifications";
    }

    public function getNotificationsForUser($user, $readStatus = true) {
        if($user === null) $user = session('_id');
        
    }

    public function renderNotification($notificationData) {
        return with($notificationData->getTemplate(),['ntfy' => $notificationData]);
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