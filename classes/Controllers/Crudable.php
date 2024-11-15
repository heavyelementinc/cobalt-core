<?php
namespace Controllers;

use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\SchemaResult;
use Controllers\Traits\Indexable;
use Drivers\Database;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;
use Routes\Route;
use TypeError;

abstract class Crudable {
    use Indexable;
    public $name;
    public string $friendly_name;
    public Database $manager;
    public int $index_limit = 50;

    protected int $index_display_action_menu = 0;

    function __construct(?string $name = null) {
        $this->name = self::className();
        $this->friendly_name = self::generate_friendly_name($name);
        $this->manager = $this->get_manager();
    }

    /** @return Database */
    abstract function get_manager(): Database;

    /** @return GenericMap */
    abstract function get_schema($data): GenericMap;

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
         * In this function, you must define the new document route just like you normally would
         * any other route. Note that the return value of this function is sent directly
         * to the client with no post-processing.
         * 
         * Also note that some template vars are set by the __new_document parent caller
         * 
         * @return string Genereally this is going to be a `view("/some/template")` call
         */
        function new_document(GenericMap $empty_schema): string {
            return $this->edit($empty_schema);
        }

        final public function __create(): ObjectId {
            // Let's touch the app's code so we can more easily debug and trace stuff
            $data = $this->create($_POST);
            
            // Now that we have our data, let's get our Schema
            $schema = $this->get_schema($data);

            /** @var GenericMap */
            $mutant = $schema->__validate($data);
            
            // Now, let's insert our content into the database.
            $result = $this->manager->insertOne($mutant);
            $insertedId = $result->getInsertedId();

            // Let's check if we need to grab a route and redirect (if this item is updatable)
            $route = route("$this->name@__edit", [(string)$insertedId]);
            header("X-Redirect: $route");

            // Return our inserted ID
            return $insertedId;
        }

