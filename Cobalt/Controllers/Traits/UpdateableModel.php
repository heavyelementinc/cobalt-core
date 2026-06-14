<?php

namespace Cobalt\Controllers\Traits;

use Cobalt\Controllers\Interfaces\BatchOperations;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Model;
use Exception;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

trait UpdateableModel {
    var $initialized = false;

    public $name;
    public Model $model;

    /**
     * `update()` is called at the beginning of the __update process. Its
     * return value is then passed off to the Schema to be validated. If
     * validation is successful, it's stored in the database.
     * 
     * To stub this, just return $post_data
     */
    function update($post_data, &$id, Model $model): array {
        return $post_data;
    }

    /**
     * Called after a document is updated
     * @param Model|BSONDocument|null $doc - The document that was updated
     * @return void 
     */
    function after_update(array $validatedFields, Model|BSONDocument|null $doc):void {
        foreach($validatedFields as $field => $value) {
            // Do not disable this code! This is vital for post-update UI consistency!
            $doc->{$field}->onUpdateConfirmed($value);
        }
    }

    /**
     * In this function, you must define the edit route just like you normally would
     * any other route. Note that the return value of this function is sent directly
     * to the client with no post-processing.
     * 
     * Also note that many template vars are set by the __edit parent caller
     * 
     *  * `title` - Override with the set('title', <value>) function
     *  * `method` - The HTTP method for the API endpoint
     *  * `action` - The API endpoint for updating this resource
     *  * `endpoint` - Alias of `action`
     *  * `autosave` - The autosave property for the form-request on this page (will be empty on a new doc)
     *  * `submit_button` - The submit button element on this page (will be empty on an existing doc)
     *  * `delete_option` - The delete option for an action-menu element (will be empty on a new doc)
     *  * `doc` - The current document to be edited
     * 
     * @return string usually this is going to be a `view($document->__get_editor_template_path())` call
     */
    abstract function edit($document):string;

    final public function __update($id): Model|BSONDocument {
        $_id = new ObjectId($id);
        $query = ['_id' => $_id];
        
        /** @var Model */
        $schema = $this->model->findOne($query);
        if(!$schema) throw new NotFound("No documents matched request", "Not found");

        // We need to undot our array so we can filter it.
        $mutant = $_POST;//array_undot($_POST);
        $data = $this->update($mutant, $id, $schema);
        
        // $schema = new $this->model([]);
        
        // Validate the submitted data
        $schema->__filter($data);
        // Get our update operators
        $update = [];
        $schema->__operators(false, $update);
        
        
        $result = $this->model->updateOne($query, $update, ['upsert' => false]);
        if($result->getMatchedCount() === 0) throw new NotFound("No document matched request", "Not found");
        $doc = $this->__read($id);
        $this->after_update($schema->getValidatedFields(), $doc);
        return $doc;
    }

    final public function __edit(string $id): string {
        $doc = $this->__read($id);
        $route = route("$this->name@__update");
        add_vars([
            'title' => 'Edit',
            'method'   => 'POST',
            'action'   => $route . "$id",
            'endpoint' => $route . "$id",
            'new_doc_disabled' => '',
            'update_doc_disabled' => 'disabled="disabled"',
            'new_doc_readonly' => '',
            'update_doc_readonly' => 'readonly="readonly"',
            'autosave' => 'autosave="autosave"',
            'submit_button' => sprintf(FLOATING_SAVE_BUTTON, 'content-save-outline', 'Save'),
            'delete_option' => "<option method=\"DELETE\" action=\"".route("$this->name@__destroy")."$id\" dangerous=\"true\" icon=\"delete-outline\">".$this->getDeleteOptionLabel($doc)."</option>",
            'doc' => $doc,
        ]);
        
        return $this->edit($doc);
    }

    public function getDeleteOptionLabel(Model $doc) {
        return "Delete";
    }

    static public function route_details_update():array {
        return [];
    }

    function __batchIdOperation($name) {
        if($this instanceof BatchOperations == false) throw new RouteNotFoundException("Controller is not an instance of BatchOperations");
        $batchOps = $this->register_batch_functions();
        /** @var BatchIdOperation $operation */
        foreach($batchOps as $operation) {
            if($name != $operation->getName()) continue;
            return $operation->runPost($_POST['_ids']);
        }
        throw new RouteNotFoundException("Specified BatchIdOperation does not exist on this controller.");
    }

    abstract public function __read(ObjectId|string $id): GenericModel|BSONDocument|null;
}