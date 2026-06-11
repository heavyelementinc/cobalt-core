<?php

namespace Cobalt\Commands\Classes;

use Countable;
use Iterator;
use Override;

class CommandList implements Iterator, Countable {
    private int $maxCommandCharLenth = 0;
    public function add(CommandItem $item) {
        $name = $item->getName();
        $this->maxCommandCharLenth = max($this->maxCommandCharLenth, strlen($name));
        $this->items[$name] = $item;
    }

    public function getMaxCommandCharLenth():int {
        return $this->maxCommandCharLenth;
    }

    public function findByCommandName(string $name):?CommandItem {
        if(key_exists($name, $this->items)) return $this->items[$name];
        return null;
    }

    private int $index = 0;
    /** @property CommandItem[] $items */
    private array $items = [];

    #[Override]
    public function current(): mixed {
        return $this->items[$this->key()];
    }

    #[Override]
    public function next(): void {
        $this->index += 1;
    }

    #[Override]
    public function key(): mixed {
        return array_keys($this->items)[$this->index];
    }

    #[Override]
    public function valid(): bool {
        return key_exists($this->key(), $this->items);
    }

    #[Override]
    public function rewind(): void {
        $this->index = 0;
    }

    #[Override]
    public function count(): int {
        return count($this->items);
    }
}