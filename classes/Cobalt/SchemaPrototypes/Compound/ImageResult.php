<?php

namespace Cobalt\SchemaPrototypes\Compound;

use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\Basic\UploadResult2;
use Cobalt\SchemaPrototypes\Traits\Prototype;
use MongoDB\Model\BSONDocument;

class ImageResult extends UploadResult2 {
    protected $type = "image";
    /** @var PersistanceMap */
    protected GenericMap $__reference;

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
        $manager_name = $this->__reference->__get_manager();
        $delete = "";
        $rename = "";
        $change = "";
        $ident = "";
        // if(!$this->getDirective("renameable")) $rename = "";
        if($manager_name) {
            $ident = " data-id=\"".$this->id($embedSize)."\"";
            $url_component = (string)$this->__reference->_id."/$this->name";
            $manager_name = base64_encode(get_class($manager_name));
            $delete =  " delete-action=\"/api/v1/image-editor/$manager_name/$url_component\"";
            $rename =  " rename-action=\"/api/v1/image-editor/$manager_name/$url_component\"";
            // $change = " replace-action=\"/api/v1/image-editor/$manager_name/$url_component\"";
        }
        return "<image-editor".$ident.$delete.$rename.$change.">".$this->embed($embedSize, $misc)."</image-editor>";
        
        // $id = "";
        // $src = "";
        // $height = "";
        // $width = "";
        // $id = " data-id=\"".$this->id("media")."\"";

        // return "<image-container$id"."$src"."$height"."$width>".$this->embed($embedSize, $misc)."</image-container>";
    }

    #[Prototype]
    protected function id($size) {
        switch($size) {
            case "thumb":
            case "thumbnail":
                return $this->value->__dataset['thumb_ref'];
            default:
                return $this->value->__dataset['ref'];
        }
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
        if(!$this->accent) return null;
        return $this->accent->getContrastColor();
    }

    function filter($value) {
        if(key_exists('url', $value)) {
            unset($value['url']);
            // If a file is being uploaded, we want the server to overwrite to specify a new accent color.
            unset($value['accent']);
            $result = $this->upload_filter($value);
            
            $this->set_updates($result);
            return [
                'ref' => $result['ref'],
                'accent' => $result['accent'],
            ];
        }
        
        $map = new GenericMap([], $this->schema['schema'] ?? $this->schema ?? [], "$this->name.");
        $result = $map->__validate($value);

        $this->set_updates($result);

        foreach($result->__validatedFields as $field => $value) {
            $result->__validatedFields[$this->name.".$field"] = $value;
            unset($result->__validatedFields[$field]);
        }

        return [
            'ref' => $result['ref'],
            'accent' => $result['accent'],
        ];
    }

    function set_updates($result) {
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

        if(isset($result->alt)) {
            update("image-result[name='$this->name'] image-editor img", ['alt' => $result['alt'] ?? '']);
            update("image-result[name='$this->name'] .alt-text", ['value' => $result['alt']]);
        }
        if(isset($result->accent)) {
            update("image-result[name='$this->name'] .accent-target", ['value' => $result['accent']]);
        }
    }

    // function setValue(mixed $value): void {
    //     if($value instanceof BSONDocument) {
    //         $this->upgrade($value);
    //         return;
    //     }
    //     if(key_exists('media', $value)) {
    //         $this->upgrade($value);
    //         return;
    //     }
    //     $this->value = $value;
    // }

    // function upgrade($value) {
    //     $this->value = [
    //         'url' => $value['media']['filename'],
    //         'res' => $value['media']['res'],
    //         'height' => $value['media']['height'],
    //         'width' => $value['media']['width'],
    //         'accent' => $value['media']['accent_color'],
    //         'thumb' => $value['thumb']['filename'] ?? null
    //     ];
    //     return;
    // }
}