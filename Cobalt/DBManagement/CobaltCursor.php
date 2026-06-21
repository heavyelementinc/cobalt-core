<?php

namespace Cobalt\DBManagement;

use Cobalt\Model\Model;
use Iterator;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\CursorInterface;

class CobaltCursor implements Iterator {
    private int $index = 0;
    
    function __construct(
        public CursorInterface|array $data,
        public ?Model $model = null
    ) {}

    public function current(): mixed {
        if($this->data instanceof CursorInterface) return $this->data->current();
        $model = new $this->model;
        $model->bsonUnserialize($this->data[$this->index]);
        return $model;
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
        if($this->data instanceof CursorInterface) return $this->model->count($this->data?->query->filter, $this->data?->query->options);
        return count($this->data);
    }

}