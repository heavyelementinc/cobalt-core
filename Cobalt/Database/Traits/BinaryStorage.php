<?php

namespace Drivers;

// use Cobalt\Maps\GenericMap;
// use Cobalt\SchemaPrototypes\SchemaResult;

use Cobalt\Database\Interfaces\DbClient;
use Cobalt\Database\Interfaces\DbCollection;
use Cobalt\Database\Interfaces\DbDatabase;
use Cobalt\Database\Interfaces\DbFilesystem;
use Cobalt\Database\Interfaces\UpdateResult;
use Cobalt\DataModel\Models\FilesystemModel;
use Cobalt\DataModel\Models\ImageMetaModel;
use Cobalt\DataModel\Models\ImageModel;
use Cobalt\DataModel\Types\DateType;
use Cobalt\DataModel\Types\DictionaryType;
use Cobalt\DataModel\Types\DataModel;
use Cobalt\DataModel\Types\IdType;
use DateTime;
use Exception;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\ServiceUnavailable;
use League\ColorExtractor\ColorExtractor;
use League\ColorExtractor\Palette;
use League\ColorExtractor\Color as ColorExtractorColor;

// use MongoDB\BSON\Document;
use MikeAlmond\Color\Color;
use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;

trait BinaryStorage {
    protected string $__db;
    protected DbClient $__binaryStorageClient;
    protected DbDatabase $__binaryStorageDatabase;
    protected DbFilesystem $__binaryStorageBucket;
    protected DbCollection $__binaryStorageCollection;
    protected bool $__isBinaryStorageInitialized = false;

    final public function __upload(string $pathToFile, FilesystemModel $details, array $uploadOptions = []):IdType {
        $this->__initFS();
        
        $md5 = md5($pathToFile);
        if($md5 !== $details->meta->md5->value) throw new Exception("Failed to upload file. Hashes do not match.");

        // We want to prevent uploading the same file to the database multiple times
        // so we should just check for the md5.
        $deduplicationSearchResult = $this->__binaryStorageCollection->findOne(['$or' => [['meta.md5' => $md5], ['md5' => $md5]]]);
        if(!is_null($deduplicationSearchResult)) {
            // If it *does* exist, then we should just return the ID
            $id = new IdType();
            $id->value = $deduplicationSearchResult->_id;
            return $id;
        }

        if(!file_exists($pathToFile)) throw new ServiceUnavailable("Could not locate file.");
        $resource = fopen($pathToFile, 'r');
        if($resource == false) throw new ServiceUnavailable("Could not open file.");

        $_id = $this->__binaryStorageBucket->uploadFromStream($details->filename->value, $resource, $uploadOptions);

        $this->__binaryStorageCollection->updateOne(
            ['_id' => $_id],
            [
                '$set' => [
                    ...$details->bsonSerialize(),
                    '__pClass' => new \MongoDB\BSON\Binary($details::class, Binary::TYPE_USER_DEFINED),
                ]
            ]
        );

        $id = new IdType();
        $id->value = $_id;
        return $id;
    }

    static public function generateImageModelFromFile(string $pathToFile, string $basenameToStore, array $arbitraryData = []):ImageModel {
        $model = new ImageModel();
        $meta = getimagesize($pathToFile);
        $model->filename->value = $basenameToStore;
        $model->uplodateDate->value = new DateTime();
        $model->meta->md5->value = md5($pathToFile);
        $model->meta->mimetype->value = $meta['mime'];
        $model->details->width->value = $meta[ImageMetaModel::WIDTH];
        $model->details->height->value = $meta[ImageMetaModel::HEIGHT];
        
        $palette = Palette::fromFilename($pathToFile);
        $extractor = new ColorExtractor($palette);
        $colors = $extractor->extract(2);
        $accent = ColorExtractorColor::fromIntToHex($colors[0]);
        $secondary = ColorExtractorColor::fromIntToHex($colors[1]);

        $model->details->accent_color->value = $accent;
        $model->details->secondary_color->value = $secondary;
        $model->details->contrast_color->value = (Color::fromHex($accent)->isDark()) ? "#FFFFFF" : "#000000";

        if(!empty($arbitraryData)) $model->setValue($arbitraryData);
        
        return $model;
    }

