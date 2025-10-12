<?php

namespace Cobalt\DefinedModel\Traits;

use MongoDB\BSON\ObjectId;

trait ModelCreate {
    var $initialized = false;
    
    public $name;

    abstract function modelView($document):string;

    public function createModelView($document):string {
        return $this->modelView($document);
    }

    final public function onCreateRoute(string $id):string {
        $doc = $this->onApiRead($id);
        $route = route("$this->name@onApiCreateRoute", ["$id"]);
        add_vars([
            'title'               => 'New',
            'method'              => 'POST',
            'action'              => $route,
            'endpoint'            => $route,
            'style'               => 'display:none;',
            'new_doc_disabled'    => 'disabled="disabled"',
            'update_doc_disabled' => '',
            'new_doc_readonly'    => 'readonly="readonly"',
            'update_doc_readonly' => '',
            'autosave'            => '',
            'submit_button'       => sprintf(FLOATING_SAVE_BUTTON, 'content-save', 'Create'),
            'delete_option'       => '',
            'doc'                 => $doc,
        ]);
        
        return $this->createModelView($doc);
    }

    final public function onApiCreateRoute():ObjectId {
        // Let's touch the app's code so we can more easily debug and trace stuff
        $data = $this->create($_POST);
        
        // Now that we have our data, let's get our Schema
        $schema = new $this($data);

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

    /**
     * This function accepts the $_POST data of the current request
     * where it may mutate or do other operatons on it before anything else
     * happens with it.
     * @param array $postData 
     * @return array 
     */
    protected function onApiCreateStart(array $postData):array {
        return $postData;
    }

    static public function routeDetailsCreate():array {
        return [];
    }
}