<?php

namespace Cobalt\Model\Types;

use Auth\UserCRUD;
use MongoDB\BSON\ObjectId;

class UserIdType extends MixedType {
    private $isCached = false;
    private $cached;
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