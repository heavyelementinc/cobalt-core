<?php

namespace Cobalt\SchemaPrototypes\Compound;

use Auth\UserCRUD;
use Cobalt\Maps\Exceptions\LookupFailure;
use Cobalt\Maps\Exceptions\SchemaExcludesUnregisteredKeys;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\Fieldable;
use Cobalt\SchemaPrototypes\Traits\MongoId;
use Exception;
use MongoDB\BSON\ObjectId;
use Validation\Exceptions\ValidationIssue;
use Cobalt\SchemaPrototypes\Traits\Prototype;
use Validation\Exceptions\ValidationFailed;

/**
 * Valid directives
 * `permission` - <string> The name of a permission that a user must have
 * `group` - <string> The name of a group that a user must belong to
 */
class UserIdResult extends SchemaResult {
    use MongoId, Fieldable;
    protected UserCRUD $userCrud;
    protected $userData = false;
    protected $initialized = false;

    function __construct() {
        $this->userCrud = new UserCRUD();
    }

    #[Prototype]
    protected function _id() {
        return $this->originalValue;
    }

    #[Prototype]
    public function field(string $class = "", array $misc = [], string $tag = "input"):string {
        // return $this->input($class, $misc, "input-user");
        return "<input-user class=\"$class\" name=\"$this->name\" value=\"".$this->getRaw()."\">".$this->options()."</input-user>";
    }

    public function getValue():mixed {
        $this->initialize();
        return $this->userData;
    }

    public function __toString(): string {
        return $this->getValue()->uname;
    }

    public function getValid():array {
        if(key_exists('permission', $this->schema)) $permission = $this->getDirective("permission");
        if(!$permission && key_exists('group', $this->schema)) $permission = $this->getDirective("group");
        if(!$permission) throw new LookupFailure("UserIdResult must have a 'permission' or 'group' set on field $this->name");
        return $this->get_valid_users($permission, 'permission');
    }

    function filter($value) {
        // Check if this userId is nullable
        if($this->getDirective('nullable') && !$value) return null;
        // if(!$value) return "";
        // Check if this is a validly-formatted MongoID
        if(!$this->isValidIdFormat($value)) throw new ValidationIssue("Does not appear to be a valid MongoId");
        
        // Upgrade the string to an actual MongoID
        $_id = new ObjectId($value);
        // Get the permission and group requirements
        $permission = $this->getDirective('permission');
        $group = $this->getDirective('group');
        // If there are no permission/group requirements, then let's return our ID
        if(!$permission && !$group) return $_id;
        
        // If there are requirements, we need to look up the user to see if they're elligble
        $result = $this->userCrud->getUserById($_id);
        if(!$result) throw new ValidationIssue("That's not a valid user");
        // Check if permissions are met
        if($permission && !has_permission($permission, $group, $result, false)) throw new ValidationIssue("User is inelligble");
        
        return $_id;
    }

    private function initialize():void {
        if($this->initialized === true) return;
        if(!$this->userCrud) $this->userCrud = new UserCRUD();
        if($this->value instanceof ObjectId === false && $this->getDirective("nullable")) {
            $this->userData = $this->getDirective("default");
            $this->initialized = true;
            return;
        }
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

    public function defaultSchemaValues(array $data = []): array{
        return [
            'nullable' => true
        ];
    }

    #[Prototype]
    public function get_name($name) {
        $user = $this->getValue();
        switch($name) {
            case "full":
                return "$user->fname $user->lname";
            case "first":
            case "fname":
                return $user->fname;
            case "last":
            case "lname":
                return $user->lname;
            default:
                return $user->uname;
        }
    }

    #[Prototype]
    public function get_avatar($size = "thumb") {
        $user = $this->getValue();
        return $user->avatar;
    }
}