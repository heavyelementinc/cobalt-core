<?php

use \Auth\UserCRUD;
use \Auth\UserValidate;
use Validation\Exceptions\ValidationFailed;

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
        $ua = new UserCRUD();
        $validated = $ua->updateUser($id, $update);
        return $validated;
    }

    function create_user() {
        $ua = new UserCRUD();

        $validated = $ua->createUser($_POST);

        return $validated;
    }

    function delete_user($id) {
        $ua = new UserCRUD();
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

    function change_my_password() {
        if (!isset($_POST['password']) || !isset($_POST['pword'])) throw new  ValidationFailed("Failed to update your password", ['pword' => "You need to specify both password fields."]);
        if ($_POST['password'] !== $_POST['pword']) throw new ValidationFailed("Failed to update your password", ['pword' => "Both password fields must match."]);
        $value = $this->update_basics(session("_id"));

        return "Success";
    }

    function get_user_menu() {
        $html = "";
        $logged_in = false;
        $html = get_route_group("user_menu", ['with_icon' => true]);
        try {
            if (has_permission("Admin_panel_access")) {
                $html += "<li><a href='/admin/'><ion-icon name='settings'></ion-icon>Admin</a></li>";
            }
            $logged_in = true;
        } catch (\Exceptions\HTTP\Unauthorized $e) {
            $html = '<li id="main-menu-SignIn" name="SignIn"><div class="user-menu-option SignIn"><ion-icon name="log-in" role="img" class="md hydrated" aria-label="log in"></ion-icon><span class="user-menu-text">Log in</span></div></li>';
        }

        if ($logged_in) {
            $html = '<li id="main-menu-SignOut" name="SignOut"><div class="user-menu-option SignOut"><ion-icon name="log-out" role="img" class="md hydrated" aria-label="log out"></ion-icon><span class="user-menu-text">Log out</span></div></li>';
        }

        add_vars([
            'title' => 'User Menu',
            'main' => str_replace("</ul>", "", $html) . "</ul>"
        ]);

        set_template("parts/main.html");
    }
}
