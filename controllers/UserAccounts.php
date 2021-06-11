<?php
class UserAccounts extends \Controllers\Pages {

    /* Working Jun 10 2021 */
    function update_permissions($id) {
        $permissions = $_POST;
        $validated = $GLOBALS['auth']->permissions->validate($id, $permissions);
        // $GLOBALS['auth']->permissions->update($permissions);
        return $validated;
    }

    /* Working Jun 10 2021 */
    function update_basics($id) {
        $update = $_POST;
        $ua = new \Auth\UserCRUD();
        $validated = $ua->updateUser($id, $update);
        return $validated;
    }

    function create_user() {
        $ua = new \Auth\UserCRUD();
        $validated = $ua->createUser($_POST);
        return $validated;
    }

    function delete_user($id) {
        $ua = new \Auth\UserCRUD();
        $user = $ua->getUserById($id);
        $username = "$user[fname] $user[lname] ($user[uname])";
        $username = str_replace("  ", " ", $username);
        confirm("Are you sure you want to delete $username?", $_POST);
        try {
            $result = $ua->deleteUserById($id);
            // return $result;
        } catch (Exception $e) {
            throw new \Exceptions\HTTP\Error($e->getMessage());
        }
        throw new \Exceptions\HTTP\Moved("/admin/users/", $result);
    }
}
