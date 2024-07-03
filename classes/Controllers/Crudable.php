<?php
namespace Cobalt\Controllers;

use Controllers\Traits\Createable;
use Controllers\Traits\Destroyable;
use Controllers\Traits\Readable;
use Controllers\Traits\Updateable;
use Routes\Route;

abstract class Crudable {
    use Createable, Readable, Updateable, Destroyable;
    
    abstract static function creatable_permissions(array $value = []): array;
    abstract static function readable_permissions(array $value = []): array;
    abstract static function updateable_permissions(array $value = []): array;
    abstract static function destroyable_permissions(array $value = []): array;

    // =========================================================================
    // =============================== API STUFF ===============================
    // =========================================================================
    static function apiv1(?string $prefix = null, array $options = []) {
        $class   = static::class;
        $mutant  = self::generate_prefix($prefix);
        $traits_in_use = class_uses($class);

        if(in_array('\\Cobalt\\Controllers\\Traits\\Readable', $traits_in_use)) {
            Route::get("$mutant/{id}", "$class@__read", self::readable_permissions($options['read'] ?? []));
        }

        if(in_array('\\Cobalt\\Controllers\\Traits\\Createable',$traits_in_use)) {
            Route::post("$mutant/create", "$class@__create", self::creatable_permissions($options['create'] ?? []));
        }

        if(in_array('\\Cobalt\\Controllers\\Traits\\Updateable',$traits_in_use)) {
            Route::post("$mutant/update/{id}", "$class@__update", self::updateable_permissions($options['update'] ?? []));
        }

        if(in_array('\\Cobalt\\Controllers\\Traits\\Destroyable',$traits_in_use)) {
            Route::delete("$mutant/delete/{id}", "$class@__destroy", self::destroyable_permissions($options['destroy'] ?? []));
        }
    }

    static function admin(?string $prefix = null, array $options = []) {
        $class   = static::class;
        $mutant  = self::generate_prefix($prefix);
        $traits_in_use = class_uses($class);

        if(in_array('\\Cobalt\\Controllers\\Traits\\Readable', $traits_in_use)) {
            Route::get("$mutant/", "$class@__index". self::readable_permissions($options['index'] ?? []));
        }

        if(in_array('\\Cobalt\\Controllers\\Traits\\Createable',$traits_in_use)) {
            Route::get("$mutant/new", "$class@__new_document", self::creatable_permissions($options['new'] ?? []));
        }

        if(in_array('\\Cobalt\\Controllers\\Traits\\Updateable',$traits_in_use)) {
            Route::get("$mutant/edit/{id}", "$class@__edit", self::updateable_permissions($options['edit'] ?? []));
        }

    }


    static function generate_prefix($supplied):string {
        if($supplied) {
            if($supplied[0] !== "/") $supplied = "/$supplied";
            return $supplied;
        }
        $prefix = preg_replace('/([A-Z])/', '-$1',self::class);
        if($prefix[0] == "-") $prefix = substr($prefix, 1);
        return "/" . strtolower($prefix);
    }

    static function permissions(?array $permissions) {
        $merged = $permissions ?? [];
        return $merged;
    }

}