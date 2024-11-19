<?php

namespace Cobalt\Model\Controllers;

use Cobalt\Controllers\Controller;
use Cobalt\Model\Model;
use Cobalt\Model\Testing\TestModel;
use MongoDB\Model\BSONDocument;

class Debug extends Controller {
    function __construct() {
        parent::__construct();
    }

    public function defineModel(): Model {
        return new TestModel();
    }

    public function test() {
        $id = 1;
        $this->model->updateOne(['_id' => $id], [
            '$set' => [
                'some_string' => "Here's a secret message from uncharted space!",
                // 'other_string' => "Test",
                'array_type' => ["Here's a secret message", 2],
                'model' => [
                    'details' => 1,
                    'string' => "Test String",
                ]
            ]
        ], ['upsert' => true]);
        $doc = $this->model->findOne(['_id' => $id]);

        return view("/Cobalt/Model/Templates/test.html", ['doc' => $doc]);
    }

}