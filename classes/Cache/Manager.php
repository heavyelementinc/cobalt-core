<?php
namespace Cache;
class Manager{

    function __construct($filename){
        $this->reference = $filename;
        $this->file_path = $this->cache_name($this->reference);
        $this->exists = $this->cache_exists();
        $this->last_modified = $this->modified();
    }

    /** Retrieve a file by it's common reference (for example, "config/settings.json") and retrieve the
     * latest file name. An argument of false will just return the pathname */
    function get($load = true){
        $path = $this->file_path;
        if(!$this->exists) throw new \Error("File `$this->reference` does not exist in cache");
        if($load === "json") return get_json($path);
        if($load) return file_get_contents($path);
        return $this->reference;
    }

    function set($contents,$json = true){
        $path = $this->file_path;
        $info = pathinfo($path,PATHINFO_DIRNAME);
        $mkdir = true;
        if(!is_dir($info)) $make_dir = mkdir($info,0777,true);
        if(!$mkdir) throw new \Error("Could not make the directory path for $this->reference");
        if($json) $contents = json_encode($contents);
        if(file_put_contents($path,$contents) === false) throw new \Error("Could not write to $this->reference.");
        return $path;
    }

    function cache_exists(){
        return file_exists($this->file_path);
    }

    function outdated($compare,$margin = 0){
        if(!$this->exists) return true;
        $path = $this->file_path;
        if(!file_exists($compare)) return false;
        $resource_date = filemtime($compare);
        $val = ($resource_date - $margin > $this->last_modified);
        return $val;
    }

    function modified(){
        if($this->exists) return filemtime($this->file_path);
        return 0;
    }

    function cache_name($reference){
        $info = pathinfo($reference);
        $version = (isset($GLOBALS['app']) && property_exists($GLOBALS['app'],'version')) ? $GLOBALS['app']->version : "000";
        $path = __APP_ROOT__ . "/cache/$info[dirname]/$info[filename].$version.$info[extension]";
        return $path;
    }
}