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
use Validation\Exceptions\ValidationFailed;

class UserCRUD extends \Drivers\Database {
    function get_collection_name() {
        return 'users';
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

    final function getUsersByPermission($permissions, $value = true) {
        if (gettype($permissions) === "string") $permissions = [$permissions];
        $perms = array_fill_keys($permissions, $value);
        return $this->find(
            [
                '$or' => [
                    ['permissions' => $perms],
                    ['groups' => 'root']
                ]
            ]
        );
    }

    final function getUsersByGroup($groups) {
        if (gettype($groups) === "string") $groups = [$groups];
        return $this->find([
            'group' => $groups
        ]);
    }

    final function updateUser($id, $request) {
        $val = new UserValidate();
        $mutant = $val->validate($request);
        $result = $this->updateOne(
            ['_id' => $this->__id($id)],
            ['$set' => $mutant]
        );
        if ($result->getModifiedCount() !== 1) throw new \Exception("Failed to update fields");
        return $mutant;
    }

    final function createUser($request) {
        $val = new UserValidate();

        $val->setMode("require");
        $mutant = $val->validate($request);
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
            $mutant,
            ['_id' => $this->__id()]
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
            if (isset($values['flags'][$n]) && $values['flags'][$n]) $checked = " checked='true'";
            $el .= "<li><input-switch name='$name'$checked></input-switch><label>$elements[label]</label></li>";
        }
        return "<ul class='list-panel'>$el</ul>";
    }
}
