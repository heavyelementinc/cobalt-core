<?php

/** Since permissions are a very important part of the user account process, we thought
 * it prudent to separate the permission modification/validation process into its own class.
 */

namespace Auth;

use Drivers\Database;
use Exception;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\Unauthorized;
use MongoDB\BSON\ObjectId;
use Render\CLITable;

class Permissions extends Database {
    /** @todo Remove /private directory */
    private $permission_files = [
        __ENV_ROOT__ . "/config/default_permissions.php",
        __APP_ROOT__ . "/config/permissions.php",
        __APP_ROOT__ . "/config/app_permissions.php",
        __APP_ROOT__ . "/private/config/permissions.php",
    ];
    public $valid = [];
    public $groups = [];
    public $group_rings = [];
    const ENUM_VALUE_DIFF = [
        "IS_DIFFERENT" => 0,
        "IS_SAME" => 1,
        "WRONG_VALUE" => 2
    ];

    function __construct() {
        parent::__construct();
        $this->load_permissions();
        // $this->collection = \db_cursor('users');
    }

    function get_collection_name() {
        return "users";
    }

    /** Load the permissions and create a list of valid groups*/
    function load_permissions() {
        /** Load both the built-in permissions as well as the app-specific ones */
        $this->valid = $this->load_permission_files($this->permission_files);
        /** Create a list of groups */
        $this->groups = [];
        if (app('Auth_enable_root_group')) $this->groups[0] = "root";
        foreach ($this->valid as $valid) {
            /** Merge the list of groups */
            array_push($this->groups, $valid['group']);
            $this->group_rings[$valid['group']] = $valid['ring'] ?? 1000;
        }
        /** Make the groups list unique */
        $this->groups = array_unique($this->groups);
        $this->valid = array_merge($this->valid ?? [], $GLOBALS['PERMISSIONS'] ?? []);
    }

    function load_permission_files(array $paths):array {
        $__permissions = [];
        foreach($paths as $index => $path) {
            if(!file_exists($path)) continue;
            include $path;
            $__permissions[] = $permissions;
        }
        return array_merge(...$__permissions);
    }

    /** Render out a list of permissions for the specified user. This is how we handle
     * assigning permissions in the user manager interface.
     */
    function get_permission_table($user = null) {
        /** Check if the session user has the permission to manage user permissions */
        if (!has_permission("Auth_allow_modifying_user_permissions")) return "<p>You can't modify user permissions</p>";
        $table = [];
        $groups = "";
        $valid = $this->valid;

        $root_group = "<fieldset><legend>Basic Permissions</legend><ul class='list-panel'>";
        $self_group_checked = json_encode(has_permission("self", null, $user));
        $root_group .= "<li>
            <input-switch name=\"permissions.self\"></input-switch> Able to modify basic info for their own account<small>This includes their name, email, TOTP enrollment status, notification subscriptions and more.</small>";
        /** If the app has enabled the `root` user group, add it to the root user */
        if (app("Auth_enable_root_group") && session()->is_root->getValue()) {
            $checked = "false";
            if ($user->is_root->getValue()) $checked = "true";
            $root_group .=  "
            <li>
                <input-switch name='is_root' checked='$checked'></input-switch> <span>Root Privilege <help-span value=\"Override *all permissions checks* for this user.\"></help-span></span>
                <small>WARNING: Enabling this permission is extremely dangerous. Root privileges give a user TOTAL CONTROL over this application.</small>
            </li>";
        }
        $root_group .= "</ul></fieldset>";

        /** Loop through the list of valid permissions */
        foreach ($valid as $name => $item) {
            if(isset($item['display']) && $item['display'] === false) continue;
            $dangerous = "";

            /** Check the user's permission status for this permission */
            // $checked = (isset($user['permissions'][$name]) && $user['permissions'][$name]) ? "true" : "false";
            $checked = json_encode(has_permission($name, $item['group'], $user));
            /** Get the current group */
            $group = $item['group'];
            $groupCheck = "false";
            /** Does the user belong to the current group? */
            if (in_array($group, (array)$user->groups)) $groupCheck = "true";
            /** Establish our group heading/container if it doesn't already exist */
            if (!key_exists($group, $table)) {
                $table[$group] = "<details><summary>$group</summary>\n<ul class='list-panel'>";
                $groups .= "<li><input-switch name='groups.$group' checked='$groupCheck'></input-switch> $group</li>";
            }
            $prettyName = $item['name'] ?? snake_case_fixer($name);
            /** Concat our current permission into the group */
            $table[$group] .= "<li><input-switch checked='$checked' name='permissions.$name' $dangerous></input-switch>$prettyName<br><small>$item[label]</small></li>\n";
        }
        /** Collapse our sorted groups to a string, closing our unordered lists and completing our HTML */
        return ['permissions' => "$root_group" . implode("</ul></details>\n", $table) . "</ul>\n", 'groups' => "<ul class='list-panel'>$groups</ul>"];
    }

