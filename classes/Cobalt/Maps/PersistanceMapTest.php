<?php

namespace Cobalt\Maps;

use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\BinaryResult;
use Cobalt\SchemaPrototypes\Basic\BooleanResult;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\SchemaPrototypes\Basic\EnumResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\Compound\MarkdownResult;
use Cobalt\SchemaPrototypes\Compound\UploadImageResult;
use Cobalt\SchemaPrototypes\MapResult;

class PersistanceMapTest extends PersistanceMap {
    function __get_schema():array {
        return [
            'array' => [
                new ArrayResult,
                'default' => [
                    'test', 
                    'value1',
                    'super2',
                    'no-op'
                ],
                'valid' => [
                    'test' => 'Test Value',
                    'enum' => 'Enum',
                    'value1' => 'Value 1',
                    'super2' => 'Super 2',
                ],
            ],
            'array_each' => [
                new ArrayResult,
                'template' => "<fieldset><label>First Name</label><input name='name.first'></fieldset>
                <fieldset><label>Last Name</label><input name='name.last'></fieldset>
                <fieldset><label>Position</label><select name='position'>{{field.position.options()}}</select></fieldset>",
                'each' => [
                    'name' => [
                        new MapResult,
                        'schema' => [
                            'first' => new StringResult,
                            'last' => new StringResult
                        ]
                    ],
                    'position' => [
                        new EnumResult,
                        'valid' => [
                            'cap' => 'Captain',
                            'xo' => 'First Officer'
                        ]
                    ]
                ]
            ],
            'binary' => [
                new BinaryResult,
                'default' => 0b0101010101,
                'valid' => [
                    'Binary 1',
                    'Binary 2',
                    'Binary 4',
                    'Binary 8',
                    'Binary 16',
                    'Binary 32',
                    'Binary 64',
                    'Binary 128',
                    'Binary 256',
                    'Binary 512'
                ]
            ],
            'bool' => [
                new BooleanResult,
                'default' => true,
            ],
            'date' => [
                new DateResult,
                'from' => 'milliseconds',
                'to' => 'milliseconds'
            ],
            'submap' => [
                new MapResult,
                'schema' => [
                    'headline' => new StringResult,
                    'subheadline' => new StringResult,
                    'map' => [
                        new BinaryResult,
                        'valid' => [
                            1 => 'Test 1',
                            2 => 'Test 2',
                            4 => 'Test 4',
                            8 => 'Test 8',
                        ]
                    ],
                    'nested' => [
                        new MapResult,
                        'schema' => [
                            'data1' => [
                                new NumberResult,
                                // 'default' => 1
                            ],
                            'data2' => [
                                new BinaryResult,
                                'valid' => [
                                    1 => 'Result 0b0001',
                                    2 => 'Result 0b0010',
                                    4 => 'Result 0b0100',
                                    8 => 'Result 0b1000',
                                ]
                            ],
                            'nested2' => [
                                new MapResult,
                                'schema' => [
                                    'markdown' => [
                                        new MarkdownResult,
                                        // 'set' => function ($val, $ref) {
                                        //     update("[name='$ref->name']", ['value' => "dogbone"]);
                                        // },
                                        'filter' => function ($val) {
                                            $this->__modify("other.unspecified", "test", false);
                                            return "Trigger ". $val;
                                        },
                                        'operator' => '$push'
                                    ],
                                    'bool' => new BooleanResult,
                                ]
                            ]
                        ],
                    ],
                ]
            ]
        ];
    }
}