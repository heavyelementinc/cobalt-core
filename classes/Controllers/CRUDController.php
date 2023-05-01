<?php

namespace Controllers;

use Exception;
use MongoDB\BSON\ObjectId;
use Routes\Route;

abstract class CRUDController extends Controller {
    
    var $initialized = false;

    public $name;
    public $manager;
    public ?array $controller_data;

    const PERMISSIONS = [
        'getable' => true,
        'create' => ['permission' => "CRUDControllerPermission",],
        'read'   => ['permission' => "CRUDControllerPermission",],
        'update' => ['permission' => "CRUDControllerPermission",],
        'delete' => ['permission' => "CRUDControllerPermission",],
    ];

    function __construct() {
        $this->name = $this::getClassName();
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
     * [
     *      'index' => [
     *          'view' => '/path/to/view.html',
     *          'each' => '/path/to/other-view.html',
     *      ]
     * ]
     * 
     * @return array 
     */
    abstract static function get_controller_data(): array;

    static function getClassName() {
        return static::class;
    }

    /** ============================================= */
    /** ============================================= */
    /** =============== API Endpoints =============== */
    /** ============================================= */
    /** ============================================= */

    /**
     * Create a database entry
     * @return \MongoDB\BSON\ObjectId
     */
    public function create(): \MongoDB\BSON\ObjectId {
        $schemaName = $this->manager->get_schema_name($_POST);
        $schema = new $schemaName();
        $mutant = $schema->__validate($_POST);
        $result = $this->manager->insertOne($mutant);
        $insertedId = $result->getInsertedId();
        $route = route("$this->name@edit", [(string)$insertedId]);
        header("X-Redirect: $route");// . (string)$insertedId);
        return $insertedId;
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
        $update  = $schema->__operators($mutant);
        $query = ['_id' => new ObjectId($id)];
        $result = $this->manager->updateOne($query, $update, ['upsert' => false]);
        return $this->read($query['_id']);
    }

    public function delete($id) {
        confirm("Are you sure you want to delete this entry?", $_POST, "Yes");
        $_id = new ObjectId($id);
        $result = $this->manager->deleteOne(['_id' => $_id]);
        return $result->getDeletedCount();
    }


    /** ============================================= */
    /** ============================================= */
    /** ============== Admin Endpoints ============== */
    /** ============================================= */
    /** ============================================= */
    

    public function index() {
        $search = $this->controller_data['index']['search'] ?? null;
        if($search) $this->enableSearchField(...$search);
        $params = $this->params($this->manager, $this->controller_data['index']['filters'] ?? [], $this->controller_data['index']['filter_misc'] ?? []);
        $result = $this->manager->findAllAsSchema(...$params);
        
        $elements = view_each(
            $this->controller_data['index']['each'] ?? "/CRUD/admin/default-list-item.html", 
            [
                'route' => route("$this->name@edit"),
                'doc' => $result
            ],
            'doc',
            ""
        );
        // foreach($result as $schema) {
        //     $elements .= $schema->__index();
        // }

        add_vars([
            'title'       => $this->controller_data['index']['title'] ?? $this->name,
            'elements'    => $elements,
            'pagination'  => $this->getPaginationLinks(),
            'href'        => route("$this->name@new_document"),
        ]);
        return set_template($this->controller_data['index']['view'] ?? "/CRUD/admin/index.html");
    }

    public function edit($id) {
        $doc = $this->read($id);
        add_vars([
            'title' => $this->controller_data['index']['title'] ?? 'Edit',
            'endpoint' => route("$this->name@update") . "$id",
            'method' => 'PUT',
            'doc' => $doc,
        ]);
        return set_template($this->controller_data['edit']['view']  ?? "/CRUD/admin/edit.html");
    }

    public function new_document() {
        $schema = $this->manager->get_schema_name();

        add_vars([
            'title'    => "New $this->name",
            'doc'      => new $schema([]),
            'endpoint' => route("$this->name@create"),
            'method'   => "POST",
            'name'     => $this->name,
        ]);
        
        return set_template($this->controller_data['new']['view'] ?? "/CRUD/admin/edit.html");
    }

    /** ============================================= */
    /** ============================================= */
    /** =============== Web Endpoints =============== */
    /** ============================================= */
    /** ============================================= */

    /** ============================================= */
    /** ============================================= */
    /** ============= Static Route Calls ============ */
    /** ============================================= */
    /** ============================================= */
    
    static function apiv1(?string $prefix = null, ?array $options = null) {
        $class   = self::getClassName();
        $mutant  = self::generate_prefix($prefix);
        $options = self::permissions($options);

        
        Route::post(   "$mutant/create", "$class@create", $options['create']);
        if($options['getable']) Route::get("$mutant/{id}", "$mutant@read", $options['read']);
        Route::post("$mutant/update/{id}", "$class@update", $options['update']);
        Route::delete( "$mutant/delete/{id}", "$class@delete", $options['delete']);
    }

    static function admin(?string $prefix = null, ?array $options = null) {
        $class = self::getClassName();
        $mutant  = self::generate_prefix($prefix);
        $permissions = self::permissions($options);
        $opts = static::get_controller_data() ?? [];
        
        Route::get(
            "$mutant", 
            "$class@index", 
            array_merge([
                'anchor' => ['name' => $opts['index']['anchor'] ?? $class],
                'navigation' => ['admin_panel']
            ], $permissions['update'] ?? [],
            $opts['index']['options'] ?? []
        ));
        Route::get("$mutant/new/", "$class@new_document", array_merge(
            $permissions['create'] ?? [],
            $opts['edit']['options'] ?? [])
        );
        Route::get("$mutant/edit/{id}/", "$class@edit",  array_merge(
            $permissions['update'],
            $opts['new']['options'] ?? []
        ));
        // Route::get("$mutant/")
    }

    static function web(?string $prefix = null) {
        $mutant = self::generate_prefix($prefix);
        Route::get($prefix, "$mutant@public_web");
    }



    static function generate_prefix($supplied):string {
        if($supplied) {
            if($supplied[0] !== "/") $supplied = "/$supplied";
            return $supplied;
        }
        $prefix = preg_replace('/([A-Z])/', '-$1',self::getClassName());
        if($prefix[0] == "-") $prefix = substr($prefix, 1);
        return "/" . strtolower($prefix);
    }

    static function permissions(?array $options) {
        $merged = array_merge(self::PERMISSIONS, static::PERMISSIONS ?? []);
        if($options === null || empty($options)) return $merged;
        return array_merge($merged, $options);
    }

    private function is_initialized() {
        if($this->initialized !== true) throw new Exception("CRUDController is not initialized");
    }
}
