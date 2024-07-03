<?php

namespace Controllers\Traits;

use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Drivers\Database;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;
use Validation\Normalize;

trait Destroyable {
    protected Database $manager;
    /**
     * Returns an instantiated \Drivers\Database instance
     * @return Database
     */
    abstract function get_manager(): Database;

    /** 
     * The return value of this method will be the prompt for the client
     * Return value must be an array with at least the first key:
     *  * `message` <string> The confirmation message to be displayed to the client
     *  * `post` <array> The data to re-POST to this endpoint
     *  * `okay` <string> The acknowledge/affirmative button label
     *  * `dangerous` <bool> Whether or not this action is dangerous
    */
    abstract function destroy(GenericMap|BSONDocument $document):array;

    /** `destroyable` cannot be implemented without also using `readable` */
    abstract function __read(ObjectId|string $id): GenericMap|BSONDocument|null;

    final protected function __destroy($id) {
        $read = $this->__read($id);
        if(!$read) throw new NotFound("Resource not found");
        $default_confirm_message = "Are you sure you want to delete this record?";
        
        $confirm_message = $this->destroy($read);

        confirm($confirm_message['message'] ?? $confirm_message[0] ?? $default_confirm_message, $confirm_message['post'] ?? $_POST, $confirm_message['okay'] ?? "Yes", $confirm_message['dangerous'] ?? true);
        
        $_id = new ObjectId($id);
        $result = $this->manager->deleteOne(['_id' => $_id]);
        header("X-Redirect: " . route("$this->name@index"));
        return $result->getDeletedCount();
    }

    static protected function destroyable_permissions(array $value = []):array {
        return array_merge(['permission' => "CRUDControllerPermission",], $value);
    }

}