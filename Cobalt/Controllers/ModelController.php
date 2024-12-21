<?php
namespace Cobalt\Controllers;

use Cobalt\Controllers\Traits\CreateableModel;
use Cobalt\Controllers\Traits\DestroyableModel;
use Cobalt\Controllers\Traits\EditableModel;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Model;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\Controllers\Traits\IndexableModel;
use Cobalt\Controllers\Traits\ReadableModel;
use Cobalt\Controllers\Traits\UpdateableModel;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;
use Routes\Route;
use TypeError;

abstract class ModelController {
    use IndexableModel, EditableModel, CreateableModel, ReadableModel, UpdateableModel, DestroyableModel;
    public $name;
    public string $friendly_name;
    public Model $model;
    public int $index_limit = 50;

    protected int $index_display_action_menu = 0;

    function __construct(?string $name = null) {
        $this->name = self::className();
        $this->friendly_name = self::generate_friendly_name($name);
        $this->model = $this->defineModel();
    }

    /** @return Model */
    abstract function defineModel(): Model;

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

            Route::get("$mutant/{id}", "$class@__read", static::route_details(
                [
                    'permission' => "CRUDControllerPermission",
                ],
                $options['read'] ?? [],
                "route_details_read")
            );
            Route::post("$mutant/create", "$class@__create", static::route_details(
                [
                    'permission' => "CRUDControllerPermission",
                ],
                $options['create'] ?? [],
                "route_details_create")
            );
            Route::post("$mutant/update/{id}", "$class@__update", static::route_details(
                [
                    'permission' => "CRUDControllerPermission",
                ],
                $options['update'] ?? [],
                "route_details_update")
            );
            Route::delete("$mutant/delete/{id}", "$class@__destroy", static::route_details(
                [
                    'permission' => "CRUDControllerPermission",
                ],
                $options['destroy'] ?? [],
                "route_details_destroy")
            );
            Route::delete("$mutant/multi-delete/", "$class@__multidestroy", static::route_details(
                [
                    'permission' => "CRUDControllerPermission",
                ],
                $options['destroy'] ?? [],
                "route_details_destroy")
            );
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
            return array_merge($default_values, $callable_results, $details);
        }

        static function generate_prefix($supplied):string {
            if($supplied) {
                if($supplied[0] !== "/") $supplied = "/$supplied";
                return $supplied;
            }
            $supplied = (new \ReflectionClass(self::className()))->getShortName();
            $prefix = preg_replace('/([A-Z])/', '-$1',$supplied);
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
        $supplied = (new \ReflectionClass(self::className()))->getShortName();
        $prefix = preg_replace('/([A-Z])/', ' $1',$supplied);
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
    function __get_action_menu(string $type = "", Model|BSONDocument|null $document = null):string {
        $class = self::className();
        $html = "";
        if($this->index_display_action_menu | CRUDABLE_DELETEABLE) {
            $html .= "<option method=\"DELETE\" action=\"".route("$class@__destroy", [(string)$document->_id])."\">Delete</option>";
        }

        return "<action-menu type=\"$type\">$html</action-menu>";
    }
}