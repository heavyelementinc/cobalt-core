<?php

namespace Cobalt\Maps;

use ArrayAccess;
use Cobalt\Maps\PersistanceException\DirectiveException;
use Cobalt\SchemaPrototypes\MapResult;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\ResultTranslator;
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
 * The following are definitions for valid schema fields
 * |:- field    -:|:- type -:|:- definition -:|
 * -------------------------------
 * | `get`        | callable | The `get` field |
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

    abstract function __get_schema():array;

    function __initialize_schema($schema = null): void
    {
        $schema = array_merge($this->__get_schema(), $this->__initialized_schema ?? []);
        parent::__initialize_schema($schema);
    }

    /** @return array */
    function bsonSerialize(): array|\stdClass|Document {
        $serializationResult = $this->__dataset;
        return $serializationResult;
    }

    function bsonUnserialize(array $data): void {
        $this->ingest($data);
    }

}