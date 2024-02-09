<?php

namespace Controllers\Traits;

use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Drivers\Database;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use Validation\Normalize;

trait Destroyable {
    protected Database $manager;
    /**
     * Returns an instantiated \Drivers\Database instance
     * @return Database
     */
    abstract function get_manager(): Database;

    abstract function destroy($id);

    abstract function read($id): GenericMap|Normalize;

    protected function __destroy($id, StringResult|string|null $title = null) {
        $read = $this->read($id);
        if(!$read) throw new NotFound("Resource not found");
        $confirm_message = "Are you sure you want to delete this entry?";
        if($title) $confirm_message = "Are you sure you want to delete the resourced labeled \"".htmlspecialchars($title)."\"?";
        
        confirm($confirm_message, $_POST, "Yes");
        
        $_id = new ObjectId($id);
        $result = $this->manager->deleteOne(['_id' => $_id]);
        header("X-Redirect: " . route("$this->name@index"));
        return $result->getDeletedCount();
    }

    protected function apiv1_destroy(?string $prefix = null, ?array $options = null) {
        
    }

}