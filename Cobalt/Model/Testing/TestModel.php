<?php

namespace Cobalt\Model\Testing;

use Cobalt\Model\Model;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\StringType;

class TestModel extends Model {

    public function getCollectionName($string = null): string {
        return "modelTesting";
    }

    public function defineSchema(array $schema = []): array {
        return [
            'some_string' => new StringType,
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
                            'another_model' => new NumberType
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