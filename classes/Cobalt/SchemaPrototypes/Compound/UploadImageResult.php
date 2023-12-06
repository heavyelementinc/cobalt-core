<?php

namespace Cobalt\SchemaPrototypes\Compound;

use Cobalt\SchemaPrototypes\Basic\UploadResult;

class UploadImageResult extends UploadResult {
    public function embed($embedSize = "media", array $misc = []) {
        $misc = array_merge(
            [
                'class' => "",
                'alt' => $this->name,
                'data' => array_merge([
                    "media-id" => $this->value["media"]['ref'],
                    "ref-id" => $this->value[$embedSize]['ref'] ?? $this->value['media']['ref']
                ], $misc['data'] ?? []),
            ],
            $misc
        );
        $class = $misc['class'];
        $data = $this->dataToEmbedTags($misc['data'] ?? []);
        $title = $misc['title'] ? "title=\"".htmlspecialchars($misc['title'])."\"" : "";
        $alt = $misc['alt'] ? "alt=\"$misc[alt]\"" : "";
        $value = $this->value[$embedSize] ?? $this->value['media'] ?? $this->schema['default'][$embedSize] ?? $this->schema['default']['media'];
        
        $type = $value['type'];
        
        $w = $value['meta']['display_width'] ?? $value['meta']['width'] ?? $value['meta']['meta']['width'];
        $h = $value['meta']['display_height'] ?? $value['meta']['height'] ?? $value['meta']['meta']['height'];

        return "<img class=\"result-embed $class\" src='$value[filename]' width=\"$w\" height=\"$h\" $alt $title>";
    }

    public function embedEditor($embedSize, array $misc = []) {
        return "<cobalt-fs>".$this->embed($embedSize, $misc)."</cobalt-fs>";
    }
}