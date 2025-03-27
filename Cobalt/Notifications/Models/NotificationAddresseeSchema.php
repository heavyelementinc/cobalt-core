<?php

namespace Cobalt\Notifications\Models;

use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\Basic\BinaryResult;
use Cobalt\SchemaPrototypes\Basic\BooleanResult;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\SchemaPrototypes\Compound\UserIdResult;
use Drivers\Database;

class NotificationAddresseeSchema extends PersistanceMap {
    public function __set_manager(?Database $manager = null): ?Database {
        return null;
    }
    public function __get_schema(): array {
        return [
            'user' => new UserIdResult,
            'seen' => [new BooleanResult],
            'read' => [new BooleanResult],
            // 'state' => [
            //     new BinaryResult,
            //     'default' => 0,
            //     'valid' => [
            //         NotificationSchema::NOTIFY_SEEN => 'Seen', // User has seen this notification in the panel
            //         NotificationSchema::NOTIFY_READ => 'Read', // User has visited the actionable location
            //         NotificationSchema::NOTIFY_MUTED => 'Muted', // User has muted further notifications
            //     ],
            // ],
        ];
    }
}