<?php

/**
 * UserCRUD - The user management database wrapper.
 * 
 * Manages creating, reading, updating, and destroying user data.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Auth;

use Auth\UserValidate;
use Auth\UserSchema;
use Cobalt\Token;
use DateTime;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\HTTPException;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\Unauthorized;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Cursor;
use PhpParser\Node\Expr\Cast\Object_;
use Validation\Exceptions\ValidationFailed;

class UserCRUD extends \Drivers\Database {
    function get_collection_name() {
        return 'users';
    }

    function get_schema_name($doc = []) {
        return "\\Auth\\UserPersistance";
    }

    final function getUserById($id) {
        return $this->findOne(['_id' => $this->__id($id)]);
    }

    final function getUserByUnameOrEmail($uname_or_email) {
        return $this->findOne(
            [
                '$or' => [
                    ['uname' => $uname_or_email],
                    ['email' => $uname_or_email]
                ]
            ]
        );
    }

    final function getUserByUsername($username) {
        return $this->findOne([
            'uname' => $username,
        ]);
    }

    /**
     * 
     * @param string|array<string> $permissions 
     * @param bool $status 
     * @param mixed $options 
     * @return Cursor 
     */
    final function getUsersByPermission(string|array $permissions, bool $status = true, ?array $options = null) {
        if(!$options) $options = [
            'limit' => 50
        ];
        if (gettype($permissions) === "string") $permissions = [$permissions];
        $perms = [
            '$or' => [
                ['is_root' => true]
            ]
        ];
        foreach($permissions as $permission) {
            $perms['$or'][count($perms['$or'])] = ["permissions.$permission" => $status];
        }
        
        return $this->find(
            $perms,
            $options
        );
    }

    final function getUserIdArrayByPermission(string|array $permissions, bool $status = true, ?array $options = null):array {
        $options = array_merge([
            'limit' => 50,
            'projection' => ['_id' => 1]
        ], $options ?? []);
        $map = [];
        foreach($this->getUsersByPermission($permissions, $status, $options) as $user) {
            $map[] = $user->_id;
        }
        return $map;
    }

    final function getUsersByGroup($groups, $options = null) {
        if(!$options) $options = [
            'limit' => 50
        ];
        if (gettype($groups) === "string") $groups = [$groups];
        return $this->find([
            'group' => $groups
        ], $options);
    }

    final function getRootUsers() {
        return iterator_to_array($this->find(['is_root' => true]));
    }

    final function findUserByToken(string $name, string $token):?UserPersistance {
        $result = $this->findOne([
            'token.name' => $name,
            'token.value' => $token,
        ]);
        if(!$result) return null;
        $tkn = $result->get_token($name);
        if($tkn === null) throw new NotFound("Bad token", true);
        $expires = $tkn->get_expires();
        // Expire token if its invalid
        if($expires && $expires > new DateTime()) {
            $modified = $result->expire_token($tkn);
            return null;
        }
        return $result;
    }

    final function updateUser($id, $request) {
        $val = new UserPersistance();
        $mutant = $val->__validate($request);
        $result = $this->updateOne(
            ['_id' => $this->__id($id)],
            ['$set' => $mutant]
        );
        if ($result->getModifiedCount() !== 1) throw new HTTPException("Failed to update fields", true);
        return new UserPersistance($mutant);
    }

    final function createUser($request, $mode = "require") {
        $val = new UserPersistance();

        // $val->setMode($mode);
        $mutant = $val->__validate($request);
        $flags = [];
        $flag = "flags.";
        $len = strlen($flag);
        foreach ($mutant as $field => $value) {
            if (substr($field, 0, $len) === $flag) {
                $flags[str_replace($flag, "", $field)] = $value;
                unset($mutant[$field]);
            }
        }
        $mutant['flags'] = $flags;

        $default = [
            'prefs' => json_decode("{}"),
            'groups' => [],
            'permissions' => json_decode("{}"),
            'tokens' => [],
            'since' => $this->__date(null),
            'flags' => [
                'verified' => false,
                'password_reset_required' => false,
            ]
        ];
        $request = array_merge(
            $default,
            $mutant->__dataset,
            // ['_id' => $mutant->_id]
        );
        $result = $this->insertOne($request);

        if ($result->getInsertedCount() !== 1) throw new \Exception("Failed to create user.");

        unset($request['pword']); // Clean up
        return $request;
    }

    final function deleteUserById($id) {
        $result = $this->deleteOne(['_id' => $this->__id($id)]);
        if ($result->getDeletedCount() !== 1) throw new \Exception("Failed to delete user.");
        return $result->getDeletedCount();
    }


    final function grant_revoke_permission($username, $permission, bool $value) {
        $query = ['$or' => [['uname' => $username],['email' => $username]]];
        $result = $this->updateOne($query,[
            '$set' => [
                "permissions.$permission" => $value
            ]
        ]);
        return $this->findOne($query)->permissions;
    }

    final function set_token($id, $type, $expires_in = "+15 minutes"): Token {
        // Create our expiration time
        $date = new DateTime();
        $date->modify($expires_in);

        $count = null;
        while(true) {
            // Generate the token
            $token = new Token(null, $date, 'login');
            $tk = $token->generate_token();
    
            // Ensure that this token is unique in the database
            $count = $this->count(['login_tokens.token' => $tk]);
            if($count !== 0) continue;
            break;
        }

        // Update the user to include
        $result = $this->updateOne(
            ['_id' => new ObjectId($id)],
            [
                '$push' => [
                    'login_tokens' => [
                        'token' => $token->get_token(),
                        'expires' => new UTCDateTime($token->get_expires()),
                        'type' => $token->get_type()
                    ]
                ]
            ]
        );
        return $token;
    }

    final function expire_login_token($token) {
        $result = $this->updateOne(
            [
                'login_tokens.token' => $token
            ],
            [
                '$pull' => ['login_tokens' => ['token' => $token]]
            ]
        );

        return $result;
    }

    final function store_integration_credentials(ObjectId $user, $type, $details, DateTime|UTCDateTime $issued) {
        if($issued instanceof DateTime) $issued = new UTCDateTime($issued);
        $result = $this->updateOne(
            ['_id' => $user],
            [
                '$set' => [
                    "integrations.$type" => [
                        'details' => $details,
                        'fresh_as_of' => $issued,
                        'provisioned' => $issued
                    ]
                ]
            ]
        );
        return $result->getModifiedCount();
    }

    final function update_integration_credentials(ObjectId $user, $type, $details, DateTime|UTCDateTime $issued):int {
        $d = [];
        foreach($details as $key => $val) {
            $d["integrations.$type.details.$key"] = $val;
        }
        if($issued instanceof DateTime) $issued = new UTCDateTime($issued);
        $d["integrations.$type.fresh_as_of"] = $issued;
        $result = $this->updateOne([
            '_id' => $user
        ],[
            '$set' => $d
        ]);
        return $result->getModifiedCount();
    }


    /* ============================
            HELPER FUNCTIONS
       ============================ */

    /**
     * Get a list of users as <option> tags (for use with select, input-array)
     * 
     * @param array|string $permissions the permission/permissions to filter by
     * @param string $display the inner text of option *"name"*, "first", "user"
     * @return array rendered options
     */
    final function getUserOptions($permissions, $display = "name") {
        $display_table = [
            'name' => function ($user) {
                return "$user[fname] " . $user['lname'][0] . ".";
            },
            'first' => function ($user) {
                return $user['fname'];
            },
            'user' => function ($user) {
                return $user['uname'];
            },
        ];
        $result = $this->getUsersByPermission($permissions);
        $options = [];
        $callable = (key_exists($display, $display_table)) ? $display_table[$display] : $display_table['name'];
        foreach ($result as $user) {
            $options[(string)$user['_id']] = $callable($user);
        }

        return $options;
    }

    final function getUserFlags($values) {
        $val = new UserValidate();
        $flags = \get_schema_group_names('flags', $val->__get_schema());
        $el = "";
        foreach ($flags as $name => $elements) {
            $checked = "";
            $n = str_replace("flags.", "", $name);
            if (isset($values->flags[$n]) && $values->flags[$n]) $checked = " checked='true'";
            $el .= "<li><input-switch name='$name'$checked></input-switch><label>$elements[label]</label></li>";
        }
        return "<ul class='list-panel'>$el</ul>";
    }

    final function destroy_expired_tokens() {
        $crud = $this;
        $date = new UTCDateTime();
        $query = [
            'login_tokens.expires' => ['$lt' => $date]
        ];
        
        $count = $crud->count($query);
        printf("Found ". fmt($count,'i') . " expired token" . plural($count)."\n");
        journal("This number is often larger than the document modified count", CL_NOTICE);
        
        // $result = $crud->find($query, ['$pull' => $query], ['limit' => $count]);
        $result = $crud->updateMany($query, [
            '$pull' => [
                'login_tokens' => ['expires' => ['$lt' => $date]],
                // 'login_tokens' => ['expires' => ['$exists' => false]],
            ]
        ]);
        $modified = $result->getModifiedCount();

        // $modified = 0;
        // foreach($result as $user) {
        //     $crud->updateOne(
        //         ['_id' => $user],
        //         ['$pull' => $query]
        //     );
        // }
        $result = $modified . " document".plural($modified)." modified";
        journal($result, CL_NOTICE);
        return $result;
    }
}
