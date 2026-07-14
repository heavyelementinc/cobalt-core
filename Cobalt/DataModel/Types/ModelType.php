<?php

namespace Cobalt\DataModel\Types;

use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Cobalt\DataModel\Directives\Filters\Valid;
use Cobalt\DataModel\Types\StringType;
use Cobalt\DataModel\Types\Generic;
use Cobalt\DataModel\Types\DictionaryType;
use Cobalt\Model\Traits\Accessible;
use Exception;
use MongoDB\BSON\Document;
use MongoDB\BSON\Persistable;
use stdClass;
use Override;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * DataModel is the final boss of Cobalt's modeling system.
 * 
 * 
 * 
 * @package Cobalt\DataModel
 */

abstract class ModelType extends DictionaryType implements Persistable {
    use Accessible;

    /**
     * This function returns the default string. This is how the field is
     * represented in an index with no index values or in a delete call.
     * @return StringType 
     */
    abstract public function getDefaultField(): StringType;

    #[Override]
    public function bsonSerialize(): array|stdClass|Document {
        return $this->serialize();
    }

    #[Override]
    public function bsonUnserialize(array $data): void {
        $this->setValue($data);
    }

    function getTypeMap(): array {
        return [
            'typeMap' => [
                'root' => 'array',
                'document' => 'array',
                'array' => 'array'
            ]
        ];
    }

}