<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Prototype;
use Cobalt\Model\Model;
use Cobalt\Model\Types\Abstracts\ForeignId;
use Cobalt\Model\Types\Traits\FileHandler;
use Exceptions\HTTP\BadRequest;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;

class FileType extends ForeignId {
    use FileHandler;
    public function runJoinQuery(Model $model, ?ObjectId $id): null|BSONArray|BSONDocument|Persistable {
        return $this->__findOne(['_id' => $id]);
    }

    function filter($oid) {
        $filesKey = $this->{MODEL_RESERVERED_FIELD__FIELDNAME};
        if($oid === '$_FILES_$' && key_exists($filesKey, $_FILES)) {
            $files = normalize_uploaded_files($_FILES);
            $count = count($files[$filesKey]);
            if($count == 0 || $count >= 2) throw new BadRequest("Too many files uploaded for $filesKey");

            $arr = $files[$filesKey][0];

            $this->filter_attributes_upload($arr['tmp_name']);

            $filename = $this->filename($arr);
            $oid = $this->__store($arr['tmp_name'], $filename);
        } else {
            $oid = $this->filter_attributes_objectid($oid);
        }
        if(!$oid) throw new BadRequest("Failed to upload files to database");

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
}