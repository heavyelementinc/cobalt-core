<?php

namespace Controllers;

use Exception;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use Routes\Route;

abstract class CRUDController extends Controller {
    
    var $initialized = false;

    public $name;
    public ?\Drivers\Database $manager;
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
     *          'view'    => '/path/to/view.html', // required
     *          'each'    => '/path/to/other-view.html', // required
     *          'anchor'  => 'SomeName', // defaults to Controller's ClassName,
     *          'options' => [] // defaults to empty array
     *      ],
     *      'edit' => [
     *          'view'    => '/path/to/edit.html', // required, path to edit form
     *          'title'   => '', // defaults to index->title or 'Edit'
     *          'options' => [], // defaults to empty array
     *      ],
     *      'new' => [
     *          'view'    => '', // defaults to edit->view, required if edit->view not specified
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
        if(is_a($schema, "\\Validation\\Normalize")) {
            $mutant = $schema->__validate($_POST);
        } else if (is_a($schema, "\\Cobalt\\PersistanceMap")) {
            $mutant = $schema->validate($_POST);
        }
        $id = new ObjectId();
        $result = $this->manager->updateOne(['_id' => $id],['$set' => $mutant], ['upsert' => true]);
        $insertedId = $result->getUpsertedId();
        $route = route("$this->name@edit", [(string)$insertedId]);
        header("X-Redirect: $route");// . (string)$insertedId);
        return $insertedId;
    }

    public function read($id): \Cobalt\PersistanceMap|\Validation\Normalize {
        $schemaName = $this->manager->get_schema_name($_POST ?? $_GET);
        $result = $this->manager->findOne(['_id' => new ObjectId($id)]);
        if(is_a($result, "\\Cobalt\\PersistanceMap")) return $result;
        return ($result) ? new $schemaName($result) : null;
    }

    public function update($id): \Cobalt\PersistanceMap|\Validation\Normalize {
        $schemaName = $this->manager->get_schema_name($_POST);
        $schema = new $schemaName();
        if(is_a($schema, "\\Validation\\Normalize")) {
            $mutant = $schema->__validate($_POST);
            $update  = $schema->__operators($mutant);
        } else if (is_a($schema, "\\Cobalt\\PersistanceMap")) {
            $mutant = $schema->validate($_POST);
            $update = $schema->operators($mutant);
        }
        
        $query = ['_id' => new ObjectId($id)];
        $result = $this->manager->updateOne($query, $update, ['upsert' => false]);
        if($result->getMatchedCount() === 0) throw new NotFound("No document matched request", "No found");
        return $this->read($id);
    }

    public function destroy($id) {
        confirm("Are you sure you want to delete this entry?", $_POST, "Yes");
        $_id = new ObjectId($id);
        $result = $this->manager->deleteOne(['_id' => $_id]);
        header("X-Redirect: " . route("$this->name@index"));
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
        $params = $this->params($this->manager, $this->controller_data['index']['filters'] ?? [], array_merge($this->controller_data['index']['filter_misc'] ?? [], $this->controller_data['index']['query_options'] ?? []));
        $result = $this->manager->findAllAsSchema(...$params);
        
        add_vars([
            'route' => route("$this->name@edit")
        ]);

        $elements = view_each(
            $this->controller_data['index']['each'] ?? "/CRUD/admin/default-list-item.html", 
            $result,
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
        return view($this->controller_data['index']['view'] ?? "/CRUD/admin/index.html");
    }

    public function edit($id) {
        $doc = $this->read($id);
        add_vars([
            'title' => $this->controller_data['edit']['title'] ?? $this->controller_data['index']['title'] ?? 'Edit',
            'endpoint' => route("$this->name@update") . "$id",
            'autosave' => 'autosave="autosave"',
            'submit_button' => '',
            'delete_option' => "<option method=\"DELETE\" action=\"".route("$this->name@destroy")."$id\" dangerous=\"true\">Delete</option>",
            'method' => 'POST',
            'doc' => $doc,
        ]);
        
        return view($this->controller_data['edit']['view']  ?? "/CRUD/admin/edit.html");
    }

    public function new_document() {
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
        ]);
        
        return view($this->controller_data['new']['view'] ?? $this->controller_data['edit']['view'] ?? "/CRUD/admin/edit.html");
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
        Route::delete( "$mutant/delete/{id}", "$class@destroy", $options['delete']);
    }

    static function admin(?string $prefix = null, ?array $options = null) {
        $class = self::getClassName();
        $mutant  = self::generate_prefix($prefix);
        $permissions = self::permissions($options);
        $opts = static::get_controller_data() ?? [];
        
        Route::get(
            "$mutant/", 
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
            $opts['edit']['options'] ?? []
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
