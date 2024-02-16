<?php

namespace Cobalt\SchemaPrototypes\Compound;

use Auth\UserCRUD;
use Cobalt\SchemaPrototypes\PersistableResult;
use Cobalt\SchemaPrototypes\Traits\MongoId;
use MongoDB\BSON\ObjectId;
use Validation\Exceptions\ValidationIssue;

class UserIdResult extends PersistableResult {
    use MongoId;
    protected $userCrud = null;
    protected $userData = false;
    protected $initialized = false;
    protected $permission = null;
    protected $group = null;

    function __construct($permission = null, $group = null) {
        $this->userCrud = new UserCRUD();
        $this->permission = $permission;
        $this->group = $group;
    }

    public function getValue():mixed {
        $this->initialize();
        return $this->userData;
    }

    public function __toString(): string {
        return $this->getValue()->uname;
    }

    public function getValid():array {
        return [];
    }

    function filter($value) {
        if($this->schema['nullable'] && !$value) return null;
        if(!$this->isValidIdFormat($value)) throw new ValidationIssue("Does not appear to be a valid MongoId");
        return new ObjectId($value);
    }

    private function initialize():void {
        if($this->initialized === true) return;
        if(!$this->userCrud) $this->userCrud = new UserCRUD();
        try {
            $this->userData = $this->userCrud->getUserById($this->value);
        } catch (\Exception $e) {

        }
        $this->initialized = true;
    }

}