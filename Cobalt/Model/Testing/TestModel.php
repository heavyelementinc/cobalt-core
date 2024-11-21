<?php

namespace Cobalt\Model\Testing;

use Cobalt\Model\Model;
use Cobalt\Model\Types\ArrayType;
use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\StringType;

class TestModel extends Model {

    public function getCollectionName($string = null): string {
        return "modelTesting";
    }

    public function defineSchema(array $schema = []): array {
        return [
            'some_string' => new StringType,
            'other_string' => [
                new StringType,
                'default' => "Default Value"
            ],
            'array_type' => [
                new ArrayType,
                'default' => ['one', 2],
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
                            'another_model' => 1
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