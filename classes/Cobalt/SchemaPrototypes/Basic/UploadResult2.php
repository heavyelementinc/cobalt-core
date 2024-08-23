<?php
namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\Maps\Exceptions\DirectiveException;
use Cobalt\SchemaPrototypes\Basic\FakeResult;
use Cobalt\SchemaPrototypes\Basic\HexColorResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\MapResult;
use Cobalt\SchemaPrototypes\Traits\ImageManipulation;
use Drivers\BinaryStorage;
use Cobalt\SchemaPrototypes\Traits\Prototype;
use Cobalt\SchemaPrototypes\Wrapper\DefaultUploadSchema;
use Cobalt\SchemaPrototypes\Wrapper\IdResult;
use Exceptions\HTTP\BadRequest;
use Validation\Exceptions\ValidationContinue;
use Validation\Exceptions\ValidationIssue;

class UploadResult2 extends MapResult {
    use BinaryStorage;
    use ImageManipulation;
    protected $type = "upload";

    function defaultSchemaValues(array $data = []): array {
        return [
            'schema' => [
                'ref' => [
                    new IdResult,
                    'nullable' => true
                ],
                'url' => new StringResult,
                'height' => new NumberResult,
                'width' => new NumberResult,
                'accent' => new HexColorResult,
                // 'contrast' => [
                //     new FakeResult,
                //     'get' => fn () => $this->accent->getContrastColor(),
                // ],
                'mimetype' => new StringResult,
                'thumb' => new StringResult,
                'thumb_height' => new NumberResult,
                'thumb_width' => new NumberResult,
                'alt' => [
                    new StringResult,
                    'filter' => fn ($val) => throw new ValidationIssue("You can't do that"),
                ],
            ]
        ];
    }

    #[Prototype]
    protected function display():string {
        return $this->embed();
    }

    /********************** EMBED CONTENT *********************/
    #[Prototype]
    protected function embed($embedSize = "media", array $misc = []) {
        // Let's establish our defaults for the $misc
        $misc = $this->defaultMiscContent($misc);

        // Unpack the $misc array and prepare for embedding
        $class = ($misc['class']) ? " class=\"$misc[class]\"" : "";
        $data = $this->dataToEmbedTags($misc['data'] ?? []);
        $title = ($misc['title']) ? "title=\"".htmlspecialchars($misc['title'])."\"" : "";
        $alt = ($misc['alt']) ? " alt=\"$misc[alt]\"" : "";
        $value = $this->getMediaElements($embedSize);
        $w = $value['width'];
        $h = $value['height'];

        // Process mimetype
        $mimetype = $value['mimetype'];
        $pos = explode("/", $mimetype);
        $sub = $pos[0];
        $enc = $pos[1];

        // What are we doing here?!?
        $rt = $this->{'value'};
        if(is_array($rt)) {
            $rt = $rt[count($rt) - 1];
        }
        
        switch(strtolower($sub)) {
            // case "video":
            //     return "<video class=\"$class\" width=\"$w\" height=\"$h\" ".$value['controls']['display'].$value['loop']['display'].$value['autoplay']['display'].$value['mute']['display']."><source src='$rt' type='$mimetype'></video>";
            // case "audio":
            //     return "<audio class=\"$class\" ".$value['mute']['display'].$value['loop']['display'].$value['controls']['display']."><source src='$rt' type='$mimetype'></audio>";
            // case "href":
            //     $fs = $value['allowfullscreen'];
            //     $allow = $value['allow'];
            //     $title = $value['title'];
            //     return "<iframe class=\"$class\" src=\"$rt\" name=\"$enc\" scrolling=\"no\" frameborder=\"0\" width=\"$w\" height=\"$h\" $fs $allow $title></iframe>";
            // case "zip":
            //     return "<a href=''></a>";
            case "image":
            default:
                return "<img$class src=\"$value[url]\"$alt width=\"$w\" height=\"$h\" style=\"background-color: ".$value['accent']."\">";
        }

        return $rt;
    }

    protected function defaultMiscContent(array $misc): array {
        $merge = array_merge([
            'class' => $this->getDirective("classes") ?? "",
            'alt' => $this->getDirective("alt") ?? $this->alt ?? $this->name,
            'data' => array_merge(
                [
                    "media-id" => $this->ref,
                    "ref-id" => $this->thumb_ref
                ],
                $misc['data'] ?? []
            )
        ], $misc);
        return $merge;
    }

    function dataToEmbedTags($data) {
        $tags = "";
        foreach($data as $name => $value) {
            $tags .= " data-$name=\"". htmlspecialchars($value) ."\"";
        }
        return $tags;
    }

