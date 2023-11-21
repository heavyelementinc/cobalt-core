<?php

use Cobalt\SchemaPrototypes\NumberResult;
use Cobalt\SchemaPrototypes\StringResult;

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

        return set_template("/debug/schema-debug.html");
    }
}

class TestSchema extends \Cobalt\PersistanceMap {
    public function __get_schema(): array {
        return [
            'html' => [
                'type' => new StringResult,
            ],
            'number' => [
                'type' => new NumberResult
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