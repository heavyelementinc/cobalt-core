<?php

namespace Controllers\Traits;

use Cobalt\Maps\GenericMap;
use Drivers\Database;
use Exception;
use Exceptions\HTTP\Error;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;
use TypeError;
use Validation\Normalize;

trait Createable {
    protected Database $manager;

    /**
     * Returns an instantiated \Drivers\Database instance
     * @return Database
     */
    abstract function get_manager(): Database;

    /**
     * This function is the first function called by __create. It's return value
     * is then processed by the rest of the __create function, it is validated
     * and then it is stored.
     * 
     * @param array $post_data - The submitted $_POST data
     * @return array|BSONDocument - Data to be validated and stored
     */
    abstract function create(array $post_data):array;

    /**
     * In this function, you must define the edit route just like you normally would
     * any other route. Note that the return value of this function is sent directly
     * to the client with no post-processing.
     * 
     * @return string Genereally this is going to be a `view("/some/template")` call
     */
    abstract function new_document(GenericMap $empty_schema): string;

    final protected function __create(): ObjectId {
        // Let's allow our app's code a place to set breakpoints, etc.
        $data = $this->create($_POST);
        
        // Now that we have our data, let's get our Schema
        $schemaName = $this->manager->get_schema_name($data);

        /** @var GenericMap */
        $schema = new $schemaName();
        if($schema instanceof GenericMap === false) throw new TypeError("Schema must be of type GenericMap!");

        /** If there's an issue with the data, we'll throw a ValidationFailed exception
         * @var GenericMap
         */
        $mutant = $schema->__validate($data);
        
        // Now, let's insert our content into the database.
        $result = $this->manager->insertOne($mutant);
        $insertedId = $result->getInsertedId();

        // Let's check if we need to grab a route and redirect (if this item is updatable)
        if( in_array("\\Cobalt\\Controllers\\Updateable", class_uses($this)) ) {
            $route = route("$this->name@edit", [(string)$insertedId]);
            header("X-Redirect: $route");
        }

        // Return our inserted ID
        return $insertedId;
    }
    
    final protected function __new_document() {
        $schema = $this->manager->get_schema_name();
        /** @var GenericMap */
        $instance = new $schema([]);

        if($instance instanceof GenericMap === false) throw new TypeError("Schema must be of type `GenericMap`");
        
        add_vars([
            'title'    => "New $this->name",
            'doc'      => $instance,
            'autosave' => 'autosave="none"',
            'style'    => 'display:none;',
            'submit_button' => '<button type="submit">Submit</button>',
            'delete_option' => '',
            'endpoint' => route("$this->name@create"),
            'method'   => "POST",
            'name'     => $this->name,
            // ...$this->get_vars($schema, "new"),
        ]);
        
        // view($this->controller_data['new']['view'] ?? $this->controller_data['edit']['view'] ?? "/CRUD/admin/edit.html");
        return $this->new_document($instance);
    }

    static protected function creatable_permissions(array $value = []):array {
        return array_merge(['permission' => "CRUDControllerPermission",], $value);
    }
}