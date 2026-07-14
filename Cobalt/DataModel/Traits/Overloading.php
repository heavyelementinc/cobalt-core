<?php

namespace Cobalt\DataModel\Traits;

use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Cobalt\DataModel\Directives\Filters\Nullable;
use Cobalt\DataModel\Filters\FilterResult;
use Cobalt\DataModel\Types\ArrayType;
use Cobalt\DataModel\Types\BooleanType;
use Cobalt\DataModel\Types\DictionaryType;
use Cobalt\DataModel\Types\StringType;
use Cobalt\DataModel\Types\Generic;
use Cobalt\DataModel\Types\NumberType;
use MongoDB\BSON\ObjectId;
use TypeError;

trait Overloading {
    protected bool $__allowOverloadedFilterFields = false;
    public readonly FilterResult $filterResult;

    protected array $_fields = [];
    protected int $_fields_iterator_index = 0;
    // abstract public function addDirective(DirectiveCommon $directive, Generic $generic);
    /**
     * @property array<Generic> $__overloaded_values
     */
    protected array $__overloaded_fields = [];
    function __get($name) {
        if(!isset($this->__overloaded_fields[$name])) return null;
        return $this->__overloaded_fields[$name];
    }

    function __set($name, $value) {
        // Catch the 'value' field
        if($name === "value") {
            return $this->setValue($value);
        }
        $field = $this->__hydrate($name, $value);
        array_push($this->_fields, $name);
        $this->__overloaded_fields[$name] = $field;
    }

    function __hydrate($name, $value, ?Generic $as = null):Generic {
        if($as) {
            $field = new $as($this, $this->rootModel);
        } else {
            $type = gettype($value);
            switch($type) {
                case "string":
                    $field = new StringType($this, $this->rootModel);
                    break;
                case "boolean":
                    $field = new BooleanType($this, $this->rootModel);
                    break;
                case "integer":
                case "double":
                case "float":
                    $field = new NumberType($this, $this->rootModel);
                    break;
                case "array":
                    if(array_is_list($value)) {
                        $field = new ArrayType($this, $this->rootModel);
                        break;
                    }
                case "object":
                    $field = new DictionaryType($this, $this->rootModel);
                    break;
                case "NULL":
                    // Set a directive
                    $field = new StringType($this, $this->rootModel);
                    $field->addDirective(new Nullable(true));
                    break;
                default:
                    throw new TypeError("Don't know how to handle $name with type of $type");
            }
        }
        // $field->setModel($this);
        $field->setName($name, $this->composeFieldname($name));
        $field->setValue($value);
        return $field;
    }

    function __isset($name) {
        return key_exists($name, $this->__overloaded_fields);
    }

    function __unset($name) {
        // When we clean up, we need to delete this field
        unset($this->__overloaded_fields[$name]);
        // And delete the fieldname
        unset($this->_fieldnames[$name]);
    }

    abstract protected function composeFieldname(string|int $name):string;
}