    /**
     * Store a file in the GridFS filesystem
     * @param string $pathToFile 
     * @param string $filenameForStorage 
     * @param array $data 
     * @param array $storageOptions 
     * @return ?ObjectId
     * @throws NotFound 
     * @throws ServiceUnavailable 
     */
    final public function __store(string $pathToFile, string $filenameForStorage, $data = [], $storageOptions = []):?ObjectId {
        $this->__initFS();
        if(!file_exists($pathToFile)) throw new NotFound("File does not exist");

        $md5_sum = md5_file($pathToFile);

        $deduplicationSearchResult = $this->__binaryStorageCollection->findOne(['md5' => $md5_sum]);
        if($deduplicationSearchResult !== null) {
            return $deduplicationSearchResult['_id'];
        }

        $resource = fopen($pathToFile, 'r');
        if($resource === false) throw new ServiceUnavailable("Could not open file");

        $id = $this->__binaryStorageBucket->uploadFromStream($filenameForStorage, $resource, $storageOptions);
        $data['meta'] = $this->__getMetadata($pathToFile);
        $data['_v'] = 3;
        
        $result = $this->__binaryStorageCollection->updateOne(
            ['_id' => $id],
            ['$set' => $data]
        );
        return $id;
    }

    final public function __cleanup($query) {
        // $this->__initFS();
        // $count = $this->__collection->count($query);
        // $docs = $this->__collection->find($query);

        // $newQuery = [$query];
        // $newQuery[1]['for'] = ['$in' => []];

        // foreach($docs as $doc) {
        //     $newQuery[1]['for']['$in'][] = $doc['_id'];
        // }

        // try {
        //     $result = $this->__collection->deleteMany(['$or' => $newQuery]);
        //     $deleted = $result->getDeletedCount();
        //     header("X-Message: Cleaned up $deleted / $count orphaned uploads");
        // } catch (\Exception $e){ 
        //     header("X-Message: Failed cleanup");
        // }
    }

    final public function __updateFile(string $filename, array $data):void {
        $this->__initFS();
        $this->__binaryStorageCollection->updateOne(
            ['name' => $filename],
            ['$set' => $data]
        );
        return;
    }

    final public function __findOne(array $query = [], array $options = []):null|BSONArray|BSONDocument|Persistable{
        $this->__initFS();
        return $this->__binaryStorageCollection->findOne($query, $options);
    }

    final public function __find(array $query = [], array $options = []) {
        $this->__initFS();
        return $this->__binaryStorageCollection->find($query, $options);
    }

    final public function __count(array $query = [], array $options = []) {
        $this->__initFS();
        return $this->__binaryStorageCollection->count($query, $options);
    }

    final public function __get_uploaded_files(string|int|null $field = null, int $limit = 0):?array {
        $result = normalize_file_array();
        if($field === null) return $result;
        
        $files = [];
        foreach($result as $index => $r) {
            if($index !== $field) continue;
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
                return $this->getRasterMetadata($path_to_file, $mime_type);
            case "video":
                return $this->getVideoMetadata($path_to_file, $mime_type);
            case "audio":
                return $this->getAudioMetadata($path_to_file, $mime_type);
        }

        return ['mimetype' => $mime_type];
    }

    private function __initFS() {
        if($this->__isBinaryStorageInitialized) return;
        $this->__db = $GLOBALS['CONFIG']['database'];
        $this->__binaryStorageClient = \db_cursor('', null, true);
        $this->__binaryStorageDatabase   = $this->__binaryStorageClient->getDatabase(config()['database']);
        $this->__binaryStorageBucket     = $this->__binaryStorageDatabase->selectFilesystemBucket();
        $this->__binaryStorageCollection = $this->__binaryStorageBucket->getFilesCollection();
        $this->__isBinaryStorageInitialized = true;
    }

    public function __rename(ObjectId $id, string $name):UpdateResult {
        return $this->__binaryStorageCollection->updateOne(
            ['_id' => $id], 
            [
                '$set' => [
                    'filename' => $name
                ]
            ]
        );
    }

    public function __alt(ObjectId $id, string $name):UpdateResult {
        return $this->__binaryStorageCollection->updateOne(
            ['_id' => $id], 
            [
                '$set' => [
                    'alt' => $name
                ]
            ]
        );
    }

    private function getRasterMetadata($path_to_file, $mime_type = null) {
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
            'mimetype' => $metadata['mimetype'],
            'width'    => $metadata[0],
            'height'   => $metadata[1],
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
            'mimetype' => $mime_type,
            'width' => $info['video']['resolution_x'],
            'height' => $info['video']['resolution_y'],
            'seconds' => $info['playtime_seconds'],
            'codec' => $info['video']['fourcc_lookup'],
            'framerate' => $info['video']['framerate'],
            'rotation' => $info['video']['rotate'],
            'audio' => $info['audio'],
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
            'mimetype' => $mime_type,
            'width'    => substr((string)$attrs->width,0,-2),
            'height'   => substr((string)$attrs->height,0,-2),
        ];
    }

    private function getMimeType($path_to_file) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $path_to_file);
        finfo_close($finfo);
        return $mime_type;
    }
}
