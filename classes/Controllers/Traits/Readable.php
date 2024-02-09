<?php

namespace Controllers\Traits;

use Cobalt\Maps\GenericMap;
use Drivers\Database;
use MongoDB\BSON\ObjectId;
use Validation\Normalize;

trait Readable {
    protected Database $manager;
    /**
     * Returns an instantiated \Drivers\Database instance
     * @return Database
     */
    abstract function get_manager(): Database;

    abstract function read($id): GenericMap|Normalize;

    protected function __read(ObjectId|string $id): GenericMap|Normalize {
        $schemaName = $this->manager->get_schema_name($_POST ?? $_GET);
        $result = $this->manager->findOne(['_id' => new ObjectId($id)]);
        
        if($result instanceof GenericMap) return $result;
        return ($result) ? new $schemaName($result) : null;
    }

}