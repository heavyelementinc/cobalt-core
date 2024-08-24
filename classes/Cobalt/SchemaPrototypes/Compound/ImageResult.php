<?php

namespace Cobalt\SchemaPrototypes\Compound;

use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\Basic\UploadResult2;
use Cobalt\SchemaPrototypes\Traits\Prototype;

class ImageResult extends UploadResult2 {
    protected $type = "image";

    #[Prototype]
    protected function field() {
        $val = $this->getValue();
        $accept = $this->getDirective('accept');
        $acceptAttr = $accept ? "accept=\"$accept\"" : "";
        return view("CRUD/fields/ImageResult.html",[
            'field'    => $this,
            'qname'    => $this->queriableName($this->name),
            'name'     => $this->name,
            'val'      => $val,
            'filename' => $val->url ?? "",
            'width'    => $val->width ?? "",
            'height'   => $val->height ?? "",
            'accent'   => $val->accent ?? "",
            // 'color'    => $val->media->meta['contrast_color'] ?? "",
            'accept'   => $acceptAttr,
            'hasThumbnail' => $this->name,
        ]);
    }

    #[Prototype]
    protected function embedEditor($embedSize, array $misc = []) {
        $delete = " delete-action=\"/api/v1/image-editor/".(string)$this->__reference->_id."/$this->name\"";
        $rename =  "rename-action=\"/api/v1/image-editor/".(string)$this->__reference->_id."/$this->name\"";
        if(!$this->getDirective("renameable")) $rename = "";
        return "<image-editor$delete $rename>".$this->embed($embedSize, $misc)."</image-editor>";
    }

    #[Prototype]
    protected function filename($size = "media") {
        switch($size) {
            case "thumb":
            case "thumbnail":
                return $this->thumb;
            default:
                return $this->url;
        }
    }

    #[Prototype]
    protected function width($size = "media") {
        switch($size) {
            case "thumb":
            case "thumbnail":
                return $this->thumb_width;
            default:
                return $this->width;
        }
    }
    
    #[Prototype]
    protected function height($size = "media") {
        switch($size) {
            case "thumb":
            case "thumbnail":
                return $this->thumb_height;
            default:
                return $this->height;
        }
    }

    #[Prototype]
    protected function accent($size = "media") {
        return $this->accent;
    }

    #[Prototype]
    protected function contrast($size = "media") {
        return $this->accent->getContrastColor();
    }

    function filter($value) {
        if(key_exists('url', $value)) {
            unset($value['url']);
            // If a file is being uploaded, we want the server to overwrite to specify a new accent color.
            unset($value['accent']);
            $result = $this->upload_filter($value);
            
            // Let's update any fields we need to change
            update("image-result[name='$this->name'] img", [
                'src' => $result['url'],
                'setAttribute' => [
                    'height' => $result['height'],
                    'width' => $result['width'],
                    'atl' => $result['alt'] ?? '',
                ]
            ]);
            update("image-result[name='$this->name'] .url-row", ['setAttribute' => ['title' => $result['url']]]);
            update("image-result[name='$this->name'] .url-row copy-span[mini]", ['value'=> $result['url']]);
            update("image-result[name='$this->name'] .url-row flex-cell", ['innerText' => $result['url']]);
            update("image-result[name='$this->name'] .width-target", ['innerText' => $result['width']]);
            update("image-result[name='$this->name'] .height-target", ['innerText' => $result['height']]);
            update("image-result[name='$this->name'] flex-row:has(.width-target)", [
                'setAttribute' => [
                    'title' => "$result[width] x $result[height]"
            ]]);


        } else {
            $map = new GenericMap([], $this->schema['schema'] ?? $this->schema ?? [], "$this->name.");
            $result = $map->__validate($value);
        }

        if(isset($result->alt)) {
            update("image-result[name='$this->name'] image-editor img", ['alt' => $result['alt'] ?? '']);
            update("image-result[name='$this->name'] .alt-text", ['value' => $result['alt']]);
        }
        if(isset($result->accent)) {
            update("image-result[name='$this->name'] .accent-target", ['value' => $result['accent']]);
        }

        return $result;
    }
}