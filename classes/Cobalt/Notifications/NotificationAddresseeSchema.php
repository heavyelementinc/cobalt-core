<?php

namespace Cobalt\Notifications;

use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\BooleanResult;
use Cobalt\SchemaPrototypes\DateResult;
use Cobalt\SchemaPrototypes\EnumResult;
use Cobalt\SchemaPrototypes\UserIdResult;

class NotificationAddresseeSchema extends PersistanceMap {
    public function __get_schema(): array {
        return [
            'id' => new UserIdResult,
            'state' => [
                new EnumResult,
                'valid' => [
                    'seen' => 'Seen',
                    'read' => 'Read',
                ]
            ],
            'modified' => new DateResult,
        ];
    }
}