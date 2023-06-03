<?php

use \Auth\UserCRUD;
use Auth\UserSchema;
use \Auth\UserValidate;
use Cobalt\Notifications\PushNotifications;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\Unauthorized;
use MongoDB\BSON\ObjectId;
use Validation\Exceptions\ValidationFailed;

class UserAccounts extends \Controllers\Pages {

    /* Working Jun 10 2021 */
    function update_permissions($id) {
        $permissions = $_POST;
        $validated = $GLOBALS['auth']->permissions->validate($id, $permissions);
        // $GLOBALS['auth']->permissions->update_permissions($permissions, $id);
        return $validated;
    }

    function update_push($id) {
        $_id = new ObjectId($id);
        $ua = new UserCRUD();
        $user = $ua->findOneAsSchema(['_id' => $_id]);
        if(!$user) throw new NotFound("Resource does not exist");
        $push = new PushNotifications();
        $updateable = [];
        foreach($_POST as $type => $value) {
            if(!key_exists($type, $push->valid)) throw new BadRequest("Bad request data");
            if(!is_bool($value)) throw new BadRequest("Value is invalid");
            if(!$push->is_elligible($user, $type)) throw new Unauthorized("This resource is inelligible for the requested update");
            
            $updateable["$push->ua_push_types.$type"] = $value;
        }
        
        $result = $ua->updateOne(['_id' => $_id],[
            '$set' => $updateable
        ]);
        return $updateable;
    }

    function update_my_push() {
        return $this->update_push((string)session('_id'));
    }

    function update_my_push_enrollment($status) {
        $_id = session('_id');
        if(!$_id) throw new Unauthorized("You must be logged in.");
        $push = new PushNotifications();
        switch($status) {
            case "subscribed":
            case "subscribe":
                $result = $push->enrollPushKeys($_id, $_POST);
                break;
            case "unsubscribed":
            case "unsubscribe":
            default:
                $result = $push->revokePushKeys($_id, $_POST);
                break;
        }
        return $result;
    }

    /* Working Jun 10 2021 */
    function update_basics($id) {
        $update = $_POST;
        $ua = new UserCRUD();
        if(key_exists('avatar', $update)) {
            $schema = $ua->findOneAsSchema(['_id' => new ObjectId($id)]);
            try {
                $schema->deleteAvatar();
            } catch(NotFound $e) {
                header("HTTP/1.1 200 OK");
                // Do nothing
            }
        }
        $validated = $ua->updateUser($id, $update);
        return $validated;
    }

    function create_user() {
        $ua = new UserCRUD();

        $validated = $ua->createUser($_POST);

        return $validated;
    }

    function account_creation() {
        $ua = new UserCRUD();

        $validated = $ua->createUser($_POST, "partial");

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

    function onboarding() {
        add_vars([
            'title' => "Make an account"
        ]);

        set_template("authentication/account-creation/onboarding.html");
    }

    function me() {
        $session = session();
        $push = new PushNotifications();
        add_vars([
            'title' => "$session->fname $session->lname",
            'doc' => $session,
            'notifications' => $push->render_push_opt_in_form_values($session),
        ]);

        set_template("/authentication/user-self-service-panel.html");
    }

    function update_me() {
        $session = session();
        if(!$session) throw new Unauthorized("You're not logged in");
        
        // Only allow these fields to be updated through this method
        $filter = ['fname', 'lname', 'uname', 'email', 'pword', 'avatar'];
        $update = [];
        foreach($filter as $key){
            if(key_exists($key, $_POST)) $update[$key] = $_POST[$key];
        }

        $schema = new UserSchema();
        $validated = $schema->validate($update);

        if(key_exists('avatar', $_POST)) {
            $avatar = $session['avatar'] ?? [];
            try{
                $session->deleteAvatar();
            } catch(NotFound $e){
                // Ignore it if we can't delete the avatar.
                header("HTTP/1.1 200 OK");
            }
        }

        $user = new UserCRUD();

        $result = $user->updateOne(['_id' => $session['_id']],['$set' => $validated]);

        return $validated;
    }

    function delete_avatar($id) {
        if($id === "me") {
            $user = session();
            $message = "your";
        } else {
            if(!has_permission("Auth_allow_editing_users")) throw new Unauthorized("You are not authorized to modify this resource.");
            $man = new UserCRUD();
            $user = $man->findOneAsSchema(['_id' => new ObjectId($id)]);
            $message = "this user's";
        }
        confirm("This action will <strong>permanently delete</strong> $message avatar. Are you sure you want to continue?", $_POST);

        return $user->deleteAvatar();
    }
}
