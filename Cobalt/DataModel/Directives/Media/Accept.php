<?php

namespace Cobalt\DataModel\Directives\Media;

use Cobalt\DataModel\Directives\Base\AbstractArrayDirective;
use TypeError;

class Accept extends AbstractArrayDirective {
    const ANY_SUPPORTED_IMAGE_TYPE = 'image/*';
    const SUPPORTED_IMAGE_MIMETYPES = [
        // 'image/apng'    => 'Animated Portable Network Graphics (APNG)',
        'image/avif '   => 'AV1 Image File Format (AVIF)',
        'image/gif'     => 'Graphics Interchange Format (GIF)',
        'image/jpeg'    => 'Joint Photographic Expert Group image (JPEG)',
        'image/png'     => 'Portable Network Graphics (PNG)',
        'image/svg+xml' => 'Scalable Vector Graphics (SVG)',
        'image/webp'    => 'Web Picture format (WEBP)',
    ];

    const SUPPORTED_VIDEO_TYPES = [

    ];

    function filter_type(string $subject_mime_type, string $type = 'image') {
        $val = $this->getValue() ?? [];
        switch($type) {
            case "image":
                $any = self::ANY_SUPPORTED_IMAGE_TYPE;
                $types = self::SUPPORTED_IMAGE_MIMETYPES;
                break;
            default:
                throw new TypeError("Unsupported type $type");
        }
        
        if(in_array($any, $val)) {
            $val += array_keys($types);
        }
        
        if(!in_array($subject_mime_type, $val)) return false;
        return true;
    }

    static function toExtension(string $mimetype, string $type = "audio"):?string {
        $ext = explode("/", $mimetype);
        $type = $ext[0];
        $ext = $ext[1];
        if(substr($ext, 0, 2) == "x-") $ext = substr($ext, 2);
        return match($ext) {
            "svg+xml" => "svg",
            "abiword" => "abw",
            "freearc" => "arc",
            "msvideo" => "avi",
            "vnd.amazon.ebook" => "azw",
            "octet-stream" => "bin",
            "bzip" => "bz",
            "bzip2" => "bz2",
            "cdf" => "cda",
            "msword" => "doc",
            "vnd.openxmlformats-officedocument.wordprocessingml.document" => "docx",
            "vnd.ms-fontobject" => "eot",
            "epub+zip" => "epub",
            "gzip" => "gz",
            "vnd.microsoft.icon" => "ico",
            "java-archive" => "jar",
            "javascript" => "js",
            "ld+json" => "jsonld",
            "mpeg" => ($type == "audio") ? "mp3" : "mpeg",
            "vnd.apple.installer+xml" => "mpkg",
            "vnd.oasis.opendocument.presentation" => "opd",
            "vnd.oasis.opendocument.spreadsheet" => "ods",
            "vnd.oasis.opendocument.text" => "odt",
            "ogg" => ($type == "audio") ? "oga" : (($type == "video") ? "ogv" : "ogx"),
            "httpd-php" => "php",
            "vnd.ms-powerpoint" => "ppt",
            "vnd.openxmlformats-officedocument.presentationml.presentation" => "pptx",
            "vnd.rar" => "rar",
            "mp2t" => "ts",
            "plain" => "txt",
            "xhtml+xml" => "xhtml",
            // "vnd.ms-excel" => "",
            default => $ext
        };
    }
}