<?php

/** Used with the '...' route path symbol, provide the string as $path amd */
function build_array_from_path(&$arr, $path, $value, $delimiter = ".") {
    $keys = explode($delimiter, $path);
    foreach ($keys as $key) {
        $arr = &$arr[$key];
    }
    $arr = $value;
}

function build_object_from_paths($object) {
    $mutant = [];
    foreach ($object as $path => $value) {
        $arr = [];
        build_array_from_path($arr, $path, $value);
        $mutant = array_merge_recursive($mutant, $arr);
    }
    return $mutant;
}


/** Used with the '...' route path symbol, provide the string as $path and valid
 * keys as $keys
 * 
 * If the path equals `/some/path/key/value` and $keys equals ['key']
 * 
 * The return value will be ['key' => 'value']
 * 
 * All other info in the string will be ignored.
 * 
 * @param string $path
 * @param array $keys a list of valid keys to parse for
 * @return array the processed associative array
 */
function associative_array_helper(string $path, array $keys) {
    $exploded = explode("/", $path);
    $array = array_fill_keys($keys, null);
    for ($i = 0; $i < count($exploded); $i++) {
        if (in_array($exploded[$i], $keys)) {
            $array[$exploded[$i]] = $exploded[$i + 1];
            $i++;
        }
    }
    return $array;
}

function associative_to_path(array $arr) {
    $path = "/";
    foreach ($arr as $name => $val) {
        $path .= "$name/$val/";
    }
    return $path;
}

/**
 * Will determine if an array has string keys
 * Will provide a false positive if indexes are non-linear
 * @param mixed $array 
 * @return bool 
 */
function is_associative_array(mixed $array) {
    if(gettype($array) !== "array") return false;
    if (array() === $array) return false;
    return array_keys($array) !== range(0, count($array) - 1);
}

/**
 * @param MongoDB\BSON\Document|mixed $it The Mongo document to be converted
 * @return array returns an array representation of the document
 */
function doc_to_array($it): array {
    if (is_array($it)) return $it;
    $result = [];
    foreach ($it as $key => $value) {
        if ($value instanceof \Traversable) {
            $result[$key] = doc_to_array($value);
        } else {
            $result[$key] = $value;
        }
    }
    return $result;
}

function iterator_to_array_recursive($it):array {
    $mutant = [];
    foreach($it as $key => $value) {
        if($value instanceof \Traversable) $value = iterator_to_array($value);
        if(is_array($value)) $mutant[$key] = iterator_to_array_recursive($value);
        else $mutant[$key] = $value;
    }
    return $mutant;
}

/**
 * Merges the elements of one or more arguments
 * @param array|Iterator $args,... Arguments
 * @return mixed 
 */
function merge() {
    $arguments = func_get_args();
    try {
        return array_merge(...$arguments);
    } catch (TypeError $e) {

    }
    $list = [];
    foreach($arguments as $i => $arg) {
        if($arg instanceof \MongoDB\Model\BSONDocument) {
            $list[$i] = doc_to_array($arg);
            continue;
        }
        if($arg instanceof Iterator) {
            $list[$i] = iterator_to_array($arg);
            continue;
        }
        $list[$i] = $arg;
    }
    return array_merge(...$list);
}

function array_append(&$array) {
    
}