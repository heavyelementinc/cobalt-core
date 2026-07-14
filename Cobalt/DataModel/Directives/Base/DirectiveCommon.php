<?php

namespace Cobalt\DataModel\Directives\Base;

use Attribute;
use Cobalt\DataModel\Filters\FilterIssue;
use Cobalt\DataModel\Types\Generic;
use Cobalt\DataModel\Types\DictionaryType;
use ReflectionClass;

/**
 * @property mixed $value
 * @package Cobalt\DataModel\Directives\Base
 */
abstract class DirectiveCommon {
    protected Generic $type;
    protected ?DictionaryType $model = null;
    protected string $name;
    protected bool $isMethod;

    function setInstance(Generic $instance) {
        $this->type = $instance;
    }

    function getInstance():Generic {
        return $this->type;
    }

    function setModel(?DictionaryType $model) {
        $this->model = $model;
    }

    function getModel():DictionaryType {
        return $this->model;
    }

    function setName(string $name){ 
        $this->name = $name;
    }

    function getName():string {
        if(isset($this->name)) return $this->name;
        return strtolower((new ReflectionClass($this))->getShortName());
    }

    abstract function setValue(mixed $value):void;
    abstract function getValue():mixed;

    function __get($name) {
        switch($name) {
            case "value":
                return $this->getValue();
            default:
                return null;
        }
    }

    function callModelMethod(string $methodName, array $args = []) {
        $args = [
            $this,
            ...$args
        ];
        if(method_exists($this->type->rootModel,$methodName)) {
            return $this->type->rootModel->{$methodName}(...$args);
        }
        return $this->model->{$methodName}(...$args);
    }

}