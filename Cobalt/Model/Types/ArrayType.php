<?php

namespace Cobalt\Model\Types;

use ArrayAccess;
use Cobalt\Model\Attributes\Directive;
use Cobalt\Model\Exceptions\DirectiveDefinitionFailure;
use Cobalt\Model\Traits\Hydrateable;
use Error;
use Stringable;

class ArrayType extends MixedType implements ArrayAccess, Stringable {
    use Hydrateable;

    public function setValue($array):void {
        $this->value = [];
        $each = null;
        
        if($this->hasDirective('each')) $each = $this->getDirective('each');
        foreach($array as $index => $value) {
            if($each) {
                if($each[0] instanceof MixedType) $each['type'] = $each[0];
                else if($each instanceof MixedType) $each = ['type' => $each];
                else if($each['type'] instanceof MixedType) {/* no-op */}
                else throw new DirectiveDefinitionFailure("Failed to find explicit 'type' declaration");
                // $this->value[$index] = new $each[0]($each, $value);
                // $this->value[$index]->setName($this->name.".$index");
                $instance = new $each[0]($each, $value);
                $this->hydrate(
                    target: $this->value,
                    field_name: $index,
                    value: $value,
                    model: $this->model,
                    name: $this->name.".$index",
                    directives: $each,
                    instance: $instance,
                );
            } else {
                $this->hydrate(
                    target: $this->value,
                    field_name: $index,
                    value: $value,
                    model: $this->model,
                    name: $this->name.".$index"
                );
            }
        }
        $this->isSet = true;
    }

    public function __toString(): string {
        return implode(", ", $this->serialize());
    }

    public function serialize() {
        $value = [];
        foreach($this->value as $i => $v) {
            $value[$i] = $v->serialize();
        }
        return $value;
    }

    public function offsetExists(mixed $offset): bool {
        return key_exists($offset, $this->value ?? []);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->value[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->hydrate(
            target: $this->value,
            field_name: $offset,
            value: $value,
            model: $this->model,
            name: $this->name.".$offset",
            instance: null,
        );
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->value[$offset]);
    }

}