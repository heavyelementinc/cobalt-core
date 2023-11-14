<?php

use \Cobalt\CLI\Migration;
use \Contact\Persistance;

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

    public function runOne($document) {
        unset($document['_id']);
        unset($document['createdAt']);
        $doc = (new Persistance())->ingest($document);
        $result = $this->insertOne($doc);
        $this->updateCounts($result);
        return $this->deleteOne(['_id' => $document['_id']],);
    }
}