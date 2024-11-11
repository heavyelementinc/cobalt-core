<?php
namespace Cobalt\Pages;

use Cobalt\Posts\PostManager;
use Cobalt\SchemaPrototypes\Basic\BooleanResult;
use Cobalt\SchemaPrototypes\Basic\EnumResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Drivers\Database;

class PostMap extends PageMap {
    function __get_schema(): array {
        $schema = parent::__get_schema();
        
        $schema['related_title'] = [
            new StringResult,
            'default' => "More Posts"
        ];

        $schema['show_main_nav'] = [
            new BooleanResult,
            'default' => true,
        ];

        $schema['type'] = [
            new StringResult,
            'default' => 'post',
            'set' => false,
        ];

        $schema['splash_type']['default'] = self::SPLASH_POSITION_CENTER;
        $schema['include_aside']['default'] = __APP_SETTINGS__['PostPages_default_aside_visibility'];
        $schema['aside_positioning']['default'] = __APP_SETTINGS__['PostPages_default_aside_flags'];
        $schema['author']['permission'] = 'Post_allowed_author';

        // unset($schema['include_in_route_group'], $schema['route_group'], $schema['route_link_label'], $schema['route_order']);
        return $schema;
    }

    function __set_manager(?Database $manager = null):?Database {
        // return new PageManager(null, __APP_SETTINGS__['Posts']['collection_name']);
        return new PostManager();
    }
}