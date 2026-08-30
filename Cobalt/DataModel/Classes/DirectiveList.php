<?php

namespace Cobalt\DataModel\Classes;

use ArrayAccess;
use Cobalt\DataModel\Directives\AllowOverloading;
use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Cobalt\DataModel\Directives\ClassList;
use Cobalt\DataModel\Directives\DefaultValue;
use Cobalt\DataModel\Directives\Filters\Arrays\Each;
use Cobalt\DataModel\Directives\Filters\Clearable;
use Cobalt\DataModel\Directives\Filters\Filter;
use Cobalt\DataModel\Directives\Filters\Max;
use Cobalt\DataModel\Directives\Filters\Min;
use Cobalt\DataModel\Directives\Filters\Nullable;
use Cobalt\DataModel\Directives\Filters\Pattern;
use Cobalt\DataModel\Directives\Filters\Required;
use Cobalt\DataModel\Directives\Filters\Valid;
use Cobalt\DataModel\Directives\Images\PreserveExifData;
use Cobalt\DataModel\Directives\Images\Thumbnail;
use Cobalt\DataModel\Directives\Label;
use Cobalt\DataModel\Directives\Media\Accept;
use Cobalt\DataModel\Directives\Media\Filename;
use Cobalt\DataModel\Directives\Media\MaxResolution;
use Cobalt\DataModel\Directives\Media\MinResolution;
use Cobalt\DataModel\Directives\PrivateValue;
use Cobalt\DataModel\Directives\ReferenceModel;
use Cobalt\DataModel\Directives\StringDirective;
use Cobalt\DataModel\Interfaces\InheritableDirective;
use Cobalt\DataModel\Types\Generic;
use Iterator;
use Override;
use Reflection;
use ReflectionClass;
use TypeError;

/**
 * @implements Iterator<string|int, DirectiveCommon>
 * @implements ArrayAccess<string|int, DirectiveCommon>
 * @package Cobalt\DataModel\Classes
 *
 * @property-read ?Accept $accept
 * @property-read ?AllowOverloading $allow_overloading
 * @property-read ?ClassList $class_list
 * @property-read ?Clearable $clearable
 * @property-read ?DefaultValue $default
 * @property-read ?Each $each
 * @property-read ?Filename $filename
 * @property-read ?Filter $filter
 * @property-read ?Label $label
 * @property-read ?Max $max
 * @property-read ?MaxResolution $max_resolution
 * @property-read ?Min $min
 * @property-read ?MinResolution $min_resolution
 * @property-read ?Nullable $nullable
 * @property-read ?Pattern $pattern
 * @property-read ?PreserveExifData $preserve_exif_data
 * @property-read ?PrivateValue $private
 * @property-read ?ReferenceModel $reference_model
 * @property-read ?Required $required
 * @property-read ?StringDirective $string_directive
 * @property-read ?Thumbnail $thumbnail
 * @property-read ?Valid $valid
 */
class DirectiveList implements Iterator, ArrayAccess {
    /**
     * @var array{allow_overloading:AllowOverloading,default:DefaultValue,external_model:ReferenceModel,max:Max,min:Min,nullable:Nullable,pattern:Pattern,private_value:PrivateValue,required:Required,valid:Valid}
     */
    private array $list = [];
    private array $misses = [];
    function __construct(protected Generic $generic) {

    }
    function hasDirective($directive):bool {
        return $this->__isset($directive);
    }

    function addDirective(DirectiveCommon $directive) {
        $directive->setInstance($this->generic);
        $directive->setModel($this->generic->model);
        $this->{$directive->getName()} = $directive;
    }

    public function __get($name): ?DirectiveCommon {
        // If we do not have an inheritable directive, just return the value
        if (key_exists($name, $this->list)) return $this->list[$name];
        
        // Check if we've traversed the tree once already and came up empty-handed
        if (key_exists($name, $this->misses)) return null;

        // If not found natively, recursively traverse up the tree
        $inheritedDirective = $this->__getInheritedValue($name);
        
        if ($inheritedDirective !== null) {
            // Cache the result within this classList so we don't have to traverse the tree again next time
            $this->list[$name] = $inheritedDirective;
            return $inheritedDirective;
        }

        return null;
    }

    function __set($name, $value) {
        if($value instanceof DirectiveCommon === false) throw new TypeError("Must be an instance of DirectiveCommon");
        $this->list[$name] = $value;
    }
    function __unset($name) {
        if(key_exists($name, $this->list)) unset($this->list[$name]);
        $this->generic->model->directives->__unset($name);
    }
    function __isset($name) {
        if(key_exists($name, $this->list)) {
            return true;
        }
        return !!$this->generic?->model?->directives->__get($name);
    }

    /**
     * Recursively traverses up the model tree to find and extract inherited directive values.
     * 
     * @param string $directiveName The name of the directive (e.g., 'default')
     * @param array $path The accumulated property path from the originating child
     * @return null|DirectiveCommon
     */
    protected function __getInheritedValue(string $directiveName, array $path = []): ?DirectiveCommon {
        $parent = $this->generic->model;

        // If $parent is null, we know we've reached the top of the tree with no matches
        if ($parent === null) {
            $this->misses[] = $directiveName;
            return null;
        }

        // Track our way up by prepending the current fieldname to the path.
        // Layer 1: ['enabled'], Layer 2: ['vowels', 'enabled']
        array_unshift($path, $this->generic->name);

        $parentDirectives = $parent->directives;

        // Check if the parent has a native directive
        if (!key_exists($directiveName, $parentDirectives->list)) {
            return $parentDirectives->__getInheritedValue($directiveName, $path);
        }

        $parentDirective = $parentDirectives->list[$directiveName];

        // Check that this is an inheritable property. Do we need to continue if it's
        // not an inheritable property? It won't change if it's not! I think we can
        // safely abort here with a *null* value?
        if ($parentDirective instanceof InheritableDirective === false) {
            $this->misses[] = $directiveName;
            return null; // $parentDirectives->__getInheritedValue($directiveName, $path);
        }

        $nestedValue = $parentDirective->getValue();
        $found = true;

        // Drill down into the array using our accumulated path
        foreach ($path as $key) {
            if (is_array($nestedValue) && array_key_exists($key, $nestedValue)) {
                $nestedValue = $nestedValue[$key];
            } else {
                // The array branch ends before reaching our target child node
                $found = false; 
                break;
            }
        }

        // If we successfully navigated the array path to find a value, 
        // return a NEW instance of the directive to apply directly to this child node.
        if (!$found) {
            return $parentDirectives->__getInheritedValue($directiveName, $path);
        }
        $className = get_class($parentDirective);
        $newDirective = new $className($nestedValue);
        $newDirective->setInstance($this->generic);
        $newDirective->setModel($parent);
        return $newDirective;
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