    function validate($id, $request) {
        $include = $id;

        $ring = (app("Auth_enable_root_group") && in_array('root', (array)session('groups'))) ? 0 : 999;
        foreach ($this->valid as $data) {
            $r = ($data['ring'] ?? 999);
            if ($r < $ring) $ring = $r;
        }

        // Establish our perms
        $perms = [];
        $level = 1000;
        foreach ($request as $name => $value) {
            // Our name
            $n = "";
            $type = "";
            if (strpos($name, "permissions.") !== false) {
                $n = str_replace("permissions.", "", $name);
                $type = "permissions";
                $level = $this->valid[$n]['ring'] ?? 1000;
            } else if (strpos($name, "groups") !== false) {
                $n = str_replace("groups.", "", $name);
                $type = "groups";
                $level = $this->group_rings[$n] ?? 1000;
                if ($n === "root") $level = 0;
            }
            if (!key_exists($type, $perms)) $perms[$type] = [];
            $perms[$type][$n] = [$name, $value];
        }

        if ($ring > $level) throw new Unauthorized("You can't grant privilege levels higher than your own.", "You can't do that.");

        $result = [[], []];
        if (isset($perms['permissions'])) $result[0] = $this->update_permissions($perms['permissions'], $include);
        if (isset($perms['groups'])) $result[1] = $this->update_groups($perms['groups'], $include);
        return array_merge($result[0], $result[1]);
        // return $result;
    }

    function update_permissions($permissions, $user_id) {
        $valid = [];

        foreach ($permissions as $name => $permission) {
            // if(!is_array($permission)) throw new BadRequest("Permissions are formatted incorrectly");
            if (preg_match("/^permissions\./",$name)) $name = preg_replace("/^permissions\./","",$name);
            if (!key_exists($name, $this->valid)) throw new BadRequest("Authenticated account is missing the '$name' permission","Your request contained unexpected data.");
            if (!is_bool($permission[1])) throw new BadRequest("'$name' must be a boolean value", "Your request contained unexpected data.");

            $valid += [$permission[0] => $permission[1]];
        }
        try {
            $this->updateOne(
                ['_id' => $this->__id($user_id)],
                ['$set' => $valid]
            );
        } catch (\Exception $e) {
            $this->updateOne(
                ['_id' => $this->__id($user_id)],
                [
                    '$set' => [
                        'permissions' => [str_replace("permissions.", "", $permission[0]) => $permission[1]]
                    ]
                ]
            );
        }

        return $valid;
    }

    // function update_permissions($validated_permissions, $user_id) {
    //     $modifiable = [
    //         '$set' => [],
    //         '$unset' => [],
    //     ];

    //     foreach($validated_permissions as $key => $permissions) {
    //         $updateKey = '$set';
    //         if($this->is_default_value($key, $permissions[1]) == self::ENUM_VALUE_DIFF['IS_SAME']) {
    //             $updateKey = '$unset';
    //         }
            
    //         $modifiable[$updateKey][$permissions[0]] = $permissions[1];
    //     }

