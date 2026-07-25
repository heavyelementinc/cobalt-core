<?php

namespace Cobalt\Database\Classes;

use Cobalt\Database\Interfaces\DbCollection;
use Cobalt\DataModel\Types\DocumentType;
use Cobalt\Model\Model;
use Iterator;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\CursorInterface;

class CobaltCursor implements Iterator {
    private int $index = 0;
    
    function __construct(
        public null|CursorInterface|array $data,
        public array|object $query,
        public ?DbCollection $collection = null
    ) {}

    public function current(): mixed {
        if($this->data instanceof CursorInterface) return $this->data->current();
        return $this->data[$this->index];
    }

    public function next(): void {
        if($this->data instanceof CursorInterface) $this->data->next();
        $this->index += 1;
    }

    public function key(): mixed {
        if($this->data instanceof CursorInterface) return $this->data->key();
        return $this->index;
    }

    public function valid(): bool {
        if($this->data instanceof CursorInterface) return $this->data->valid();
        return key_exists($this->index, $this->data);
    }

    public function rewind(): void {
        if($this->data instanceof CursorInterface) $this->data->rewind();
        $this->index = 0;
    }

    public function count():?int {
        if($this->data instanceof CursorInterface) {
            return $this->collection->count($this->query['filter'], $this->query['options']);
        }
        return count($this->data);
    }

}