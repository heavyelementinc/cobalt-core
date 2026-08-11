<?php

namespace Cobalt\DataModel\Types;

use Cobalt\DataModel\Directives\Media\Accept;
use Cobalt\DataModel\Traits\FileHandler;
use Cobalt\DataModel\Traits\FileHandlerImage;
use Cobalt\JobQueue\Enums\JobState;
use Cobalt\JobQueue\Interfaces\BatchItem;
use Cobalt\JobQueue\Interfaces\JobHandler;
use Cobalt\JobQueue\Models\Job;
use Exception;
use Imagick;
use Override;

class ImageType extends ForeignDocumentType {
    use FileHandlerImage;
    const FILE_UPLOAD_INDICATOR = '$_FILES_$';

    function __construct(null|DictionaryType|ArrayType $model = null, ?DictionaryType $rootModel = null) {
        // $this->
        parent::__construct($model, $rootModel);
    }

    /**
     * @return ?DataModel 
     * @throws Exception 
     */
    // public function getValue(): mixed {
    //     if(!isset($this->value)) {
    //         if(!isset($this->directives->external_model)) {
    //             throw new Exception("Required directive `external_model` is not defined on ObjectIdType: `".($this->name ?? "%field_name%")."`");
    //         }
    //         // Populate this element when its value is actually read and not before!
    //         $this->value = $this->directives->external_model->getValue()->findOne(['_id' => $this->objectId]);
    //     }
    //     return $this->value;
    // }

    #[Override]
    public function filter(mixed $oid, mixed $raw): mixed {
        $filesKey = $this->getFieldDotNotation();
        if($oid === self::FILE_UPLOAD_INDICATOR) {
            if(!key_exists($filesKey, $_FILES)) return $this->filterResult->addIssue($this, "Upload is missing requested field.");
            $mutated = $this->filterUploadedFile($_FILES, $filesKey);
        } else {
            // Handle any incoming commands we might have
            $oid = $this->handleIncomingCommands($oid);
            $mutated = $this->filterAttributesObjectId($oid);
        }
        return $mutated;
    }

    const ACCEPTED_IMAGE_MIMETYPES = [];

    public function filterUploadedFile(array $_files, string $key) {
        $files = normalize_uploaded_files($_files);
        $count = count($files[$key]);
        if($count === 0 || $count >= 2) return $this->filterResult->addIssue($this, "Too many files uploaded for this document.");

        // Handle filtering the filename
        $this->__filterFilename($files[$key][0]);
        return $this->scheduleUploadJob($files[$key][0]);
    }

    public function filterAttributesObjectId($file) {
        $_id = IdType::toObjectId($file);
        if(is_string($_id)) {
            return $this->filterResult->addIssue($this, $_id);
        }
        return $_id;
    }

    public function scheduleUploadJob(array $files) {
        $name = $this->getFieldDotNotation();
        $this->filterResult->job->addBatchItem(new BatchItem($name, 'store', []));
        // If we have a directive thumbnail, let's set up for it.
        if($this->directives->thumbnail) {
            $this->filterResult->job->addBatchItem(
                new BatchItem($name, 'thumbnail', [
                    $files['tmp_name'],
                    $this->directives->thumbnail->getValue(),
                    $this->directives->thumbnail->suffix
                ])
            );
        }
    }

    function store(Job $job, string $pathToFile, string $pathForStorage, array $data, array $storageOptions) {
        $this->objectId = $this->__upload($pathToFile, $pathForStorage, $data, $storageOptions);
        return "Stored $pathForStorage";
    }

    function thumbnail(Job $job, string $pathToFile, array $widthHeightRes, string $suffix, ?string $pathForStorage = null) {
        $mimetype       = mime_content_type($pathToFile);
        $file_extension = Accept::toExtension($mimetype, "image");
        $pathinfo = pathinfo($pathForStorage ?? $pathToFile);
        $newFilename = $pathinfo['filename'] . "." . sprintf($suffix, ($pathinfo['extension'] ?? $file_extension));
        
        $imagic = new Imagick($pathToFile);
        $imagic->thumbnailImage($widthHeightRes[1], $widthHeightRes[0], true, false);
        $temp_name = __APP_ROOT__."/ignored/tmp/$newFilename";
        $imagic->writeImage($temp_name);

        // $this->__store($target);
        unlink($temp_name);
        return "Generated thumbnail";
    }
}
