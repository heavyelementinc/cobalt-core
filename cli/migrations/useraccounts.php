<?php

use \Cobalt\CLI\Migration;
use \Auth\UserPersistance;

class useraccounts extends Migration {

    function config():void {
        $this->__run_one = true;
    }

    function runAll() {
        return null;
    }

    public function get_collection_name() {
        return "users";
    }

    public function beforeOneExecute(): ?\MongoDB\Driver\Cursor {
        return $this->find([], ['limit' => $this->count([])]);
    }

    public function runOne($document) {
        $id = $document['_id'];
        $document['__v'] = "2.0";
        $result = $this->deleteOne(['_id' => $id]);
        $doc = (new UserPersistance())->ingest($document);
        $this->updateCounts($this->insertOne($doc));
        return $result;
    }
}