<?php

namespace Cobalt\Model\Testing\Controller;

use Cobalt\Controllers\ModelController as ControllersModelController;
use Cobalt\Model\Model;
use Cobalt\Model\Testing\TestModel;
use MongoDB\Model\BSONDocument;

class ModelController extends ControllersModelController {
    public static function defineModel(): Model {
        return new TestModel();
    }

    public function edit($document): string {
        return "";
    }

    public function destroy(Model|BSONDocument $document): array {
        return [
            'dangerous' => true,
            'message' => '',
            'okay' => '',
            'post' => ''
        ];
    }

}