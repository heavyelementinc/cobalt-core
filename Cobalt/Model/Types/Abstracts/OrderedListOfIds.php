<?php

namespace Cobalt\Model\Types\Abstracts;

use ArrayAccess;
use Cobalt\Model\Model;
use Exception;
use MongoDB\BSON\ObjectId;
use Validation\Exceptions\ValidationIssue;
use Cobalt\Model\Attributes\Prototype;
use Cobalt\Model\Types\MixedType;
use Iterator;
use MongoDB\Driver\Cursor;

abstract class OrderedListOfIds extends MixedType implements Iterator {
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
        $value = [];
        foreach($oids as $val) {
            if(!$val) throw new ValidationIssue("Contains invalid file IDs");
            try {
                $value[] = new ObjectId($val);
            } catch (Exception $e) {
                throw new ValidationIssue("`$val` was not a valid ObjectId");
            }
        }
        $this->setValue($oids);
        
        return $value;
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
        $tag = $tag ?? "object-gallery";
        // Get the route
        $route = route($this->model::class."@__model", [(string)$this->model->_id, $this->name]);
        // Build our gallery tag
        $gallery = "<$tag $attrs $accept method=\"GET\" action=\"$route\">";
        // Loop through all the objects that belong to this field
        foreach($this->getValue() as $index => $item) {
            $gallery .= view($this->fieldItemTemplate(), ['item' => $item, 'object_id' => $this->raw[$index], 'ordered_list' => $this]);
        }
        $gallery .= "</$tag>";
        return $gallery;
    }

    private int $index = 0;
    public function current(): mixed {
        return $this->value[$this->index];
    }

    public function next(): void {
        $this->index += 1;
    }

    public function key(): mixed {
        return $this->index;
    }

    public function valid(): bool {
        if($this->index < 0) return false;
        return $this->index <= (count($this->value) - 1);
    }

    public function rewind(): void {
        $this->index = 0;
    }

    public function offsetExists(mixed $offset): bool {
        return key_exists($offset, $this->value);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->value[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->value[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->value[$offset]);
    }
}