    //     try {
    //         $result = $this->updateOne([
    //             '_id' => new ObjectId($user_id),
    //         ],[
    //             '$set' => $validated_permissions
    //         ]);
    //     } catch(Exception $e) {

    //     }

    //     return $result->getModifiedCount();
    // }

    function update_groups($groups, $user_id) {
        $user = $this->findOne(['_id' => $this->__id($user_id)]);

        if (isset($groups['root'])) {
            if ($groups['root'][1] === true) {
                \confirm(
                    "<h1>Hold on...</h1>
                    <p>You're about to grant $user[fname] $user[lname] total control over " . app("app_short_name") . "!</p>
                    <p>This means they'd be allowed to remove your root privileges.</p> 
                    <p>Are you <strong>sure</strong> you want to continue?</p>",
                    $_POST,
                    "I'm sure",
                    true
                );
            } else if ((string)$user['_id'] === (string)session('_id')) {
                $remaining = $this->count(['groups' => 'root']) - 1;
                if ($remaining === 0) throw new Unauthorized("There must always be at least one root user in a Cobalt application","You're the only root user and therefore you cannot remove yourself from this group.");
                \confirm(
                    "<h1>Hold on...</h1>
                    <p>You're about to revoke your own root privileges!</p>
                    <p>If you do this, there will only be <strong>$remaining</strong> root users left and you'll have to have one of them restore you to this group!</p>
                    <p>Are you <strong>sure</strong> you want to continue?</p>",
                    $_POST,
                    "I'm sure",
                    true
                );
            }
        }
        $query = [
            '$addToSet' => ['groups' => ['$each' => []]],
            '$pull' => ['groups' => ['$in' => []]]
        ];
        $return = [];
        foreach ($groups as $group => $data) {
            $key = '$addToSet';
            if ($data[1] === false) $key = '$pull';
            if (!in_array($group, $this->groups)) throw new BadRequest("'$group' is an invalid group","Your request contained unexpected data.");
            if ($key === '$addToSet') array_push($query[$key]['groups']['$each'], $group);
            if ($key === '$pull') array_push($query[$key]['groups']['$in'], $group);
            $return[$data[0]] = (bool)$data[1];
            foreach ($this->valid as $perm => $meta) {
                if ($meta['group'] === $group) {
                    $return["permissions.$perm"] = (isset($user['permissions'][$perm])) ? $user['permissions'][$perm] : (bool)$data[1];
                }
            }
        }

        if (count($query['$addToSet']['groups']['$each']) === 0) unset($query['$addToSet']);
        if (count($query['$pull']['groups']['$in']) === 0) unset($query['$pull']);

        if (count($query) === 0) throw new BadRequest("No valid groups were provided.","Your request contained unexpected data.");

        $result = $this->updateOne(
            ['_id' => $this->__id($user_id)],
            $query
        );

        if ($result->getModifiedCount() !== 1) throw new BadRequest("Matched " . $result->getMatchedCount() . " and failed to update.", "Failed to add user to group.");
        return $return;
    }

    function get_cli_permission_list() {
        // $rows = [
        //     'label' => [
        //         'callback' => fn ($val) => fmt($val, 'normal', 'normal')
        //     ]
        // ];
        $index = 0;
        $table = "";
        foreach($this->valid as $permission => $data) {
            $table .= "$index - $permission\n";
            $index += 1;
        }
        return $table;
    }

    function is_default_value(string $key, bool $value):int {
        if(key_exists($key, $this->valid)) return self::ENUM_VALUE_DIFF['WRONG_VALUE'];
        if($this->valid[$key]['default'] === $value) return self::ENUM_VALUE_DIFF['IS_SAME'];
        else return self::ENUM_VALUE_DIFF['IS_DIFFERENT'];
    }

    function get_valid_permissions() {
        $perms = [];
        foreach($this->valid as $key => $details){
            $perms[$key] = $details['name'] ?? snake_case_fixer($key);
        }
        return $perms;
    }
}
