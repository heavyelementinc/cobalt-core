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

class DefaultUploadSchema2 extends GenericMap {
    // function __construct($doc, $schema = []) {
    //     parent::__construct($doc, $schema);
    // }

    public function __get_schema(): array {
        return [
            'media' => [
                new MapResult,
                'schema' => [
                    'ref' => new IdResult,
                    'filename' => new StringResult,
                    'meta' => [
                        new MapResult,
                        'schema' => [
                            'height' => new NumberResult,
                            'width' => new NumberResult,
                            'accent_color' => new HexColorResult,
                            'contrast_color' => new HexColorResult,
                            'mimetype' => new StringResult,
                        ]
                    ],
                ]
            ],
            'thumb' => [
                new MapResult,
                'ref' => new IdResult,
                'filename' => new StringResult,
                'meta' => [
                    new MapResult,
                    'schema' => [
                        'height' => new NumberResult,
                        'width' => new NumberResult,
                        'accent_color' => new HexColorResult,
                        'contrast_color' => new HexColorResult,
                        'mimetype' => new StringResult,
                    ]
                ]
            ],
            'accent_color' => [
                new HexColorResult,
                'get' => function () {
                    return $this->{'media.meta.accent_color'};
                },
                'set' => function ($value) {
                    $this->__validatedFields['media.meta.accent_color'] = $value;
                    $color = new HexColorResult();
                    $color->setSchema([]);
                    $color->setValue($value);
                    $this->__validatedFields['media.meta.contrast_color'] = $color->getContrastColor();
                    throw new ValidationContinue("Continue");
                }
            ]
            // 'ref' => 
            // 'filename' => new StringResult,
            // 'height' => new NumberResult,
            // 'width' => new NumberResult,
            // 'mimetype' => new ArrayResult,
            // 'thumb' => new StringResult,
            // 'thumb_height' => new NumberResult,
            // 'thumb_width' => new NumberResult,
            // 'thumb_ref' => new IdResult,
            // 'accent_color' => new HexColorResult,
            // 'contrast_color' => [
            //     new FakeResult,
            //     'get' => fn () => $this->accent_color->getContrastColor(),
            // ],
        ];
    }
    
    
}