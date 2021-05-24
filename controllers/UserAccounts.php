<?php
class UserAccounts extends \Controllers\Pages {
    function update_permissions() {
        $permissions = $_POST;
        $validated = $GLOBALS['auth']->permissions->validate($permissions);
        // $GLOBALS['auth']->permissions->update($permissions);
        return $validated;
    }

    function update_basics() {
        $update = $_POST;
        $ua = new \Auth\UserAccountValidation($update);
        $validated = $ua->validate();
    }
}
