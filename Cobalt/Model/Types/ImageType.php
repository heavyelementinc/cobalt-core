<?php
namespace Cobalt\Model\Types;

use Cobalt\Commands\Exceptions\CommandError;
use Cobalt\Model\Attributes\Prototype;
use Cobalt\Model\Filters\Issues\FilterIssue;
use Cobalt\Model\GenericModel;
use Cobalt\JobQueue\Interfaces\JobHandler;
use Cobalt\Model\Interfaces\ServerEvents;
use Cobalt\Model\Model;
use Cobalt\Model\Types\Abstracts\ForeignId;
use Cobalt\Model\Types\Traits\FileHandler;
use Cobalt\Model\Jobs\Job;
use Error;
use Exceptions\HTTP\BadRequest;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use Override;

class ImageType extends ForeignId {
    use FileHandler;
    
    /** Accept must return an array of valid mimetypes */
    const ACCEPT_DIRECTIVE = "accept";
    const ACCEPT_FAIL_POLICY__ERROR = 0;

    /** May return an Array<int> with no keys, [width, height]
     * OR return a string "widthxheight"
     */
    const MAX_RESOLUTION_DIRECTIVE = "max_resolution";
    const MAX_RESOLUTION_POLICY_DIRECTIVE = "max_policy";
    const MAX_RESOLUTION_POLICY__ERROR = 0;
    const MAX_RESOLUTION_POLICY__SCALE = 1;

    const MIN_RESOLUTION_DIRECTIVE = "min_resolution";
    const MIN_RESOLUTION_POLICY_DIRECTIVE = "min_policy";
    const MIN_RESOLUTION_POLICY__ERROR = 0;
    const MIN_RESOLUTION_POLICY__SCALE = 1;
    const FILE_UPLOAD_INDICATOR = '$_FILES_$';

    public function runJoinQuery(Model $model, ?ObjectId $id): null|BSONArray|BSONDocument|Persistable {
        return $this->__findOne(['_id' => $id]);
    }

    function filter($oid) {
        $filesKey = $this->{MODEL_RESERVERED_FIELD__FIELDNAME};
        if($oid === self::FILE_UPLOAD_INDICATOR && key_exists($filesKey, $_FILES)) {
            $files = normalize_uploaded_files($_FILES);
            $count = count($files[$filesKey]);
            if($count == 0 || $count >= 2) throw new BadRequest("Too many images uploaded for $filesKey");

            $arr = $files[$filesKey][0];

            $this->filter_attributes_upload($arr['tmp_name']);
            // TODO: Pre-allocate ObjectId!
            return null;
            // $oid = $this->__store($arr['tmp_name'], $filename);
        } else {
            $oid = $this->filter_attributes_objectid($this->handle_incoming_commands($oid));
        }
        // update("[name='$this->name']",['classList' => ['add' => ['working']]]);
        // if(!$oid) throw new BadRequest("Failed to upload image to database");
        
        return parent::filter($oid);
    }


    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        return parent::field($class, $misc, $tag ?? "file-id");
    }

    #[Prototype]
    protected function getLabel($includeHtml = true, $small_text = ""): string {
        return parent::getLabel($includeHtml, $small_text);
    }

    #[Prototype]
    protected function getColor($type = "accent") {
        match($type) {
            'contrast' => $this->value['meta']['contrast_color'],
            default => $this->value['meta']['accent_color']
        };
    }

    function fieldItemTemplate(): string {
        return "Cobalt/Model/templates/types/image-type.php";
    }

    #[Override]
    public function filter_setup(array &$toValidate, string $key, Job $filterJob, GenericModel $model):void {
        if($toValidate[$key] === self::FILE_UPLOAD_INDICATOR) {
            $files = normalize_uploaded_files($_FILES);
            $this->prep_for_async_storage($files[$key]);
            $filterJob->addOneToQueue([
                'file' => $files[$key][0],
                'name' => $key,
            ]);
        }
    }

    // #[Override]
    // public function __job__on_start(object $item, Job $job, int $index) {
    //     $filename = $this->filename($item->file);
    //     $path = $item->file->tmp_name;
    //     if(!file_exists($path)) {
    //         $job->updateQueueItem($index, Job::STATUS_ABORTED, 'File missing');
    //         throw new CommandError("File is missing");
    //     }
    //     $id = $this->__store($path, $filename, $item->file->options ?? []);
    //     $this->model->updateOne(['_id' => $job->getAdopted()->refid], ['$set' => [$item['name'] => $id]]);
    //     unlink($path);
    //     $job->updateQueueItem($index, Job::STATUS_FINISHED, "Done");
    //     $job->increment();

    // }

    // #[Override]
    // public function __job__on_complete(object $item, Job $job, int $index) {
        
    // }

}