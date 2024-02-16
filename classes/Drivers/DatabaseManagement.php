<?php

namespace Drivers;

use Cobalt\Maps\GenericMap;
use Cobalt\SchemaPrototypes\Basic\UploadResult;
use Cobalt\SchemaPrototypes\MapResult;
use Cobalt\SchemaPrototypes\Wrapper\DefaultUploadSchema;
use Exception;

class DatabaseManagement {
    private $db;

    const IGNORED = ["sessions","cron"];

    function __construct() {
        $this->db = db_cursor('database', null, false, true);
    }

    public function collections() {
        return $this->db->listCollections();
    }
    
    public function export($file = null, $talk = false, $ignored = true, $extraIgnored = [], $onlyExport = null) {
        if(!$file) $file = app("DB_export_directory");
        $file = __APP_ROOT__ . $file;
        if(!file_exists($file)) mkdir($file, 0777, true);

        if($talk) say("Started database export");
        $extraIgnored = array_merge($extraIgnored ?? [], $this::IGNORED);
        $db_backup = [];
        $collections = $this->db->listCollections();

        if(is_array($onlyExport)) {
            $c = [];
            foreach($collections as $col) {
                if(in_array($col->getName(), $onlyExport)) $c[] = $col;
            }
            $collections = $c;
            if(count($collections) === 0) return fmt("No collections to export. Aborting.", 'e');
        }

        foreach($collections as $collection) {
            $whole_collection = [];
            $name = $collection->getName();
            if($ignored === true && in_array($name, $extraIgnored)) continue;
            $count = $this->db->{$name}->count([]);
            $result = $this->db->{$name}->find([],['limit' => $count + 1]);
            if($talk) printf($name . " contains $count document(s)");

            $entries = [];
            foreach($result as $row) {
                if($row instanceof GenericMap) {
                    $row_to_array = $this->preserveMap($row);
                } else {
                    $row_to_array = iterator_to_array($row);
                }
                array_push($entries, $row_to_array);
                if($talk) printf(".");
            }

            array_push($db_backup, [
                'collection' => $name,
                'data' => $entries
            ]);
            if($talk) say(" done", "i");
        }
        $filepath = $file . $this->get_backup_file_name();
        if($talk) printf("Writing file... ");
        if(!is_writable(pathinfo($filepath, PATHINFO_DIRNAME))) return say(" file path is not writeable!", "e");
        try {
            if(file_put_contents($filepath, json_encode($db_backup, JSON_THROW_ON_ERROR, 1024)) === false) return say(" writing $filepath failed!", "e");
        } catch (Exception $e) {
            if($talk) {
                printf(fmt("ERROR!\n"), 'e');
                printf(fmt($e->getMessage()."\n",'e'));
                exit;
            }
        }
        if(!file_exists($filepath)) return say(" an unknown error occurred. $filepath does not exist.");
        if($talk) {
            say(" done", "i");
            print(fmt("$filepath ", 'i'));
            say(number_format(filesize($filepath) / 1024, 2,"",".") . "Kb");
        }
        return;
    }

    function preserveMap(GenericMap $map) {
        // Let's get the actual values from the database
        $__dataset = $map->jsonSerialize();
        foreach($map->readSchema() as $key => $value) {
            // And then lets overwrite them with the <Type>Result value
            $val = $map->{$key};
            if($value['type'] instanceof MapResult) {
                if($val->value === null) continue;
                $__dataset[$key] = $this->preserveMap($val->value);
                continue;
            }
            $__dataset[$key] = $val;
        }

        if($map instanceof DefaultUploadSchema) {
            foreach($__dataset as $key => $val) {
                $__dataset[$key] = iterator_to_array_recursive($val);
                unset($__dataset[$key]['meta']["$key.meta"]);
            }
        }

        return iterator_to_array_recursive($__dataset);
    }

    function get_backup_file_name() {
        $name = $this->db->getDatabaseName();
        return $name . "-" . time() . ".json";
    }

    public function import($filename, $talk = false, $caution = true) {
        if(!file_exists($filename)) return say("File `$filename` does not exist.", "e");
        $contents = json_decode(file_get_contents($filename),true);
        $count = count($contents);
        if($talk) say("Loaded $count collections from file", "i");
        $read = "n";
        if(function_exists("say")) say("This operation will ".fmt("drop","e")." any collections contained within this backup before restoring them from this backup.");
        if($caution) {
            if(function_exists("say")) say("Any data in your database will be lost if not contained in this backup.", "e");
            $read = readline("Are you sure you want to continue? y/N");
            if(!cli_to_bool($read)) return fmt("Aborted.","e");
        }
        $db = $this->db;
        $collections_restored = 0;
        $documents_inserted = 0;
        foreach($contents as $col) {
            $collection_name = $col['collection'];
            $docs = $col['data'];
            if($talk) say("Dropping collection $collection_name for clean restoration", 'e');
            $db->dropCollection($collection_name);
            if($talk) printf("Processing collection ".fmt($collection_name, "i")." and its ".count($docs)." documents");
            $collection = $db->{$collection_name};
            $collections_restored++;
            foreach($docs as $row) {
                $json_row = json_encode($row);
                $bson = \MongoDB\BSON\fromJSON($json_row);
                $row = \MongoDB\BSON\toPHP($bson);

                if($row instanceof GenericMap) {
                    // $row->__dataset['_id'] = $row->id;
                }
                $result = $collection->insertOne($row);

                $documents_inserted += $result->getInsertedCount();
                if($talk) printf(".");
            }
            if($talk) say(" done", "i");
        }
        return "Restored $collections_restored collection".plural($collections_restored)." and $documents_inserted document".plural($documents_inserted).".";
    }
}
