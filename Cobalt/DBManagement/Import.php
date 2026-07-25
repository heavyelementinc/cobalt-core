<?php
namespace Cobalt\DBManagement;

use Cobalt\DBManagement\Exceptions\NoMetadataFound;
use Drivers\DatabaseManagement;
use Exception;
use MongoDB\BSON\Document;
use MongoDB\Database;

class Import extends DatabaseManagement {
    private array $meta = [];
    private float $benchmark_start = 0;
    private float $wait_times = 0;
    private string $currentCollection = "";
    private int $importedForCollection = 0;

    public function import(string $filename, bool $talk = false, bool $caution = true, array $collectionsToImport = []) {
        $this->benchmark_start = microtime(true);
        // Confirm we're in CLI mode
        if($talk) $talk = function_exists('say');
        if(!file_exists($filename)) {
            $filename = app("DB_export_directory").$filename;
            if(!file_exists($filename)) {
                $failed = "Database export `$filename` does not exist";
                if($talk) print(fmt("Database export `$filename` does not exist", "e"));
                throw new Exception($failed);
            }
        }
        // Let's open our file for reading
        $handle = fopen($filename, "r");
        if(!$handle) {
            // Error handling in case the file can't be opened for reading
            $failed = "Failed to open `$filename`";
            if($talk) print(fmt($failed, 'e'));
            throw new Exception($failed);
        }

        // We've validated that our log file exists and we've ensured that
        // the handle is valid, now we need to start reading lines        
        /** @var Database $db */
        $db = db_cursor(null, null, false, true);

        // Init $results to track of our progress
        $results = [
            'insertedTotal' => 0,
        ];

        try {
            // Let's start by fetching our export metadata:
            $this->meta = $this->import_read_meta_from_file($handle, $results, $talk, $caution);
        } catch (NoMetadataFound $e) {
            return $this->import_1_0($filename, $talk, $caution, $results);
        }

        // Now that we've loaded our metadata, let's figure our which version
        // we're going to use to import our data.
        switch($this->meta['exportVersion']) {
            case self::EXPORT_VERSION_2_0:
                $this->import_2_0($handle, $db, $results, $talk, $caution, $collectionsToImport);
                break;
        }

        fclose($handle);

        $benchmark_end = microtime(true);
        $wait_time = $this->wait_times;

        if($talk) say("\n\n   Imported ".
            fmt($results['insertedTotal'],'i').
            " documents in ". round(abs(($benchmark_end - $this->benchmark_start) - $wait_time),2) . 
            " seconds"
        );

    }

    function import_read_meta_from_file($handle, array &$results, bool $talk, bool $caution):array {
        // Move pointer to EOF
        fseek($handle, 0, SEEK_END);
        
        // Keep track of if we've found our 'meta' line
        $meta_found = false;
        // Iterate backwards through the file keeping track of our offset
        $i = 0;
        // No magic numbers allowed
        $seek_success = 0;
        while(true) {
            $i -= 1;
            $seek_result = fseek($handle, $i, SEEK_END);
            if($seek_result !== $seek_success) throw new NoMetadataFound("Seek failed.");
            $char = fgetc($handle);
            if($i < -1 && $char === "\n") {
                // If we've found a new line, we move forward one and break this loop
                fseek($handle, $i + 1, SEEK_END);
                $meta_found = true;
                break;
            }
        }
        if(!$meta_found) {
            throw new NoMetadataFound("Could not locate export meta details");
        }
        $meta = fgets($handle);
        $meta_decoded = json_decode($meta, true);
        $reason = "";
        if(!self::is_line_meta_entry($meta_decoded, $reason)) {
            throw new NoMetadataFound($reason);
        }
        $meta_decoded['totalDocuments'] = 0;
        $meta_decoded['number_digits'] = 0;
        foreach($meta_decoded['collectionDetails'] as $collection => $details) {
            $meta_decoded['totalDocuments'] += $details['exported'];
            $len = strlen((string)$details['exported']);
            if($len > $meta_decoded['number_digits']) $meta_decoded['number_digits'] = $len;
        }

        if($talk) {
            say("Archive version ".fmt($meta_decoded['exportVersion'],'w'));
            say("Export date ".fmt($meta_decoded['exportedAt'], 'w'));
            say("Collections reported: " . fmt(count($meta_decoded['collectionDetails']),'w'));
            say("Documents reported: ".fmt($meta_decoded['totalDocuments'],'w'));
            say("Exported from ".fmt($meta_decoded['databaseName'],'w'));
            say("Importing into ".fmt($this->db->getDatabaseName(),'w'));
        }
        
        // Reset the pointer to the start of file
        fseek($handle, 0, SEEK_SET);

        return $meta_decoded;
    }

