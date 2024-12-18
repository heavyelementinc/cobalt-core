<?php

use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Drivers\Database;

class DebugRenderer {
    function render($userInput = "Test") {
        $schema = new TestSchema();
        $schema->ingest([
            'html' => "<h1>Here's *some* markdown</h1><p>Lorem ipsum *dolor* sit **amit.**</p>",
            'number' => 3
        ]);

        add_vars([
            'doc' => $schema
        ]);

        return view("/debug/schema-debug.html");
    }
}

class TestSchema extends PersistanceMap {

    public function __set_manager(?Database $manager = null): ?Database {
        return null;
    }
    
    public function __get_schema(): array {
        return [
            'html' => [
                'type' => new StringResult,
            ],
            'number' => [
                'type' => new NumberResult,
            ],
            'selectArr' => [
                'type' => new StringResult,
                'valid' => [
                    'Option 1',
                    'Option 2',
                    'Option 3',
                ]
            ]
        ];
    }

}