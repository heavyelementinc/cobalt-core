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
}
