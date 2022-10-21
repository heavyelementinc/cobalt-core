<?php
/**
 * CRUD - The Cobalt Engine Warpper
 * "It takes the tedium out of route implementation"
 * 
 * Steps you need to take:
 *  1) Make sure your CRUD heir and its companion controller match.
 */
namespace Cobalt\Wrappers;

use MongoDB\BSON\ObjectId;
use Routes\Route;
use Validation\Normalize;

abstract class CRUD extends \Drivers\Database {
    /**
     * This function must return an array of items which lists a value
     * @return array
     */
    abstract function table_layout(): array;

    /**
     * The list method returns entries for the administrative index.
     * @param array $query 
     * @param array $options 
     * @return iterable of Normalize
     */
    function list($query = [], $options = []):iterable {
        return $this->findAllAsSchema($query, $options);
    }

    /**
     * The index method returns entries for the public index
     * @param array $query 
     * @param array $options 
     * @return iterable of Normalize
     */
    function index($query = [], $options = []):iterable {
        return $this->findAllAsSchema(
            array_merge(
                $query, ['public' => true]
            ),
            $options
        );
    }

    /**
     * The entry method returns a normalized representation of an element
     * @return Normalize 
     */
    function entry($url_or_id, $additionalQuery, $options = []):\Validation\Normalize {
        $query = ['url_slug' => $url_or_id];

        if(self::isValidDbId($url_or_id)) $query = ['_id' => new ObjectId($url_or_id)];

        return $this->findOneAsSchema(array_merge($additionalQuery, $query), $options);
    }

    static function isValidDbId($id):bool {
        if($id instanceof ObjectId) return true;
        try {
            new ObjectId($id);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    static function prepare_prefix($prefix) {
        $mutant = $prefix;
        if($mutant[0] !== "/") $mutant = "/$mutant";
        if($mutant[strlen($mutant) - 1] === "/") $mutant = substr($mutant,0, -1);
        return $mutant;
    }

    static function get_controller_name():string {
        $class = explode("\\",self::class);
        return $class[count($class) - 1];
    }

    static function declare_public_routes($prefix):void {
        $pfx = self::prepare_prefix($prefix);
        $controller = self::get_controller_name();
        Route::get("$pfx", "$controller@index");
        Route::get("$pfx/{slug}", "$controller@entry");
    }

    static function declade_admin_routes($prefix):void {
        $pfx = self::prepare_prefix($prefix);
        $controller = self::get_controller_name();
        Route::get("$pfx", "$controller@list");
        Route::get("$pfx/edit/{id}", "$controller@edit");
    }

    static function declare_api_routes($prefix):void {
        $pfx = self::prepare_prefix($prefix);
        $controller = self::get_controller_name();
        Route::get("$pfx/list",         "$controller@list");
        Route::get("$pfx/{id}",         "$controller@entry");
        Route::put("$pfx/{id}?",        "$controller@update");
        Route::post("$pfx/{id}/upload", "$controller@upload");
        Route::delete("$pfx/{id}",      "$controller@delete");
    }
}