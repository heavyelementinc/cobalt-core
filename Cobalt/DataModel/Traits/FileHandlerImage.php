<?php

namespace Cobalt\DataModel\Traits;

use Cobalt\DataModel\Directives\Image\Resolution;
use Cobalt\DataModel\Directives\Media\ResolutionConstraints;
use League\ColorExtractor\Color as ColorExtractorColor;
use League\ColorExtractor\ColorExtractor;
use League\ColorExtractor\Palette;
use MikeAlmond\Color\Color;
use Cobalt\DataModel\Types\Generic;

/**
 * @mixin Generic
 */
trait FileHandlerImage {
    use FileHandlerGeneric;

    protected function filter_preserve_exif_data(array &$toValidate, string &$filename, bool &$addExtension) {
        $preserveExifData = $this->directives->preserve_exif_data?->value ?? false;
        if($preserveExifData == true) return;
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
        
        return [
            'width' => $metadata[0],
            'height' => $metadata[1],
            'mimetype' => $metadata['mimetype'],
            'accent_color' => $accent,
            'secondary_color' => $secondary,
            'contrast_color' => (Color::fromHex($accent)->isDark()) ? "#FFFFFF" : "#000000"
        ];
    }

    private function getSVGMetadata($path_to_file, $mime_type = null) {
        if(!$mime_type) $mime_type = $this->getMimeType($path_to_file);
        
        $xml = simplexml_load_file($path_to_file);
        $attrs = $xml->attributes();

        return [
            'width'    => substr((string)$attrs->width,0,-2),
            'height'   => substr((string)$attrs->height,0,-2),
            'mimetype' => $mime_type,
        ];
    }


    protected function filter_resolution(string $filename, int $width, int $height) {
        // $accepted = $this->directives->accept?->value ?? [];
        // if(!in_array($mimetype, $accepted)) return $this->filterResult->addIssue($this, "Invalid mimetype $mimetype");

        $policy = $this->directives->resolution ?? false;
        if($policy) {
            $policy->filter($filename, $width, $height);
        }
    }

}