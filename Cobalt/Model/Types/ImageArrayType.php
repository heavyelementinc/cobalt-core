<?php
namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;
use Cobalt\Model\Model;
use Cobalt\Model\Types\Abstracts\OrderedListOfForeignIds;
use Cobalt\Model\Types\Traits\FileHandler;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Cursor;
use MongoDB\Model\BSONDocument;
use Validation\Exceptions\ValidationIssue;

class ImageArrayType extends OrderedListOfForeignIds {
    use FileHandler;
    protected string $operator = '$set';
    public function runJoinQuery(Model $model, array $ids): ?Cursor {
        return $this->__find(['_id' => ['$in' => $ids]], ['limit' => count($ids)]);
    }
    
    function filter($oids) {
        $filesKey = $this->{MODEL_RESERVERED_FIELD__FIELDNAME};
        if($oids === '$_FILES_$' && key_exists($filesKey, $_FILES)) {
            $oids = [];
            // $result = $this->uploadFilesAndGetArrayOfIds($filesKey, ['for' => $this->model->_id ?? null], $_FILES);
            $files = normalize_uploaded_files($_FILES);
            foreach($files[$filesKey] as $index => $arr) {
                $filename = $this->filename($arr);
                $result = $this->__store($arr['tmp_name'], $filename);
                if(!$result) throw new ValidationIssue("Failed to store $arr[file]");
                $oids[] = $result;
            }
            $this->operator = '$addToSet';
        }
        return parent::filter($oids);
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
}