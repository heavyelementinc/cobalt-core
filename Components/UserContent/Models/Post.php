<?php

namespace Components\UserContent\Models;

use Cobalt\Model\Types\BooleanType;
use Cobalt\Model\Types\StringType;
use Drivers\DatabaseManagement;

class Post extends Page {

    public function defineSchema(array $schema = []): array {
        $schema = parent::defineSchema($schema);
        $schema['related_title'] = [
            new StringType,
            'default' => "More Posts",
        ];

        $schema['show_main_nav'] = [
            new BooleanType,
            'default' => true,
        ];

        $schema['type'] = [
            new StringType,
            'default' => 'post',
            'set' => true
        ];

        $schema['flags']['default'] = self::FLAGS_INCLUDE_PERMALINK;
        $schema['splash_type']['default'] = self::SPLASH_POSITION_CENTER;
        $schema['include_aside']['default'] = __APP_SETTINGS__['PostPages_default_aside_visibility'];
        $schema['aside_positioning']['default'] = __APP_SETTINGS__['PostPages_default_aside_flags'];
        $schema['author']['permission'] = 'Post_allowed_author';

        return $schema;
    }

    public function __beforeMigrationUpgrade(array $doc, array &$mutated_doc, array &$update, int $count, DatabaseManagement $manager): void {
        throw new \Exception('Not implemented');
    }
}