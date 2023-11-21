<?php

namespace Cobalt\SchemaPrototypes;

use Auth\UserCRUD;
use Cobalt\SchemaPrototypes\Traits\MongoId;
use MongoDB\BSON\ObjectId;
use Validation\Exceptions\ValidationIssue;

class UserIdArrayResult extends ArrayResult {
    use MongoId;
    private UserCRUD $userCRUD;
    private array $userData;
    private $toStringField = "uname";

    public function setValue($value):void {
        $this->init();
        if($value === null) {
            $this->userData = [];
            return;
        };
        $this->userData = iterator_to_array($this->userCRUD->find(['_id' => ['$in' => $value]]));
    }

    public function getValue():mixed {
        return $this->userData;
    }

    function __toString(): string {
        $result = array_map(fn ($v) => $v->{$this->toStringField}, $this->userData);
        return implode(", ", $result) ?? "No users";
    }

    function init() {
        $this->userCRUD = new userCRUD();
    }

    function filter($value) {
        $this->init();
        $upgraded = [];
        foreach($value as $val) {
            if($val instanceof ObjectId) {
                $upgraded[] = $val;
                continue;
            }
            if(!$this->isValidIdFormat($val)) throw new ValidationIssue("Not a valid ID");
            $upgraded[] = new ObjectId($val);
        }
        return $upgraded;
    }
}