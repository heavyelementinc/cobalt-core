<?php

namespace Cobalt\DataModel\Directives\Base;

use Attribute;
use Iterator;
use Override;

// #[Attribute()]
abstract class AbstractArrayDirective extends DirectiveCommon implements Iterator {
    protected array|string $value;

    function __construct(array|string $array){
        $this->setValue($array);
    }

    #[Override]
    public function setValue(mixed $value): void {
        $this->value = $value;
        $this->isMethod = is_string($value);
    }

    /**
     * @return array
     */
    #[Override]
    public function getValue():mixed {
        if(is_string($this->value)) {
            return $this->callModelMethod($this->value, [$this->type->raw]);
        }
        return $this->value;
    }

    private $index = 0;
    #[Override]
    public function current(): mixed {
        return $this->value[$this->key()];
    }

    #[Override]
    public function next(): void {
        $this->index += 1;
    }

    #[Override]
    public function key(): mixed {
        return array_keys($this->value)[$this->index];
    }

    #[Override]
    public function valid(): bool {
        return key_exists($this->key(), $this->value);
    }

    #[Override]
    public function rewind(): void {
        $this->index = 0;
    }
}