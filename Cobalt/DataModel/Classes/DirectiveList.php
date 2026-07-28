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
use Cobalt\DataModel\Types\Generic;
use Iterator;
use Override;
use TypeError;

/**
 * @implements Iterator<string|int, DirectiveCommon>
 * @implements ArrayAccess<string|int, DirectiveCommon>
 * @package Cobalt\DataModel\Classes
 *
 *
 *
 *
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

    function __get($name):?DirectiveCommon {
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