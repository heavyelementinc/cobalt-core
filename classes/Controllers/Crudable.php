<?php
namespace Controllers;

use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\SchemaResult;
use Controllers\Traits\Indexable;
use Drivers\Database;
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

        static public function creatable_permissions(array $defaultValue = [], array $userSuppliedValue = []):array {
            return array_merge(['permission' => "CRUDControllerPermission",], $defaultValue, $userSuppliedValue);
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
                'next_page'    => $hypermedia['next'],
                'previous_page'=> $hypermedia['previous'],
                'page_number'  => $hypermedia['page'],
                'href'         => $new_doc_href,
            ]);
            return $this->index();
        }
        
        static public function readable_permissions(array $defaultValue = [], array $userSuppliedValue = []):array {
            return array_merge(['permission' => "CRUDControllerPermission",], $defaultValue, $userSuppliedValue);
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
         * @return string Genereally this is going to be a `view("/some/template")` call
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
            return $this->__read($id);
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
                'delete_option' => "<option method=\"DELETE\" action=\"".route("$this->name@__destroy")."$id\" dangerous=\"true\">Delete</option>",
                'doc' => $doc,
            ]);
            
            return $this->edit($doc);
        }

        static public function updateable_permissions(array $defaultValue = [], array $userSuppliedValue = []):array {
            return array_merge(['permission' => "CRUDControllerPermission",], $defaultValue, $userSuppliedValue);
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
        */
        abstract function destroy(GenericMap|BSONDocument $document):array;

        final public function __destroy($id) {
            $read = $this->__read($id);
            if(!$read) throw new NotFound("Resource not found");
            $default_confirm_message = "Are you sure you want to delete this record?";
            
            $confirm_message = $this->destroy($read);

            confirm($confirm_message['message'] ?? $confirm_message[0] ?? $default_confirm_message, $confirm_message['post'] ?? $_POST, $confirm_message['okay'] ?? "Yes", $confirm_message['dangerous'] ?? true);
            
            $_id = new ObjectId($id);
            $result = $this->manager->deleteOne(['_id' => $_id]);
            header("X-Redirect: " . route("$this->name@__index"));
            return $result->getDeletedCount();
        }

        static public function destroyable_permissions(array $defaultValue = [], array $userSuppliedValue = []):array {
            return array_merge(['permission' => "CRUDControllerPermission",], $defaultValue, $userSuppliedValue);
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

            Route::get("$mutant/{id}", "$class@__read", self::readable_permissions($options['read'] ?? []));
            Route::post("$mutant/create", "$class@__create", self::creatable_permissions($options['create'] ?? []));
            Route::post("$mutant/update/{id}", "$class@__update", self::updateable_permissions($options['update'] ?? []));
            Route::delete("$mutant/delete/{id}", "$class@__destroy", self::destroyable_permissions($options['destroy'] ?? []));
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

            Route::get("$mutant/", "$class@__index", self::readable_permissions([
                    'anchor' => ['name' => $options['index']['anchor'] ?? self::generate_friendly_name()],
                    'navigation' => [$options['index']['navigation'] ?? 'admin_panel']
                ],
                $options['index'] ?? []
                )
            );
            Route::get("$mutant/new", "$class@__new_document", self::creatable_permissions($options['new'] ?? []));
            Route::get("$mutant/edit/{id}", "$class@__edit", self::updateable_permissions($options['edit'] ?? []));
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
}