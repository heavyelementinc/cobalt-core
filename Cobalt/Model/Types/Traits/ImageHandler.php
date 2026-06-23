<?php

namespace Cobalt\Model\Types\Traits;

use Cobalt\Model\Filters\Issues\FilterIssue;
use Cobalt\Model\Types\ImageArrayType;
use Cobalt\Model\Types\ImageType;
use Exceptions\HTTP\BadRequest;
use MongoDB\BSON\ObjectId;

class ImageHandler {
    
    /**
     * This function takes an uploaded file and will throw an ValidationFailed or other Validation error
     * if the file does not satisfy field directive requirements
     * @param string $path 
     * @return void 
     */
    static function filter_attributes_upload(string $path) {
        $image_mimetype   = mime_content_type($path);
        $image_resolution = getimagesize($path);
        $this->filter_image($image_mimetype, $image_resolution);
    }

    static function filter_uploaded_image(array &$normalizedUploadFileArray, ImageType|ImageArrayType $instanced_field) {

    }

    static function min_max_resolution($normalizedUploadFileArray, ImageType|ImageArrayType $instanced_field) {
        
    }

    /**
     * This function verifies that the given ObjectID satisfies field directive requirements
     * @param string|ObjectId $oid 
     * @return ObjectId 
     */
    static function filter_attributes_objectid(string|ObjectId $oid):ObjectId {
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

    static function filter_image(string $mimetype, string|array $size) {
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

    static function normalize_resolution(string|array $size) {
        $res = ['width' => null, 'height' => null];
        if(is_string($size)) $size = explode("x", strtolower($size));
        if(is_array($size)) {
            $res['width'] = $size['width'] ?? trim($size[0]);
            $res['height'] = $size['height'] ?? trim($size[1]);
        }
    }
}