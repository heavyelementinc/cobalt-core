<?php

use \Cobalt\CLI\Migration;
use \Contact\Persistance;
use MongoDB\InsertManyResult;
use MongoDB\InsertOneResult;
use MongoDB\UpdateResult;

class contactform extends Migration {

    function config():void {
        $this->__run_one = true;
    }

    function runAll() {
        return null;
    }

    public function get_collection_name() {
        return "CobaltContactForm";
    }

    public function beforeOneExecute(): ?\MongoDB\Driver\Cursor {
        return $this->find([], ['limit' => $this->count([])]);
    }

    public function runOne($document):null|InsertOneResult|InsertManyResult|UpdateResult {
        $id = $document['_id'];
        unset($document['_id']);
        $doc = (new Persistance())->ingest($document);
        $result = $this->updateOne(['_id' => $id], ['$set' => $doc]);
        // $result = $this->deleteOne(['_id' => $id]);
        return $result;
    }
}