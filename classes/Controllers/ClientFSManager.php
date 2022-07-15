<?php
namespace Controllers;
require_once __ENV_ROOT__ . "/globals/image_handling.php";

use Drivers\FileSystem;
use Exception;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;

trait ClientFSManager {
    public $fs = null;
    
    function __construct() {
        $this->fs = new FileSystem();
    }


    public function download($filename) {
        $this->fs->download($filename);
    }

    public function delete($id) {
        $_id = new \MongoDB\BSON\ObjectId($id);
        $result = $this->fs->findOne(["_id" => $_id]);
        if($result === null) throw new NotFound("That file was not found");
        confirm("Are you sure you want to delete <strong>" . htmlspecialchars($result->filename) . "</strong>?",[]);

        if($result->thumbnail_id) $this->fs->delete($result->thumbnail_id);
        $result = $this->fs->delete($_id);

        return (string)$_id;
    }

    /**
     * 
     * @param string|int $key The key of the $_FILES field
     * @param int $index The index of $_FILES to use
     * @return array 
     */
    private function clientUploadFile($key, $index = null, $arbitrary_data = null, $files = null):array {
        if(!$files) $files = $_FILES;
        if(empty($files)) throw new BadRequest("No files were uploaded");
        
        $file_array = [];
        
        if(!key_exists($key,$files)) throw new \Exception("The key does not exist");
        
        $file = $files[$key];
        
        if(!isset($file['tmp_name'])) throw new \Exception("There is no tmp_name");
        if(!is_array($file['tmp_name'])) $file_array = $file;
        else $file_array = [
            'name' => $file['name'][$index],
            'tmp_name' => $file['tmp_name'][$index],
        ];

        $thumb_id = (string)$this->fs->upload($file_array,$index,$arbitrary_data);

        return ['_id' => $thumb_id, 'name' => $file_array['name']];
    }

    private function clientUploadFiles($key, $arbitrary_data = null, $files = null) {
        if(!$files) $files = $_FILES;
        if(empty($files)) throw new BadRequest("No files were uploaded");
        if(empty($files[$key])) throw new BadRequest("No files were uploaded");
        $ids = [];
        foreach($files[$key]['tmp_name'] as $i => $file) {
            $ids = array_merge($ids,$this->clientUploadFile($key,$i,$arbitrary_data));
        }
        return $ids;
    }

    private $thumbnail_suffix = "thumb";

    /**
     * Upload a file
     * @param mixed $key the key in $_FILES to be uploaded
     * @param mixed $index  the index in $_FILES[$key] to be uploaded
     * @param mixed $thumbnail_x  the maximum width
     * @param mixed $thumbnail_y  
     * @param array $arbitrary_data 
     * @return array 
     * @throws BadRequest 
     * @throws Exception 
     */
    private function clientUploadImageThumbnail($key, $index, $thumbnail_x, $thumbnail_y = null, $arbitrary_data = [], $files = null) {
        $tmp_name = "/tmp/" . random_string(16);
        $name           = pathinfo($_FILES[$key]['name'][$index],PATHINFO_FILENAME);
        $extension      = pathinfo($_FILES[$key]['name'][$index],PATHINFO_EXTENSION);
        $thumbnail_name = "$name.$this->thumbnail_suffix.$extension";
        if(!$files) $files = $_FILES;
        
        $generationResult = createThumbnail($files[$key]['tmp_name'][$index],$tmp_name,$thumbnail_x, $thumbnail_y);
        if(!$generationResult) throw new BadRequest("Cannot generate a thumbnail for this file type.");

        // Prepare our data so we can upload them to GridFS
        $clone = [
            $key => [
                'name' => [
                    $files[$key]['name'][$index],
                    $thumbnail_name,
                ],
                'tmp_name' => [
                    $files[$key]['tmp_name'][$index],
                    $tmp_name,
                ]
            ]
        ];

        $thumb = $this->clientUploadFile($key,1,['isThumbnail' => true],$clone);
        $thumb_id = array_keys($thumb)[0];

        $arbitrary_data = array_merge($arbitrary_data, [
            'thumbnail_id' => $thumb_id,
            'thumbnail' => $clone[$key]['name'][1]]
        );

        return $this->clientUploadFile($key,0,$arbitrary_data,$clone);

    }

    private function clientUploadImagesAndThumbnails($key,$thumbnail_x, $thumbnail_y = null, $arbitrary_data = [], $files = null) {
        $ids = [];
        if(!$files) $files = $_FILES;
        foreach($files[$key]['tmp_name'] as $index => $file) {
            $ids = array_merge($ids,$this->clientUploadImageThumbnail($key,$index,$thumbnail_x, $thumbnail_y, $arbitrary_data));
        }
        return $ids;
    }

    /**
     * Get a directory listing of the files in GridFS. Filters and options supported
     * 
     * @todo add more display modes
     * @param string $href the href to access the files
     * @param array $query 
     * @param string $mode ['list'] returns an unorganized list
     * @param array $parent 
     * @param array $child 
     * @return string 
     */
    final public function directoryListing(string $href = "", string $mode = "list", array $query = ['filter' => [], 'options' => []], $parent = [], $child = []){
        
        $query['filter'] = array_merge(['isThumbnail' => ['$exists' => false]], $query['filter'] ?? []);
        
        $docs = $this->fs->find($query['filter'] ?? [],$query['options'] ?? []);

        $this->createFormatTable();
        if(!key_exists($mode,$this->format_table)) $mode = "list";
        $container = $this->format_table[$mode];
        
        // Inherit default class names for container
        $string = "<$container[container]";
        if(isset($parent['class'])) $parent['class'] = $container['class'] ." ". $parent['class'];
        else $parent['class'] = $container['class'];

        // Add all HTML properties
        foreach($parent as $property=>$value) {
            $string .= " $property='".htmlspecialchars($value)."'";
        }

        $string .= ">"; // Close container starting tag

        // Loop through available docs
        foreach($docs as $doc) {
            // Execute the tag_start callback:
            $string .= "<" . $container["tag_start"]($doc, $href) . " data-id='".(string)$doc->_id."'";
            foreach($child as $property => $value) {
                $string .= " $property='".htmlspecialchars($value)."'"; // Add properties
            }
            $string .= ">"; // Close HTML tag

            // If we have an HREF, let's set that
            $string .= $container['anchor']($doc, $href);

            // If we have 
            $string .= ($container['tag_end']) ? "</$container[tag_end]>" : "";
        }
        return $string . "</$container[container]>";
    }

    private function createFormatTable() {
        $this->format_table = [
            'list' => [
                'container' => 'ul',
                'class' => 'cobalt--fs-directory-listing cfs--list',
                'tag_start' => function ($value) {
                    return "li";
                },
                'tag_end' => "li",
                'anchor' => function ($doc, $href) {
                    return (!$href) ? $doc->filename : "<a href='$href".htmlspecialchars($doc->filename)."'>".htmlspecialchars($doc->filename)."</a>";
                }
            ],
            'gallery' => [
                'container' => 'div',
                'class' => 'cobalt--fs-directory-listing cfs--picture-gallery',
                'tag_start' => function ($value, $href) {
                    return "picture onclick='lightbox(\"$href"."$value->filename\")'><img src='$href".($value->thumbnail ?? $value->filename)."'";
                },
                'tag_end' => "picture",
                'anchor' => fn () => ""
            ]
        ];
    }

}