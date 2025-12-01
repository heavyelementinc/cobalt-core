<?php
namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;
use Cobalt\Model\Filters\Issues\FilterIssue;
use Cobalt\Model\Model;
use Cobalt\Model\Types\Abstracts\ForeignId;
use Cobalt\Model\Types\Traits\FileHandler;
use Error;
use Exceptions\HTTP\BadRequest;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;

class ImageType extends ForeignId {
    use FileHandler;
    
    /** Accept must return an array of valid mimetypes */
    const ACCEPT_DIRECTIVE = "accept";
    const ACCEPT_FAIL_POLICY__ERROR = 0;

    /** May return an Array<int> with no keys, [width, height]
     * OR return a string "widthxheight"
     */
    const MAX_RESOLUTION_DIRECTIVE = "max_resolution";
    const MAX_RESOLUTION_POLICY_DIRECTIVE = "max_policy";
    const MAX_RESOLUTION_POLICY__ERROR = 0;
    const MAX_RESOLUTION_POLICY__SCALE = 1;

    const MIN_RESOLUTION_DIRECTIVE = "min_resolution";
    const MIN_RESOLUTION_POLICY_DIRECTIVE = "min_policy";
    const MIN_RESOLUTION_POLICY__ERROR = 0;
    const MIN_RESOLUTION_POLICY__SCALE = 1;

    public function runJoinQuery(Model $model, ?ObjectId $id): null|BSONArray|BSONDocument|Persistable {
        return $this->__findOne(['_id' => $id]);
    }

    function filter($oid) {
        $filesKey = $this->{MODEL_RESERVERED_FIELD__FIELDNAME};
        if($oid === '$_FILES_$' && key_exists($filesKey, $_FILES)) {
            $files = normalize_uploaded_files($_FILES);
            $count = count($files[$filesKey]);
            if($count == 0 || $count >= 2) throw new BadRequest("Too many images uploaded for $filesKey");

            $arr = $files[$filesKey][0];

            $this->filter_attributes_upload($arr['tmp_name']);

            $filename = $this->filename($arr);
            $oid = $this->__store($arr['tmp_name'], $filename);
        } else {
            $this->filter_attributes_objectid($oid);
        }
        return parent::filter($oid);
    }

    protected function filter_attributes_upload(string $path) {
        $image_mimetype   = mime_content_type($path);
        $image_resolution = getimagesize($path);
        $this->filter_image($image_mimetype, $image_resolution);
    }

    protected function filter_attributes_objectid(string|ObjectId $oid) {
        if($oid instanceof ObjectId === false ) {
            if(!$oid) throw new BadRequest("Malformed ObjectId");
            $oid = new ObjectId($oid);
        }

        $result = $this->__find(['_id' => $oid],[]);
        if(!$result) throw new FilterIssue("Failed to find the referenced ForeignId");

        $image_mimetype = $result->meta->mimetype->value;
        $image_resolution = [$result->meta->width->value, $result->meta->height->value];
        $this->filter_image($image_mimetype, $image_resolution);

    }

    protected function filter_image(string $mimetype, string|array $size) {
        $accepted = $this->directiveOrNull("accept");
        if(is_array($accepted)) {
            if(!in_array($mimetype, $accepted)) throw new FilterIssue("Invalid mimetype $mimetype");
        }

        $max_resolution = $this->directiveOrNull(self::MAX_RESOLUTION_DIRECTIVE);
        $min_resolution = $this->directiveOrNull(self::MIN_RESOLUTION_DIRECTIVE);

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
            $policy = $this->directiveOrNull(self::MIN_RESOLUTION_POLICY_DIRECTIVE);
            switch($policy) {
                case null:
                case self::MIN_RESOLUTION_POLICY__ERROR:
                    throw new FilterIssue("Image is too small (must be larger than than $min_resolution[width]x$min_resolution[height])");
                    break;
                default:
                    throw new Error("Unknown policy $policy");
            }
        }
        if($failed & $failed_min_width || $failed & $failed_min_height) {
            $policy = $this->directiveOrNull(self::MAX_RESOLUTION_POLICY_DIRECTIVE);
            switch($policy) {
                case null:
                case self::MAX_RESOLUTION_POLICY__ERROR:
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
    }

    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        return parent::field($class, $misc, $tag ?? "file-id");
    }

    #[Prototype]
    protected function getLabel($includeHtml = true, $small_text = ""): string {
        return parent::getLabel($includeHtml, $small_text);
    }

    // public function initDirectives(): array {
    //     return [
    //         // 'operator' => function (&$operators, &$field, &$details) {
    //         //     if($this->operator === '$set') {
    //         //         $operators[$this->operator][$field] = $details;
    //         //         return;
    //         //     }
    //         //     $operators[$this->operator][$this->{MODEL_RESERVERED_FIELD__FIELDNAME}] = ['$each' => $details];
    //         // },
    //         'schema' => [
    //             // $schema
    //             'chunkSize' => new NumberType,
    //             'filename' => new StringType,
    //             'length' => new NumberType,
    //             'uploadDate' => new DateType,
    //             'md5' => new StringType,
    //             '_v' => new NumberType,
    //             'meta' => [
    //                 new ModelType,
    //                 'schema' => [
    //                     'width' => new NumberType,
    //                     'height' => new NumberType,
    //                     'mimetype' => new StringType,
    //                     'accent_color' => new HexColorType,
    //                     'contrast_color' => new HexColorType,
    //                 ]
    //             ]
    //         ]
    //     ];
    // }
}