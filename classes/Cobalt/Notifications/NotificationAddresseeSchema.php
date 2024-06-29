<?php

namespace Cobalt\Notifications;

use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\Basic\BinaryResult;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\SchemaPrototypes\Compound\UserIdResult;

class NotificationAddresseeSchema extends PersistanceMap {
    public function __get_schema(): array {
        return [
            'user' => new UserIdResult,
            'state' => [
                new BinaryResult,
                'default' => 0,
                'valid' => [
                    0b0001 => 'Seen', // User has seen this notification in the panel
                    0b0010 => 'Read', // User has visited the actionable location
                    0b0100 => 'Muted', // User has muted further notifications
                ],
            ],
            'modified' => new DateResult
        ];
    }
}