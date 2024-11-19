<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\GenericModel;
use Cobalt\Model\Model;

class ModelType extends MixedType {
    public function setValue($value):void {
        // Let's check if the value is already a Model (this could be because 
        // we) persisted some data from the DB, etc.
        if($value instanceof Model) {
            $this->value = $value;
            $this->isSet = true;
            return;
        }
        // Otherwise, we'll grab the schema for this value and we'll instance
        // a GenericModel
        $model = ($this->hasDirective('schema')) ? $this->getDirective('schema') : [];
        $this->value = new GenericModel($model, $value);
        $this->isSet = true;
    }

    public function __get($name) {
        if(isset($this->{$name})) return $this->{$name};
        return parent::__get($name);
    }

    public function __set($name, $value) {
        $this->{$name} = $value;
    }

    public function __isset($property) {
        return isset($this->{$property});
    }
}