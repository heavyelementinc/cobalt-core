<?php

namespace Cobalt\Customization;

use ArrayAccess;

class CustomizationManager extends \Drivers\Database {

    private $cache = [];

    public function get_collection_name() {
        return 'customizations';
    }

    public function get_schema_name($doc = []) {
        return '\\Cobalt\\Customization\\CustomSchema';
    }
    
    public function getCustomizationByUniqueName($name, $options = []) {
        // if(key_exists($name, $this->cache)) return $this->cache[$name];
        return $this->findOneAsSchema(['unique_name' => $name], $options);
    }

    public function getCustomizationValue($name) {
        if(key_exists($name, $this->cache)) return $this->cache[$name];
        // Optimization technique: only return the value
        $val = $this->getCustomizationByUniqueName($name, ['projection' => ['type' => 1, 'value' => 1, 'meta' => 1]]);

        $this->cache[$name] = $val;
        return $this->cache[$name];
    }

    public function getCustomizationsByGroupName($group) {
        return $this->findAllAsSchema(['group' => $group]);
    }

    public function group_options() {
        $result = $this->distinct('group');
        $options = "";
        
        foreach($result as $opt) {
            $options .= "<option value='$opt'>$opt</option>";
        }

        return $options;
    }

    function __get($name) {
        $value = $this->getCustomizationValue($name);
        if(!$value) {
            if(app("debug")) trigger_error("The customization $name is referenced by not set.", E_USER_NOTICE);
            if(app("error_on_missing_customization")) throw new \Exception("Missing customization value '$name'!");
        }

        return $value;
    }
}
