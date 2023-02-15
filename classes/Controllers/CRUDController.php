<?php

namespace Controllers;

use Exception;
use MongoDB\BSON\ObjectId;
use Routes\Route;

abstract class CRUDController extends Controller {
    
    var $initialized = false;

    public ?\Drivers\Database $manager = null;
    public ?array $controller_data = null;

    function __construct() {
        $manager = $this->get_manager();
        $this->manager = (new $manager());
        if($this->manager instanceof \Drivers\Database === false) throw new Exception("Manager must be an instance of Database Driver");
        $this->controller_data = $this->get_controller_data();
        $this->initialized = true;
    }

    abstract function get_manager(): string;

    /**
     * Fields required include:
     * 
     * @return array 
     */
    abstract static function get_controller_data(): array;

    /**
     * Create a database entry
     * @return \MongoDB\BSON\ObjectId
     */
    public function create(): \MongoDB\BSON\ObjectId {
        $schemaName = $this->manager->get_schema_name($_POST);
        $schema = new $schemaName();
        $mutant = $schema->__validate($_POST);
        $result = $this->manager->insertOne($mutant);
        return $result->getInsertedId();
    }

    public function read($id): \Validation\Normalize {
        $schemaName = $this->manager->get_schema_name($_POST ?? $_GET);
        $result = $this->manager->findOne(['_id' => new ObjectId($id)]);
        return ($result) ? new $schemaName($result) : null;
    }

    public function update($id): \Validation\Normalize {
        $schemaName = $this->manager->get_schema_name($_POST);
        $schema = new $schemaName();
        $mutant = $schema->__validate($_POST);
        $query = ['_id' => $this->manager->__id($id)];
        $result = $this->manager->updateOne($query, $mutant, ['upsert' => true]);
        return $this->read($query['_id']);
    }

    public function delete($id) {
        confirm("Are you sure you want to delete this entry?", $_POST, "Yes");
        $_id = $this->manager->__id($id);
        $result = $this->manager->deleteOne(['_id' => $_id]);
        return $result->getDeletedCount();
    }

    public function index() {
        $params = $this->getParams($this->manager, []);
        $result = $this->manager->findAllAsSchema($params);
        $elements = "";
        foreach($result as $schema) {
            $elements .= $schema->__index();
        }
        add_vars([
            'title' => $this->controller_data['index']['title'] ?? __CLASS__,
            'elements' => $elements,
            'pagination' => $this->getPaginationControls()
        ]);
        return set_template($this->controller_data['index']['view'] ?? "/CRUD/admin/index.html");
    }

    public function edit($id) {
        $doc = $this->read($id);
        add_vars([
            'title' => $this->controller_data['index']['title']($doc) ?? 'Edit',
        ]);
        return set_template($this->controller_data['edit']['view']($doc)  ?? "/CRUD/admin/edit.html");
    }

    static function web(?string $prefix = null) {
        $mutant = self::generate_prefix($prefix);
        Route::get($prefix, "$mutant@public_web");
    }

    const PERMISSIONS = [
        'getable' => true,
        'create' => ['permission' => "CRUDControllerPermission",],
        'read'   => ['permission' => "CRUDControllerPermission",],
        'update' => ['permission' => "CRUDControllerPermission",],
        'delete' => ['permission' => "CRUDControllerPermission",],
    ];

    static function apiv1(?string $prefix = null, ?array $options = null) {
        $class = __CLASS__;
        $mutant  = self::generate_prefix($prefix);
        $options = self::permissions($options);

        
        Route::post(  $prefix, "$class@create", $options['create']);
        if($options['getable']) Route::get($prefix, "$mutant@read", $options['read']);
        Route::put(   $prefix, "$class@update", $options['update']);
        Route::delete($prefix, "$class@delete", $options['delete']);
    }

    static function admin(?string $prefix = null, ?array $options = null) {
        $class = __CLASS__;
        $mutant  = self::generate_prefix($prefix);
        $options = self::permissions($options);
        
        Route::get(
            "$mutant", 
            "$class@index", 
            array_merge([
                'anchor' => ['name' => $options['anchor'] ?? $class],
                'navigation' => ['admin_panel']
            ], $options['update'] ?? []
        ));
        Route::get("$mutant/edit/{id}/", "$class@edit",  $options['update']);
    }

    static function generate_prefix($supplied):string {
        if($supplied) {
            if($supplied[0] !== "/") $supplied = "/$supplied";
            return $supplied;
        }
        $prefix = preg_replace('/([A-Z])/', '-$1',__CLASS__);
        if($prefix[0] == "-") $prefix = substr($prefix, 1);
        return "/" . strtolower($prefix);
    }

    static function permissions(?array $options) {
        if($options === null || empty($options)) return self::PERMISSIONS;
        return array_merge(self::PERMISSIONS, $options);
    }

    private function is_initialized() {
        if($this->initialized !== true) throw new Exception("CRUDController is not initialized");
    }
}
