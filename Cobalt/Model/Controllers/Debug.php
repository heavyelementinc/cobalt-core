<?php

namespace Cobalt\Model\Controllers;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Cobalt\Model\Testing\TestModel;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;

class Debug extends ModelController {
    function __construct() {
        parent::__construct();
    }

    public function edit($document): string {
        return view('/Cobalt/Model/Testing/Templates/edit.php');
    }

    public function destroy(Model|BSONDocument $document): array {
        return [
            'message' => 'Delete',
            'post' => $_POST,
            'okay' => 'Okay',
            'dangerous' => true,
        ];
    }

    public static function getControllerData(): array {
        return [];
    }

    public function defineModel(): Model {
        return new TestModel();
    }

    public function test() {
        $id = new ObjectId("67621db67f2b9604aa034c34");
        $doc = [
            'some_string' => "Here's a secret message from **uncharted** space!",
            // 'other_string' => "Test",
            'array_type' => [
                ['field' => 3],
                ['field' => 2]
            ],
            // 'number' => 2,
            // 'model' => [
            //     'details' => 1,
            //     'string' => "Test String",
            // ],
            // 'submodel' => [
            //     'data' => [
            //         'a_number' => 4
            //     ]
            // ]
        ];
        if($this->model->countDocuments(['_id' => $id]) === 0) {
            $model = new $this->model();
            $model->setData(array_merge(['_id' => $id],$doc));
            $model->includeId(true);
            // $serial = $model->bsonSerialize();
            $this->model->insertOne($model);
        } else {
            $this->model->updateOne(['_id' => $id], ['$set' => $doc]);
        }
        
        $document = $this->model->findOne(['_id' => $id]);
        
        // $str = $document->some_string->md();

        return view("/Cobalt/Model/Testing/Templates/test.php", ['doc' => $document]);
    }

}