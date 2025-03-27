<?php

namespace Cobalt\Notifications\Models;

use Cobalt\Model\Model;

class Notification_1_0 extends Model {
    public function defineSchema(array $schema = []): array {
        return [
            'from' => [
                
            ]
        ];
    }

    public static function __getVersion(): string { }

    public function getCollectionName($string = null): string { }

}