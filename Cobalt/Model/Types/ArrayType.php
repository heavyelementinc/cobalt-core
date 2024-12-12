<?php

namespace Cobalt\Model\Types;

use ArrayAccess;
use Cobalt\Model\Attributes\Directive;
use Cobalt\Model\Exceptions\DirectiveDefinitionFailure;
use Cobalt\Model\Traits\Hydrateable;
use Stringable;

class ArrayType extends MixedType implements ArrayAccess, Stringable {
    use Hydrateable;

    public function setValue($array):void {
        $this->value = [];
        $each = $this->getDirective('each');
        foreach($array as $index => $value) {
            if($each) {
                if($each[0] instanceof MixedType) $each['type'] = $each[0];
                else if($each instanceof MixedType) $each = ['type' => $each];
                else if($each['type'] instanceof MixedType) {/* no-op */}
                else throw new DirectiveDefinitionFailure("Failed to find explicit 'type' declaration");
                $this->value[$index] = new $each[0]($each, $value);
                $this->value[$index]->setName($this->name.".$index");
                $this->hydrate($this->value, $index, $value, null, $this->model, $this->name.".$index");
            } else {
                $this->hydrate($this->value, $index, $value, null, $this->model, $this->name.".$index");
            }
        }
        $this->isSet = true;
    }

    public function __toString(): string {
        return implode(", ", $this->__getStorable());
    }

    public function __getStorable() {
        $value = [];
        foreach($this->value as $i => $v) {
            $value[$i] = $v->__getStorable();
        }
        return $value;
    }

    public function offsetExists(mixed $offset): bool {
        return key_exists($offset, $this->value);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->value[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->hydrate($this->value, $offset, $value, null, $this->model, $this->name.".$offset");
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->value[$offset]);
    }

}