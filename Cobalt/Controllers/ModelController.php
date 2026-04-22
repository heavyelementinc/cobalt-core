<?php
namespace Cobalt\Controllers;

use Cobalt\Controllers\Interfaces\BatchOperations;
use Cobalt\Controllers\Traits\CreateableModel;
use Cobalt\Controllers\Traits\DestroyableModel;
use Cobalt\Controllers\Traits\EditableModel;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Model;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\Controllers\Traits\IndexableModel;
use Cobalt\Controllers\Traits\ReadableModel;
use Cobalt\Controllers\Traits\SearchableModel;
use Cobalt\Controllers\Traits\SortableModel;
use Cobalt\Controllers\Traits\UpdateableModel;
use MongoDB\Model\BSONDocument;
use Routes\Options;
use Routes\Route;
use TypeError;

abstract class ModelController extends Controller {
    use IndexableModel, SearchableModel, SortableModel, EditableModel, CreateableModel, ReadableModel, UpdateableModel, DestroyableModel;
    public $name;
    public string $friendly_name;
    public Model $model;
    public int $index_limit = 50;

    static $api_read_permission          = "CRUDControllerPermission";
    static $api_create_permission        = "CRUDControllerPermission";
    static $api_update_permission        = "CRUDControllerPermission";
    static $api_destroy_permission       = "CRUDControllerPermission";
    static $api_multidestroy_permission  = "CRUDControllerPermission";
    static $api_batch_archive_permission = "CRUDControllerPermission";
    static $api_archive_permission       = "CRUDControllerPermission";
    static $admin_index                  = "CRUDControllerPermission";
    static $admin_new_document           = "CRUDControllerPermission";
    static $admin_edit                   = "CRUDControllerPermission";

    protected int $index_display_action_menu = 0;

    function __construct(?string $name = null) {
        $this->name = static::className();
        $this->friendly_name = static::generate_friendly_name($name);
        $this->model = static::defineModel();
    }

    /** @return Model */
    abstract static function defineModel(): Model;

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
            $class   = static::className();
            $mutant  = static::generate_prefix($prefix);
            $modelClass = static::defineModel()::class;

            Route::get("$mutant/{id}", "$class@__read", static::route_details(
                [
                    'permission' => static::$api_read_permission,
                ],
                $options['read'] ?? [],
                "route_details_read")
            );
            Route::get("$mutant/{id}/model/{name}", "$modelClass@__model", static::route_details(
                [
                    'permission' => static::$api_read_permission
                ],
                $options['read'] ?? [],
                "route_details_read"
            ));
            Route::post("$mutant/create", "$class@__create", static::route_details(
                [
                    'permission' => static::$api_create_permission,
                ],
                $options['create'] ?? [],
                "route_details_create")
            );
            Route::post("$mutant/update/{id}", "$class@__update", static::route_details(
                [
                    'permission' => static::$api_update_permission,
                ],
                $options['update'] ?? [],
                "route_details_update")
            );
            Route::delete("$mutant/delete/{id}", "$class@__destroy", static::route_details(
                [
                    'permission' => static::$api_destroy_permission,
                ],
                $options['destroy'] ?? [],
                "route_details_destroy")
            );
            Route::delete("$mutant/multi-delete/", "$class@__multidestroy", static::route_details(
                [
                    'permission' => static::$api_multidestroy_permission,
                ],
                $options['destroy'] ?? [],
                "route_details_destroy")
            );
            Route::delete("$mutant/archive/batch", "$class@__archive_batch", static::route_details(
                [
                    'permission' => static::$api_batch_archive_permission,
                ],
                $options['destroy'] ?? [],
                "route_details_destroy")
            );
            Route::delete("$mutant/archive/{id}", "$class@__archive", static::route_details(
                [
                    'permission' => static::$api_archive_permission,
                ],
                $options['destroy'] ?? [],
                "route_details_destroy")
            );
            // if(static implements BatchOperations) {
            Route::post("$mutant/batch/{id}", "$class@__batchIdOperation", static::route_details([],[],"route_details_update"));
            // }
            
            set_crudable_flag($class, CRUDABLE_CONFIG_APIV1);
        }

        /**
         * `options` keys:
         *  * index
         *  * new
         *  * edit
         */
        static function admin(?string $prefix = null, array $options = []) {
            $class   = static::className();
            $mutant  = static::generate_prefix($prefix);

            $anchor = [
                'name' => $options['name'] ?? $options['anchor'] ?? static::generate_friendly_name()
            ];
            if(key_exists('submenu_group', $options)) {
                $anchor['submenu_group'] = $options['submenu_group'];
            }

            Route::get("$mutant/", "$class@__index", static::route_details(
                    [
                    'anchor' => $anchor,
                    'navigation' => [$options['navigation'] ?? 'admin_panel'],
                    'permission' => static::$admin_index,
                ],
                $options['index'] ?? [],
                "route_details_index"
            ));
            Route::get("$mutant/new", "$class@__new_document", static::route_details(
                [
                    'permission' => static::$admin_new_document,
                ],
                $options['new_document'] ?? [],
                "route_details_create"
            ));
            Route::get("$mutant/edit/{id}", "$class@__edit", static::route_details(
                [
                    'permission' => static::$admin_edit,
                ],
                $options['edit'] ?? [],
                "route_details_update"
            ));
            set_crudable_flag($class, CRUDABLE_CONFIG_ADMIN);
        }
    
    function __set_action_menu(int $state) {
        $this->index_display_action_menu = $state;
    }

    function __get_action_menu_state(): bool {
        return $this->index_display_action_menu;
    }

    /** $type - can be blank or "options" */
    function __get_action_menu(string $type = "", Model|BSONDocument|null $document = null):string {
        $class = static::className();
        $html = "";
        if($this->index_display_action_menu | CRUDABLE_DELETEABLE) {
            $html .= "<option method=\"DELETE\" action=\"".route("$class@__destroy", [(string)$document->_id])."\">Delete</option>";
        }

        return "<action-menu type=\"$type\">$html</action-menu>";
    }

}