    private function import_2_0($handle, Database $db, array &$results, bool $talk, bool $caution, array $collectionsToImport = []) {
        $has_passed_sanity_check = $this->import_archive_sanity_check_2_0($handle, $talk, $caution);
        if(!$has_passed_sanity_check) {
            // throw new Error("Corrupted archive");
            return;
        }
        // return;
        $bootstrap_complete = $this->import_bootstrap_2_0($db, $talk, $caution, $collectionsToImport);
        if(!$bootstrap_complete) return 0;
        
        // Read each line
        while(true) {
            $next = fgets($handle);
            if(!$next) break;
            $result = $this->import_line_from_file_2_0($next, $db, $talk, $collectionsToImport);
            if($result === self::IMPORT__LINE_SUCCESS) {
                $results['insertedTotal'] += 1;
                continue;
            }
            if($result === self::IMPORT__LINE_SKIPPED) {
                $results['skippedTotal'] += 1;
                continue;
            }
            if($result === self::IMPORT__LINE_META) break;
            if($result === self::IMPORT__LINE_FAILED) break;
        }
    }

    private function import_archive_sanity_check_2_0($handle, bool $talk, bool $caution):bool {
        $archive_integrity_message = "Archive integrity check...";
        if($talk) {
            print("$archive_integrity_message 0\r");
        }
        $export_parse_results = [];

        $i = 0;
        // Loop through our file and confirm our meta details matches our export
        while(true) {
            $line = fgets($handle);
            if(!$line) break;
            $reason = "";
            $decoded = json_decode($line, true);
            if(self::is_line_meta_entry($decoded, $reason)) break;
            $export_parse_results[$decoded['col']] += 1;
            if($talk) print("\r$archive_integrity_message ".fmt($i, "i"));
            $i += 1;
        }

        $this->meta['totalEnumeratedDocuments'] = $i;

        // Reset the pointer to the start of file
        fseek($handle, 0, SEEK_SET);

        // Use array_intersect to see if there are more/fewer collections than
        // our meta details say there should be
        // $intersection = array_intersect(array_keys($export_parse_results), array_keys($this->meta['collectionDetails']));
        // $intersection_count = count($intersection);
        // $intersection_failure_message = "It appears this export is corrupted. Run this command again with --skip-integrity-check";
        // if($intersection_count !== count($export_parse_results)
        // || $intersection_count !== count($this->meta['collectionDetails'])) {
        //     throw new Exception($intersection_failure_message);
        // }

        $wait_times = [];
        // Loop through the collections and compare numbers
        foreach($this->meta['collectionDetails'] as $collection => $details) {
            if($export_parse_results[$collection] === $details['exported']) continue;
            if($talk) print("\r$archive_integrity_message ".fmt("FAILED!", "e")."\n");

            $error = "COLLECTION MISMATCH: $collection should have ".fmt($details['exported'],"i")." document".plural($details['exported'])." but we found ".fmt($export_parse_results[$collection],'w').".";
            if(!$caution) {
                if($talk) say("$error Aborting.", "e");
                return false;
            }
            print($error);
            $wait_start = microtime(true);
            $result = readline(" Continue? (y/N) > ");
            $wait_end = microtime(true);
            $wait_times[] = $wait_end - $wait_start;
            if(cli_to_bool($result)) continue;
            if($talk) print(fmt(" Aborting.", "e"));
            return false;
        }
        if($talk) print("\r$archive_integrity_message ".fmt("PASSED!", "s")."\n");
        $this->wait_times += array_sum($wait_times);
        return true;
    }

    private function import_bootstrap_2_0(Database $db, bool $talk, bool $caution, array $collectionsToImport = []):bool {
        $collections_to_drop = array_keys($this->meta['collectionDetails']);
        if(!empty($collectionsToImport)) $collections_to_drop = $collectionsToImport;
        if($caution) {
            if(!$talk) throw new Exception("`caution` must be set through a CLI session");
            if($caution) {
                $color = "33";
                print("The following collections will be dropped: \033[39m".PHP_EOL." * \033[$color"."m".implode("\033[39m".PHP_EOL." * \033[$color"."m",$collections_to_drop).PHP_EOL);
                $warning = "  >>> By continuing, any data in your live database WILL BE LOST! <<<";
                print("\033[31;5m$warning\033[0m\r");
                // sleep(2);
                print(fmt("$warning                 \n", "e"));

                $wait_start = microtime(true);
                $read = readline("Are you sure you want to continue? (y/N) > ");
                $wait_end = microtime(true);
                $this->wait_times += $wait_end - $wait_start;
                if(!cli_to_bool($read)) {
                    say("Aborted. No changes were made to the database.","i");
                    return false;
                }
            }
        } else {
            print("Dropping the following collections: \033[31m".implode("\033[39m, \033[31m",$collections_to_drop))."\033[39m\n";
        }

        // Prep database

        print("Dropping collections: ");
        $joiner = "";
        foreach($collections_to_drop as $i => $collection) {
            if($i >= 1) $joiner = ", ";
            print($joiner . fmt($collection,"e"));
            $db->dropCollection($collection);
        }
        print("\n");

        return true;
    }

