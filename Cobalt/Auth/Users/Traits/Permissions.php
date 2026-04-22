<?php

namespace Cobalt\Auth\Users\Traits;

use Cobalt\Auth\Permissions\Exceptions\PermissionException;
use Cobalt\Auth\Users\Models\User;
use Exceptions\HTTP\Unauthorized;

trait Permissions {
    
    static function hasPermission(?User $user, string $permission, bool $throwOnFail = true):bool {
        if(!$user) {
            if($throwOnFail) throw new Unauthorized("Session does not exist");
            return false;
        }
        $permissionSingleton = auth()->getPermissionSingleton();
        // Check if this permission exists
        $isValidPermissionId = $permissionSingleton->isValidPermissionIdentifier($permission);
        if(!$isValidPermissionId) throw new PermissionException("Invalid permission identifier");
        
        // If the permission does exist, let's check if this user is root
        if($user->is_root->value === true) return true;

        return self::explicitPermission($user, $permission);
    }

    static function explicitPermission(?User $user, string $permission) {
        // If the user is not root, let's check if they have the permission
        if(!key_exists($permission, $user->__dataset['permissions']->getValue()->__dataset ?? [])) return false;
        
        return $user->__dataset['permissions']->getValue()->__dataset[$permission]->getValue();
    }

    function findUsersByPermission(string|array $permission, bool $permissionType = true) {
        global $auth;
        $permissionSingleton = $auth->getPermissionSingleton();
        if(is_string($permission)) $permission = [$permission];
        $queryModel = [
            '$or' => [
                ['is_root' => true]
            ]
        ];
        if(!$permissionType) $queryModel = [
            ['is_root' => ['$ne' => true]],
            '$or' => []
        ];
        foreach($permission as $perm) {
            $isValidPermissionId = $permissionSingleton->isValidPermissionIdentifier($perm);
            if(!$isValidPermissionId) throw new PermissionException("Invalid permission identifier");
            $queryModel['$or'][] = ["permissions.$perm" => $permissionType];
        }
        return $this->find($queryModel);
    }

}