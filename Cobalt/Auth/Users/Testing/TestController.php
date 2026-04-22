<?php

namespace Cobalt\Auth\UserAccounts\Testing;

use Cobalt\Auth\Users\UserCRUD;
use Cobalt\Auth\UserAccounts\UserPersistance;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;

class TestController {
    function loadUser($id = null) {
        $query = [];
        if($id !== null) $query = ['_id' => new ObjectId($id)];
        $crud = new UserCRUD();
        $userDoc = $crud->findOne($query, ['projection' => ['__pclass' => 0]]);
        if(!$userDoc) throw new NotFound("Not found");

        $user = new UserPersistance();
        $user->modelUnserialize($userDoc);

        return view("");
    }
}