<?php

namespace Drivers;

use Exception;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\RangeNotSatisfiable;
use MongoDB\GridFS\Bucket;

class FileSystem {
    // public $db = __APP_SETTINGS__['database'];
    // public $bucket;
    protected $db = null;
    // protected $client = null;
    // protected $database = null;
    // protected $collection = null;
    protected \MongoDB\Client $client;
    protected \MongoDB\Database $database;
    protected \MongoDB\GridFS\Bucket $bucket;
    protected \MongoDB\Collection $collection;

    function __construct($database = null) {
        if ($database !== null) $this->db = $database;
        // $this->client = new \MongoDB\Client($GLOBALS['config']['server_address']);
        $this->db = $GLOBALS['CONFIG']['database'];
        $this->client = \db_cursor('', null, true);
        $this->database = $this->client->{$this->db};
        $this->bucket = $this->database->selectGridFSBucket();
        $this->collection = $this->database->{'fs.files'};
    }


    /**
     * Downloads a file from GridFS to the client making the request
     * 
     * @todo fully support Range headers (only partially implemented, here)
     * @param string $filename 
     * @param array $options 
     * @return never Creating a download for the client will exit this application!
     */
    final public function download(string $filename, $options = ['revision' => -1]): never {
        ob_clean();
        try{
            $stream = $this->getStream("/".$filename, $options);
        } catch(Exception $e) {
            try {
                $stream = $this->getStream($filename, $options);
            } catch(Exception $e) {
                throw new NotFound("Not found");
            }
        }
        $metadata = $this->bucket->getFileDocumentForStream($stream);
        
        $headers = getallheaders();

        header("ETag: $metadata->md5");
        $this->partial_content($stream, $metadata, $headers);

        $mime = mime_content_type($stream);
        header("Content-Type: $mime");
        header("Content-Length: $metadata->length");
        // if(isset($_GET['nocache'])) header("Cache-Control: no-cache");

        fpassthru($stream);

        exit;
    }

    final public function getBucket(): Bucket {
        return $this->bucket;
    }

    final public function count(array $filter) {
        return $this->collection->count($filter);
    }

    private function partial_content(&$stream, $metadata, $headers) {
        if(isset($headers['Range'])) {
            if(isset($headers['If-Range'])) {
                if(strtotime($headers['If-Range']) * 1000 < $metadata->uploadDate->milliseconds) return;
            }
            header("HTTP/1.1 206 Partial Content");
            $range = $this->parse_range($headers['Range']);
            if(fseek($stream,(int)$range[0]) === -1) throw new RangeNotSatisfiable("There was an issue");
            header("Content-Range: bytes $range[0]-$metadata->length/$metadata->length");
        } else {
            $range = null;
        }
        return $range;
    }


    /**
     * 
     * @param string|array $file_data 
     * @param int $key 
     * @param mixed $arbitrary_data 
     * @return void 
     * @throws Exception 
     */
    final public function upload(string|array $file_data, $key = 0, $arbitrary_data = null) {
        $type = gettype($file_data);
        if($type === "string") $file_array = $this->path_or_key($file_data, $key);
        else $file_array = $file_data;
        $farray = $this->validate_file_array($file_array);
        // Let's prevent duplication
        $md5 = md5_file($file_data['tmp_name']);
        $deduplication_search_result = $this->findOne(['md5' => $md5]);
        if($deduplication_search_result !== null) {
            $id = $deduplication_search_result->_id;
            // $setQuery = ['$addToSet' => ['filename' => $farray['name']]];
            // if(is_string($deduplication_search_result->filename)) $setQuery = ['$set' => ['filename' => array_unique([$deduplication_search_result->filename, $farray['name']])]];
            // $this->updateOne(['_id' => $id], $setQuery);
            return $id;
        }
        // else {
            $resource = fopen($farray['tmp_name'], 'r');
            if($resource === false) throw new \Exceptions\HTTP\ServiceUnavailable("The upload returned an unexpected error");
            $id = $this->bucket->uploadFromStream($farray['name'], $resource);
        // }

        if(is_array($arbitrary_data)) {
            $collection = $this->bucket->getFilesCollection();
            $result = $collection->updateOne(
                ['_id' => $id],
                ['$set' => $arbitrary_data]
            );
            if($result->getModifiedCount() === 0) throw new \Exception("Modification of document failed");
        }
        
        return $id;
    }

    /** Will return a stream. To send the file to the client
     * @return resource File stream
     */
    final public function getStream(string $filename, $options = ['revision' => -1]) {
        return $this->bucket->openDownloadStreamByName($filename, $options);
    }

    final public function find($filter = [], array $options = [], $thumbnail = false) {
        if($thumbnail === false) array_merge(['isThumbnail' => ['$exists' => false]],$filter);
        $options = array_merge(['sort' => ['order' => 1]],$options ?? []);
        return $this->bucket->find($filter,$options);
    }


    final public function findByFilename($filename, $filter = [], array $options = []) {
        return $this->bucket->findOne(
            array_merge(
                ['name' => $filename],
                $filter
            ),
            $options
        );
    }

    final public function findOne($filter = [], array $options = []) {
        return $this->bucket->findOne($filter, $options);
    }

    final public function findMany($filter = [], array $options = []) {
        return $this->bucket->find($filter, $options);
    }

    final public function updateOne($filter, array $options = []) {
        return $this->collection->updateOne($filter, $options);
    }

    final public function updateMany($filter, array $options = []) {
        return $this->collection->updateMany($filter, $options);
    }

    final public function rename($id, string $newName) {
        return $this->bucket->rename($id, $newName);
    }

    final public function delete($id) {
        return $this->bucket->delete($id);
    }

    final protected function path_or_key($value, $key = 0) {
        if(gettype($value) === "string") {
            return [
                'name' => pathinfo($value,PATHINFO_BASENAME),
                'tmp_name' => $value
            ];
        }

        if(!isset($value['tmp_name'])) throw new Exception("No path to file");

        if(isset($value['tmp_name'][$key])) return [
            'name' => $value['name'][$key],
            'tmp_name' => $value['tmp_name'][$key]
        ];
        elseif (!file_exists($value)) throw new Exception("No file found");
        
        return $value;
    }

    final protected function validate_file_array(array $array) {
        if(!isset($array['name'])) throw new Exception("No name specified");
        if(!is_string($array['name'])) throw new Exception("'name' must be a string");
        
        if(!isset($array['tmp_name'])) $array['tmp_name'] = $array['name'];
        if(!is_string($array['tmp_name'])) throw new Exception("The tmp_name must be a string");
        // if(!file_exists($array(['tmp_name']))) throw new Exception("File does not exist");
        
        return $array;
    }

    final protected function parse_range($range){
        $bytes = str_replace("bytes=","",$range);
        $arr = explode("-",$bytes);
        if(!is_numeric($arr[0])) throw new RangeNotSatisfiable("Malformed range header");
        return $arr;
    }
    
}
