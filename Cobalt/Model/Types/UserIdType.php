<?php

namespace Cobalt\Model\Types;

use Auth\CRUDUser;
use Auth\UserCRUD;
use Cobalt\Model\Model;
use Cobalt\Model\Types\Abstracts\ForeignId;
use MongoDB\BSON\ObjectId;
use Cobalt\Model\Attributes\Prototype;


class UserIdType extends MixedType {
    private $isCached = false;
    private $cached;

    // public function getModel(): Model {
        
    // }

    // public function interpretRawValue(&$id): ?ObjectId {

    // }

    // public function storeValue(ObjectId $id): ?ObjectId { }

    // public function fieldItemTemplate(): string { }
    public function getValue():mixed {
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

    #[Prototype]
    protected function get_filter_field($param_value, $param_name, $cast_type) {
        return "$param_value->fname ".$param_value->lname->value[0] .".";
    }
}