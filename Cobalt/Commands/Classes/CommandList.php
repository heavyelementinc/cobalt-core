<?php

namespace Cobalt\Commands\Classes;

use Cobalt\Commands\Exceptions\CommandError;
use Countable;
use Exception;
use Iterator;
use Override;
use ReflectionMethod;
use ReflectionParameter;

class CommandList implements Iterator, Countable {
    private int $maxCommandCharLenth = 0;
    private CommandItem $default;
    
    public function add(CommandItem $item, bool $default = false) {
        $name = $item->getName();
        $this->maxCommandCharLenth = max($this->maxCommandCharLenth, strlen($name));
        $this->items[$name] = $item;
        if($default) {
            if($this->hasDefaultCommand()) throw new CommandError(sprintf("Command `%s` is set as the default command but `%s` is already the default", $item->getName(), $this->default->getName()));
            $this->setDefaultCommand($item);
        }
    }

    public function setDefaultCommand(CommandItem $item) {
        $reflection = new ReflectionMethod($item->getInstance(), $item->getFunction());
        $params = $reflection->getParameters();
        /** @var ReflectionParameter $param */
        foreach($params as $param) {
            if(!$param->isOptional()) throw new CommandError(sprintf("Command `%s` is unfit default candidate. (Must not require any arguments!)", $item->getName()));
        }
        $this->default = $item;
    }

    public function hasDefaultCommand():bool {
        return isset($this->default);
    }

    public function getDefaultCommand():?CommandItem {
        if(!$this->hasDefaultCommand()) return null;
        return $this->default;
    }


    public function getMaxCommandCharLenth():int {
        return $this->maxCommandCharLenth;
    }

    public function findByCommandName(?string $name):?CommandItem {
        if(key_exists($name, $this->items)) return $this->items[$name];
        if($this->hasDefaultCommand()) return $this->getDefaultCommand();
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