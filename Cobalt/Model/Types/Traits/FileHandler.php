<?php

namespace Cobalt\Model\Types\Traits;

use Cobalt\Model\Model;
use Drivers\BinaryStorage;
use Exception;
use League\ColorExtractor\Color as ColorExtractorColor;
use League\ColorExtractor\ColorExtractor;
use League\ColorExtractor\Palette;
use MikeAlmond\Color\Color;
use MongoDB\BSON\ObjectId;

trait FileHandler {
    use BinaryStorage;

    public function getModel(): Model {
        return $this->model;
    }

    public function interpretRawValue(&$value): ?ObjectId {
        $id = $value['media']['ref'] ?? $value['media']['id'] ?? $value;
        if($id instanceof ObjectId) {
            return $id;
        } else {
            try {
                return new ObjectId($id);
            } catch (Exception $e) {
                return null;
            }
        }
        return $id;
    }

    public function storeValue(ObjectId $id): ?ObjectId {
        return $id;
    }

    function fieldItemTemplate(): string {
        return "Cobalt/Model/templates/types/gallery-item.php";
    }


    public function queryForObjects(int $limit, int $skip, string $sortField = "_id", int $sortDirection = -1, string $search = "", bool $exclude = true): array {
        $query = ['isThumbnail' => ['$exists' => false]];
        if($exclude) {
            if(is_array($this->raw)) {
                $query['_id'] = ['$nin' => $this->raw];
            } else {
                $query['_id'] = ['$ne' => $this->raw];
            }
        }
        $options = ['limit' => $limit, 'skip' => $skip * $limit, 'sort' => [$sortField => $sortDirection]];
        return [
            'cursor' => $this->__find($query, $options),
            'count' => $this->__count($query, $options)
        ];
    }

    public function filename($arr) {
        $filename = $arr['name'];
        $addExtension = false;
        if($this->hasDirective("filename")) {
            $filename = $this->getDirective("filename", $arr['name']);
            $addExtension = true;
        }
        if($this->directiveOrNull("obscure_filename") ?? true) {
            $filename = guidv4($filename);
            $addExtension = true;
        }
        if($addExtension) {
            $info = pathinfo($arr['name']);
            $filename = "/res/fs/$filename.".strtolower($info['extension']);
        }
        return $filename;
    }

    /**
     * 
     * @param mixed $file_array 
     * @return void 
     */
    public function getMetadata($path_to_file): array {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $path_to_file);
        finfo_close($finfo);
        $type = explode("/",$mime_type);

        switch($type[0]) {
            case ($mime_type === "image/svg+xml"):
                return $this->getSVGMetadata($path_to_file, $mime_type);
            case "image":
                return $this->getImageMetadata($path_to_file, $mime_type);
            case "video":
                return $this->getVideoMetadata($path_to_file, $mime_type);
            case "audio":
                return $this->getAudioMetadata($path_to_file, $mime_type);
        }

        return ['mimetype' => $mime_type];
    }

    public function getImageMetadata($path_to_file, $mime_type = null) {
        if(!$mime_type) $mime_type = $this->getMimeType($path_to_file);
        
        $metadata = getimagesize($path_to_file);
        if(!$metadata) $metadata = [null, null, 'mimetype' => mime_content_type($path_to_file)];
        $metadata['mimetype'] = mime_content_type($path_to_file);

        $palette = Palette::fromFilename($path_to_file);
        $extractor = new ColorExtractor($palette);
        $colors = $extractor->extract(2);
        $accent = ColorExtractorColor::fromIntToHex($colors[0]);
        $secondary = ColorExtractorColor::fromIntToHex($colors[1]);
        
        $meta = [
            'width' => $metadata[0],
            'height' => $metadata[1],
            'mimetype' => $metadata['mimetype'],
            'accent_color' => $accent,
            'secondary_color' => $secondary,
            'contrast_color' => (Color::fromHex($accent)->isDark()) ? "#FFFFFF" : "#000000"
        ];
        return $meta;
    }

    public function getVideoMetadata($path_to_file, $mime_type = null) {
        if(!$mime_type) $mime_type = $this->getMimeType($path_to_file);

        $id3 = new \getID3();
        $info = $id3->analyze($path_to_file);
        
        $meta = [
            'width' => $info['video']['resolution_x'],
            'height' => $info['video']['resolution_y'],
            'seconds' => $info['playtime_seconds'],
            'codec' => $info['video']['fourcc_lookup'],
            'framerate' => $info['video']['framerate'],
            'rotation' => $info['video']['rotate'],
            'audio' => $info['audio'],
            'mimetype' => $mime_type,
        ];

        return $meta;
    }

    public function getAudioMetadata($path_to_file, $mime_type = null) {
        if(!$mime_type) $mime_type = $this->getMimeType($path_to_file);
        
        $id3 = new \getID3();
        $info = $id3->analyze($path_to_file);
        
        $meta = $info['audio'];
        $meta['mimetype'] = $mime_type;
        $meta['seconds'] = $info['playtime_seconds'];
        return $meta;
    }

    private function getSVGMetadata($path_to_file, $mime_type = null) {
        if(!$mime_type) $mime_type = $this->getMimeType($path_to_file);
        
        $xml = simplexml_load_file($path_to_file);
        $attrs = $xml->attributes();

        return [
            'width'    => substr((string)$attrs->width,0,-2),
            'height'   => substr((string)$attrs->height,0,-2),
            'mimetype' => $mime_type
        ];
    }

    public function getMimeType($path_to_file) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $path_to_file);
        finfo_close($finfo);
        return $mime_type;
    }

    
}