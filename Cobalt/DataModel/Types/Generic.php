<?php

namespace Cobalt\DataModel\Types;

use Cobalt\DataModel\Types\DictionaryType;
use Cobalt\DataModel\Classes\Undefined;
use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Cobalt\DataModel\Classes\DirectiveList;
use Cobalt\DataModel\Filters\FilterResult;
use Cobalt\DataModel\Traits\GenericFields;
use Cobalt\DataModel\Traits\GenericFilters;
use Cobalt\DataModel\Traits\GenericPrototypes;
use JsonSerializable;
use Override;
use ReflectionAttribute;
use ReflectionClass;
use Serializable;
use Stringable;
use TypeError;

/**
 * The final boss of generic types in Cobalt Engine. *All* schemas and their 
 * fields are based on this abstract class.
 * 
 * @property mixed|null $value
 * @property mixed|Undefined $raw - Returns the raw value OR Undefined
 * @property string $name
 * @property ?DictionaryType $model
 * @property DirectiveList $directives
 * @package Cobalt\DataModel\Types
 */
abstract class Generic implements Stringable, JsonSerializable {
    use GenericPrototypes, GenericFilters, GenericFields;
    protected mixed $value;
    protected string $name;
    protected string $fieldname;
    // An array may be a model
    protected null|DictionaryType|ArrayType $model = null;
    protected ?DictionaryType $rootModel = null;
    protected DirectiveList $directives;

    function __construct(null|DictionaryType|ArrayType $model = null, ?DictionaryType $rootModel = null) {
        if($model) {
            $this->setModel($model);
            $this->setRootModel($rootModel);
            
            // Set up filter results
            $this->filterResult = $model->filterResult ?? new FilterResult();
            $this->filterResult->setModel($rootModel);
        }

        $this->directives = new DirectiveList($this);
        // Handle built-in attributes
        $class = new ReflectionClass($this);
        $classAttributes = $class->getAttributes();
        $this->handleAttributes($classAttributes, $this->directives);
    }

    /**
     * @param ReflectionAttribute[] $attributes 
     * @param DirectiveList $list 
     * @return void 
     */
    static function handleAttributes(array $attributes, DirectiveList $list) {
        foreach($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if($instance instanceof DirectiveCommon == false) continue;
            $list->addDirective($instance);
        }
    }

    /**
     * Called when typecasing this Generic into a string
     * @return string 
     */
    #[Override]
    public function __toString(): string {
        return $this->display() ?? "";
    }

    /**
     * Returns the current Generic in a way that's suitable for 
     * comparison with the Valid directive.
     * 
     * The option $key will be compared to this array using `in_array`
     * @return null|array<int|string>
     */
    public function getValidComparisonValues(): null|array {
        return [$this->value];
    }

    #[Override]
    public function jsonSerialize(): mixed {
        return $this->getValue();
    }

    const SERIALIZE_MODE_ALL_FIELDS = 1;
    const SERIALIZE_MODE_ONLY_MODIFIED = 2;
    /**
     * Returns the raw data that's suitable for database storage
     * @return mixed 
     */
    abstract function serialize(int $mode = self::SERIALIZE_MODE_ALL_FIELDS);
    /**
     * Sets the value of this Generic
     * @param mixed $mixed 
     * @return Generic 
     */
    abstract function setValue($mixed):void;

    /**
     * Returns the value of this Genric. This method should
     * always return the canonincal value (either it's explicit value
     * or the value as defined by computed directives).
     * 
     * It is called by the $generic->value getter!
     * 
     * Use `$generic->value ?? "Some value"` for nullish coalescence
     * @return mixed the canonincal value of this Generic
     */
    public function getValue(): mixed {
        if(!isset($this->value)) {
            if($this->directives->hasDirective('default')) {
                return $this->directives->default->getValue();
            } else {
                return null;
            }
        }
        return $this->value;
    }

    function __get($name) {
        switch($name) {
            case "value":
                return $this->getValue();
            case "raw":
                return (isset($this->value)) ? $this->value : new Undefined();
            case "name":
                return $this->name;
            case "model":
                return $this->model;
            case "root":
            case "rootModel":
                return $this->rootModel;
            case "directives":
                return $this->directives;
        }

        if(isset($this->directive->{$name})) {
            return $this->directives->{$name};
        }
    }

    function __set($name, $value) {
        switch($name) {
            case "value":
                return $this->setValue($value);
            case "name":
                return $this->setName($value);
            case "model":
                return $this->setModel($value);
            case "root":
            case "rootModel":
                return $this->rootModel = $value;
            default:
                throw new TypeError("Field `$name` is not defined and this model does not support overloading");
        }
    }

    /**
     * Defines the parent model
     * @param DictionaryType|ArrayType $model 
     * @return void 
     */
    function setModel(DictionaryType|ArrayType $model) {
        $this->model = $model;
    }

    function setRootModel(DictionaryType $model) {
        $this->rootModel = $model;
    }

    /**
     * Set the fieldname of this Generic
     * @param string $name 
     * @return void 
     */
    function setName(string $name, ?string $fieldname = null) {
        $this->name = $name;
        $this->fieldname = $fieldname ?? $name;
    }

    /**
     * Returns the list of directives for this Generic
     * @return DirectiveList
     */
    function getDirectives():DirectiveList {
        return $this->directives;
    }

    function addDirective(DirectiveCommon $directive):self {
        $this->directives[$directive->getName()] = $directive;
        return $this;
    }

    function getFieldDotNotation(?string $prefix = null) {
        $fieldname = $this->name ?? "";//throw new TypeError("Failed to find fieldname");
        $current = $this->model;
        while(true) {
            if(is_null($current)) break;
            if($current === $this->rootModel) break;
            $name = $current->name;
            if($name) $fieldname = "$name.$fieldname";
            $current = $current->model;
        }
        return $fieldname;
    }
}