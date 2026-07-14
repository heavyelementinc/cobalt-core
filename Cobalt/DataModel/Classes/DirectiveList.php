<?php

namespace Cobalt\DataModel\Classes;

use ArrayAccess;
use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Cobalt\DataModel\Types\Generic;
use Iterator;
use Override;
use TypeError;

class DirectiveList implements Iterator, ArrayAccess {
    /**
     * @var array{allow_overloading:AllowOverloading,default:DefaultValue,external_model:ExternalModel,max:Max,min:Min,nullable:Nullable,pattern:Pattern,private_value:PrivateValue,required:Required,valid:Valid}
     */
    private array $list = [];
    function __construct(protected Generic $generic) {

    }
    function hasDirective($directive) {
        return $this->__isset($directive);
    }

    function addDirective(DirectiveCommon $directive) {
        $directive->setInstance($this->generic);
        $directive->setModel($this->generic->model);
        $this->{$directive->getName()} = $directive;
    }

    function __get($name) {
        return $this->list[$name] ?? null;
    }

    function __set($name, $value) {
        if($value instanceof DirectiveCommon === false) throw new TypeError("Must be an instance of DirectiveCommon");
        $this->list[$name] = $value;
    }
    function __unset($name) {
        unset($this->list[$name]);
    }
    function __isset($name) {
        return key_exists($name, $this->list);
    }

    #[Override]
    public function offsetExists(mixed $offset): bool {
        return $this->__isset($offset);
    }

    #[Override]
    public function offsetGet(mixed $offset): mixed {
        return $this->__get($offset)?->getValue();
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void {
        $this->__set($offset, $value);
    }

    #[Override]
    public function offsetUnset(mixed $offset): void {
        $this->__unset($offset);
    }

    private int $index = 0;
    #[Override]
    public function current(): mixed {
        return $this->list[$this->key()];
    }

    #[Override]
    public function next(): void {
        $this->index += 1;
    }

    #[Override]
    public function key(): mixed {
        return array_keys($this->list)[$this->index];
    }

    #[Override]
    public function valid(): bool {
        return key_exists($this->key(), $this->list);
    }

    #[Override]
    public function rewind(): void {
        $this->index = 0;
    }

}