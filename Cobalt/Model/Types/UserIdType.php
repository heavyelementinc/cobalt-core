<?php

namespace Cobalt\Model\Types;

use Auth\CRUDUser;
use Cobalt\Auth\Users\Models\User;
use Cobalt\Auth\Users\UserCRUD;
use Cobalt\Model\Model;
use Cobalt\Model\Types\Abstracts\ForeignId;
use MongoDB\BSON\ObjectId;
use Cobalt\Model\Attributes\Prototype;

class UserIdType extends ForeignId {
    private $isCached = false;
    private $cached;

    public function getModel(): Model {
        return new User();
    }

    public function interpretRawValue(&$id): ?ObjectId {
        return new ObjectId($id);
    }

    public function storeValue(ObjectId $id): ?ObjectId {
        return $id;
    }

    public function fieldItemTemplate(): string {
        return "Cobalt/Auth/Users/templates/users/object-picker.php";
    }

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