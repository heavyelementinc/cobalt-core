<?php

use Cobalt\Maps\PersistanceMapTest;
use Cobalt\SchemaPrototypes\Basic\BinaryResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\Compound\MarkdownResult;
use Cobalt\SchemaPrototypes\SchemaResult;
use MongoDB\BSON\UTCDateTime;

class SchemaDebug {
    var $values = [];
    var $test;
    function __construct()
    {
        $this->values = [
            'array' => [
                'test',
                'value1',
                'super2',
                'no-op'
            ],
            'array_each' => [
                [
                    'name' => ['first' => 'James', 'last' => 'Kirk'],
                    'position' => "cap",
                ],
                [
                    'name' => ['first' => 'Spock', 'last' => ''],
                    'position' => "xo",
                ]
            ],
            'binary' => 0b1010101010,
            'bool' => true,
            'date' => (new UTCDateTime())->toDateTime()->format('u'),
            'submap' => [
                'headline' => 'Some headline',
                'subheadline' => 'Sub headline',
                'map' => 0b01101,
                'nested' => [
                    'data1' => 1,
                    'data2' => 0b0100,
                    'nested2' => [
                        'markdown' => "Here's a *test* of **markdown**\n\n* Test",
                        'bool' => true
                    ]
                ]
            ]
        ];
        $this->test = new PersistanceMapTest();
        $this->test->ingest($this->values);
    }

    function filter_test() {
        $this->test->__validate($_POST);
        return $this->test->__operators();
    }

    function arrayresult() {
        return view("/debug/Prototypes/array.html", [
            'type' => $this->test
        ]);
    }

    function arrayeach() {
        $result = $this->test['array_each'][0]->name->first;
        return view("/debug/Prototypes/array-each.html", [
            'type' => $this->test
        ]);
    }

    function binaryresult() {
        return view('/debug/Prototypes/binary.html', [
            'type' => $this->test
        ]);
    }

    function boolresult() {
        return view('/debug/Prototypes/bool.html', [
            'type' => $this->test
        ]);
    }

    function dateresult() {
        return view('/debug/Prototypes/date.html', [
            'type' => $this->test
        ]);
    }

    function submapresult() {
        $result = $this->test['submap']['nested']['nested2']['markdown']->md();
        return view('/debug/Prototypes/submap.html', [
            'type' => $this->test,
            'result' => $result
        ]);
    }

    
}