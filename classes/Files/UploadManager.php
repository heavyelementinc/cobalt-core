<?php

namespace Files;

use Drivers\Watch;
use Exception;
use Exceptions\HTTP\BadGateway;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\HTTPException;

class UploadManager {
    // Prefix and suffix stuff
    public $prefix = "ugc";
    public $thumbnail_suffix = "thumbnail";
    public $files = null;

    function __construct($files = null) {
        if ($files === null) $this->set_files($_FILES);
        else $this->set_files($files);
    }

    /**
     * @todo make the error checking more robust!
     */
    public function set_files($files) {
        // Let's check for errors
        $errors = 0;
        $this->files = [];
        $iteration = -1;
        foreach ($files as $field => $f) {
            // Sum the errors array, if it's not zero, there was an error.
            $errors += array_sum($f['error']);
            if ($errors !== 0) throw new \Exceptions\HTTP\Error("File failed to upload");
            // Loop through each 'name'
            foreach ($f['name'] as $i => $name) {
                $iteration++;
                $this->files[$iteration] = [
                    'field_name' => $field,
                    'file_name' => $name,
                    'tmp_name' => $f['tmp_name'][$i],
                    'size' => $f['size'][$i],
                ];
            }
        }

        if ($errors !== 0) throw new BadRequest("There was a problem with your uploaded files");
    }

    public function restore($files, $prefix = "ugc", $thumbnail_suffix = "thumbnail") {
        $this->prefix = $prefix;
        $this->thumbnail_suffix = $thumbnail_suffix;
        $this->files = $files;
    }

    public function move_all_files_to_dir($directory = null, $original_filename = false) {
        if ($directory === null) throw new Exception("No directory specified.");
        foreach ($this->files as $i => $file) {
            $filename = $this->get_unique_filename($file, $directory, $original_filename);
            $this->files[$i]['path'] = str_replace("//", "/", $filename);
            if (!move_uploaded_file($file['tmp_name'], $filename)) throw new Exception("Could not move file");
        }
    }

    private function get_unique_filename($file, $directory, $original_filename = true, $depth = 0) {
        // Limit of 25 files in one directory
        if ($depth === 25) throw new Exception("Cannot find a suitable name for storage");
        $extension = "." . pathinfo($file['file_name'], PATHINFO_EXTENSION);
        if ($original_filename) $n = str_replace($extension, "", $file['file_name']);
        else $n = uniqid($this->prefix . "_", true);

        $path = "$directory/" . str_replace(['.', '\\', '/'], "", $n);

        if ($depth !== 0) $path .= "_$depth";

        $path .= $extension;

        // If the file doesn't exist, return the filename. We're all good.
        if (!file_exists($path)) return $path;

        // Otherwise, we do a bit of recursiveness to try and find a filename that is unique.
        return $this->get_unique_filename($file, $directory, $original_filename, $depth++);
    }

    public function generate_thumbnails($constrain_by_max_dimension = 200, $watch = null) {
        if ($watch) {
            $watch->queue();
        }
        $watch->total(count($this->files));
        foreach ($this->files as $i => $file) {
            // Check if we've got a path to work with, otherwise use tmp names
            $path = (isset($file['path'])) ? $file['path'] : $file['tmp_name'];
            $pathinfo = pathinfo($path);
            $extension = ($pathinfo['extension']) ? $pathinfo['extension'] : pathinfo($file['file_name'], PATHINFO_EXTENSION);
            $filename = $pathinfo['dirname'] . "/$pathinfo[filename]_" . $this->thumbnail_suffix . ".$extension";
            $this->files[$i]['thumb'] = $filename;

            // Okay, now let's start doing the math to configure thumbnail
            $image_data = getimagesize($path);
            $s = $this->get_resized_dimensions($image_data, $constrain_by_max_dimension);

            // Determine which methods we should use
            $methods = $this->get_image_function($image_data);
            if ($methods === null) continue;
            $get_method = $methods[0];
            $set_method = $methods[1];

            // Okay, now let's do the actual work
            $old = $get_method($path);
            $new = imagecreatetruecolor($s['new_width'], $s['new_height']);
            imagecopyresized($new, $old, 0, 0, 0, 0, $s['new_width'], $s['new_height'], $image_data[0], $image_data[1]);
            $set_method($new, $filename);
            if ($watch) $watch->inc();
        }
        if ($watch) $watch->done();
    }

