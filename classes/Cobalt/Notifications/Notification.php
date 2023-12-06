<?php

namespace Cobalt\Notifications;

use Cobalt\PersistanceMap;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\SchemaPrototypes\Basic\EnumResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\Compound\IpResult;
use Cobalt\SchemaPrototypes\Compound\MarkdownResult;
use Cobalt\SchemaPrototypes\Compound\UserIdResult;

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