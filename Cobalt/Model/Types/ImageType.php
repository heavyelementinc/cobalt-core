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

class ImageType extends ForeignId {
    use FileHandler;
    public function runJoinQuery(Model $model, ?ObjectId $id): null|BSONArray|BSONDocument|Persistable {
        return $this->__findOne(['_id' => $id]);
    }

    function filter($oid) {
        $filesKey = $this->{MODEL_RESERVERED_FIELD__FIELDNAME};
        if($oid === '$_FILES_$' && key_exists($filesKey, $_FILES)) {
            $files = normalize_uploaded_files($_FILES);
            $count = count($files[$filesKey]);
            if($count == 0 || $count >= 2) throw new BadRequest("Too many images uploaded for $filesKey");
            $arr = $files[$filesKey][0];
            $filename = $this->filename($arr);
            $oid = $this->__store($arr['tmp_name'], $filename);
        }
        return parent::filter($oid);
    }

    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        return parent::field($class, $misc, $tag ?? "file-id");
    }

    // public function initDirectives(): array {
    //     return [
    //         // 'operator' => function (&$operators, &$field, &$details) {
    //         //     if($this->operator === '$set') {
    //         //         $operators[$this->operator][$field] = $details;
    //         //         return;
    //         //     }
    //         //     $operators[$this->operator][$this->{MODEL_RESERVERED_FIELD__FIELDNAME}] = ['$each' => $details];
    //         // },
    //         'schema' => [
    //             // $schema
    //             'chunkSize' => new NumberType,
    //             'filename' => new StringType,
    //             'length' => new NumberType,
    //             'uploadDate' => new DateType,
    //             'md5' => new StringType,
    //             '_v' => new NumberType,
    //             'meta' => [
    //                 new ModelType,
    //                 'schema' => [
    //                     'width' => new NumberType,
    //                     'height' => new NumberType,
    //                     'mimetype' => new StringType,
    //                     'accent_color' => new HexColorType,
    //                     'contrast_color' => new HexColorType,
    //                 ]
    //             ]
    //         ]
    //     ];
    // }
}