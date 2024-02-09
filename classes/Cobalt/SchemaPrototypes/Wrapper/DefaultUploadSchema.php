<?php

namespace Cobalt\SchemaPrototypes\Wrapper;

use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\FakeResult;
use Cobalt\SchemaPrototypes\Basic\HexColorResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
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
            'media.ref' => new IdResult,
            'media.filename' => new StringResult,
            'media.meta.height' => new NumberResult,
            'media.meta.width' => new NumberResult,
            'media.meta.accent_color' => new HexColorResult,
            'media.meta.contrast_color' => new HexColorResult,
            'media.meta.mimetype' => new StringResult,

            'thumb' => new ArrayResult,
            'thumb.ref' => new IdResult,
            'thumb.filename' => new StringResult,
            'thumb.meta.height' => new NumberResult,
            'thumb.meta.width' => new NumberResult,
            'thumb.meta.accent_color' => new HexColorResult,
            'thumb.meta.contrast_color' => new HexColorResult,
            'thumb.meta.mimetype' => new StringResult,

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