        final public function __new_document() {
            $instance = $this->get_schema([]);
            
            $action = route("$this->name@__create");
            add_vars([
                'title'    => "New $this->friendly_name",
                'doc'      => $instance,
                'autosave' => 'autosave="none"',
                'style'    => 'display:none;',
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

    // =========================================================================
    // ================================= READ ==================================
    // =========================================================================

        /**
         * This function is passed the document from the database (if you've done
         * your job right, it should already persist as an object)
         * 
         * The return value of this method is immediately sent to the client.
         */
        function read($document): GenericMap|BSONDocument|null {
            return $document;
        }

        final public function __read(ObjectId|string $id): GenericMap|BSONDocument|null {
            $result = $this->manager->findOne(['_id' => new ObjectId($id)]);
            if(!$result) throw new NotFound("No records match that request");
            return $this->read($result);
        }

        function index():string {
            return view("/admin/crudable/default_index.html");
        }

        // function index_row(GenericMap $document):string {
        //     return $view($schema, $document);
        // }

        final public function __index():string {
            $this->init($this->get_schema([]), $_GET);
            $new_doc_href = route("$this->name@__new_document");
            $hypermedia = $this->get_hypermedia();
            add_vars([
                'title'        => $this->friendly_name,
                'table_header' => $this->get_table_header(),
                'documents'    => $this->get_table_body(),
                'hypermedia'   => $hypermedia,
                'next_page'    => $hypermedia['next'],
                'previous_page'=> $hypermedia['previous'],
                'page_number'  => $hypermedia['page'],
                'filters'      => $hypermedia['filters'],
                'search'       => $hypermedia['search'],
                'multidelete_button' => $hypermedia['multidelete_button'],
                'page_param'   => QUERY_PARAM_PAGE_NUM,
                'search_param' => QUERY_PARAM_SEARCH,
                'href'         => $new_doc_href,
            ]);
            $index = $this->index();
            return $index;
        }
        
        static public function route_details_read():array {
            return [];
        }

        static public function route_details_index():array {
            return [];
        }
        

    // =========================================================================
    // ================================ CREATE =================================
    // =========================================================================

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
         * @param GenericMap|BSONDocument|null $doc - The document that was updated
         * @return void 
         */
        function after_update(GenericMap|BSONDocument|null $doc):void {

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

        final public function __update($id): GenericMap|BSONDocument {
            $data = $this->update($_POST, $id);
            /** @var GenericMap */
            $schema = $this->get_schema([]);
            
            // Validate the submitted data
            $schema->__validate($data);
            // Get our update operators
            $update = $schema->__operators();
            
            $query = ['_id' => new ObjectId($id)];
            $result = $this->manager->updateOne($query, $update, ['upsert' => false]);
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
                'autosave' => 'autosave="autosave"',
                'submit_button' => '',
                'delete_option' => "<option method=\"DELETE\" action=\"".route("$this->name@__destroy")."$id\" dangerous=\"true\">".$this->getDeleteOptionLabel($doc)."</option>",
                'doc' => $doc,
            ]);
            
            return $this->edit($doc);
        }

        public function getDeleteOptionLabel(GenericMap $doc) {
            return "Delete";
        }

        static public function route_details_update():array {
            return [];
        }

    
    // =========================================================================
    // ================================ DESTROY ================================
    // =========================================================================
        
        /** 
         * The return value of this method will be the prompt for the client
         * Return value must be an array with at least the first key:
         *  * `message` <string> The confirmation message to be displayed to the client
         *  * `post` <array> The data to re-POST to this endpoint
         *  * `okay` <string> The acknowledge/affirmative button label
         *  * `dangerous` <bool> Whether or not this action is dangerous
         * @return array{message: string, post: array{poop: bool}, okay: string, dangerous: bool}
        */
        abstract function destroy(GenericMap|BSONDocument $document):array;

        public function __destroy($id) {
            $read = $this->__read($id);
            if(!$read) throw new NotFound(ERROR_RESOURCE_NOT_FOUND);
            $default_confirm_message = "Are you sure you want to delete this record?";
            
            $confirm_message = $this->destroy($read);

            confirm($confirm_message['message'] ?? $confirm_message[0] ?? $default_confirm_message, $confirm_message['post'] ?? $_POST, $confirm_message['okay'] ?? "Yes", $confirm_message['dangerous'] ?? true);
            
            $_id = new ObjectId($id);
            $result = $this->manager->deleteOne(['_id' => $_id]);
            header("X-Redirect: " . route("$this->name@__index"));
            return $result->getDeletedCount();
        }

        public function __multidestroy() {
            $upgraded = [];
            foreach($_POST[CRUDABLE_MULTIDESTROY_FIELD] as $id) {
                if(!$id) throw new BadRequest("Invalid ID found", "Invalid ID supplied");
                $upgraded[] = new ObjectId($id);
            }
            $query = ['_id' => ['$in' => $upgraded]];
            $results = $this->manager->count($query);
            confirm("This will delete $results document".plural($results).". Do you want to continue?", $_POST);

            $deleted = $this->manager->deleteMany($query);
            header("X-Redirect: " . route("$this->name@__index"));
        }

        static public function route_details_destroy():array {
            return [];
        }

    // =========================================================================
    // ================================ ROUTING ================================
    // =========================================================================
        /**
         * `options` keys:
         *  * create
         *  * read
         *  * update
         *  * destroy
         */
        static function apiv1(?string $prefix = null, array $options = []) {
            $class   = self::className();
            $mutant  = self::generate_prefix($prefix);

            Route::get("$mutant/{id}", "$class@__read",   static::route_details(['permission' => "CRUDControllerPermission"],$options['read'] ?? [], "route_details_read"));
            Route::post("$mutant/create", "$class@__create", static::route_details(['permission' => "CRUDControllerPermission"],$options['create'] ?? [], "route_details_create"));
            Route::post("$mutant/update/{id}", "$class@__update", static::route_details(['permission' => "CRUDControllerPermission"],$options['update'] ?? [], "route_details_update"));
            Route::delete("$mutant/delete/{id}", "$class@__destroy", static::route_details(['permission' => "CRUDControllerPermission"],$options['destroy'] ?? [], "route_details_destroy"));
            Route::delete("$mutant/multi-delete/", "$class@__multidestroy", static::route_details(['permission' => "CRUDControllerPermission"],$options['destroy'] ?? [], "route_details_destroy"));
            set_crudable_flag($class, CRUDABLE_CONFIG_APIV1);
        }

        /**
         * `options` keys:
         *  * index
         *  * new
         *  * edit
         */
        static function admin(?string $prefix = null, array $options = []) {
            $class   = self::className();
            $mutant  = self::generate_prefix($prefix);

            Route::get("$mutant/", "$class@__index", self::route_details(
                    [
                    'anchor' => [
                        'name' => $options['anchor'] ?? self::generate_friendly_name()
                    ],
                    'navigation' => [$options['navigation'] ?? 'admin_panel'],
                    'permission' => "CRUDControllerPermission",
                ],
                $options['index'] ?? [],
                "route_details_index"
            ));
            Route::get("$mutant/new", "$class@__new_document", self::route_details(
                [
                    'permission' => "CRUDControllerPermission",
                ],
                $options['new_document'] ?? [],
                "route_details_create"
            ));
            Route::get("$mutant/edit/{id}", "$class@__edit", self::route_details(
                [
                    'permission' => "CRUDControllerPermission",
                ],
                $options['edit'] ?? [],
                "route_details_update"
            ));
            set_crudable_flag($class, CRUDABLE_CONFIG_ADMIN);
        }

        static function route_details(array $default_values, array $details, string $callable) {
            $callable_results = static::$callable($details);
            return array_merge($default_values, $details, $callable_results);
        }

        static function generate_prefix($supplied):string {
            if($supplied) {
                if($supplied[0] !== "/") $supplied = "/$supplied";
                return $supplied;
            }
            $prefix = preg_replace('/([A-Z])/', '-$1',self::className());
            if($prefix[0] == "-") $prefix = substr($prefix, 1);
            return "/" . strtolower($prefix);
        }

        static function permissions(?array $permissions) {
            $merged = $permissions ?? [];
            return $merged;
        }

        static function className() {
            return static::class;
        }


    static function generate_friendly_name(?string $supplied = null):string {
        if($supplied) return $supplied;
        
        $prefix = preg_replace('/([A-Z])/', ' $1',self::className());
        if($prefix[0] == "-") $prefix = substr($prefix, 1);
        return trim($prefix);
    }


    
    function __set_action_menu(int $state) {
        $this->index_display_action_menu = $state;
    }

    function __get_action_menu_state(): bool {
        return $this->index_display_action_menu;
    }

    /** $type - can be blank or "options" */
    function __get_action_menu(string $type = "", GenericMap|BSONDocument|null $document = null):string {
        $class = self::className();
        $html = "";
        if($this->index_display_action_menu | CRUDABLE_DELETEABLE) {
            $html .= "<option method=\"DELETE\" action=\"".route("$class@__destroy", [(string)$document->_id])."\">Delete</option>";
        }

        return "<action-menu type=\"$type\">$html</action-menu>";
    }
}