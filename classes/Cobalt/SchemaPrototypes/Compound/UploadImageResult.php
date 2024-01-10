<?php

namespace Cobalt\SchemaPrototypes\Compound;

use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\Basic\UploadResult;
use Validation\Exceptions\ValidationContinue;
use Cobalt\SchemaPrototypes\Traits\Prototype;
use JsonSerializable;

class UploadImageResult extends UploadResult {
    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    /**============= PROTOTYPE METHODS =============**/
    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    
    #[Prototype]
    protected function embed($embedSize = "media", array $misc = []) {
        $misc = array_merge(
            [
                'class' => "",
                'alt' => $this->name,
                'lightbox' => false,
                'data' => [],
            ],
            $misc
        );
        $class = $misc['class'];

        $misc['data'] = array_merge([
            "media-src" => $this->value->__dataset->media->filename,
            "media-id" => $this->value->__dataset->media->ref,
            "ref-id" => $this->value->__dataset[$embedSize]["ref"] ?? $this->value->__dataset["media"]["ref"]
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
        
        $value = (isset($this->value->__dataset)) ? $this->value->__dataset[$embedSize] : $this->value[$embedSize] ?? $this->value['media'] ?? $this->schema['default'][$embedSize] ?? $this->schema['default']['media'];
        
        $type = $value['type'];
        
        $w = $value['meta']['width']  ?? $value['meta']['meta']['width'];
        $h = $value['meta']['height'] ?? $value['meta']['meta']['height'];
        
        return "<img class=\"result-embed $class\" src='$value[filename]'$lightbox width=\"$w\" height=\"$h\" ".$alt.$title.$data.">";
    }

    #[Prototype]
    protected function embedEditor($embedSize, array $misc = []) {
        return "<image-editor>".$this->embed($embedSize, $misc)."</image-editor>";
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
        return view("CRUD/fields/UploadResult.html",[
            'field' => $this,
            'qname' => $this->queriableName($this->name),
            'name'  => $this->name,
            'val'   => $val,
            'filename' => $val->__dataset['media']['filename'],
            'width'    => $val->__dataset['media']['meta']['width'],
            'height'   => $val->__dataset['media']['meta']['height'],
            'accent'   => $val->__dataset['media']['meta']['accent_color'],
            'color'    => $val->__dataset['media']['meta']['contrast_color'],
            'hasThumbnail' => $this->name,
        ]);
    }

    function setValue(mixed $value): void {
        $this->originalValue = $value;
        if(!is_array($value) && $value === $_POST[$this->name]) $value = [$this->name => $value];
        $this->value = $value;
    }

    
    public function jsonSerialize(): mixed {
        return $this->originalValue->__dataset;
    }
    // function __isset($path) {
    //     if($this->getValue()['isset'] === false) return false;
    //     return true;
    // }
}