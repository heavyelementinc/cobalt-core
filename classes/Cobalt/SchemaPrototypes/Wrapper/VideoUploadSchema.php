<?php

namespace Cobalt\SchemaPrototypes\Wrapper;

use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\Compound\UploadImageResult;

class VideoUploadSchema extends GenericMap {
    public function __get_schema(): array {
        return [
            'ref' => new IdResult,
            'poster' => [
                new UploadImageResult,
                'thumbnail' => false,
            ],
            'filename' => new StringResult,
            'height' => new NumberResult,
            'width' => new NumberResult,
            'mimetype' => new StringResult,
            'sources' => [
                new ArrayResult,
                'each' => [
                    'filename' => new StringResult,
                    'mimetype' => new StringResult,
                ]
            ]
        ];
    }
}