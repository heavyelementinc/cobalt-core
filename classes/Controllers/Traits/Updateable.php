<?php

namespace Controllers\Traits;

use Cobalt\Maps\GenericMap;
use Drivers\Database;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use Validation\Normalize;

trait Updateable {
    protected Database $manager;
    /**
     * Returns an instantiated \Drivers\Database instance
     * @return Database
     */
    abstract function get_manager(): Database;

    abstract function update($id): ObjectId;

    abstract function read($id): GenericMap|Normalize;

    protected function __update($id): GenericMap|Normalize {
        $schemaName = $this->manager->get_schema_name($_POST);
        $schema = new $schemaName();

        if($schema instanceof GenericMap) {
            $mutant = $schema->validate($_POST);
            $update = $schema->operators();
        } else if ($schema instanceof Normalize) {
            $mutant = $schema->__validate($_POST);
            $update  = $schema->__operators($mutant);
        }
        
        $query = ['_id' => new ObjectId($id)];
        $result = $this->manager->updateOne($query, $update, ['upsert' => false]);
        if($result->getMatchedCount() === 0) throw new NotFound("No document matched request", "No found");
        return $this->read($id);
    }

}