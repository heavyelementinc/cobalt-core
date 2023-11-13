<?php

namespace Cobalt\SchemaPrototypes;

use MongoDB\BSON\Document;
use MongoDB\BSON\Persistable;
use stdClass;

class UploadResult extends SchemaResult implements Persistable{
    protected $type = "upload";

    function setValue($value):void {
        $this->value = $value;
    }

    public function embed() {
        $type = $this->value['type'];
        // if(!$type) return $this->embed_from_value($val, $field);
        $mimetype = $this->value['meta']['meta']['mimetype'] ?? $this->value['meta']['mimetype'];
        $pos = explode("/",$mimetype);
        $sub = $pos[0];
        $enc = $pos[1];
        $rt = $this->{'value'};
        if(is_array($rt)) {
            $rt = $rt[count($rt) - 1];
        }
        $w = $this->value['meta']['display_width'] ?? $this->value['meta']['width'] ?? $this->value['meta']['meta']['width'];
        $h = $this->value['meta']['display_height'] ?? $this->value['meta']['height'] ?? $this->value['meta']['meta']['height'];
        switch(strtolower($type)) {
            case "image":
                $rt = "<img src='$rt' width=\"$w\" height=\"$h\">";
                break;
            case "video":
                $rt = "<video width=\"$w\" height=\"$h\" ".$this->{'meta.controls.display'}.$this->{'meta.loop.display'}.$this->{'meta.autoplay.display'}.$this->{'meta.mute.display'}."><source src='$rt' type='$mimetype'></video>";
                break;
            case "audio":
                $rt = "<audio ".$this->{'meta.mute.display'}.$this->{'meta.loop.display'}.$this->{'meta.controls.display'}."><source src='$rt' type='$mimetype'></audio>";
                break;
            case "href":
                $fs = $this->{'meta.allowfullscreen'};
                $allow = $this->{'meta.allow'};
                $title = $this->{'meta.title'};
                $rt = "<iframe src=\"$rt\" name=\"$enc\" scrolling=\"no\" frameborder=\"0\" width=\"$w\" height=\"$h\" $fs $allow $title></iframe>";
                break;
        }

        return $rt;
    }

    
    public function bsonSerialize(): array|stdClass|Document {
        return $this->value;
    }

    public function bsonUnserialize(array $data): void {
        $this->value = $data;
    }


}