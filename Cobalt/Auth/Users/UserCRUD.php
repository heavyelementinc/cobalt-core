<?php

namespace Cobalt\Auth\Users;

use User;

class UserCRUD {
    private ?User $user;
    public function __construct() {
        /** @var Authentication $auth */
        global $auth;
        $this->user = $auth->getCurrentSessionUser();
    }

    public function getRootUsers() {
        
    }
}