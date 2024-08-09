<?php

use Cobalt\SchemaPrototypes\Basic\HexColorResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\MapResult;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Wrapper\IdResult;

class ImageResult extends SchemaResult {
    function defaultSchemaValues(array $data = []): array
    {
        $schema = [
            'ref' => [
                new IdResult,
                'nullable' => true,
            ],
            'filename' => [
                new StringResult,
                'default' => '/core-content/img/default.jpg'
            ],
            'height' => [
                new NumberResult,
                'default' => 150,
            ],
            'width' => [
                new NumberResult,
                'default' => 150
            ],
            'accent_color' => new HexColorResult,
            'contrast_color' => new HexColorResult,
        ];
        return [
            'schema' => $schema,
            'alt' => '',
            'accept' => ['jpeg','png'],
            'compression_quality' => 100,
            'thumbnail_compression' => 80,
        ];
    }
}