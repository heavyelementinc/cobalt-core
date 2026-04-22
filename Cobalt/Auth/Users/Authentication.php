<?php

namespace Cobalt\Auth\Users;

use Cobalt\Auth\Session\Models\Session;
use Cobalt\Auth\Users\Models\User;
use Cobalt\Auth\Permissions\PermissionManager;
use Cobalt\Auth\Users\Controllers\Users;

class Authentication {
    private PermissionManager $permissions;
    private ?Session $session;
    
    function __construct(){
        $this->permissions = new PermissionManager();
    }

    public function restoreSession() {
        // Find the current session
        $this->session = (new Session())->findOne([
            'token_session' => $_COOKIE[Session::SESSION_COOKIE_KEY]
        ]);
    }

    public function getPermissionSingleton():PermissionManager {
        return $this->permissions;
    }

    // Returns the current session data if it exists, or null otherwise.
    public function getSession():?Session {
        return $this->session;
    }

    public function isUserLoggedIn():bool {
        return $this->getCurrentSessionUser() instanceof User;
    }

    // The goal is to support multiple user logins at once
    public function getCurrentSessionUser():?User {
        // Get the current index of the logged in user
    $index = $this->session?->current_index->value ?? 0;
        // Returns either the currently indexed user or null
        return $this->session?->represents[$index] ?? null;
    }

    public function has_permission($permission, $isRoot = null, ?User $user = null, $throw_no_session = true) {
        if(!$user) $user = $this->getCurrentSessionUser();
        return User::hasPermission($user, $permission, $throw_no_session);
    }

    public function logInUser(User $user) {
        $_SESSION[Users::LOGIN_USER_LOGGED_IN_KEY] = true;
        if(!$this->session) {
            Session::newSession($user);
            return true;
        }
        $this->session->logInUser($user);
        return true;
    }
}