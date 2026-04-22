<?php

namespace Cobalt\Model\Types;

class ArrayOfPermissionsType extends ArrayType {

    public function finalInitialization():void {
        $this->define_valid(auth()->getPermissionSingleton()->getValidPermissions(), 'valid');
    }

    function __get($name)
    {
        return $this->value[$name];
    }

}