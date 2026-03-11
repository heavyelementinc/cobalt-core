<?php

namespace Drivers;

use ArrayAccess;
use Cobalt\Maps\GenericMap;
use Cobalt\Model\Interfaces\Migration;
use Cobalt\Model\Model;
use Cobalt\SchemaPrototypes\Basic\UploadResult;
use Cobalt\SchemaPrototypes\MapResult;
use Cobalt\SchemaPrototypes\Wrapper\DefaultUploadSchema;
use DateTime;
use Error;
use Exception;
use Exceptions\HTTP\Error as HTTPError;
use Generator;
use Iterator;
use JsonSerializable;
use MongoDB\BSON;
use MongoDB\BSON\Document;
use MongoDB\BSON\Persistable;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Database;
use MongoDB\InsertOneResult;
use MongoDB\Model\BSONDocument;
use MongoDB\Model\CollectionInfo;
use MongoDB\UpdateResult;

class DatabaseManagement {
    private $db;

    const IGNORED = ["sessions","cron"];

    function __construct() {
        $this->db = db_cursor('database', null, false, true);
    }

    public function collections() {
        return $this->db->listCollections();
    }

    const EXPORT_VERSION_2_0 = "2.0";
    const EXPORT_VERSION_1_0 = null;
    const EXPORT_VERSION = self::EXPORT_VERSION_2_0;

    const EXPORT_SUPPORTED_VERSIONS = ['2.0'];

    const EXPORT_ENCODING__PLAIN = 0;
    const EXPORT_ENCODING__GZIP  = 1;

    const IMPORT__LINE_SUCCESS = 0;
    const IMPORT__LINE_FAILED = 1;
    const IMPORT__LINE_META = 2;


    public function export($file = null, bool $asArchive = false, $talk = false, $ignored = true, $extraIgnored = [], $onlyExport = null) {
        $benchmark_start = time();
        // Let's ensure that we're in CLI mode by looking for the 'say' function
        if($talk) $talk = function_exists("say"); 
        if($talk) print("Started database export");
        // Get our DB_export_directory
        if(!$file) $file = app("DB_export_directory");
        $file = __APP_ROOT__ . $file;
        if(!file_exists($file)) mkdir($file, 0777, true);

        $extraIgnored = array_merge($extraIgnored ?? [], $this::IGNORED);

        $collections = $this->db->listCollections();
        $backup_path = $file . $this->get_backup_file_name($asArchive);
        $handle = fopen($backup_path, "w+");
        if($handle === false) throw new Error("Cannot open $backup_path for writing.");
        $meta = [
            'isMeta' => true,
            'encoding' => self::EXPORT_ENCODING__PLAIN,
            'exportedAt' => null,
            'exportVersion' => self::EXPORT_VERSION,
            'collectionDetails' => [],
            'databaseName' => config()['database'],
        ];
        foreach($collections as $collection) {
            $name = $collection->getName();
            if($ignored === true && in_array($name, $extraIgnored)) continue;
            if(is_array($onlyExport) && !in_array($name, $onlyExport)) continue;
            $this->export_collection($handle, $collection, $meta, $talk);
        }
        $meta['exportedAt'] = date_format(new DateTime(),"c");
        fwrite($handle, json_encode($meta));
        fclose($handle);
        // if($asArchive){
        //     if($talk) print("Compressing export... please wait...");
        //     $archive_path = $this->get_archive_file_name($backup_path);
        //     $archive = new ZipArchive();
        //     $archive->open($archive_path);
        //     $archive->setCompressionName('file', ZipArchive::CM_BZIP2, 9);
        //     $archive->addFile($backup_path, 'file');
        //     $result = $archive->count();
        //     if($result === 1) {
        //         unlink($backup_path);
        //         $backup_path = $archive_path;
        //         say(" success!", "s");
        //     }
        //     $archive->close();
        // }

        $benchmark_end = time();
        if($talk) {
            printf("Exported database in %s\n", fmt(($benchmark_end - $benchmark_start) . " seconds"));
            printf("Exported to %s\n", fmt($backup_path,"w"));
        }
    }