    function getMediaElements($size = "media") {
        switch($size) {
            case "thumb":
            case "thumbnail":
            case "small":
                return [
                    'url' => $this->value->thumb->getValue(),
                    'height' => $this->value->thumb_height->getValue(),
                    'width' => $this->value->thumb_width->getValue(),
                    'mimetype' => $this->value->mimetype->getValue(),
                    'accent' => $this->value->accent->getValue(),
                ];
            default:
                return [
                    'url' => $this->value->url->getValue(),
                    'height' => $this->value->height->getValue(),
                    'width' => $this->value->width->getValue(),
                    'mimetype' => $this->value->mimetype->getValue(),
                    'accent' => $this->value->accent->getValue(),
                ];
        }
    }


    /******************* UPLOAD HANDLING **********************/
    function upload_filter($values) {
        // Let's run checks on our values before we do any uploading of the file
        // to our database. If validation fails, we don't want to have an orphaned
        // file in the FS.
        $values = $this->value->__validate($values);

        // If everything passed, let's grab our uploaded file
        $uploadedFiles = $this->__get_uploaded_files();
        $errors = [];
        foreach($uploadedFiles as $file) {
            if($file['input_name'] !== $this->name) continue;
            // Let's do some basic error checking
            switch($file['error']) {
                case UPLOAD_ERR_NO_FILE:
                    if($this->schema['required']) throw new ValidationContinue("No file uploaded");
                    continue;
                case UPLOAD_ERR_OK:
                    // Do nothing and continue
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
        // Check for errors and, if there are any, return them to the client in a ValidationIssue
        if(count($errors) >= 1) throw new ValidationIssue(implode("\n", $errors));
        
        // Store our files
        $this->storeFile($uploadedFiles[0], $values->__validatedFields);
        return $this->value;
    }

    public function storeFile(?array $result, array $mergedata = []) {
        if(!$result) throw new ValidationIssue("The provided file array was null");

        $filename = $this->getFilename($result);
        $map = ['mapId' => $this->__reference->_id];

        // Upload our main file to the database filesystem
        $media = $this->upload_file($filename[0], array_merge($result, $map));
        
        // Let's reformat the data we are storing...
        $resource = [
            'url'      => "/res/fs/$filename[0]",
            'ref'      => $media['ref'],
            'mimetype' => $media['meta']['mimetype'],
            'height'   => $media['meta']['height'],
            'width'    => $media['meta']['width'],
            'accent'   => $media['meta']['accent_color'],
            'alt'      => $mergedata['alt'] ?? '',
        ];

        if(isset($this->schema['thumbnail'])) {
            if($resource['mimetype'] !== "image/svg+xml") {
                $thumbnail = $this->storeThumbnail($filename[1], $result, ['for' => $media['ref']]);
                $resource['thumb'] = "/res/fs/$filename[1]";
                $resource['thumb_ref'] = $thumbnail['ref'];
                $resource['thumb_height'] = $thumbnail['meta']['height'];
                $resource['thumb_width'] = $thumbnail['meta']['height'];
            }
        }
        $this->setValue($resource);
        return $result;
    }

    /**
     * 
     * @param array $fileArray 
     * @return array{0:string, 1:string}
     * @throws DirectiveException 
     */
    private function getFilename($fileArray) {
        // Check if the schema defines a filename for this field
        $directive_specified_filename = $this->getDirective("filename");
        if($directive_specified_filename) return $directive_specified_filename;

        // Check if the schema allows the user-supplied filename to be retained
        $retain_user_supplied_filename = $this->getDirective("preserve_filename");
        if($retain_user_supplied_filename) return $fileArray['name'];

        // If we've made it here, we need to supply a custom filename
        $unique = uniqid();
        $ext = pathinfo($fileArray['name'], PATHINFO_EXTENSION);
        $filename = hash('sha1', $unique . "-" . pathinfo($fileArray['name'], PATHINFO_BASENAME));
        // Return an array of 
        return ["$filename.$ext", "$filename.thumb.$ext"];
    }

    private function upload_file($filename, $data, $additional_data = []) {
        
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
            $this->schema['thumbnail'][0] ?? 200, // Width
            $this->schema['thumbnail'][1] ?? null // Height
        );

        $data['tmp_name'] = $destination;

        return $this->upload_file(
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
            $additional = $this->getDirective($field, false, $filename, $data, $supplemental);
            if(!isset($additional)) continue;
            if($found === true) throw new ValidationIssue("This schema contains more than one storage directive!");
            $found = true;
        }

        return array_merge(
            $fromSchema, // Get additional data
            $supplemental
        );
    }
}