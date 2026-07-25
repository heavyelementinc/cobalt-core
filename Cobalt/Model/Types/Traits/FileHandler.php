<?php

namespace Cobalt\Model\Types\Traits;

use Cobalt\Model\Filters\Issues\FilterIssue;
use Cobalt\Model\Model;
use Cobalt\Model\Types\ImageType;
use Drivers\BinaryStorage;
use Error;
use Exception;
use Exceptions\HTTP\BadRequest;
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
        // Handle all kinds of legacy bullshit
        $id = $value['media']['ref'] ?? $value['media']['id'] ?? $value['_id'] ?? $value['id'] ?? $value;
        if($id instanceof ObjectId) {
            return $id;
        } else if(is_string($id)) {
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
        if($count = $this->__count(['filename' => $filename], [])) {
            $filename = "$filename-$count";
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
                return $this->getRasterMetadata($path_to_file, $mime_type);
            case "video":
                return $this->getVideoMetadata($path_to_file, $mime_type);
            case "audio":
                return $this->getAudioMetadata($path_to_file, $mime_type);
        }

        return ['mimetype' => $mime_type];
    }

    public function getRasterMetadata($path_to_file, $mime_type = null) {
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

    public function filterMimetypeOrFileExtension(array $fileUploadDetails, string|array $accepts) {
        if(!is_array($accepts)) $accepts = [$accepts];
        foreach($accepts as $accept) {
            if($accept[0] === ".") {
                $ext = pathinfo($fileUploadDetails['name'], PATHINFO_EXTENSION);
                if($this->filterFileExtension($ext,$accept)) return true;
            }

            $lastChar = strlen($accept);
            if($accept[$lastChar - 1] === "*") {
                if($this->filterGlobFiletype($fileUploadDetails['type'],$accept)) return true;
            }

            if($accept === $fileUploadDetails['type']) return true;
        }
        return false;
    }

    public function filterFileExtension($file_extension, $required):bool {
        // If our file extensions does not start with a period, let's do that.
        if($file_extension[0] !== ".") $file_extension = ".$file_extension";
        if($required[0] !== ".") $required = ".$required";

        if($required === $file_extension) return true;
        return false;
    }

    public function filterGlobMimetype($file_mimetype, $required):bool {
        // We should have been passed a "<type>/*" string for required
        // So we'll grab our string's length
        $length = strlen($required);
        // Let's truncate both oure required and file mimetypes to just before the "*"
        $mimetype_prefix = substr($file_mimetype, 0, $length - 1);
        $required_prefix = substr($required, 0, $length - 1);
        // Compare our truncated strings and return true if they match
        if($mimetype_prefix === $required_prefix) return true;
        return false;
    }

    
    public function handle_incoming_commands(string|array $data) {
        if(!is_array($data)) return $data;
        if(!key_exists('id', $data)) throw new Exception('Objects must contain an id!');
        
        // Only handle supported requests
        if(key_exists("filename", $data)) {
            $this->updateMeta(new ObjectId($data['id']), 'filename', $data['filename']);
        }
        if(key_exists("alt", $data)) {
            $this->updateMeta(new ObjectId($data['id']), 'alt', $data['alt']);
        }
        if(key_exists("accent_color", $data)) {
            $this->updateMeta(new ObjectId($data['id']), 'accent_color', $data['accent_color']);
        }
        if(key_exists("contrast_color", $data)) {
            $this->updateMeta(new ObjectId($data['id']), 'contrast_color', $data['contrast_color']);
        }
        return $data['id'];
    }

    public function updateMeta(ObjectId $oid, string $field, string $value) {

        switch($field) {
            case "filename":
                return $this->renameFile($oid, $value);
            case "alt":
                return $this->__alt($oid, $value);
            case "accent_color":
            case "contrast_color":
                return $this->__updateColor($oid, $value, $field);
        }
        throw new Exception("Unsupported meta update");
    }

    public function renameFile($oid, $value) {
        $search = ["/", " ", "&",];
        $replace = ["", "-", "and",];
        $existing = $this->__binaryStorageCollection->findOne(['_id' => $oid]);
        $oldName = pathinfo($existing['filename']);
        $ext = mime_content_type_to_extension($existing['meta']['mimetype']) ?? $oldName['extension'];
        $path = ($oldName['pathname']) ? $oldName['pathname'] . "/" : "";
        $newName = $path. preg_replace("/([^A-Za-z0-9-])/","",str_replace($search, $replace, trim($value))) . (($ext) ? ".$ext" : "");
        $canonicalizedPath = realpath($newName);
        if(!$canonicalizedPath) $canonicalizedPath = $newName;
        $count = $this->__binaryStorageCollection->findOne(['filename' => $canonicalizedPath]);
        if($count && (string)$oid !== (string)$count['_id']) {
            throw new BadRequest("Cannot rename file. That filename already exists!", true);
        }
        return $this->__rename($oid, $canonicalizedPath);
    }

    public function __updateColor(ObjectId $oid, string $value, string $type) {
        return $this->__binaryStorageCollection->updateOne(
            ['_id' => $oid], 
            [
                '$set' => [
                    "meta.$type" => $value
                ]
            ]
        );
    }

    
    /**
     * This function takes an uploaded file and will throw an ValidationFailed or other Validation error
     * if the file does not satisfy field directive requirements
     * @param string $path 
     * @return void 
     */
    protected function filter_attributes_upload(string $path) {
        $image_mimetype   = mime_content_type($path);
        $image_resolution = getimagesize($path);
        $this->filter_image($image_mimetype, $image_resolution);
    }

    protected string $pathToTemp = __APP_ROOT__ ."/tmp";
    
    protected function prep_for_async_storage(array &$normalizedFiles) {
        if(!file_exists($this->pathToTemp)) {
            mkdir($this->pathToTemp, 0777);
        }
        foreach($normalizedFiles as $index => $file) {
            $new_temp_name = str_replace("/tmp",$this->pathToTemp,$file['tmp_name']);
            $moveResult = move_uploaded_file($file['tmp_name'], $new_temp_name);
            if(!$moveResult) throw new Exception("Failed to move file");
            $normalizedFiles[$index]['tmp_name'] = $new_temp_name;
        }
    }

    /**
     * This function verifies that the given ObjectID satisfies field directive requirements
     * @param string|ObjectId $oid 
     * @return ObjectId 
     */
    protected function filter_attributes_objectid(string|ObjectId $oid):ObjectId {
        if($oid instanceof ObjectId === false ) {
            if(!$oid) throw new BadRequest("Malformed ObjectId");
            $oid = new ObjectId($oid);
        }

        $result = $this->__findOne(['_id' => $oid],[]);
        if(!$result) throw new FilterIssue("Failed to find the referenced ForeignId");

        $image_mimetype    = $result['meta']['mimetype'];
        $image_resolution = [$result['meta']['width'], $result['meta']['height']];
        $this->filter_image($image_mimetype, $image_resolution);
        return $oid;
    }

    protected function filter_image(string $mimetype, string|array $size) {
        $accepted = $this->directiveOrNull("accept");
        if(is_array($accepted)) {
            if(!in_array($mimetype, $accepted)) throw new FilterIssue("Invalid mimetype $mimetype");
        }

        /** @var array $max_resolution */
        $max_resolution = $this->directiveOrNull(ImageType::MAX_RESOLUTION_DIRECTIVE) ?? [];
        /** @var array $min_resolution */
        $min_resolution = $this->directiveOrNull(ImageType::MIN_RESOLUTION_DIRECTIVE) ?? [];

        $failed = 0;

        $failed_max_width  = 0b1;
        $failed_max_height = 0b01;
        $failed_min_width  = 0b001;
        $failed_min_height = 0b0001;

        if($max_resolution) {
            $max_resolution = $this->normalize_resolution($max_resolution);

            if($size[0] > $max_resolution['width']) $failed += $failed_max_width;
            if($size[1] > $max_resolution['height']) $failed += $failed_max_height;
        }

        if($min_resolution) {
            $min_resolution = $this->normalize_resolution($min_resolution);

            if($size[0] < $min_resolution['width']) $failed = $failed_min_width;
            if($size[1] < $min_resolution['height']) $failed = $failed_min_height;
        }

        if($max_resolution && $min_resolution) {
            if($max_resolution['width'] < $min_resolution['width']) throw new FilterIssue("Impossible width constraints");
            if($max_resolution['height'] < $min_resolution['height']) throw new FilterIssue("Impossible height constraints");
        }

        if($failed & $failed_max_width || $failed & $failed_max_height) {
            $policy = $this->directiveOrNull(ImageType::MIN_RESOLUTION_POLICY_DIRECTIVE);
            switch($policy) {
                case null:
                case ImageType::MIN_RESOLUTION_POLICY__ERROR:
                    throw new FilterIssue("Image is too small (must be larger than than $min_resolution[width]x$min_resolution[height])");
                    break;
                default:
                    throw new Error("Unknown policy $policy");
            }
        }
        if($failed & $failed_min_width || $failed & $failed_min_height) {
            $policy = $this->directiveOrNull(ImageType::MAX_RESOLUTION_POLICY_DIRECTIVE);
            switch($policy) {
                case null:
                case ImageType::MAX_RESOLUTION_POLICY__ERROR:
                    throw new FilterIssue("Image is too large (can be no greater than $max_resolution[width]x$max_resolution[height])");
                    break;
                default:
                    throw new Error("Unknown policy $policy");
            }
        }
    }

    protected function normalize_resolution(string|array $size) {
        $res = ['width' => null, 'height' => null];
        if(is_string($size)) $size = explode("x", strtolower($size));
        if(is_array($size)) {
            $res['width'] = $size['width'] ?? trim($size[0]);
            $res['height'] = $size['height'] ?? trim($size[1]);
        }
        return $res;
    }

    public function cache_files_to_upload() {

    }
}