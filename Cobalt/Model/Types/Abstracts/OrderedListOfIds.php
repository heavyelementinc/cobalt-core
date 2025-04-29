<?php

namespace Cobalt\Model\Types\Abstracts;
use Cobalt\Model\Model;
use Exception;
use MongoDB\BSON\ObjectId;
use Validation\Exceptions\ValidationIssue;
use Cobalt\Model\Attributes\Prototype;
use Cobalt\Model\Types\MixedType;
use MongoDB\Driver\Cursor;

abstract class OrderedListOfIds extends MixedType {
    public array $raw = [];

    abstract function getModel(): Model;
    /**
     * Prepare an $id for storage. 
     * @param mixed $id 
     * @return null|ObjectId Return an ObjectId or `null`, if null, the value will be ignored
     */
    abstract function restoreValue(&$id): ?ObjectId;
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
        return (string)count($this->raw);
    }

    // If needed, you can override this functionality (as we do with the ImageArrayType)
    public function queryForValues(Model $model, array $ids): ?Cursor {
        return $model->find(['_id' => ['$in' => $ids]], ['limit' => count($ids)]);
    }

    public function setValue($images):void {
        $ids = [];
        foreach($images as $value) {
            // This is the primary data structure
            if($value instanceof ObjectId) {
                $ids[] = $value;
                continue;
            }
            $ids[] = $this->restoreValue($value);
        }
        $this->raw = $ids;

        $model = $this->getModel();
        
        // Now that we have all our IDs, let's find the details
        $results = $this->queryForValues($model, $ids);
        $unordered = [];
        // if($result) $details = iterator_to_array($result);
        foreach($results as $result) {
            $unordered[(string)$result->_id] = $result;
        }

        $ordered = [];
        foreach($ids as $id) {
            $ordered[] = $unordered[(string)$id];
        }

        parent::setValue($ordered);
    }

    function filter($oids) {
        if(!empty($_FILES)) {
            // $this->readFile();
        }
        $value = [];
        foreach($oids as $val) {
            if(!$val) throw new ValidationIssue("Contains invalid file IDs");
            try {
                $value[] = new ObjectId($val);
            } catch (Exception $e) {
                throw new ValidationIssue("`$val` was not a valid ObjectId");
            }
        }
        return $value;
    }

    #[Prototype]
    protected function field(string $class = "", array $misc = [], ?string $tag = null):string {
        // $gallery = $this->input($class, $misc); // "<input class='input-type--file' type='file' name='$this->name' multiple='multiple'>";
        [$data, $attrs] = $this->defaultFieldData($misc);
        $accept = $this->directiveOrNull("accept") ?? "";
        if($accept) $accept = "accept=\"$accept\"";
        $tag = $tag ?? "object-gallery";
        $route = route($this->model::class."@__model", [(string)$this->model->_id, $this->name]);
        $gallery = "<$tag $attrs $accept method=\"GET\" action=\"$route\">";
        foreach($this->getValue() as $item) {
            $gallery .= view($this->fieldItemTemplate(), ['item' => $item, 'ordered_list' => $this]);
        }
        $gallery .= "</$tag>";
        return $gallery;
    }
}