<?php

namespace Cobalt\Maps;

trait NestedFind {
    public function __isset($name):bool {
        if($name === "_id") return !!$this->id;
        if(key_exists($name, $this->__hydrated)) return true;
        if(key_exists($name, $this->__schema)) return true;
        if(key_exists($name, $this->__dataset)) return true;
        $nestedFindResult = false;
        // if(strpos($name, ".")) $nestedFindResult = $this->__nestedFind($name);
        // if($nestedFindResult) return true;
        
        // // // if($this->__setChecker($name, $this->__strictFind)) return true;
        // if(strpos($name, ".")) return $this->__isset_deep($name);

        return false;
    }

    // function __nestedFind($name) {
    //     // Let's say $name = "media"
    //     foreach($this->__schema as $field => $value) {
    //         // Eventually, $field === "media.filename"
    //         if(strpos($field, ".") === false) continue; // If this field is not a dot notation path, continue
    //         if(strpos($field, $name) === 0) {
    //             if(key_exists($name, $this->__dataset)) return true;
    //         }
    //     }
    //     return false;
    // }

    // function __isset_deep($name) {
    //     $explodedName = explode(".", $name);
    //     $found = "";
    //     $candidate = [$this, null, ""];
    //     while(count($explodedName) > 0) {
    //         $currentPath = array_shift($explodedName);
    //         $remainingPath = "$currentPath.". implode(".", $explodedName);
    //         $candidate[1] = null;
    //         $candidate[2] = "";

    //         if($candidate[0] instanceof SchemaResult) {
    //             $candidate = $this->__handleSchemaResult($candidate[0], $currentPath, $remainingPath);
    //         }

    //         if($candidate[0] instanceof GenericMap) {
    //             $candidate = $this->__handleGenericMap($candidate[0], $currentPath, $remainingPath);
    //         }
            
    //         if($candidate[1] === true) $found .= ($found) ? ".$candidate[2]" : "$candidate[2]";
    //     }
    //     if($name === $found) return true;
    //     return false;
    // }

    // function __handleGenericMap($candidate, $name, $remaining) {
    //     $isset = isset($candidate[$name]);
    //     if($isset) return [$candidate[$name], true, $name];
    //     $isset = isset($candidate[$remaining]);
    //     if($isset) return [$candidate[$remaining], true, $remaining];
    //     return [$candidate, false, null];
    // }

    // function __handleSchemaResult($candidate, $name, $remaining) {
    //     if($candidate instanceof MapResult) return [$candidate->getRaw(), null, ""];
    //     return [$candidate, null, ""];
    // }

    
}