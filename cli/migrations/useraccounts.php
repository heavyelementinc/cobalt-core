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
        // say("\n\nMigrating user ".fmt($document->uname, "i"));
        $id = $document['_id'];
        $document['__v'] = "2.0";
        $result = $this->deleteOne(['_id' => $id]);
        $document->_id = $id;
        if(in_array('root', (array)$document->groups)) {
            // say(" - User was in 'root' group... updating");
            $document->is_root = true;
            $index = array_search('root', (array)$document->groups);
            unset($document->groups[$index]);
        }

        if(key_exists('verified', (array)$document->flags)) {
            $document->state = UserPersistance::STATE_USER_VERIFIED;
        }

        // $document->__pclass = new \MongoDB\BSON\Binary('QXV0aFxVc2VyUGVyc2lzdGFuY2U=', 128);

        $doc = new UserPersistance();
        $doc->ingest($document);
        $doc->__include_immutable_fields(true);
        $result = $this->insertOne($doc);
        return $result;
    }
}