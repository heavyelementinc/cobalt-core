<?php

namespace Cobalt\SchemaPrototypes\Compound;

use ArrayAccess;
use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\Basic\HexColorResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\Basic\UploadResult;
use Cobalt\SchemaPrototypes\MapResult;
use Validation\Exceptions\ValidationContinue;
use Cobalt\SchemaPrototypes\Traits\Prototype;
use Cobalt\SchemaPrototypes\Wrapper\DefaultUploadSchema;
use Cobalt\SchemaPrototypes\Wrapper\IdResult;
use JsonSerializable;
use MongoDB\BSON\Document;
use MongoDB\BSON\Persistable;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use stdClass;

/**
 * @deprecated Use ImageResult instead
 * @package Cobalt\SchemaPrototypes\Compound
 */
class UploadImageResult extends UploadResult implements Persistable, ArrayAccess{

    function defaultSchemaValues(array $data = []): array {
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
        ];
        return [
            'schema' => [
                'media' => [
                    new MapResult,
                    'schema' => $schema,
                ],
                'thumb' => [
                    new MapResult,
                    'schema' => $schema,
                ],
                'alt' => new StringResult,
                'accent_color' => new HexColorResult,
                'contrast_color' => new HexColorResult,
            ],
            'isset' => false
        ];
    }

    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    /**============= PROTOTYPE METHODS =============**/
    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    
    #[Prototype]
    protected function embed($embedSize = "media", array $misc = []) {
        $misc = array_merge(
            [
                'class' => "",
                'alt' => $this->getDirective('alt') ?? $this->name,
                'lightbox' => false,
                'data' => [],
            ],
            $misc
        );
        $class = $misc['class'];

        $misc['data'] = array_merge([
            "media-src" => $this->value->media->filename,
            "media-id" => $this->value->media->ref,
            "ref-id" => $this->value->{$embedSize}->ref ?? $this->value->media->ref
        ], $misc['data'] ?? []);

        $lightbox = "";
        if($misc['lightbox']) {
            $lightboxGroup = ($misc['lightbox']) ? ",true" : ",false";
            $misc['data']['group'] = $misc['lightbox'];
            $lightbox = " onclick=\"shadowbox(this$lightboxGroup)\"";
        }

        $data = $this->dataToEmbedTags($misc['data'] ?? []);
        $title = $misc['title'] ? " title=\"".htmlspecialchars($misc['title'])."\"" : "";
        $alt = $misc['alt'] ? " alt=\"$misc[alt]\"" : "";
        
        $value = (isset($this->value)) ? $this->value[$embedSize] : $this->value[$embedSize] ?? $this->value['media'] ?? $this->schema['default'][$embedSize] ?? $this->schema['default']['media'];
        
        $type = $value->type;
        
        $w = $value->meta->width  ?? $value->meta->meta->width;
        $h = $value->meta->height ?? $value->meta->meta->height;
        
        return "<img class=\"result-embed $class\" src='$value->filename'$lightbox width=\"$w\" height=\"$h\" ".$alt.$title.$data.">";
    }

    #[Prototype]
    protected function embedEditor($embedSize, array $misc = []) {
        return "<image-editor>".$this->embed($embedSize, $misc)."</image-editor>";
    }

    #[Prototype]
    protected function ref($type = "media") {
        return $this->value->__dataset[$type]['ref'];
    }

    #[Prototype]
    protected function filename($type = "media") {
        return $this->value->{$type}->filename;
    }

    #[Prototype]
    protected function height($type = "media") {
        return $this->value->{$type}->meta->height;
    }

    #[Prototype]
    protected function width($type = "media") {
        return $this->value->{$type}->meta->width;
    }

    #[Prototype]
    protected function accent($type = "media") {
        return $this->value->{$type}->meta->accent_color;
    }

    #[Prototype]
    protected function contrast($type = "media") {
        return $this->value->{$type}->meta->contrast_color;
    }

    #[Prototype]
    protected function mimetype($type = "media") {
        return $this->value->{$type}->meta->mimetype;
    }

    function filter($value) {
        // Get the uploaded file(s) and store it in $result
        $result = parent::filter(null);
        $targetName = "." . $this->queriableName($this->name) . "-upload-target";
        update("$targetName img.result-embed", ['src' => $result['media']['filename']]);
        update("$targetName .filename-target", ['value' => $result['media']['filename']]);
        update("$targetName .width-target", ['innerText' => $result['media']['meta']['width']]);
        update("$targetName .height-target", ['innerText' => $result['media']['meta']['height']]);
        update("$targetName .accent-target", ['value' =>    $result['media']['meta']['accent_color']]);
        update("$targetName .contrast-target", ['value' => $result['media']['meta']['contrast_color']]);
        return $result;
    }

    #[Prototype]
    protected function field() {
        $val = $this->getValue();
        $accept = $this->getDirective('accept');
        $acceptAttr = $accept ? "accept=\"$accept\"" : "";
        return view("CRUD/fields/UploadResult.html",[
            'field'    => $this,
            'qname'    => $this->queriableName($this->name),
            'name'     => $this->name,
            'val'      => $val,
            'filename' => $val->media->filename ?? "",
            'width'    => $val->media->meta['width'] ?? "",
            'height'   => $val->media->meta['height'] ?? "",
            'accent'   => $val->media->meta['accent_color'] ?? "",
            'color'    => $val->media->meta['contrast_color'] ?? "",
            'accept'   => $acceptAttr,
            'hasThumbnail' => $this->name,
        ]);
    }

    function setValue(mixed $value): void {
        $this->originalValue = $value;

        switch(gettype($value)) {
            case "string":
                $this->value = new DefaultUploadSchema($value, $this->schema);
                break;
            case "array":
            case $value instanceof BSONDocument:
            case $value instanceof BSONArray:
            case $value instanceof stdClass:
                $this->value = new DefaultUploadSchema($value);
                break;
            default:
                $this->value = $value;
        }
        // if(gettype($value) === 'string') {
        //     $value = [];
        // }
        // if(gettype($value) === "string") {
        //     $this->value = new DefaultUploadSchema($value, $this->schema);
        // } else {
        //     $this->value = $value;
        // }
    }

    
    public function jsonSerialize(): mixed {
        return $this->originalValue->__dataset;
    }
    // function __isset($path) {
    //     if($this->getValue()['isset'] === false) return false;
    //     return true;
    // }

    
    public function bsonSerialize(): array|stdClass|Document {
        return $this->value->__dataset;
    }

    public function bsonUnserialize(array $data): void {
        $this->value = new DefaultUploadSchema($data);
    }

    public function get_image_result_format(): array {
        $model = [
            'ref' => $this->value->__dataset['media']['ref'],
            'url' => $this->value->__dataset['media']['filename'],
            'mimetype' => $this->value->__dataset['media']['meta']['mimetype'],
            'height' => $this->value->__dataset['media']['meta']['height'],
            'width' => $this->value->__dataset['media']['meta']['width'],
            'accent' => $this->value->__dataset['media']['meta']['accent_color'],
        ];
        if(key_exists('thumb', $this->value->__dataset)) {
            $model['thumb'] = $this->value->__dataset['thumb']['filename'];
            $model['thumb_width'] = $this->value->__dataset['thumb']['meta']['height'];
            $model['thumb_width'] = $this->value->__dataset['thumb']['meta']['width'];
        }
        return $model;
    }

    public function offsetExists(mixed $offset): bool {
        return isset($this->value[$offset]);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->value[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->value[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->value[$offset]);
    }
    
}