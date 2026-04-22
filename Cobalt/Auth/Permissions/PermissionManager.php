<?php

namespace Cobalt\Auth\Permissions;

use Cobalt\Auth\Permissions\Exceptions\PermissionException;

class PermissionManager {
    const ENV_PERMISSIONS = __ENV_ROOT__ . "/config/default_permissions.php";
    const APP_PERMISSIONS = __APP_ROOT__ . "/config/permissions.php";

    /** @property array<Permission> $permissions */
    protected array $permissions = [];
    protected array $valid = [];

    function __construct(){
        $this->initializePermissions();
    }

    function initializePermissions() {
        $env = $this->loadPermissionFile(self::ENV_PERMISSIONS, false);
        $app = $this->loadPermissionFile(self::APP_PERMISSIONS, false);
        
        // Pull in the global PERMISSIONS details;
        global $PERMISSIONS;
        $permission = $PERMISSIONS ?? [];
        $this->permissions = array_merge($env, $permission, $app);
        /**
         * @var string $key
         * @var Permission $val
         */
        foreach($permission as $key => $val) {
            $this->valid[$key] = $val->getName();
        }
    }

    private function loadPermissionFile(string $file, bool $required = false):array {
        if(!file_exists($file)) {
            if($required) throw new PermissionException("Failed to load required permission file $file");
            return [];
        } 
        $permissions = include $file;
        if(!is_array($permissions)) throw new PermissionException("File appears to be malformed");
        $built = [];
        foreach($permissions as $permission) {
            if($permission instanceof Permission == false) {
                throw new PermissionException("Permission must be of instance Permission");
            }
            $built[$permission->getIdentifier()] = $permission;
        }
        return $built;
    }

    function getValid() {
        $valid = [];
        /** @var string $key
         * @var Permission $permission
         */
        foreach($this->permissions as $key => $permission) {
            $valid[$key] = $permission->getLabel();
        }
        return $valid;
    }

    function isValidPermissionIdentifier(string $identifier):bool {
        return key_exists($identifier, $this->permissions);
    }

    function getAllPermissions() {
        return $this->permissions;
    }

    /**
     * This function returns 'valid' set of permissions used in the typical
     * MixedType 'valid' directive format
     * @return array
     */
    function getValidPermissions():array {
        return $this->valid;
    }

    function getPermission(string $identifier):?Permission {
        if(!key_exists($identifier, $this->permissions)) return null;
        return $this->permissions[$identifier];
    }

    

}