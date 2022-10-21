<?php

/**
 * Cache Manager
 * 
 * The Cobalt Engine offers a filename-based approach to cached files. This class
 * handles caches.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 Heavy Element, Inc.
 */

namespace Cache;

use Exception;

class Manager extends \Drivers\FileSystem {

    private $cache_dir = __APP_ROOT__ . "/cache";

    function __construct($filename) {
        parent::__construct();
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
    public function cache_exists():bool {
        if($this->findByFilename($this->file_path) !== null) return true;
        return false;
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
        $version = __APP_SETTINGS__['version'] ?? "000";
        $path = $this->cache_dir . "/$info[dirname]/$info[filename].$version.$info[extension]";
        return $path;
    }
}
