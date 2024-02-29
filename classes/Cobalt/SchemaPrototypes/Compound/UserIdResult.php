<?php

namespace Cobalt\SchemaPrototypes\Compound;

use Auth\UserCRUD;
use Cobalt\SchemaPrototypes\PersistableResult;
use Cobalt\SchemaPrototypes\Traits\MongoId;
use Exception;
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

    static function get_valid_users($groupOrPermission = null, $type = null, $storage = null, $valueCallback = null) {
        if(!$type) $type = "all";
        if(!$storage) $storage = "_id";
        $value = function ($doc) {
            $name = "$doc->fname $doc->lname";
            if($name === " ") $name = $doc->uname;
            return $name;
        };
        
        if(is_callable($valueCallback)) $value = $valueCallback;
        $options = [
            'permission' => [
                'method' => 'getUsersByPermission',
                'query' => [$groupOrPermission]
            ],
            'group'      => [
                'method' => 'getUsersByGroup',
                'query' => [$groupOrPermission]
            ],
            'all'        => [
                'method' => 'find',
                'query' => []
            ],
        ];
        if(!key_exists($type, $options)) throw new Exception("$type is an invalid way to look up users");

        $crud = new UserCRUD();
        
        $valid = [];
        foreach($crud->{$options[$type]['method']}(...$options[$type]['query']) as $doc) {
            $valid[(string)$doc->_id] = $value($doc);
        }

        return $valid;
    }

}