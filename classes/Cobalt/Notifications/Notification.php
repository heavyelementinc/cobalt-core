<?php

namespace Cobalt\Notifications;

use Cobalt\PersistanceMap;
use Cobalt\SchemaPrototypes\ArrayResult;
use Cobalt\SchemaPrototypes\DateResult;
use Cobalt\SchemaPrototypes\EnumResult;
use Cobalt\SchemaPrototypes\IpResult;
use Cobalt\SchemaPrototypes\MarkdownResult;
use Cobalt\SchemaPrototypes\PersistanceMapResult;
use Cobalt\SchemaPrototypes\StringResult;
use Cobalt\SchemaPrototypes\UserIdArrayResult;
use Cobalt\SchemaPrototypes\UserIdResult;

class Notification extends PersistanceMap {

    public function __get_schema(): array {
        return [
            'from' => [
                new UserIdResult("Notifications_can_send_notification"),
                'nullable' => true
            ],
            'for' => [
                new ArrayResult,
                'each' => new NotificationAddresseeResult
            ],
            'read_status' => new ArrayResult,
            'subject' => [
                new StringResult,
                'char_limit' => 80
            ],
            'body' => [
                new MarkdownResult
            ],
            /** Automatically set by the sendNotification method */
            'action' => new NotificationActionSchema,
            'type' => [
                new EnumResult,
                'valid' => [
                    0 => "Notification"
                ],
            ],
            'sent' => [
                new DateResult,
            ],
            'ip' => [
                new IpResult,
                'nullable' => true
            ],
            'template' => [
                'get' => fn ($val) => $val ?? "/cobalt/notifications/notification-1.0.html"
            ]
            // 'token' => new StringResult,
            // 'version' => new StringResult,
        ];
    }

    function getUserIdsByUsernames() {

    }

}