    private function import_line_from_file_2_0(string $line, Database $db, bool $talk = false, array $collectionsToImport = []):int {
        $content = json_decode($line, true);
        if(!empty($collectionsToImport) && !in_array($content['col'], $collectionsToImport)) {
            return self::IMPORT__LINE_SKIPPED;
        }
        $reason = "";
        if(self::is_line_meta_entry($content, $reason)) return self::IMPORT__LINE_META;
        $collection = $db->{$content['col']};
        $doc = $this->import_parse_line_2_0($content['doc']);
        $inserted_result = $collection->insertOne($doc);
        $inserted = $inserted_result->getInsertedCount();
        if($inserted !== 1) return self::IMPORT__LINE_FAILED;

        $import_string = " > Importing %s / %s for collection %s";
        if($this->currentCollection != $content['col']) {
            // $success = $this->importedForCollection == $this->meta['collectionDetails'][$content['col']]['exported'];
            $this->importedForCollection = 0;
            $this->currentCollection = $content['col'];
            // print("... " . fmt(($success) ? "SUCCESS!":"FAIL!", ($success) ? "s" : "e"));
            print("\n");
        } else {
            print("\r");
        }

        $this->importedForCollection += 1;
        printf("$import_string",
            fmt(str_pad($this->importedForCollection, $this->meta['number_digits'], " ", STR_PAD_LEFT),"s"),
            str_pad($this->meta['collectionDetails'][$content['col']]['exported'],$this->meta['number_digits'], " ", STR_PAD_LEFT),
            fmt($content['col'],'w')
        );
        return self::IMPORT__LINE_SUCCESS;
    }

    private function import_parse_line_2_0(string $json) {
        return Document::fromJSON($json);
    }


    private function import_1_0(string $filename, bool $talk = false, bool $caution = true, array &$results) {
        if(!file_exists($filename)) return say("File `$filename` does not exist.", "e");
        $contents = json_decode(file_get_contents($filename),true);
        $count = count($contents);
        if($talk) {
            say("Archive version ".fmt('1.0','w'));
            $explode = explode("-",pathinfo($filename,PATHINFO_FILENAME));
            say("Export date ".fmt(date('c',$explode[count($explode) - 1]), 'w'));
            say("Collections reported: ".fmt($count, 'w'));
            $docs = array_reduce($contents, fn ($accumulator, $item) => count($item['data']) + $accumulator, 9);
            say("Documents reported: ".fmt($docs,'w'));
        }
        if(function_exists("say")) say("This operation will ".fmt("drop","e")." any collections contained within this backup before restoring them from this backup.");
        if($caution) {
            if(function_exists("say")) say("Any data in your database will be lost if not contained in this backup.", "e");
            $read = readline("Are you sure you want to continue? (y/N) > ");
            if(!cli_to_bool($read)) return fmt("Aborted.","e");
        }
        /** @var Database $db */
        $db = db_cursor(null, null, false, true);
        $collections_restored = 0;
        $documents_inserted = 0;
        foreach($contents as $col) {
            $collection_name = $col['collection'];
            $docs = $col['data'];
            if($talk) say("Dropping collection $collection_name for clean restoration", 'e');
            $db->dropCollection($collection_name);
            if($talk) printf("Collection ".fmt($collection_name, "w")."");
            $collection = $db->{$collection_name};
            $collections_restored++;
            foreach($docs as $i => $row) {
                $document = Document::fromJSON(json_encode($row));
                $result = $collection->insertOne($document);
                $documents_inserted += $result->getInsertedCount();
                if($talk) print("\r > Restoring ".fmt($i + 1, "s")." of " . count($docs) . " documents");
                $results['insertedTotal'] += 1;
            }
            if($talk) print("\n");
        }
        return "Restored $collections_restored collection".plural($collections_restored)." and $documents_inserted document".plural($documents_inserted).".";
    }

}