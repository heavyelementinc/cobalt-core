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
            ]
        ];
    }

}