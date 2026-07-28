<?php

namespace Cobalt\Model\Traits;

use Cobalt\Model\Attributes\DoNotSet;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Types\MixedType;
use ReflectionException;
use ReflectionProperty;
use TypeError;
/**
 * @experimental
 */
trait PropertyHandler {
    public function __set($property, $value) {
        // If we don't allow undefined schema fields
        // if(!key_exists($property, $this->__schema) && !$this->__schema_allow_undefined_fields) {
        //     throw new TypeError("ERROR: `$property` is not a defined field. Type: " . gettype($value));
        // }
        try {
            $prop = new ReflectionProperty($this, $property);
        } catch(ReflectionException $e) {
            $this->hydrate(
                target: $this->miscFields,
                field_name: $property,
                value: $value,
                model: $this,
                name: $property,
                directives: [],
                instance: null
            );
            return;
            // throw new TypeError("ERROR: `$property` is not a defined field. Type: " . gettype($value));
        }

        if(!$prop::IS_READONLY) throw new TypeError("The property $property must be readonly");
        if($prop->isInitialized($this)) {
            return $this->{$property}->setValue($value);
            // throw new TypeError("The property has been initialized and must not be changed");
        }
        $type = $prop->getType()->getName();
        $type = new $type;
        if(!is_a($type, MixedType::class)) throw new TypeError("All fields must be an instance of MixedType");
        $this->{$property} = $this->newHydrate(
            field_name: $property,
            value: $value,
            type: new $type,
            model: $this,
            directives: $this->__schema[$property] ?? []
        );

    }

    public function newHydrate(string|int $field_name, mixed $value, ?MixedType $type, ?GenericModel $model, ?array $directives = []) {
        $type->setName($field_name);
        $type->setModel($model);
        $type->setDirectives($directives ?? []);
        if($value instanceof DoNotSet === false) $type->setValue($value);
        $type->finalInitialization();
        return $type;
    }
}