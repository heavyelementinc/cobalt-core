<?php

use Auth\AdditionalUserFields;
use Auth\MultiFactorManager;
use Auth\SessionManager;
use \Auth\UserCRUD;
use Auth\UserPersistance;
use Auth\UserSchema;
use \Auth\UserValidate;
use Cobalt\Notifications\PushNotifications;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\Unauthorized;
use MongoDB\BSON\ObjectId;
use MongoDB\Exception\BadMethodCallException;
use Validation\Exceptions\ValidationFailed;
use Validation\Exceptions\ValidationIssue;

class UserAccounts extends \Controllers\Pages {

    /** @deprecated */
    function update_permissions($id) {
        $permissions = $_POST;
        $validated = $GLOBALS['auth']->permissions->validate($id, $permissions);
        // $GLOBALS['auth']->permissions->update_permissions($permissions, $id);
        return $validated;
    }

    function update_push($id) {
        $_id = new ObjectId($id);
        $ua = new UserCRUD();
        $user = $ua->findOne(['_id' => $_id]);
        if(!$user) throw new NotFound(ERROR_RESOURCE_NOT_FOUND);
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

    function totp_enroll() {
        reauthorize("You must re-authenticate to add TOTP to your account.", $_POST);
        $user = session();
        $totp = new MultiFactorManager($user);
        $backups = $totp->enroll_user($user, $_POST['verification']);
        $body = "<p>Back up these recovery codes somewhere safe! If you lose access to your TOTP app, you can use these codes as a way to recover access to your account. <strong>You will <u>not</u> see these backup codes again!<strong></p><ul>";
        foreach($backups as $b) {
            $body .= "<li>$b</li>";
        }
        $body .= "</ul>";

        update("#enrollment-pane", ['innerHTML' => $body]);

        return $body;
    }

    function totp_unenroll() {
        // confirm("Are you sure you want to remove TOTP Authentication from your account?", $_POST);
        reauthorize("You must re-authenticate to remove TOTP from your account.", $_POST);
        $user = session();
        $totp = new MultiFactorManager($user);

        $result = $totp->unenroll_user($user);
        update("#enrollment-pane",['innerHTML' => "<legend>Two-Factor Authentication</legend></p>You've opted out of TOTP 2FA</p>"]);
        return $result->getModifiedCount();
    }

    /* Working Jun 10 2021 */
    function update_basics($id) {
        $update = $_POST;
        $ua = new UserCRUD();
        if(key_exists('avatar', $update)) {
            $schema = $ua->findOne(['_id' => new ObjectId($id)]);
            try {
                $schema->deleteAvatar();
            } catch(NotFound $e) {
                header("HTTP/1.1 200 OK");
                // Do nothing
            }
        }
        if(key_exists("flags.locked", $update)) {
            $schema = $ua->findOne(['_id' => new ObjectId($id)]);
            if(confirm("Are you sure you want to lock $schema->name's account? Doing so will log $schema->them out from all devices and $this->they will not be able to log back in until $schema->their account is unlocked!", $_POST)) {
                $session = new SessionManager();
                $result = $session->destroy_session_by_user_id($id);
            }
        }
        $validated = $ua->updateUser($id, $update);
        
        if(key_exists('avatar', $update)) {
            update("#profile-picture .cobalt-user--avatar", ['attributes' => ['src' => "/res/fs/".$validated['avatar']['thumb']['filename']]]);
        }

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
        // if (!password_verify($_POST['current'], session("pword"))) throw new Unauthorized("You must enter your current password", true); 
        if (!reauthorize("You must confirm your password", $_POST)) return;
        if (!isset($_POST['password']) || !isset($_POST['pword'])) throw new  ValidationFailed("Failed to update your password", ['pword' => "You need to specify both password fields."]);
        if ($_POST['password'] !== $_POST['pword']) throw new ValidationFailed("Failed to update your password", ['pword' => "Both password fields must match."]);
        $_POST = ['pword' => $_POST['pword']];
        $value = $this->update_basics(session("_id"));
        header("X-Status: @info Your password was updated successfully");
        // update("");
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

        return view("parts/main.html");
    }

    function onboarding() {
        add_vars([
            'title' => "Make an account"
        ]);

        return view("authentication/account-creation/onboarding.html");
    }

    function me() {
        $session = session();
        $push = new PushNotifications();
        $multifactor = new \Auth\MultiFactorManager($session);

        $addtl = new AdditionalUserFields();
        $fields = $addtl->__get_additional_user_tabs();
        $links = "";
        $extensions = "";
        foreach($fields as $field => $data) {
            if(!$data['self_service']) continue;
            $view = $data['self_service'];
            if($view === true) $view = $data['view'];
            $icon = $data['icon'] ?? 'card-bulleted-outline';
            $data['name'] = "<i name='$icon'></i> $data[name]";
            $links .= "<a href='#$field'>$data[name]</a>";
            $extensions .= "<div id='$field'>".view($view, ['user_account' => $session])."</div>";
        }

        $sessionMan = new SessionManager();
        $sessions = $sessionMan->session_manager_ui_by_user_id(session('_id'));

        add_vars([
            'title' => "$session->fname $session->lname",
            'doc' => $session,
            'notifications' => $push->render_push_opt_in_form_values($session),
            '2fa' => $multifactor->get_multifactor_enrollment($session),
            'integrate' => (new IntegrationsController())->getOauthIntegrations(),
            'links' => $links,
            'extensions' => $extensions,
            'sessions' => "<div id='sessions'>$sessions</div>",
            'method' => "POST",
            'endpoint' => "/api/v1/user/me/"
        ]);

        return view("/authentication/user-self-service-panel.html");
    }

    function update_me() {
        $session = session();
        if(!$session) throw new Unauthorized("You're not logged in");
        
        // Only allow these fields to be updated through this method
        $filter = ['fname', 'lname', 'uname', 'email', 'pword', 'avatar', 'fediverse_profile', 'youtube_profile', 'instagram_profile', 'facebook_profile', 'twitter_profile', 'default_bio_blurb'];
        $update = [];
        foreach($filter as $key){
            if(key_exists($key, $_POST)) $update[$key] = $_POST[$key];
        }

        if(empty($update)) throw new BadRequest("There are no fields to update","Your request could not be processed");

        $schema = new UserPersistance();
        
        $validated = $schema->__validate($update)->__validatedFields;

        if(key_exists('avatar', $_POST)) {
            $avatar = $session['avatar'] ?? [];
            try{
                $session->deleteAvatar();
            } catch(NotFound $e){
                // Ignore it if we can't delete the avatar.
                header("HTTP/1.1 200 OK");
            }
            update(".cobalt-user--avatar", ['attributes' => ['src' => "/res/fs/".$validated['avatar']['thumb']['filename']]]);
        } else {
            unset($validated['avatar']);
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
            $user = $man->findOne(['_id' => new ObjectId($id)]);
            $message = "this user's";
        }
        confirm("This action will <strong>permanently delete</strong> $message avatar. Are you sure you want to continue?", $_POST);

        return $user->deleteAvatar();
    }

    function log_out_session_by_id($id) {
        $sess = new SessionManager();
        $result = $sess->destroy_session_by_session_id(new ObjectId($id));
        return $result;
    }
}
