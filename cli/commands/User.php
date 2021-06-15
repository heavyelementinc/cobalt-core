<?php

class User {
    public $help_documentation = [
        'create' => [
            'description' => "[firstname [username [password [email]]]] - Create new user. Password cannot contain spaces if called as single command.",
            'context_required' => true
        ],
        'password' => [
            'description' => "username|email - Change a user's password",
            'context_required' => true
        ],
        'password_reset' => [
            'description' => "username|email true|false - require the user to reset their password on next login",
            'context_required' => true
        ],
        'delete' => [
            'description' => 'username ["force"] - Delete a user account',
            'context_required' => true
        ],
        'find' => [
            'description' => '"uname"|"email" value - Query for a user account',
            'context_required' => true
        ]
    ];

    function create($first = null, $username = null, $password = null, $email = null) {
        if (!app("Auth_user_accounts_enabled")) throw new Exception("User accounts are not enabled");

        if ($first === null) {
            $u['fname'] =    readline("First name of user > ");
            $u['lname'] =    readline("Last name of user  > ");
        } else {
            $u['fname'] = $first;
            $u['lname'] = "";
        }

        if ($username === null) $u['uname'] = readline("Provide a username > ");
        else $u['uname'] = $username;

        if ($password === null) $u['pword'] = readline("User's password .. > ");
        else $u['pword'] = $password;

        if ($email === null) $u['email'] =    readline("Email address .... > ");
        else $u['email'] = $email;

        $crud = new Auth\UserCRUD();
        $result = $crud->createUser($u);
        return "User created with id " . fmt($result['_id'], "i");
    }

    function password($username = null) {
        if (!app("Auth_user_accounts_enabled")) throw new Exception("User accounts are not enabled");
        if ($username === null) throw new Exception("Missing operand. You must specify a username and password to be changed");
        $pword = readline("Provide a new password > ");
        $confirm = readline("Confirm the new password > ");

        if ($pword !== $confirm) throw new Exception("Passwords did not match. Aborting.");
        $accounts = new Auth\UserCRUD();
        $user = $accounts->getUserByUnameOrEmail($username);

        if ($user === null) throw new Exception("That user doesn't exist.");

        $crud = new Auth\UserCRUD();
        // NOTE: Hashing is taken care of in the UserAccountValidation class
        $result = $crud->updateUser($user['_id'], ['pword' => $pword]);

        if ($result) return "Password updated!";
        throw new Exception("An unknown error ocurred.");
    }

    function password_reset($uname, $status) {
        $new_status = cli_to_bool($status);
        $ua = new Auth\UserCRUD();
        $user = $ua->getUserByUnameOrEmail($uname);
        if ($user === null) throw new Exception("Invalid user account");

        $old_status = $user['flags']['password_reset_required'] ?? null;
        if ($new_status === $old_status) say("Nothing to update", "e");

        $result = $ua->updateUser($user['_id'], ['flags.password_reset_required' => $new_status]);

        return "Set password reset flag from: `" . json_encode($old_status) . "` to: `" . json_encode($new_status) . "`";
    }

    function delete($username, $confirm = false) {
        if (!app("Auth_user_accounts_enabled")) throw new Exception("User accounts are not enabled");
        $accounts = new Auth\UserCRUD();
        $user = $accounts->getUserByUnameOrEmail($username);

        if ($user === null) throw new Exception("That user account doesn't exist");

        if ($confirm === "force") $confirm = true;

        if ($confirm === false) {
            $name = "$user[fname] $user[lname]";
            if ($name === " ") $name = "No first/last name specified";
            say($name, "b");
            say("$user[uname]\n", "i");
            $confirm = confirm_message("Are you sure you want to delete this user?", false);
        }

        if ($confirm !== true) return fmt("Aborting", "e");
        $crud = new Auth\UserCRUD();
        $result = $crud->deleteUserById($user['_id']);
        return "Removed user account " . fmt((string)$user['_id'], "i");
    }

    function find($type, $value) {
        if (!app("Auth_user_accounts_enabled")) throw new Exception("User accounts are not enabled");
        return fmt("This has not been implemented yet", "e");
    }

    // function enable_accounts($bool){
    //     $bool = cli_to_bool($bool,false);

    // }
}
