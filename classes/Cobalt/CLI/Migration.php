<?php

namespace Cobalt\CLI;

/**
 * Usage: extend this function, in the config method you must set either __run_all or $__run_one to true
 * @package Cobalt\CLI
 */

abstract class Migration extends \Drivers\Database{    
    protected $__run_all = null;
    protected $__run_one = null;
    protected $__total_document_count = 0;
    protected $__total_modified = 0;
    protected $__total_inserted = 0;
    protected $__total_deleted = 0;
    protected $__total_upserted = 0;
    protected $__benchmark_start = null;
    protected $__benchmark_end = null;

    abstract function config():void;

    abstract function beforeOneExecute():?\MongoDB\Driver\Cursor;
    
    /**
     * Returns the result of modifying all documents
     * @param mixed $document 
     * @return null|\MongoDB\BulkWriteResult
     */
    abstract function runAll();

    /**
     * Returns the result of one modification
     * @param mixed $document 
     * @return null|\MongoDB\BulkWriteResult
     */
    abstract function runOne($document);

    public function execute() {
        $this->__benchmark_start = microtime(true);
        if(!$this->__run_all && !$this->__run_one) throw new \Exception("Migration must set either '__run_all' or '__run_one' to `true` in its constructor!");
        
        if($this->__run_all) {
            $result = $this->runAll();
            $this->updateCounts($result);
        }

        if($this->__run_one) $this->runPerDocument();
        $this->__benchmark_end = microtime(true);
    }

    private function runPerDocument() {
        $cursor = $this->beforeOneExecute();
        print(fmt('Executing per-document migration (this may take a while)','i'));
        foreach($cursor as $iteration) {
            $result = $this->runOne($iteration);
            $this->updateCounts($result);
            print(".");
        }
        print("\n");
    }

    function postExecute() {

    }

    function updateCounts($result):void {
        if(!$result) return;
        if(method_exists($result, 'getMatchedCount')) $this->__total_document_count += $result->getMatchedCount();
        if(method_exists($result, 'getModifiedCount')) $this->__total_modified += $result->getModifiedCount();
        if(method_exists($result, 'getInsertedCount')) $this->__total_inserted += $result->getInsertedCount();
        if(method_exists($result, 'getDeletedCount')) $this->__total_deleted += $result->getDeletedCount();
        if(method_exists($result, 'getUpsertedCount')) $this->__total_upserted += $result->getUpsertedCount();
    }

    function printResults() {
        $modified = $this->__total_modified + $this->__total_inserted + $this->__total_deleted + $this->__total_upserted;
        print("Modified $modified documents in " . $this->__benchmark_end - $this->__benchmark_start . " seconds\n");
        print(fmt("Modified count may be greater than document count", 'i')."\n");
        print("Total Modified: " . fmt($this->__total_modified, 'i') . "\n");
        print("Total Inserted: " . fmt($this->__total_inserted, 'i') . "\n");
        print("Total Deleted:  " . fmt($this->__total_deleted, 'i') . "\n");
        print("Total Upserted: " . fmt($this->__total_upserted, 'i') . "\n");
    }
}