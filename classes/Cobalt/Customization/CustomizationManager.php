<?php

namespace Cobalt\Customization;

use ArrayAccess;
use Cobalt\Extensions\Extensions;
use MongoDB\BSON\ObjectId;

class CustomizationManager extends \Drivers\Database {
    const CUSTOMIZATION_FILE = [
        __ENV_ROOT__ . "/config/customizations.php",
        __APP_ROOT__ . "/config/customizations.php",
    ];

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

    function load() {
        global $DECLARED_CUSTOMIZATIONS;
        $DECLARED_CUSTOMIZATIONS = [];
        $customization_files = self::CUSTOMIZATION_FILE;
        Extensions::invoke("register_customizations", $customization_files);
        foreach($customization_files as $file) {
            if(!file_exists($file)) continue;
            if(is_callable("say")) say("Importing " . obfuscate_path_name($file));
            require($file);
        }
        return $DECLARED_CUSTOMIZATIONS;
    }

    function import(bool $reset = false) {
        if(is_callable("say")) say("", "i");
        // Load our default definitions
        $this->load();
        
        // Reference our customizations and loop through them
        global $DECLARED_CUSTOMIZATIONS;
        foreach($DECLARED_CUSTOMIZATIONS as $definition) {
            // 
            $results = $this->getCustomizationByUniqueName($definition['unique_name']);
            if($reset === false) {
                if($results) {
                    if(is_callable("say")) say("[".fmt("SKIP", "i")."] " . fmt("$definition[unique_name] ", "b"));
                    continue;
                }
            }
            $upsert = $this->updateOne(['_id' => $results['_id'] ?? new ObjectId()], ['$set' => $definition], ['upsert' => true]);
            if(is_callable("say")) {
                if($upsert->getModifiedCount()) print("[".fmt("OKAY","s")."] " . fmt("$definition[unique_name] ", "b") . "\n");
                else print("[".fmt("PASS","w")."] " . fmt("$definition[unique_name] ", "b") . "\n");
            }
        }
    }
}
