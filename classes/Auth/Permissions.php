<?php
/** Since permissions are a very important part of the user account process, we thought
 * it prudent to separate the permission modification/validation process into its own class.
 */
namespace Auth;
class Permissions{
    private $permission_files = [
        __ENV_ROOT__ . "/config/default_permissions.json",
        __APP_ROOT__ . "/config/app_permissions.json"
    ];
    public $valid = [];

    function __construct(){
        $this->load_permissions();
        $this->collection = \mongo_cursor('users');
    }

    /** Load the permissions and create a list of valid groups*/
    function load_permissions(){
        /** Load both the built-in permissions as well as the app-specific ones */
        $this->valid = \get_all_where_available($this->permission_files);
        /** Create a list of groups */
        $this->groups = [];
        foreach($this->valid as $valid){
            /** Merge the list of groups */
            $this->groups = [...$this->groups,...array_keys($valid['groups'])];
        }
        /** Make the groups list unique */
        $this->groups = array_unique($this->groups);
    }

    /** Render out a list of permissions for the specified user. This is how we handle
     * assigning permissions in the user manager interface.
     */
    function get_permission_table($user = null){
        /** Check if the session user has the permission to manage user permissions */
        if(!has_permission("Auth_allow_modifying_user_permissions")) return "<p>You can't modify user permissions</p>";
        $table = [];
        $valid = $this->valid;
        /** If the app has enabled the `root` user group, add it to the root user */
        if( app("Auth_enable_root_group") ) {
            $valid = array_merge(
                [
                    'root' => [
                        'groups' => [
                            'root' => true,
                        ],
                        'label' => 'A root user is able to bypass ANY priviliged check.',
                        'dangerous' => true,
                        'default' => false
                    ]
                ],
                $valid
            );
        }
        /** Loop through the list of valid permissions */
        foreach($valid as $name => $item){
            $dangerous = "";
            /** Check the user's permission status for this permission */
            $checked = (isset($user['permissions'][$name]) && $user['permissions'][$name]) ? "true" : "false";
            /** Get the current group */
            $group = array_keys((array)$item['groups'])[0]; 
            $groupCheck = "false";
            /** Does the user belong to the current group? */
            if(in_array($group,(array)$user['groups'])) $groupCheck = "true";
            /** Establish our group heading/container if it doesn't already exist */
            if(!key_exists($group,$table)) $table[$group] = "<h2>$group <input-switch name='groups.$group' checked='$groupCheck'></input-switch></h2>\n<ul>";
            /** Concat our current permission into the group */
            $table[$group] .= "<li><input-switch type='checkbox' checked='$checked' name='permissions.$name' $dangerous></input-switch>$item[label]</li>\n";
        }
        /** Collapse our sorted groups to a string, closing our unordered lists and completing our HTML */
        return implode("</ul>\n",$table) . "</ul>\n";
    }

    function validate($request){
        $include = $request['include'];
        unset($request['include']);
        // Establish our perms
        $perms = [];
        foreach($request as $name => $value){
            // Our name
            $n = "";
            $type = "";
            if(strpos($name,"permissions.") !== false) {
                $n = str_replace("permissions.","",$name);
                $type = "permissions";
            } else if(strpos($name,"groups") !== false) {
                $n = str_replace("groups.","",$name);
                $type = "groups";
            }
            if(!key_exists($type,$perms)) $perms[$type] = [];
            $perms[$type][$n] = [$name,$value];
        }
        $result = [null,null];
        if(isset($perms['permissions'])) $result[0] = $this->update_permissions($perms['permissions'],$include);
        if(isset($perms['groups'])) $result[1] = $this->update_groups($perms['groups'],$include);
        return [...$result];
    }

    /** TODO: Migrate this over to \Auth\UserAccountValidation->validate_permissions */
    function update_permissions($permissions,$user_id){
        $valid = [];
        foreach($permissions as $name => $permission){
            if(!key_exists($name,$this->valid)) throw new \Exceptions\HTTP\BadRequest("Your request contained unexpected data.");
            if(!is_bool($permission[1])) throw new \Exceptions\HTTP\BadRequest("Your request contained unexpected data.");
            
            $valid += [$permission[0] => $permission[1]];
        }
        $this->collection->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($user_id)],
            ['$set' => $valid]
        );
        return $valid;
    }

    /** TODO: Migrate this over to \Auth\UserAccountValidation->validate_groups */
    function update_groups($groups,$user_id){
        $valid = [
            '$addToSet' => ['groups' => ['$each' => []]],
            '$pull' => ['groups' => []]
        ];
        $return = [];
        foreach($groups as $group => $data){
            $key = '$addToSet';
            if($data[1] === false) $key = '$pull';
            if(!in_array($group,$this->groups)) throw new \Exceptions\HTTP\BadRequest("Your request contained unexpected data.");
            if($key === '$addToSet') array_push($valid[$key]['groups']['$each'],$group);;
            if($key === '$pull') array_push($valid[$key]['groups'],$group);
            array_push($return,[$data[0] => (bool)$data[1]]);
        }

        if(count($valid['$addToSet']['groups']['$each']) === 0) unset($valid['$addToSet']);
        if(count($valid['$pull']['groups']) === 0) unset($valid['$pull']);

        if(count($valid) === 0) throw new \Exceptions\HTTP\BadRequest("Your request contained unexpected data.");

        $result = $this->collection->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($user_id)],
            $valid
        );

        return $return;

        // if($result->getModifiedCount() !== 1)

    }
}