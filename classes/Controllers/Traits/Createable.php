<?php

namespace Controllers\Traits;

use Cobalt\Maps\GenericMap;
use Drivers\Database;
use MongoDB\BSON\ObjectId;
use Validation\Normalize;

trait Createable {
    protected Database $manager;
    /**
     * Returns an instantiated \Drivers\Database instance
     * @return Database
     */
    abstract function get_manager(): Database;

    abstract function create($id): ObjectId;

    abstract function new_document($id);

    protected function __create(): ObjectId {
        $schemaName = $this->manager->get_schema_name($_POST);
        $schema = new $schemaName();
        if($schema instanceof Normalize) {
            $mutant = $schema->__validate($_POST);
        } else if ($schema instanceof GenericMap) {
            $mutant = $schema->validate($_POST);
        }
        $result = $this->manager->insertOne($mutant);
        $insertedId = $result->getInsertedId();
        $route = route("$this->name@edit", [(string)$insertedId]);
        header("X-Redirect: $route");
        return $insertedId;
    }

    protected function __new_document() {
        $schema = $this->manager->get_schema_name();

        add_vars([
            'title'    => "New $this->name",
            'doc'      => new $schema([]),
            'autosave' => 'autosave="none"',
            'style'    => 'display:none;',
            'submit_button' => '<button type="submit">Submit</button>',
            'delete_option'   => '',
            'endpoint' => route("$this->name@create"),
            'method'   => "POST",
            'name'     => $this->name,
            ...$this->get_vars($schema, "new"),
        ]);
        
        return view($this->controller_data['new']['view'] ?? $this->controller_data['edit']['view'] ?? "/CRUD/admin/edit.html");
    }
}