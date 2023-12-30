<?php

namespace Cobalt;

use MongoDB\BSON\ObjectId;

class SubMap extends PersistanceMap {
    private array $__stored;

    function __construct($document = null, array $schema= []) {
        $this->id = new ObjectId;
        $this->__store_schema($schema);
        $this->__initialize_schema();
        if($document !== null) $this->ingest($document);
    }

    function __store_schema(array $value):void {
        $this->__stored = $value;
    }

    function __get_schema(): array {
        $var = $this->__stored;
        unset($this->_stored);
        return $var;
    }
}