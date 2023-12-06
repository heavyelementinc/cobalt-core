<?php
namespace Controllers;
require_once __ENV_ROOT__ . "/globals/image_handling.php";

use Drivers\FileSystem;
use Exception;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use MikeAlmond\Color\Color;
use MongoDB\BSON\ObjectId;

trait ClientFSManager {
    public ?FileSystem $fs = null;
    protected $format_table = null;
    
    /**
     * This property is used to assign a path name to the file being uploaded
     * You should *not* include a / to start or a trailing / at the end of the
     * pathname.
     * @var string
     */
    public $fs_filename_path = "";
    public $last_modified_result = null;

    public function setFilenamePath($path) {
        $start = 0;
        $end = null;
        if($path[0] === "/") $start = 1;
        if($path[strlen($path) - 1] === "/") $end = -1;
        $mutant = substr($path, $start, $end);
        $this->fs_filename_path = $mutant;
        return $this->fs_filename_path;
    }
    
    // function __construct() {
    //     $this->initFS();
    // }

    function initFS(){
        if($this->fs == null) $this->fs = new FileSystem();
        if($this->format_table == null) $this->createFormatTable();
    }

    function getFileMetadataById($id) {
        $this->initFS();
        $_id = new \MongoDB\BSON\ObjectId($id);
        return $this->fs->findOne(['_id' => $_id]);
    }

    // function getFileMetadataByName()

    public function download($filename) {
        $this->initFS();
        header("Cache-Control: private, max-age=31536000, immutable");
        $this->fs->download($filename);
    }

    public function findFiles($query = [], $options = []) {
        $this->initFS();
        return $this->fs->find($query, $options);
    }

    public function findFile($query = [], $options = []) {
        $this->initFS();
        return $this->fs->findOne($query, $options);
    }

    public function delete($id, $skipConfirm = false) {
        $this->initFS();
        $_id = new \MongoDB\BSON\ObjectId($id);
        $result = $this->fs->findOne(["_id" => $_id]);
        if($result === null) throw new NotFound("That file was not found");
        if($skipConfirm == false) confirm("Are you sure you want to delete <strong>" . htmlspecialchars($result->filename) . "</strong>?",[]);

        if($result->thumbnail_id) $this->fs->delete($result->thumbnail_id);
        $result = $this->fs->delete($_id);
        $this->last_modified_result = $result;
        return (string)$_id;
    }

    public function deleteManyIds($ids) {
        $_ids = [];
        foreach($ids as $id) {
            array_push($_ids, new \MongoDB\BSON\ObjectId($id));
        }
        $result = $this->fs->deleteMany(['_id' => ['$in' => $id]]);
        $this->last_modified_result = $result;
        return $result->getDeletedCount();
    }

    public function deleteAllBelongingToId($parent_id, $key = "for") {
        $_id = new ObjectId($parent_id);
        $result = $this->findMany([$key => $_id]);
        $this->last_modified_result = $result;
        $deleted = 0;
        foreach($result as $doc) {
            $r = $this->delete($doc['_id']);
            $deleted += 1;
        }
        return $deleted;
    }

    public function updateSortOrder($data = null) {
        if(!$_POST) throw new BadRequest("Malformed request.");
        if(!$data) $data = $_POST;
        if(gettype($data))
        $validated_data = [];

        foreach($data as $index => $value) {
            if(!is_numeric($index)) {
                throw new BadRequest("Malformed request.");
            }
            try{
                $validated_data[$index] = [
                    'filter' => ['_id' => new ObjectId($value)],
                    'update' => ['$set' => ['order' => $index]],
                ];
            } catch (Exception $e) {
                throw new BadRequest("Malformed identifier");
            }
        }

        $this->initFS();
        $count = 0;
        foreach($validated_data as $query) {
            $result = $this->fs->updateOne($query['filter'],$query['update']);
            $count += $result->getModifiedCount();
        }

        return $count;
    }

    /**
     * 
     * @param mixed $query 
     * @return void 
     */
    public function updateMetadata($query, $data):object {
        $this->initFS();
        $result = $this->fs->updateOne($query, $data);
        $this->last_modified_result = $result;
        return $result;
    }

