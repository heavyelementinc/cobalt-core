<?php
namespace Cobalt\Model\Types;

use Cobalt\Auth\Users\Models\User;
use Cobalt\Model\Model;
use Cobalt\Model\Types\Abstracts\ForeignId;
use Cobalt\Model\Types\Abstracts\OrderedListOfForeignIds;
use MongoDB\BSON\ObjectId;

class ArrayOfUsersType extends OrderedListOfForeignIds {
    public function getModel(): Model {
        return new User();
    }

    public function interpretRawValue(&$id): ?ObjectId {
        return new ObjectId($id);
    }

    public function storeValue(ObjectId $id): ?ObjectId {
        return $id;
    }

    public function serialize() {
        return $this->raw;
    }

    public function fieldItemTemplate(): string {
        return "Cobalt/Auth/Users/templates/users/object-picker.php";
    }

}