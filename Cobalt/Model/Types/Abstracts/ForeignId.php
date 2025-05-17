<?php

namespace Cobalt\Model\Types\Abstracts;

use Cobalt\Model\Attributes\Directive;
use Cobalt\Model\Attributes\Prototype;
use Cobalt\Model\Model;
use Cobalt\Model\Types\DateType;
use Cobalt\Model\Types\HexColorType;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\ModelType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\StringType;
use Exception;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use MongoDB\Driver\Cursor;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use Validation\Exceptions\ValidationIssue;

abstract class ForeignId extends MixedType {
    public ?ObjectId $raw = null;
    abstract function getModel(): Model;

    /**
    * Called once per item in the OrderedList *before* as the value is set in
    * the array that will be used to join the foreign keys in the database
    * @param mixed $id 
    * @return null|ObjectId Return an ObjectId or `null`, if null, the value will be ignored
    */
   abstract function interpretRawValue(&$id): ?ObjectId;
   abstract function storeValue(ObjectId $id): ?ObjectId;
   
   /**
    * This function returns the path to a template which can be used to generate
    * each item in an object-gallery or file-gallery element
    * @return string - Path
    */
   abstract function fieldItemTemplate(): string;

   /**
     * Called when displaying the item as a table column
     * @return string 
     */
    public function display(): string {
        return "[Object $this->raw]";
    }

    // If needed, you can override this functionality (as we do with the ImageArrayType)
    public function runJoinQuery(Model $model, ?ObjectId $id): null|BSONArray|BSONDocument|Persistable {
        return $model->findOne(['_id' => $id]);
    }

    /**
     * 
     * @param int $limit 
     * @param int $skip 
     * @param string $sortField 
     * @param int $sortDirection 
     * @param string $search 
     * @return array {cursor: ?Cursor, count: int}
     */
    public function queryForObjects(int $limit, int $skip, string $sortField = "_id", int $sortDirection = -1, string $search = "", bool $excludeCurrent = true): array {
        // if($search)
        $query = [];
        if($excludeCurrent) {
            $query['_id'] = ['$nin' => $this->raw];
        }
        $options = ['limit' => (int)$limit, 'skip' => (int)$skip, 'sort' => [$sortField => $sortDirection]];
        $model = $this->getModel();
        return [
            'cursor' => $model->find($query, $options),
            'count' => $model->count($query, $options)
        ];
    }

    public function setValue($originalValue):void {
        if($originalValue === null) {
            parent::setValue(null);
            return;
        }
        if($originalValue instanceof ObjectId == false) {
            $originalValue = $this->interpretRawValue($originalValue);
            if(is_null($originalValue)) {
                parent::setValue(null);
                return;
            }
            if($originalValue instanceof ObjectId == false) {
                parent::setValue($this->directiveOrNull("default"));
            }
        }
        
        $this->raw = $originalValue;

        $model = $this->getModel();
        
        // Now that we have all our IDs, let's find the details
        $result = $this->runJoinQuery($model, $originalValue);
        
        parent::setValue($result);
    }

    public function filter($oid) {
        if(!$oid) throw new ValidationIssue('$oid must not be blank!');
        try {
            $_id = new ObjectId($oid);
        } catch (Exception $e) {
            throw new ValidationIssue('Specified $oid contains invalid characters');
        }
        $this->setValue($_id);
        return $_id;
    }


    public function onUpdateConfirmed($value):void {
        update("[name='$this->name']", ['outerHTML' => $this->field()]);
    }

    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        // Get any fallback data we need
        [$data, $attrs] = $this->defaultFieldData($misc);
        // Check if the 'accept' field is set
        $accept = $this->directiveOrNull("accept") ?? "";
        if($accept) $accept = "accept=\"$accept\"";
        // Check if the tag is not null
        $tag = $tag ?? "object-id";
        // Get the route
        $route = route($this->model::class."@__model", [(string)$this->model->_id, $this->name]);
        // Build our gallery tag
        $gallery = "<$tag $attrs $accept max='1' method=\"GET\" action=\"$route\">";
        // Loop through all the objects that belong to this field
        // foreach($this->getValue() as $index => $item) {
        if($this->raw) {
            $gallery .= view($this->fieldItemTemplate(), ['item' => $this->getValue(), 'object_id' => $this->raw, 'ordered_list' => $this]);
        }
        // }
        $gallery .= "</$tag>";
        return $gallery;
    }

    public function __toString(): string {
        return (string)$this->raw;
    }

    function __get($name) {
        return $this->value->{$name};
    }

    function __set($name, $value) {
        $this->value->{$name} = $value;
    }

    function __isset($name) {
        return isset($this->value->{$name});
    }

    // public function initDirectives(): array {
    //     return [
    //         // 'operator' => function (&$operators, &$field, &$details) {
    //         //     if($this->operator === '$set') {
    //         //         $operators[$this->operator][$field] = $details;
    //         //         return;
    //         //     }
    //         //     $operators[$this->operator][$this->name] = ['$each' => $details];
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