    /**
     * 
     * @param string|int $key The key of the $_FILES field
     * @param int $index The index of $_FILES to use
     * @return array 
     */
    public function clientUploadFile($key, $index = null, $arbitrary_data = null, $files = null, $meta = false):array {
        $this->initFS();
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

        if(!isset($meta['isThumbnail'])) $file_array['name'] = $this->prefixFilename($file_array['name']);


        $meta = $this->getMetadata($file_array['tmp_name']);

        $arbitrary_data = array_merge($arbitrary_data, ['meta' => $meta]);

        $thumb_id = $this->fs->upload($file_array,$index,$arbitrary_data);
        $returnable = ['id' => $thumb_id, 'filename' => $file_array['name']];
        if($meta) {
            $returnable['meta'] = $arbitrary_data['meta'];
        }
        return $returnable;
    }

    public function clientUploadFiles($key, $arbitrary_data = null, $files = null, $meta = false) {
        if(!$files) $files = $_FILES;
        if(empty($files)) throw new BadRequest("No files were uploaded");
        if(empty($files[$key])) throw new BadRequest("No files were uploaded");
        $ids = [];
        foreach($files[$key]['tmp_name'] as $i => $file) {
            $ids = array_merge($ids,$this->clientUploadFile($key, $i, $arbitrary_data, $files, $meta));
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
    public function clientUploadImageThumbnail($key, $index, $thumbnail_x, $thumbnail_y = null, $arbitrary_data = [], $files = null, $meta = false) {
        if($files === null) $files = $_FILES;
        $tmp_name = "/tmp/" . random_string(16);
        $path           = pathinfo($files[$key]['name'][$index],PATHINFO_DIRNAME);
        $path = ($path) ? "$path/" : "";
        $name           = pathinfo($files[$key]['name'][$index],PATHINFO_FILENAME);
        $extension      = pathinfo($files[$key]['name'][$index],PATHINFO_EXTENSION);
        
        $thumbnail_name = "$name.$this->thumbnail_suffix.$extension";
        
        if(!$files[$key]['tmp_name'][$index]) throw new BadRequest("Invalid indicies");

        $generationResult = createThumbnail($files[$key]['tmp_name'][$index],$tmp_name,$thumbnail_x, $thumbnail_y);
        if(!$generationResult) throw new BadRequest("Cannot generate a thumbnail for this file type.");

        // Prepare our data so we can upload them to GridFS
        $toInsert = [
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

        // First, let's insert our thumbnail
        $thumb = $this->clientUploadFile($key,1,['isThumbnail' => true], $toInsert, $meta);
        $thumb_id = $thumb['id'];

        $arbitrary_data = array_merge($arbitrary_data, [
            'thumbnail_id' => $thumb_id,
            'thumbnail' => $thumb['filename']]
        );

        // Now let's insert our actual image
        $returnable = $this->clientUploadFile($key, 0, $arbitrary_data, $toInsert, $meta);
        
        $returnable = [
            'media' => $returnable,
            'thumb' => $thumb
        ];

        return $returnable;

    }

    private function prefixFilename($filename) {
        if($this->fs_filename_path) $filename = trim_trailing_slash($this->fs_filename_path)."/$filename";
        return $filename;
    }

    public function clientUploadImagesAndThumbnails($key,$thumbnail_x, $thumbnail_y = null, $arbitrary_data = [], $files = null, $meta = false) {
        $ids = [];
        $assoc = is_associative_array($files);
        if(!$files) $files = $_FILES;
        foreach($files[$key]['tmp_name'] as $index => $file) {
            
            array_push($ids, $this->clientUploadImageThumbnail($key,$index,$thumbnail_x, $thumbnail_y, $arbitrary_data, $files, $meta));
        }
        return $ids;
    }

    public function renameFile($id, $submittedName = null) {
        $this->initFS();
        if(is_null($submittedName)) $submittedName = $_POST['rename'];
        $_id = new ObjectId($id);
        $q = ['_id' => $_id];

        $newName = $this->prefixFilename($submittedName);
        $result = $this->fs->findOne($q);

        $oldExtension = pathinfo($result['filename'], PATHINFO_EXTENSION);
        $newExtension = pathinfo($newName, PATHINFO_EXTENSION);
        if(!$newExtension) {
            $newName .= ".$oldExtension";
            $newExtension = $oldExtension;
        } else if($oldExtension !== $newExtension) confirm("WARNING: You're changing the file extension for this file. It may become unreadable. Are you sure you want to continue?", $_POST);

        $update = ['filename' => $newName];

        $thumbnail = $this->fs->findOne(['_id' => $result->thumbnail_id]);
        $thumbnail_filename = null;
        if($thumbnail) {
            $thumbnail_filename = str_replace(".$newExtension", ".$this->thumbnail_suffix.$newExtension", $newName);
            $update['thumbnail'] = $thumbnail_filename;
        }

        $modified = $this->updateMetadata($q,[
            '$set' => $update
        ]);

        $returnValues = [
            'name' => "/res/fs$newName",
        ];

        if($thumbnail_filename) {
            $modified_thumb = $this->updateMetadata(['_id' => $thumbnail->_id],[
                '$set' => ['filename' => $thumbnail_filename]
            ]);
            $returnValues['thumbnail'] = "/res/fs$thumbnail_filename";
        }
        
        return $returnValues;
    }

    /**
     * Get a directory listing of the files in GridFS. Filters and options supported
     * 
     * @todo add more display modes
     * @param string $href the href to access the files
     * @param string $mode ['list'|'gallery']
     * @param array $query 
     * @param array $parent 
     * @param array $child 
     * @return string 
     */
    final public function directoryListing(string $href = "", string $mode = "list", array $query = ['filter' => [], 'options' => []], array $options = []){
        if($href === "") $href = "/res/fs";
        // if($this->fs_filename_path) $href = trim_trailing_slash($href) . trim_trailing_slash($this->fs_filename_path);
        $options = array_merge([
            'parent' => [],
            'child' => [],
            'lazy' => true],
            $options
        );
        
        $this->initFS();
        $query['filter'] = array_merge(['isThumbnail' => ['$exists' => false]], $query['filter'] ?? []);
        $query['options'] = array_merge(['sort' => ['order' => 1, '_id' => 1]],$query['options'] ?? []);
        
        $docs = $this->fs->find($query['filter'] ?? [],$query['options'] ?? []);

        $this->createFormatTable();
        if(!key_exists($mode,$this->format_table)) $mode = "list";
        $container = $this->format_table[$mode];
        
        // Inherit default class names for container
        $string = "<$container[container]";
        if(isset($options['parent']['class'])) $options['parent']['class'] = $container['class'] ." ". $options['parent']['class'];
        else $options['parent']['class'] = $container['class'];

        // Add all HTML properties
        foreach($options['parent'] as $property=>$value) {
            $string .= " $property='".htmlspecialchars($value)."'";
        }

        $string .= ">"; // Close container starting tag

        // Loop through available docs
        foreach($docs as $doc) {
            // Execute the tag_start callback:
            $string .= "<" . $container["tag_start"]($doc, $href, $options['lazy']) . " data-id='".(string)$doc->_id."'";
            foreach($options['child'] as $property => $value) {
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
                'tag_start' => function ($value, $href, $lazy = true) {
                    $lazy = ($lazy) ? " loading='lazy'" : "";
                    return "img src='$href".($value->thumbnail ?? $value->filename)."' onclick='lightbox(this)' full-resolution='$href"."$value->filename'$lazy";
                },
                'tag_end' => "",
                'anchor' => fn () => ""
            ],
            'limitedGallery' => [
                'container' => 'div',
                'class' => 'cobalt--fs-directory-listing cfs--picture-gallery',
                'tag_start' => function ($value, $href, $lazy = true) {
                    $lazy = ($lazy) ? " loading='lazy'" : "";
                    return "img src='$href".($value->thumbnail ?? $value->filename)."' full-resolution='$href"."$value->filename'$lazy";
                },
                'tag_end' => "",
                'anchor' => fn () => ""
            ],
            'carousel' => [
                'container' => 'cobalt-carousel',
                'class' => 'cobalt--fs-directory-listing cfs-carousel',
                'tag_start' => function ($value, $href, $lazy = true) {
                    $lazy = ($lazy) ? " loading='lazy'" : "";
                    return "img src='$href".($value->thumbnail ?? $value->filename)."' draggable='false' onclick='lightbox(this)' full-resolution='$href"."$value->filename'$lazy";
                },
                'tag_end' => "",
                'anchor' => fn () => ""
            ]
        ];
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
                return $this->getImageMetadata($path_to_file, $mime_type);
            case "video":
                return $this->getVideoMetadata($path_to_file, $mime_type);
            case "audio":
                return $this->getAudioMetadata($path_to_file, $mime_type);
        }

        return ['mimetype' => $mime_type];
    }

    public function getImageMetadata($path_to_file, $mime_type = null) {
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

    public function getVideoMetadata($path_to_file, $mime_type = null) {
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

    public function getAudioMetadata($path_to_file, $mime_type = null) {
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
