<?php

namespace Cobalt\Model\Types;

class ArrayOfPermissionsType extends ArrayType {

    public function finalInitialization():void {
        $valid = [];
        foreach($GLOBALS['auth']->permissions->valid as $index => $arr) {
            $valid[$index] = strip_tags($arr['label']);
        }
        $this->define_valid($valid, 'valid');
    }

}