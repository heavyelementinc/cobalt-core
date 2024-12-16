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
        $doc = [
            'some_string' => "Here's a secret message from **uncharted** space!",
            // 'other_string' => "Test",
            'array_type' => [
                ['field' => 3],
                ['field' => 2]
            ],
            'number' => 2,
            'model' => [
                'details' => 1,
                'string' => "Test String",
            ],
            'submodel' => [
                'data' => [
                    'another_model' => 4
                ]
            ]
        ];
        if($this->model->countDocuments(["_id"=>$id]) === 0) {
            $model = new $this->model();
            $model->setData(array_merge(['_id' => $id],$doc));
            $model->includeId(true);
            $this->model->insertOne($model);
        } else {
            $this->model->updateOne(['_id' => $id], ['$set' => $doc]);
        }
        
        $document = $this->model->findOne(['_id' => $id]);
        
        // $str = $document->some_string->md();

        return view("/Cobalt/Model/Testing/Templates/test.php", ['doc' => $document]);
    }

}