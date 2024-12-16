<?php

namespace Controllers\Traits;

use Cobalt\Maps\GenericMap;
use Drivers\Database;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;
use TypeError;
use Validation\Normalize;

trait Updateable {
    protected Database $manager;
    /**
     * Returns an instantiated \Drivers\Database instance
     * @return Database
     */
    abstract function get_manager(): Database;

    abstract function update($id): ObjectId;

    abstract function edit($document):string;

    /** `destroyable` cannot be implemented without also using `readable` */
    abstract function __read(ObjectId|string $id): GenericMap|BSONDocument|null;

    final protected function __update($id): GenericMap|BSONDocument {
        $schemaName = $this->manager->get_schema_name($_POST);
        /** @var GenericMap */
        $schema = new $schemaName();
        if($schema instanceof GenericMap === false) throw new TypeError("Schema must be of type `GenericMap`");
        
        // Validate the submitted data
        $schema->validate($_POST);
        // Get our update operators
        $update = $schema->operators();
        
        $query = ['_id' => new ObjectId($id)];
        $result = $this->manager->updateOne($query, $update, ['upsert' => false]);
        if($result->getMatchedCount() === 0) throw new NotFound("No document matched request", "No found");
        return $this->__read($id);
    }

    final protected function __edit(string $id): string {
        $doc = $this->__read($id);
        add_vars([
            'title' => 'Edit',
            'endpoint' => route("$this->name@update") . "$id",
            'autosave' => 'autosave="autosave"',
            'submit_button' => '',
            'delete_option' => "<option method=\"DELETE\" action=\"".route("$this->name@destroy")."$id\" dangerous=\"true\">Delete</option>",
            'method' => 'POST',
            'doc' => $doc,
            // ...$this->get_vars($doc, "edit"),
        ]);
        
        return $this->edit($doc);
    }

    static protected function updateable_permissions(array $value = []):array {
        return array_merge(['permission' => "CRUDControllerPermission",], $value);
    }
}