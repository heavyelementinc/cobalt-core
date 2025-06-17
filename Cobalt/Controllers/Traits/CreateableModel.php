<?php

namespace Cobalt\Controllers\Traits;

use Cobalt\Model\GenericModel;
use Cobalt\Model\Model;
use MongoDB\BSON\ObjectId;
use MongoDB\InsertOneResult;

trait CreateableModel {
    var $initialized = false;

    public $name;
    public Model $model;

    // =========================================================================
    // ================================ CREATE =================================
    // =========================================================================

    /**
     * This function is the first operation called by __create. Its return value
     * is then processed by the rest of the __create function, it is validated
     * and then it is stored in the database. To stub this, just return $post_data;
     * 
     * @param array $post_data - The submitted $_POST data
     * @return array|BSONDocument - Data to be validated and stored
     */
    function create(array $post_data):array {
        return $post_data;
    }

    /**
     * Do something to the data
     * 
     * @param GenericModel $model 
     * @param InsertOneResult $result 
     * @return void 
     */
    function post_create(GenericModel &$model, ObjectId $id, InsertOneResult $result):void {
    }

    /**
     * In this function, you must define the new document route just like you normally would
     * any other route. Note that the return value of this function is sent directly
     * to the client with no post-processing.
     * 
     * Also note that some template vars are set by the __new_document parent caller
     * 
     * @return string Genereally this is going to be a `view("/some/template")` call
     */
    function new_document(GenericModel $empty_schema): string {
        return $this->edit($empty_schema);
    }

    final public function __create(): ObjectId {
        // Let's touch the app's code so we can more easily debug and trace stuff
        $data = $this->create($_POST);
        
        // Now that we have our data, let's get our Schema
        $schema = new $this->model($data);

        /** @var Model */
        $mutant = $schema->__filter($data);
        
        // Now, let's insert our content into the database.
        $result = $schema->insertOne($mutant);
        $insertedId = $result->getInsertedId();
        if(method_exists($this, "postCreate")) $this->postCreate($result, $insertedId, $result);
        // Let's check if we need to grab a route and redirect (if this item is updatable)
        $route = route("$this->name@__edit", [(string)$insertedId]);
        header("X-Redirect: $route");

        // Return our inserted ID
        return $insertedId;
    }

    final public function __new_document() {
        $instance = new $this->model([]);
        
        $action = route("$this->name@__create");
        add_vars([
            'title'    => "New $this->friendly_name",
            'doc'      => $instance,
            'autosave' => 'autosave="none"',
            'style'    => 'display:none;',
            'new_doc_disabled' => 'disabled="disabled"',
            'update_doc_disabled' => '',
            'new_doc_readonly' => 'readonly="readonly"',
            'update_doc_readonly' => '',
            'submit_button' => '<button type="submit">Submit</button>',
            'delete_option' => '',
            'method'   => "POST",
            'action'   => $action,
            'endpoint' => $action, /** @deprecated */
            'name'     => $this->name,
        ]);
        
        return $this->new_document($instance);
    }

    static public function route_details_create():array {
        return [];
    }

}