<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\SchemaPrototypes\MapResult;
use Cobalt\SchemaPrototypes\Traits\ImageManipulation;
use Cobalt\SchemaPrototypes\Wrapper\DefaultUploadSchema;
use Drivers\BinaryStorage;
use Exceptions\HTTP\BadRequest;
use Validation\Exceptions\ValidationContinue;
use Validation\Exceptions\ValidationIssue;
use Cobalt\SchemaPrototypes\Traits\Prototype;

/**
 * ## `UploadResult` schema directives
 *  * `thumbnail` => [int <width>, ?int <height>] or `false` to prevent thumbnail generation
 *  * `filename`  => `false` generates unique filename, `true` preserves user-supplied filename, <string> specifies the desired filename
 *  * `required`  => `true` 
 *  * `limit`     => [int 1] the number of files to upload,
 *  * `accept`    => List of mimetypes that fieldable accepts
 *  * `formats`   => UNIMPLEMENTED!
 * @package Cobalt\SchemaPrototypes
 */
class UploadResult extends MapResult {
    use BinaryStorage;
    use ImageManipulation;
    protected $value = [];
    protected $type = "upload";
    /**
     * If filename is a string, that string will be used as the filename
     * If filename is `true`, the filename submitted by the user will be used
     * If filename is `false`, a UUID will be generated with appropriate extension
     * @var string|bool
     */
    protected string|bool $filename = "";
    
    /**+++++++++++++++++++++++++++++++++++++++++++++**/
    /**============= PROTOTYPE METHODS =============**/
    /**+++++++++++++++++++++++++++++++++++++++++++++**/

    #[Prototype]
    protected function display():string {
        return $this->embed();
    }

    #[Prototype]
    protected function embed($embedSize = "media", array $misc = []) {
        $misc = array_merge([
            'class' => "",
            'alt' => $this->name,
            'data' => array_merge([
                "media-id" => $this->value["media"]['ref'],
                "ref-id" => $this->value[$embedSize]['ref'] ?? $this->value['media']['ref']
            ], $misc['data'] ?? [])
        ], $misc);
        $class = $misc['class'];
        $data = $this->dataToEmbedTags($misc['data'] ?? []);
        $title = $misc['title'] ? "title=\"".htmlspecialchars($misc['title'])."\"" : "";
        $alt = $misc['alt'] ? "alt=\"$misc[alt]\"" : "";
        $value = $this->value[$embedSize] ?? $this->value['media'];
        // if(!$value) return "Nothing embedable";
        $type = $value['type'];
        // if(!$type) return $this->embed_from_value($val, $field);
        $mimetype = $value['meta']['meta']['mimetype'] ?? $value['meta']['mimetype'];
        $pos = explode("/",$mimetype);
        $sub = $pos[0];
        $enc = $pos[1];
        $rt = $this->{'value'};
        if(is_array($rt)) {
            $rt = $rt[count($rt) - 1];
        }
        $w = $value['meta']['display_width'] ?? $value['meta']['width'] ?? $value['meta']['meta']['width'];
        $h = $value['meta']['display_height'] ?? $value['meta']['height'] ?? $value['meta']['meta']['height'];
        switch(strtolower($type)) {
            case "video":
                return "<video class=\"$class\" width=\"$w\" height=\"$h\" ".$value['meta']['controls']['display'].$value['meta']['loop']['display'].$value['meta']['autoplay']['display'].$value['meta']['mute']['display']."><source src='$rt' type='$mimetype'></video>";
            case "audio":
                return "<audio class=\"$class\" ".$value['meta']['mute']['display'].$value['meta']['loop']['display'].$value['meta']['controls']['display']."><source src='$rt' type='$mimetype'></audio>";
            case "href":
                $fs = $value['meta']['allowfullscreen'];
                $allow = $value['meta']['allow'];
                $title = $value['meta']['title'];
                return "<iframe class=\"$class\" src=\"$rt\" name=\"$enc\" scrolling=\"no\" frameborder=\"0\" width=\"$w\" height=\"$h\" $fs $allow $title></iframe>";
            case "image":
            default:
                return "<img class=\"$class\" src=\"$value[filename]\" width=\"$w\" height=\"$h\" style=\"background-color: ".$value['meta']['accent']."\">";
        }

        return $rt;
    }

    function dataToEmbedTags($data) {
        $tags = "";
        foreach($data as $name => $value) {
            $tags .= " data-$name=\"". htmlspecialchars($value) ."\"";
        }
        return $tags;
    }

    // function setValue($value):void {
    //     if(!$value) $this->value = [];
    //     else $this->value = $value;
    // }

    /**
     * Stores the list of schema directives for this item
     * @param null|array $schema 
     * @return void 
     */
    function setSchema(?array $schema):void {
        $this->schema = array_merge($this->defaultSchemaValues(), $schema);

        if(isset($this->schema['filename'])) $this->setFilename($this->schema['filename']);
        else $this->setFilename(false);
    }

