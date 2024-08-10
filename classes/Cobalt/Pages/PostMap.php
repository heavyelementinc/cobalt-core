<?php
namespace Cobalt\Pages;

use Cobalt\SchemaPrototypes\Basic\BooleanResult;
use Cobalt\SchemaPrototypes\Basic\EnumResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;

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

        // unset($schema['include_in_route_group'], $schema['route_group'], $schema['route_link_label'], $schema['route_order']);
        return $schema;
    }
}