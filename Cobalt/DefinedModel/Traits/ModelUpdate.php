<?php
namespace Cobalt\DefinedModel\Traits;

use Cobalt\DefinedModel\DefinedModel;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;

trait ModelUpdate {
    var $initialized = false;
    public $name;
    
    abstract function modelView($document):string;
    // abstract function filterAllFields(array $data):void;
    // abstract function getUpdateOperators():void;

    final public function onApiUpdateRoute($id): int {
        $query = ['_id' => new ObjectId($id)];
        /** @var DefinedModel */
        $doc = $this->findOne($query);
        if(!$doc) throw new NotFound("No documents matched request", "Not found");
        $data = $this->onApiUpdate($_POST, $id, $doc);
        $this->filterAllFields($data);
        $updateQuery = [];
        $this->getUpdateOperators(false, $updateQuery);
        $result = $this->updateOne($query, $updateQuery, ['upsert' => false]);
        $matchedCount = $result->getMatchedCount();
        if($matchedCount === 0) throw new NotFound("No document matched request", "Not found");
        // $updatedDocument = $this->__read($id);
        $this->onUpdateSuccess($data);
        return $matchedCount;
    }

    /**
     * `onApiUpdate()` is called at the beginning of the _onApiUpdate process. 
     * Its return value is then passed off to the DefinedModel to be validated. 
     * If validation is successful, it's stored in the database.
     * 
     * To stub this, just return $post_data
     */
    protected function onApiUpdate($post_data, &$id, DefinedModel $model): array {
        return $post_data;
    }

    protected function onUpdateSuccess(array $data):void {
        // foreach($data as $name => $value) {
        //     update("[name=\"$name\"]");
        // }
    }

    final public function onEditRoute(string $id):string {
        $doc = $this->onApiRead($id);
        $route = route("$this->name@onApiUpdateRoute");
        add_vars([
            'title'               => 'Edit',
            'method'              => 'POST',
            'action'              => $route . "$id",
            'endpoint'            => $route . "$id",
            'new_doc_disabled'    => '',
            'update_doc_disabled' => 'disabled="disabled"',
            'new_doc_readonly'    => '',
            'update_doc_readonly' => 'readonly="readonly"',
            'autosave'            => 'autosave="autosave"',
            'submit_button'       => '',
            'delete_option'       => "<option method=\"DELETE\" action=\"".route("$this->name@__destroy")."$id\" dangerous=\"true\" icon=\"delete-outline\">".$this->getDeleteOptionLabel($doc)."</option>",
            'doc'                 => $doc,
        ]);
        
        return $this->modelView($doc);
    }


    protected function getDeleteOptionLabel(DefinedModel $doc) {
        return "Delete";
    }

    static public function routeDetailsUpdate():array {
        return [];
    }
}