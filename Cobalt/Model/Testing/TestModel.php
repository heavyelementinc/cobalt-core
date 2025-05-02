<?php

namespace Cobalt\Model\Testing;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\StringType;

class TestModel extends Model {
    // public function defineController(): ModelController {
    //     return 
    // }

    public static function __getVersion(): string {
        return "1.0";
    }

    public function getCollectionName($string = null): string {
        return "modelTesting";
    }

    public function defineSchema(array $schema = []): array {
        return [
            'some_string' => [
                new StringType,
                'index' => [
                    'title' => 'Some String',
                    'order' => 1,
                    'searchable' => true,
                ]
            ],
            'other_string' => [
                new NumberType,
                'default' => 3
            ],
            'array_type' => [
                new ArrayType,
                'default' => [
                    ['field' => 1],
                    ['field' => 2]
                ],
                'each' => [
                    new ModelType,
                    'schema' => [
                        'field' => new NumberType
                    ]
                ]
            ],
            'model' => [
                new ModelType,
            ],
            'submodel' => [
                new ModelType,
                'schema' => [
                    
                    'data' => [
                        new ModelType,
                        'schema' => [
                            
                            'a_number' => [
                                new NumberType,
                                'default' => 9
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    public function prototype_test() {
        return "Here's a <em>really</em> secret message from uncharted space";
    }
}