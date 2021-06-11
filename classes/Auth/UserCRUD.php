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

    final function getUsersByPermission($permissions) {
        if (gettype($permissions) === "string") $permissions = [$permissions];
        return $this->find([
            'permission' => $permissions
        ]);
    }

    final function getUsersByGroup($groups) {
        if (gettype($groups) === "string") $groups = [$groups];
        return $this->find([
            'group' => $groups
        ]);
    }

    final function updateUser($id) {
        $val = new UserValidate();
        $mutant = $val->validate($_POST);
        $this->updateOne(
            ['_id' => $this->__id($id)],
            ['$set' => $mutant]
        );
        return $mutant;
    }

    final function createUser() {
        $val = new UserValidate();

        $val->setMode("require");
        $request = $val->validate($_POST);

        $default = [
            'prefs' => [],
            'groups' => [],
            'permissions' => [],
            'tokens' => [],
            'verified' => false,
        ];
        $request = array_merge(
            $default,
            $request,
            ['_id' => $this->__id(), 'since' => $val->since()]
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
     * @return string rendered options
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
        $options = "";
        $callable = (key_exists($display, $display_table)) ? $display_table[$display] : $display_table['name'];
        foreach ($result as $user) {
            $options .= "<option value='" . (string)$user['_id'] . "'>" . $callable($user) . "</option>";
        }

        return $options;
    }
}
