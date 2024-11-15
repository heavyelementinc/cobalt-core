<?php

namespace Cobalt\Maps;

use ArrayAccess;
use Cobalt\Maps\PersistanceException\DirectiveException;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\SchemaPrototypes\MapResult;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\ResultTranslator;
use Drivers\Database;
use Exception;
use Exceptions\HTTP\BadRequest;
use Iterator;
use JsonSerializable;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\Document;
use SchemaDebug;
use stdClass;
use TypeError;
use Validation\Exceptions\ValidationFailed;
use Validation\Exceptions\ValidationIssue;

/**
 * Schema
 * ======
 * The Cobalt Engine Normalization and Database Persistance Engine
 * 
 * The abstract Schema class is designed to persist across database storage
 * and retrieval, it provides a convenient method for mutating data in a 
 * predictable way, and an easy syntax for setting and getting data with 
 * prototypal inheritance for classes.
 * 
 * The following are definitions for valid schema directives
 * |:- directive -:|:- type -:|:- return type         -:|:- definition -:|
 * --------------------------------------------------
 * | `get`           | callable | mixed                                          | The `get` field |
 * | `filter`        | callable | mixed (return is validated and stored)         | Called within `try` block, catches ValidationContinue, ValidationIssue, ValidationFailed |
 * | `set`           | callable | mixed (return is stored)                       | A function that's called when a field is set (AFTER validation) |
 * | `pattern`       | mixed    | string | A RegEx pattern to match against input |
 * | `pattern_flags` | mixed    | string | A string of RegEx flags |
 * | 
 * 
 * Schemas will return field data as Schema<Type>Result objects. These
 * provide a convenient way to access and mutate data through prototypical
 * inheritance.
 * 
 * 
 * 
 * @package Cobalt
 */
abstract class PersistanceMap extends GenericMap implements Persistable {
    
    protected bool $index_add_id_checkbox = false;
    private bool $__include_immutables = false;
    protected ?Database $__manager = null;

    function __construct($doc = null, $schema = [], $__namePrefix = "") {
        parent::__construct($doc, $schema, $__namePrefix);
    }

    function __include_immutable_fields(bool $value) {
        $this->__include_immutables = $value;
    }

    function __set_index_checkbox_state(bool $state) {
        $this->index_add_id_checkbox = $state;
    }

    function __get_index_checkbox_state(): bool {
        return $this->index_add_id_checkbox;
    }

    /**
     * 
     * @return array 
     */
    abstract function __get_schema():array;

    public function __get_manager():?Database {
        return $this->__manager;
    }

    public function __get_editor_template_path():?string {
        return null;
    }

    abstract function __set_manager(?Database $manager = null):?Database;

    function __initialize_schema($schema = null): void {
        $this->__manager = $this->__set_manager();
        $this->__schema['creationDate'] = [
            'type' => new DateResult,
            'set' => false,
            'immutable' => true,
            'default' => fn () => new UTCDateTime($this->id->getTimestamp() * 1000),
        ];
        $schema = array_merge($this->__get_schema(), $this->__schema ?? []);
        parent::__initialize_schema($schema);
    }

    /** @return array */
    function bsonSerialize(): array|\stdClass|Document {
        $serializationResult = $this->__dataset;
        if($this->__include_immutables) {
            $serializationResult['_id'] = $this->id;
        }
        return $serializationResult;
    }

    function bsonUnserialize(array $data): void {
        $this->ingest($data);
    }
}

