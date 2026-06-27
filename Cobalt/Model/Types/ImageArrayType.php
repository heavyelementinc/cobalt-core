<?php
namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Interfaces\ServerEvents;
use Cobalt\Model\Model;
use Cobalt\Model\Types\Abstracts\OrderedListOfForeignIds;
use Cobalt\Model\Types\Traits\FileHandler;
use Cobalt\JobQueue\Jobs\Job;
use Cobalt\Model\Interfaces\JobHandler;
use Exceptions\HTTP\BadRequest;
use Iterator;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Cursor;
use MongoDB\Model\BSONDocument;
use Override;
use Validation\Exceptions\ValidationIssue;

class ImageArrayType extends OrderedListOfForeignIds implements JobHandler {
    use FileHandler;
    protected string $operator = '$set';

    public function runJoinQuery(Model $model, array $ids): ?Cursor {
        $result = $this->__find(['_id' => ['$in' => $ids]], ['limit' => count($ids)]);
        if($result instanceof Cursor) return $result;
        return null;
    }
    
    function filter($oids) {
        $filesKey = $this->{MODEL_RESERVERED_FIELD__FIELDNAME};
        if($oids === '$_FILES_$' && key_exists($filesKey, $_FILES)) {
            $files = normalize_uploaded_files($_FILES);
            foreach($files[$filesKey] as $key => $file) {
                // TODO: Pre-allocate ObjectIds!
                $this->filter_attributes_upload($file['tmp_name']);
            }
            return [];
        }
        
        $array_of_id_strings = [];
        foreach($oids as $key => $value) {
            array_push($array_of_id_strings, $this->handle_incoming_commands($value));
        }
        
        return parent::filter($array_of_id_strings);
    }

    function filter_attributes_objectid($oids):array {
        // If we just have a single object ID, interpret it and return it as index 0 of an array
        if(is_string($oids)) return [$this->interpretRawValue($oids)];
        // If don't have an array by this point, throw a BadRequest
        if(!is_array($oids)) throw new BadRequest("Malformed field", true);
        // Let's build a list of interpreted ObjectIds
        $mutant = [];
        foreach($oids as $item) {
            // Interpret our raw values
            $mutant[] = $this->interpretRawValue($item);
        }
        return $mutant;
    }

    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        return parent::field($class, $misc, $tag ?? "file-gallery");
    }

    
    public function initDirectives(): array {
        return [
            'operator' => function (&$operators, &$field, &$details) {
                if($this->operator === '$set') {
                    $operators[$this->operator][$field] = $details;
                    return;
                }
                $operators[$this->operator][$this->{MODEL_RESERVERED_FIELD__FIELDNAME}] = ['$each' => $details];
            }
        ];
    }

    function fieldItemTemplate(): string {
        return "Cobalt/Model/templates/types/image-type.php";
    }
     
    #[Override]
    public function filter_setup(array &$toValidate, string $key, Job $filterJob, GenericModel $model):void {
        $files = normalize_uploaded_files($_FILES);
        foreach($files as $name => $file) {
            $filterJob->addOneToQueue([
                'file' => $file,
                'name' => $this->name,
            ]);
        }
    }

    #[Override]
    public function __job__on_start(object $item, Job $job, int $index) {
        $oids = [];
        // $result = $this->uploadFilesAndGetArrayOfIds($filesKey, ['for' => $this->model->_id ?? null], $_FILES);
        foreach($job->adopted->queue[$this->name] as $index => $arr) {
            $filename = $this->filename($arr);
            $result = $this->__store($arr['tmp_name'], $filename);
            if(!$result) throw new ValidationIssue("Failed to store $arr[file]");
            $oids[] = $result;
        }
        $this->operator = '$addToSet';
    }

    #[Override]
    public function __job__on_complete(object $item, Job $job, int $index) {
        foreach($job->adopted->queue[$this->name] as $index => $files) {

        }
    }
}