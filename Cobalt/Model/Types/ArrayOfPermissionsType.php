<?php

namespace Cobalt\Model\Types;

class ArrayOfPermissionsType extends ArrayType {

    public function finalInitialization():void {
        $this->define_valid($GLOBALS['auth']->getPermissionSingleton()->getValid(), 'valid');
    }

}