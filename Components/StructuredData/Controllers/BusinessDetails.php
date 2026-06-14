<?php

namespace Components\StructuredData\Controllers;

use Cobalt\Controllers\Controller;
use Cobalt\Controllers\ModelController;
use Cobalt\Controllers\Traits\CreateableModel;
use Cobalt\Controllers\Traits\UpdateableModel;
use Cobalt\Model\GenericModel;
use Cobalt\Model\Model;
use Components\StructuredData\Model\BusinessInfo;
use MongoDB\BSON\ObjectId;
use Override;
use MongoDB\Model\BSONDocument;

class BusinessDetails extends Controller {
    use UpdateableModel {
        __update as modelUpdate;
    }
    use CreateableModel;
    
    const ID = "businessInfo";

    #[Override]
    public function __read(ObjectId|string $id): GenericModel|BSONDocument|null {
        $this->initialized = true;
        $this->model = new BusinessInfo();
        return $this->model->findOne(['_ident' => self::ID]);
    }
    public function read(string $id) {
        return $this->model->findOne(['_ident' => self::ID]);
    }

    #[Override]
    public function edit($document): string
    {
        throw new \Exception('Not implemented');
    }
    // #[Override]
    // public static function defineModel(): Model {
    //     return new BusinessInfo();
    // }

    public function __edit() {
        $model = new BusinessInfo();
        $result = $model->findOne(['_ident' => self::ID]);
        $modelExists = true;
        if(!$result) {
            $modelExists = false;
            $result = $model;
        }
        return view("Components/StructuredData/templates/admin/business-details.php", [
            'doc' => $result,
            'autosave' => ($modelExists) ? "autosave=\"autosave\"" : "",
            'button' => ($modelExists) ? sprintf(FLOATING_SAVE_BUTTON, 'content-save', 'Save') : sprintf(FLOATING_SAVE_BUTTON, 'content-save', 'Create') ,
        ]);
    }

    public function __update() {
        $this->model = new BusinessInfo();
        $exists = $this->model->count(['_ident' => self::ID]);
        if($exists == 0) {
            $_POST['_ident'] = self::ID;
            return $this->__create();
        }
        $id = $this->model->findOne(['_ident' => self::ID], ['projection' => ['_id' => 1]]);
        return $this->modelUpdate( (string)$id->_id);
    }

    function created(mixed $insertedId) {
        
    }

}