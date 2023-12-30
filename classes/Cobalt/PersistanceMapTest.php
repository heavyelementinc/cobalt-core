<?php

namespace Cobalt;

use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\BinaryResult;
use Cobalt\SchemaPrototypes\Basic\BooleanResult;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\Compound\MarkdownResult;
use Cobalt\SchemaPrototypes\SubMapResult;

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
                new SubMapResult,
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
                        new SubMapResult,
                        'schema' => [
                            'data1' => new NumberResult,
                            'data2' => new MarkdownResult,
                        ]
                    ],
                ]
            ]
        ];
    }
}