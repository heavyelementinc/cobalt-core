<?php

namespace Cobalt\Controllers\Traits;

use Cobalt\Model\Model;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;

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
    function update($post_data, $id): array {
        return $post_data;
    }

    /**
     * Called after a document is updated
     * @param Model|BSONDocument|null $doc - The document that was updated
     * @return void 
     */
    function after_update(Model|BSONDocument|null $doc):void {

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
        $data = $this->update($_POST, $id);
        /** @var Model */
        $schema = new $this->model([]);
        
        // Validate the submitted data
        $schema->__filter($data);
        // Get our update operators
        $update = $schema->__operators();
        
        $query = ['_id' => new ObjectId($id)];
        $result = $this->model->updateOne($query, $update, ['upsert' => false]);
        if($result->getMatchedCount() === 0) throw new NotFound("No document matched request", "No found");
        $doc = $this->__read($id);
        $this->after_update($doc);
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
            'submit_button' => '',
            'delete_option' => "<option method=\"DELETE\" action=\"".route("$this->name@__destroy")."$id\" dangerous=\"true\">".$this->getDeleteOptionLabel($doc)."</option>",
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
}