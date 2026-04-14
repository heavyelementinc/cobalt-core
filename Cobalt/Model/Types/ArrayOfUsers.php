<?php

use Cobalt\Auth\Users\Models\User;
use Cobalt\Model\Model;
use Cobalt\Model\Types\Abstracts\ForeignId;
use MongoDB\BSON\ObjectId;

class ArrayOfUsers extends ForeignId {
    public function getModel(): Model {
        return new User();
    }

    public function interpretRawValue(&$id): ?ObjectId
    {
        return new ObjectId($id);
    }

    public function storeValue(ObjectId $id): ?ObjectId
    {
        return $id;
    }

    public function fieldItemTemplate(): string
    {
        return "Cobalt/Auth/Users/templates/users/object-picker.php";
    }

}