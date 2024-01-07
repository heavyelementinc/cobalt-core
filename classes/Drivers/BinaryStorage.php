<?php

namespace Drivers;

use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\SchemaResult;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\ServiceUnavailable;
use MongoDB\BSON\Document;
use MikeAlmond\Color\Color;


trait BinaryStorage {
    protected string $__db;
    protected \MongoDB\Client $__client;
    protected \MongoDB\Database $__database;
    protected \MongoDB\GridFS\Bucket $__bucket;
    protected \MongoDB\Collection $__collection;
    protected bool $__initialized = false;

    final public function __store(string $pathToFile, string $filenameForStorage, $data = [], $storageOptions = []) {
        $this->__initFS();
        if(!file_exists($pathToFile)) throw new NotFound("File does not exist");
        $resource = fopen($pathToFile, 'r');
        if($resource === false) throw new ServiceUnavailable("Could not open file");

        $id = $this->__bucket->uploadFromStream($filenameForStorage, $resource, $storageOptions);

        $result = $this->__collection->updateOne(
            ['_id' => $id],
            ['$set' => $data]
        );
        return $id;
    }

    final public function __cleanup($query) {
        $this->__initFS();
        $count = $this->__collection->count($query);
        $docs = $this->__collection->find($query);

        $newQuery = [$query];
        $newQuery[1]['for'] = ['$in' => []];

        foreach($docs as $doc) {
            $newQuery[1]['for']['$in'][] = $doc['_id'];
        }

        try {
            $result = $this->__collection->deleteMany(['$or' => $newQuery]);
            $deleted = $result->getDeletedCount();
            header("X-Message: Cleaned up $deleted / $count orphaned uploads");
        } catch (\Exception $e){ 
            header("X-Message: Failed cleanup");
        }
    }

    final public function __updateFile(string $filename, array|GenericMap|SchemaResult|Document $data) {
        $this->__initFS();
        $this->__collection->updateOne(
            ['name' => $filename],
            ['$set' => $data]
        );
        return;
    }

    final public function __findOne(string $filename, array $options = []){
        return $this->__collection->findOne(['name' => $filename], $options);
    }

    final public function __get_uploaded_files(string|int|null $field = null, int $limit = 0):?array {
        $result = normalize_file_array();
        if($field === null) return $result;
        
        $files = [];
        foreach($result as $index => $r) {
            if($r['input_name'] !== $field) continue;
            $files[] = $r;
            $i = count($files);
            if($limit && $i >= $limit) break;
        }
        if($limit === 1) return $files[0];
        return $files;
    }

    public function __getMetadata($path_to_file): array {
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

    private function __initFS() {
        if($this->__initialized) return;
        $this->__db = $GLOBALS['CONFIG']['database'];
        $this->__client = \db_cursor('', null, true);
        $this->__database = $this->__client->{$this->__db};
        $this->__bucket = $this->__database->selectGridFSBucket();
        $this->__collection = $this->__bucket->getFilesCollection();
        $this->__initialized = true;
    }

    
    
    private function getImageMetadata($path_to_file, $mime_type = null) {
        if(!$mime_type) $mime_type = $this->getMimeType($path_to_file);
        
        $metadata = getimagesize($path_to_file);
        if(!$metadata) $metadata = [null, null, 'mimetype' => mime_content_type($path_to_file)];
        $metadata['mimetype'] = mime_content_type($path_to_file);
        // $avg = \image_average_color($path_to_file, true);
        $img = imagecreatefromstring(file_get_contents($path_to_file));
        $scaled = imagescale($img, 1, 1);
        if($scaled !== false) {
            $index = imagecolorat($scaled, 0, 0);
            $rgb = imagecolorsforindex($scaled, $index);
    
            $avg = sprintf('#%02X%02X%02X', $rgb['red'], $rgb['green'], $rgb['blue']);
        } else $avg = "#fff";

        $meta = [
            'width' => $metadata[0],
            'height' => $metadata[1],
            'mimetype' => $metadata['mimetype'],
            'accent_color' => $avg,
            'contrast_color' => (Color::fromHex($avg)->isDark()) ? "#FFFFFF" : "#000000"
        ];
        return $meta;
    }

    private function getVideoMetadata($path_to_file, $mime_type = null) {
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

    private function getAudioMetadata($path_to_file, $mime_type = null) {
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

    private function getMimeType($path_to_file) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $path_to_file);
        finfo_close($finfo);
        return $mime_type;
    }
}