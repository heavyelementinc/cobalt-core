<?php

namespace Cobalt\SchemaPrototypes\Wrapper;

use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\HexColorResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SubMap;

class DefaultUploadSchema extends SubMap {

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
            'thumb.meta.mimetype' => new StringResult

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