    private function export_collection($handle, CollectionInfo $collection, &$meta, $talk = false) {
        $name = $collection->getName();
        $count = $this->db->{$name}->count();
        $meta['collectionDetails'][$name] = [
            'name'  => $name,
            'exported' => 0,
            'count' => $count,
        ];
        $cursor = $this->db->{$name}->find([],[
            'limit' => $count + 1000, // Find all documents in the collection
            // 'projection' => ['__pclass' => 0] // Let's recover the actual BSON
            'typeMap' => [
                'root' => 'array',
                'document' => 'array',
                'array' => 'array',
            ]
        ]);
        foreach($cursor as $i => $document) {
            $this->export_document($handle, $name, $document, $meta, $collection);
            if($talk) print("\rExporting document ".str_pad($i + 1,strlen($count), "0")." of $count from collection ".fmt($name, "i"));
        }
        if($talk) print("\n");
    }

    private function export_document($handle, string $name, array $BSONDocument, &$meta, CollectionInfo $collection) {
        // Let's normalize our inputs
        $doc = $BSONDocument;
        
        $data = [
            'col' => $name,
            'doc' => json_encode($doc),
        ];

        $json = json_encode($data);
        $int = fwrite($handle, $json.PHP_EOL);
        if($int === false) throw new Exception("Failed to write ".strlen($json)." bytes to backup file Collection: $name, Document: $doc[id].");
        $meta['collectionDetails'][$name]['exported'] += 1;
    }

    function get_backup_file_name() {
        $name = $this->db->getDatabaseName();
        return $name . "-" . time() . ".v".self::EXPORT_VERSION.".json";
    }

    function get_archive_file_name(string $json_file_path) {
        return substr($json_file_path, 0, -5) . ".tar.gz";
    }

    static function is_line_meta_entry(array $line, &$reason):bool {
        // Meta sanity check
        $sanity_check_failed_string = "Required meta key missing: `%s`";
        if(!key_exists('isMeta', $line)) {
            $reason = sprintf($sanity_check_failed_string, 'isMeta');
            return false;
        }
        if(!key_exists('encoding', $line)) {
            $reason = sprintf($sanity_check_failed_string, 'encoding');
            return false;
        }
        if(!key_exists('exportVersion', $line)) {
            $reason = sprintf($sanity_check_failed_string, 'exportVersion');
            return false;
        }
        if(!key_exists('collectionDetails', $line)) {
            $reason = sprintf($sanity_check_failed_string, 'collectionDetails');
            return false;
        }
        return true;
    }


