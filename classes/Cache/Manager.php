<?php

/**
 * Cache Manager
 * 
 * The Cobalt Engine offers a file-based approach to cached files. This class
 * handles caches.
 * 
 * @todo Provide a mongo-based cache alternative
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2025 Heavy Element, Inc.
 */

namespace Cache;

use Exception;

class Manager {

    private $cache_dir = __APP_ROOT__ . "/cache/" . __COBALT_VERSION . "/" . __APP_SETTINGS__['version'];

    public $reference;
    public $file_path;
    public $exists;
    public $last_modified;

    function __construct($filename) {
        if (!is_dir($this->cache_dir)) mkdir($this->cache_dir, 0777, true);
        $this->reference = $filename;
        $this->file_path = $this->cache_name($this->reference);
        $this->exists = $this->cache_exists();
        $this->last_modified = $this->modified();
    }

    /** Retrieve a file by it's common reference for example:
     * "config/settings.json" 
     * 
     * That filename will be parsed and retrieved from the cache.
     * 
     * @param bool $load [true] false will return the pathname */
    public function get($load = true) {
        if ($load == false) return $this->file_path;
        $path = $this->file_path;
        if (!$this->exists) throw new \Exception("File `$this->reference` does not exist in cache");
        if ($load === "json") return get_json($path);
        if ($load) return file_get_contents($path);
        return $this->reference;
    }

    /**
     * Set a cache item
     * 
     * @param string|json-able $contents string
     * @param bool $json [true] json_encode the contents
     * @return mixed 
     * @throws Exception 
     */
    public function set($contents, $json = true) {
        $path = $this->file_path;
        $info = pathinfo($path, PATHINFO_DIRNAME);
        $mkdir = true;
        if (!is_dir($info)) $mkdir = mkdir($info, 0777, true);
        if (!$mkdir) throw new \Exception("Could not make the directory path for $this->reference");
        if ($json) $contents = json_encode($contents);
        if (@file_put_contents($path, $contents) === false) throw new \Exception("Could not write to $this->reference.");
        return $path;
    }

    /**
     * Checks if a cached version of the common reference exists.
     * 
     * @return bool 
     */
    public function cache_exists() {
        return file_exists($this->file_path);
    }

    function outdated($compare, $margin = 0) {
        if (!$this->exists) return true;
        $path = $this->file_path;
        if (!file_exists($compare)) return false;
        $resource_date = filemtime($compare);
        $val = ($resource_date - $margin > $this->last_modified);
        return $val;
    }

    function modified() {
        if ($this->exists) return filemtime($this->file_path);
        return 0;
    }

    private function cache_name($reference) {
        $info = pathinfo($reference);
        // $version = VERSION_HASH;
        $path = $this->cache_dir . "/$info[dirname]/$info[filename].$info[extension]";
        return $path;
    }

    public function empty() {
        // if(rmdir($this->cache_dir)) {
        //     return true;
        // }
        // return error_get_last();
        $deleted = [];
        rrmdir($this->cache_dir, $deleted);
        return $deleted;
    }
}