    function defaultSchemaValues(array $data = []): array {
        $defaultValue = [
            'media' => [
                'ref' => '',
                'filename' => '/core-content/img/default.jpg',
                'meta' => [
                    'heght' => 150,
                    'width' => 150,
                ]
            ],
            'isset' => false
        ];
        
        return [
            'filename'  => false, // Preserve filename? False will generate a new filename
            'thumbnail' => __APP_SETTINGS__['UploadResult_default_thumbnail'],
            'required'  => false,
            'limit'     => 1,
            'default'   => $defaultValue,
        ];
    }

    function setFilename(string|bool $filename) {
        $this->filename = $filename;
    }

    function filter($value) {
        $uploadedFiles = $this->__get_uploaded_files();
        $errors = [];
        foreach($uploadedFiles as $file) {
            if($file['input_name'] !== $this->name) continue;
            switch($file['error']) {
                case UPLOAD_ERR_NO_FILE:
                    if($this->schema['required']) throw new ValidationContinue("No file uploaded");
                    continue;
                case UPLOAD_ERR_OK:
                    continue;
                case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = "File size exceeded";
                    continue;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = "The upload was only partially recieved";
                    continue;
                case UPLOAD_ERR_NO_TMP_DIR:
                case UPLOAD_ERR_CANT_WRITE:
                case UPLOAD_ERR_EXTENSION:
                    $errors[] = "Server error";
                    continue;
            }
        }
        if(count($errors) >= 1) throw new ValidationIssue(implode("\n", $errors));
        $this->storeFile($uploadedFiles[0]);
        return $this->value;
    }

    public function storeFile(?array $result) {
        if(!$result) return;
        $filename = $this->getFilename($result);

        $map = ['mapId' => $this->__reference->_id];

        $this->__cleanup($map);
        
        $file['media'] = $this->store($filename[0], array_merge($result, $map));
        $file['media']['filename'] = "/res/fs/" . $filename[0];
        $mainResourceId = $file['media']['ref'];

        if(isset($this->schema['thumbnail'])) {
            if(mime_content_type($result['tmp_name']) !== "image/svg+xml") {
                $file['thumb'] = $this->storeThumbnail($filename[1], $result, ['for' => $mainResourceId]);;
                $file['thumb']['filename'] = "/res/fs/" . $filename[1];
            }
        }

        $result = new DefaultUploadSchema($file);
        
        $this->setValue($result);
        return $result;
    }


    private function store($filename, $data, $additional_data = []) {
        $pathToFile = $data['tmp_name'];

        if(!file_exists($pathToFile)) throw new BadRequest("File does not exist");
        $addtl = $this->getAdditionalData($filename, $data, $additional_data);
        $addtl['meta'] = $this->__getMetadata($pathToFile);
        $result = $this->__store($data['tmp_name'], $filename, $addtl);
        
        return array_merge($addtl, ['ref' => $result]);
    }

    private function storeThumbnail($filename, $data, $additional_data = []) {
        $destination = "/tmp/".uniqid("", true);

        createThumbnail(
            $data['tmp_name'], // Source
            $destination, // Destination
            $this->schema['thumbnail'][0], // Width
            $this->schema['thumbnail'][1]  // Height
        );

        $data['tmp_name'] = $destination;

        return $this->store(
            $filename, // Filename
            $data, // File information
            array_merge($additional_data, ['isThumbnail' => true])
        );
    }

    private function getAdditionalData($filename, $data, $supplemental = []):array {
        // Only the first matching directive will be used!
        $supportedFields = ['store', 'supplemental', 'additional_data'];
        $fromSchema = [];
        $found = false;

        foreach($supportedFields as $field) {
            if(!isset($this->schema[$field])) continue;
            if($found === true) throw new ValidationIssue("This schema contains more than one storage directive!");
            if(is_callable($this->schema[$field])) $fromSchema = $this->schema[$field]($filename, $data, $supplemental, $this);
            else $fromSchema = $this->schema[$field];
            $found = true;
        }

        return array_merge(
            $fromSchema, // Get additional data
            $supplemental
        );
    }
    
    private function getFilename($fileArray) {
        if(is_string($this->filename)) return $this->filename;
        if($this->filename) return $fileArray['name'];
        $unique = uniqid();
        $ext = pathinfo($fileArray['name'], PATHINFO_EXTENSION);
        $filename = hash('sha1', $unique . "-" . pathinfo($fileArray['name'], PATHINFO_BASENAME));
        return ["$filename.$ext", "$filename.thumb.$ext"];
    }

    function __toString():string {
        return $this->value->media->filename ?? $this->schema['default']['media']['filename'];
    }

    function jsonSerialize(): mixed {
        return $this->value->__dataset;
    }

}