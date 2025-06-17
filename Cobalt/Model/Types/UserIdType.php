<?php

namespace Cobalt\Model\Types;

use Auth\CRUDUser;
use Auth\UserCRUD;
use Cobalt\Model\Model;
use Cobalt\Model\Types\Abstracts\ForeignId;
use MongoDB\BSON\ObjectId;

class UserIdType extends MixedType {
    private $isCached = false;
    private $cached;

    // public function getModel(): Model {
        
    // }

    // public function interpretRawValue(&$id): ?ObjectId {

    // }

    // public function storeValue(ObjectId $id): ?ObjectId { }

    // public function fieldItemTemplate(): string { }
    public function getValue() {
        if(!$this->isSet) return $this->getUserById($this->directiveOrNull(DIRECTIVE_KEY_DEFAULT));
        if(!$this->value) return $this->getUserById($this->directiveOrNull(DIRECTIVE_KEY_DEFAULT));
        return $this->getUserById($this->value);
    }

    private function getUserById(?ObjectId $id) {
        if(!$id) return null;
        $crud = new UserCRUD();
        if($this->isCached) return $this->cached;
        
        $this->cached = $crud->getUserById($id);
        $this->isCached = true;
        return $this->cached;
    }
}