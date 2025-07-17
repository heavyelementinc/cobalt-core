<?php

use \Cobalt\CLI\Migration;
use Cobalt\Pages\Models\PageMap;
use MongoDB\InsertManyResult;
use MongoDB\InsertOneResult;
use MongoDB\UpdateResult;

class pages extends Migration {

    function config():void {
        $this->__run_one = true;
    }

    function runAll() {
        return null;
    }

    public function get_persistance() {
        return new PageMap();
    }

    public function get_collection_name() {
        return "CobaltPages";
    }

    public function beforeOneExecute(): ?\MongoDB\Driver\Cursor {
        return $this->find([], ['limit' => $this->count([]), 'projection' => ['__pclass' => 0]]);
    }

    public function runOne($document):null|InsertOneResult|InsertManyResult|UpdateResult {
        $id = $document['_id'];
        unset($document['_id']);
        $persistance = $this->get_persistance();
        $doc = [
            '__pclass' => new \MongoDB\BSON\Binary($persistance::class, \MongoDB\BSON\Binary::TYPE_USER_DEFINED),
            '__version' => PageMap::__get_version()
        ];
        if(is_string($document->route_group)) {
            $doc['route_group'] = [$document->route_group];
        }
        $result = $this->updateOne(['_id' => $id], ['$set' => $doc]);
        return $result;
    }
}