    public function generate_thumbnails_exec() {
        $watch = new Watch();
        $watch_id = $watch->get_id();
        $result = $watch->queue(['data' => [$this->files, $this->prefix, $this->thumbnail_suffix]]);

        $command = "file thumbnails $watch_id";
        $result = async_cobalt_command($command);
        return $result;
    }

    private function get_image_function($image_data) {
        $valid_types = [
            IMG_GIF => ['imagecreatefromgif', 'imagegif'],
            IMG_JPG => ['imagecreatefromjpeg', 'imagejpeg'],
            3       => ['imagecreatefrompng', 'imagepng'],
            IMG_PNG => ['imagecreatefrompng', 'imagepng'],
            IMG_WBMP => ['imagecreatefromwbmp', 'imagewbmp'],
            IMG_XPM => ['imagecreatefromxpm', 'imagexpm'],
            IMG_WEBP => ['imagecreatefromwebp', 'imagewebp'],
            IMG_BMP => ['imagecreatefrombmp', 'imagebmp'],
        ];
        $valid_types = [
            "image/bmp" => ['imagecreatefrombmp', 'imagebmp'],
            "image/gif" => ['imagecreatefromgif', 'imagegif'],
            "image/jpeg" => ['imagecreatefromjpeg', 'imagejpeg'],
            "image/png" => ['imagecreatefrompng', 'imagepng'],
            "image/wbmp" => ['imagecreatefromwbmp', 'imagewbmp'],
            "image/xpm" => ['imagecreatefromxpm', 'imagexpm'],
            "image/webp" => ['imagecreatefromwebp', 'imagewebp'],
        ];
        if (key_exists($image_data["mime"], $valid_types)) return $valid_types[$image_data["mime"]];
        return null;
    }

    private function get_resized_dimensions($image_data, $constrain_by_max_dimension) {
        $o_width = $image_data[0];
        $o_height = $image_data[1];
        if ($o_width > $o_height) {
            $new_width = $constrain_by_max_dimension;
            $new_height = intval($o_height * $new_width / $o_width);
        } else {
            $new_height = $constrain_by_max_dimension;
            $new_width = intval($o_width * $new_height / $o_height);
        }
        return [
            'original_width' => $o_width,
            'original_height' => $o_height,
            'new_width' => $new_width,
            'new_height' => $new_height
        ];
    }
}

// function makeThumbnails($updir, $img, $id) {
//     // Set width values
//     $thumbnail_width = 134;
//     $thumbnail_height = 189;
//     $thumb_beforeword = "thumb";

//     // Get image details
//     $arr_image_details = getimagesize("$updir" . $id . '_' . "$img"); // pass id to thumb name
//     $original_width = $arr_image_details[0];
//     $original_height = $arr_image_details[1];
//     if ($original_width > $original_height) {
//         $new_width = $thumbnail_width;
//         $new_height = intval($original_height * $new_width / $original_width);
//     } else {
//         $new_height = $thumbnail_height;
//         $new_width = intval($original_width * $new_height / $original_height);
//     }
//     $dest_x = intval(($thumbnail_width - $new_width) / 2);
//     $dest_y = intval(($thumbnail_height - $new_height) / 2);
//     if ($arr_image_details[2] == IMAGETYPE_GIF) {
//         $imgt = "ImageGIF";
//         $imgcreatefrom = "ImageCreateFromGIF";
//     }
//     if ($arr_image_details[2] == IMAGETYPE_JPEG) {
//         $imgt = "ImageJPEG";
//         $imgcreatefrom = "ImageCreateFromJPEG";
//     }
//     if ($arr_image_details[2] == IMAGETYPE_PNG) {
//         $imgt = "ImagePNG";
//         $imgcreatefrom = "ImageCreateFromPNG";
//     }
//     if ($imgt) {
//         $old_image = $imgcreatefrom("$updir" . $id . '_' . "$img");
//         $new_image = imagecreatetruecolor($thumbnail_width, $thumbnail_height);
//         imagecopyresized($new_image, $old_image, $dest_x, $dest_y, 0, 0, $new_width, $new_height, $original_width, $original_height);
//         $imgt($new_image, "$updir" . $id . '_' . "$thumb_beforeword" . "$img");
//     }
// }
