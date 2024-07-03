<?php

namespace Controllers\Traits;

use Cobalt\Maps\GenericMap;
use Drivers\Database;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;
use Validation\Normalize;

trait Readable {
    protected Database $manager;
    /**
     * Returns an instantiated \Drivers\Database instance
     * @return Database
     */
    abstract function get_manager(): Database;

    /**
     * The return value of this method is immediately sent to the client.
     */
    abstract function read($document): GenericMap|BSONDocument|null;

    final protected function __read(ObjectId|string $id): GenericMap|BSONDocument|null {
        $result = $this->manager->findOne(['_id' => new ObjectId($id)]);
        if(!$result) throw new NotFound("No records match that request");
        if($result instanceof GenericMap) return $this->read($result);

        $schemaName = $this->manager->get_schema_name($_POST ?? $_GET);
        return $this->read(($result) ? new $schemaName($result) : $result);
    }
    
    static protected function readable_permissions(array $value = []):array {
        return array_merge(['permission' => "CRUDControllerPermission",], $value);
    }
}