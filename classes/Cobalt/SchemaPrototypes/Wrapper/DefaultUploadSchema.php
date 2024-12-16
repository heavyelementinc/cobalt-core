<?php

namespace Cobalt\SchemaPrototypes\Wrapper;

use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\FakeResult;
use Cobalt\SchemaPrototypes\Basic\HexColorResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\MapResult;
use MongoDB\BSON\Document;
use MongoDB\BSON\Persistable;
use stdClass;
use Validation\Exceptions\ValidationContinue;

class DefaultUploadSchema extends GenericMap {
    // function __construct($doc, $schema = []) {
    //     parent::__construct($doc, $schema);
    // }

    public function __get_schema(): array {
        return [
            'ref' => new IdResult,
            'url' => new StringResult,
            'height' => new NumberResult,
            'width' => new NumberResult,
            'accent' => new HexColorResult,
            'contrast' => [
                new FakeResult,
                'get' => fn () => $this->accent->getContrastColor(),
            ],
            'mimetype' => new StringResult,
            'thumb' => new StringResult,
            'thumb_height' => new NumberResult,
            'thumb_width' => new NumberResult,
        ];
    }
    
    
}