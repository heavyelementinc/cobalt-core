<?php

namespace Cobalt\Notifications;

use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\BinaryResult;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\SchemaPrototypes\Basic\EnumResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\Compound\IpResult;
use Cobalt\SchemaPrototypes\Compound\MarkdownResult;
use Cobalt\SchemaPrototypes\Compound\UserIdResult;
use Cobalt\SchemaPrototypes\MapResult;

class Notification extends PersistanceMap {

    public function __get_schema(): array {
        $addressee = new NotificationAddresseeSchema();
        return [
            'from' => [
                new UserIdResult("Notifications_can_send_notification"),
                'nullable' => true,
                'coalesce' => true,
                'default' => [
                    'uname' => 'Web Admin',
                ]
            ],
            'for' => [
                new ArrayResult,
                'each' => $addressee->__get_schema(),
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
            'action' => [
                new MapResult,
                'schema' => [
                    'params' => new ArrayResult,
                    'context' => new StringResult,
                    'route' => new StringResult,
                    'path' => new StringResult,
                ]
            ],
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
            ],
            // 'token' => new StringResult,
            'version' => [
                new StringResult,
                'default' => '1.0'
            ],
        ];
    }

    function getUserIdsByUsernames() {

    }

}