    public function export1($file = null, $talk = false, $ignored = true, $extraIgnored = [], $onlyExport = null) {
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
            $error_count = 0;
            $error_log = "";
            $whole_collection = [];
            $name = $collection->getName();
            if($ignored === true && in_array($name, $extraIgnored)) continue;
            $count = $this->db->{$name}->count([]);
            $result = $this->db->{$name}->find([],['limit' => $count + 1]);
            if($talk) printf(fmt($name, "i") . " contains $count document(s)");

            $entries = [];
            foreach($result as $i => $row) {
                // Let's reset the state of the current document output
                $output_type = "normal";
                
                try {
                    if ($row instanceof Persistable) {
                        $row_to_array = $row->bsonSerialize();
                    } else if($row instanceof JsonSerializable) {
                        $row_to_array = $row->jsonSerialize();
                    } else if($row instanceof GenericMap) {
                        $row_to_array = $this->preserveMap($row);
                    } else if($row instanceof Iterator) {
                        $row_to_array = iterator_to_array($row);
                    } else {
                        $row_to_array = $row;
                    }
                } catch (Exception $e) {
                    $error_log .= "EX: $row->_id: " . $e->getMessage() . "\n";
                    $output_type = "e";
                    $error_count += 1;
                } catch (Error $e) {
                    $error_log .= "ER: $row->_id: " . $e->getMessage() . "\n";
                    $output_type = "e";
                    $error_count += 1;
                }
                array_push($entries, $row_to_array);
                if($talk) printf(fmt(".", $output_type));
            }

            array_push($db_backup, [
                'collection' => $name,
                'data' => $entries
            ]);
            $export_summary = fmt(" done", "i");
            if($error_count >= 1) $export_summary .= " (with ".fmt("$error_count", "e") . " error" .plural($error_count)."!)\n" . fmt($error_log, "i");
            if($talk) print($export_summary."\n".$error_log);
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
        $__dataset['_id'] = $map->_id;
        foreach($map->readSchema() as $key => $value) {
            // And then lets overwrite them with the <Type>Result value
            $val = $map->{$key};
            if($value['type'] instanceof MapResult) {
                if($val->value === null) continue;
                $__dataset[$key] = $this->preserveMap($val->getValue());
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

    /**
     * 
     * @param Model $model 
     * @param bool $dropBeforeInit 
     * @return InsertOneResult 
     * @throws mixed 
     */
    function initialize(Model $model, bool $dropBeforeInit = false) {
        if($model instanceof Migration == false) throw new Exception("Model must implement migration");
        if($dropBeforeInit) {
            $model->drop();
        }
        return $model->__initializeDataset();
    }

    const CONVERT_TYPE_DONE = 0;
    const CONVERT_TYPE_SKIP = 1;
    const CONVERT_TYPE_UPDATE = 2;
    function convert(Model $model, $honor_skips = true, $version = "latest") {
        // Loop through each document in the collection
        $count = $model->count([]);
        $iterator = $model->find([], [
            'limit' => $count + 1000,
            'typeMap' => [
                'root' => 'array',
                'document' => 'array',
                'array' => 'array',
            ]
        ]);
        
        $implements_migration_interface = $model instanceof Migration;
        $overrides = [
            '__pclass' => new \MongoDB\BSON\Binary($model::class, \MongoDB\BSON\Binary::TYPE_USER_DEFINED),
            '__version' => $model->__getVersion(),
            '__upgraded' => new UTCDateTime(),
        ];
        foreach($iterator as $doc) {
            $skipped = false;
            // Filter out any conforming documents
            if($honor_skips && $doc['__pclass'] && $doc['__pclass']->getData() === $overrides['__pclass']->getData()) {
                yield ['type' => self::CONVERT_TYPE_SKIP, 'value' => 1, 'doc' => $doc, 'result' => null];
                $skipped = true;
            }
            // Filter out any conforming versions
            if($honor_skips && $skipped === false && $doc['__version'] && $doc['__version'] === $overrides['__version']) {
                yield ['type' => self::CONVERT_TYPE_SKIP, 'value' => 1, 'doc' => $doc, 'result' => null];
            }

            if($skipped === false) {
                $mutated_doc = $doc;
                $update = [];
                // $this->upgrade($model, $mutated_doc, $version, $doc, $count);
                // For each document, check if the model has __beforeMigrationUpgrade method 
                if($implements_migration_interface) {
                    // If it does, run it
                    $model->__beforeMigrationUpgrade($doc, $mutated_doc, $update, $count, $this);
                }

                if(count($update)) {
                    // Get a list of keys in $update that are not known to be valid MongoDB update params
                    $difference = array_diff(array_keys($update), DATABASE_UPDATE_OPERATORS);
                    
                    // If the update has been modified, let's do a quick sanity check
                    if(count($difference)) {
                        throw new HTTPError("\$update param contains unknown operators");
                    }
                }

                // Upgrade each document's __pclass field
                $mutated_doc = array_merge($mutated_doc, $overrides);
                
                // Set up the $set update directive to include the mutated document.
                $update['$set'] = array_merge($mutated_doc, $update['$set'] ?? []);

                // Save the changes to the database
                $result = $model->updateOne(['_id' => $mutated_doc['_id']], $update);
                
                // Yield our result
                yield ['type' => self::CONVERT_TYPE_UPDATE, 'value' => $result->getModifiedCount(), 'doc' => $mutated_doc, 'result' => $result,];
    
                // For each document, check if the model has __afterUpgrade method and run it
                if($implements_migration_interface) {
                    // If it does, run it
                    $model->__afterMigrationUpgrade($result, $mutated_doc, $doc, $this);
                }
            }
        }

        return ['type' => self::CONVERT_TYPE_DONE, 'value' => 0, 'doc' => [], 'result' => null];
    }

    // private function upgrade($model, array &$mutated_doc, int|float $version, array $doc, int $count):void {
    //     // If version number is null, then we'll assume it's the latest version
    //     if($mutated_doc['__version'] == null) return;
    //     // If the version number is one less than $version, return it as it's ready to be upgraded
    //     if($mutated_doc['__version'] == $version - 1) return;
    //     $v = $mutated_doc['__version'];
    //     while(true) {
    //         if($v == $version - 1) return;
    //         $m = $this->methodName($v);
    //         $method_begin = "__beforeMigrationUpgrade$m";
    //         $method_end = "__afterMigrationUpgrade$m";
    //         if(method_exists($model, $method_begin)) {
    //             $model->{$method_begin}($doc, $mutated_doc, $count, $this);
    //         }
    //         // if(method_exists($model, $method_end)) {
    //         //     $model->{$method_end}()
    //         // }
    //     }
    //     // return $document;
    // }

    // private function methodName(int|float|string $version) {
    //     $version = (string)$version;
    //     return str_replace(".", "_", $version);
    // }

}
