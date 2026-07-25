<?php

namespace Cobalt\DataModel\Traits;

use Cobalt\Database\Interfaces\UpdateResult;
use Cobalt\DataModel\Directives\Media\Accept;
use Cobalt\DataModel\Filters\FilterIssue;
use Cobalt\DataModel\Types\DictionaryType;
use Cobalt\DataModel\Types\Generic;
use Cobalt\DataModel\Types\ImageType;
use Drivers\BinaryStorage;
use Error;
use Exception;
use Exceptions\HTTP\BadRequest;

use MongoDB\BSON\ObjectId;
use TypeError;

/**
 * @mixin ImageType
 */
trait FileHandler {
    use BinaryStorage;

    public function getModel(): DictionaryType {
        return $this->model;
    }

    public function __filterFilename(array $arr) {
        $filename = $arr['name'];
        $addExtension = false;
        
        $this->filterFilenameDirective($arr, $filename, $addExtension);
        $this->filterObscureFilename($arr, $filename, $addExtension);
        $this->filterFilenameIsUnique($arr, $filename, $addExtension);
        
        if($addExtension) {
            $info = pathinfo($arr['name']);
            $filename = "/res/fs/$filename.".strtolower($info['extension']);
        }
        return $filename;
    }

    /**
     * 
     * @param mixed $path_to_file 
     * @return array{width:int,height:int,mimetype:string,accent_color:string,secondary_color:string,contrast_color:string}
     */
    abstract public function getMetadata(string $path_to_file):array;

    public function getMimeType($path_to_file) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $path_to_file);
        finfo_close($finfo);
        return $mime_type;
    }

    public function handleIncomingCommands(string|array $data) {
        // If we've been passed a string, then return the string
        if(!is_array($data)) return $data;

        // If we have an array, check to ensure we have enough info to complete
        // the incoming command.
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
        if(key_exists("secondary_color", $data)) {
            $this->updateMeta(new ObjectId($data['id']), 'secondary_color', $data['secondary_color']);
        }
        if(key_exists("contrast_color", $data)) {
            $this->updateMeta(new ObjectId($data['id']), 'contrast_color', $data['contrast_color']);
        }
        // Return the objectId so our objectFilter can handle things.
        return $data['id'];
    }

    public function updateMeta(ObjectId $oid, string $field, string $value):UpdateResult {

        switch($field) {
            case "filename":
                return $this->renameFile($oid, $value);
            case "alt":
                return $this->__alt($oid, $value);
            case "accent_color":
            case "secondary_color":
            case "contrast_color":
                return $this->__updateColor($oid, $value, $field);
        }
        throw new Exception("Unsupported meta update");
    }

    public function renameFile($oid, $value):UpdateResult {
        // Find the existing file in the database
        $existing = $this->__binaryStorageCollection->findOne(['_id' => $oid]);
        // Establish the old name
        $oldName = pathinfo($existing['filename']);
        $ext = Accept::toExtension($existing['meta']['mimetype']) ?? $oldName['extension'];
        $path = ($oldName['pathname']) ? $oldName['pathname'] . "/" : "";

        $search = ["/", " ", "&",];
        $replace = ["", "-", "-",];
        $replaced = str_replace($search, $replace, trim($value));
        $newName = $path. preg_replace("/([^A-Za-z0-9-])/","",$replaced) . (($ext) ? ".$ext" : "");
        $canonicalizedPath = realpath($newName);
        if(!$canonicalizedPath) $canonicalizedPath = $newName;
        
        $count = $this->__binaryStorageCollection->findOne(['filename' => $canonicalizedPath]);
        if($count && (string)$oid !== (string)$count['_id']) {
            throw new BadRequest("Cannot rename file. That filename already exists!", true);
        }
        return $this->__rename($oid, $canonicalizedPath);
    }

    public function __updateColor(ObjectId $oid, string $value, string $type):UpdateResult {
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
        if(!$result) {
            $this->filterResult->addIssue($this, "Failed to find the referenced ForeignId");
            throw new TypeError("Failed to find the referenced ForeignId");
        }

        $image_mimetype    = $result['meta']['mimetype'];
        $image_resolution = [$result['meta']['width'], $result['meta']['height']];
        $this->filter_image($image_mimetype, $image_resolution);
        return $oid;
    }

    

    public function cache_files_to_upload() {

    }
}