<?php

class User{
    public $help_documentation = [
        'create' => [
            'description' => "[username [password [email]]] - Create new user. Password cannot contain spaces if called as single command.",
            'context_required' => true
        ],
        'password' => [
            'description' => "username|email - Change a user's password",
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

    function create($username = null,$password = null,$email = null){
        if(!app("Auth_user_accounts_enabled")) throw new Exception("User accounts are not enabled");
        if($username === null) {
            $u['uname'] = readline("Provide a username > ");
            $u['fname'] = readline("First name of user > ");
            $u['lname'] = readline("Last name of user > ");
        } else {
            $u['uname'] = $username;
            $u['fname'] = "";
            $u['lname'] = "";
        }
        if($password === null) $u['pword'] = readline("User's password > ");
        else $u['pword'] = $password;
        
        if($email === null) $u['email'] = readline("Email address > ");
        else $u['email'] = $email;

        $crud = new Auth\CRUDUser();
        $result = $crud->add_user($u);
        return "User created with id " . fmt($result['_id'],"i");
    }

    function password($username = null){
        if(!app("Auth_user_accounts_enabled")) throw new Exception("User accounts are not enabled");
        if($username === null) throw new Exception("Missing operand. You must specify a username and password to be changed");
        $pword = readline("Provide a new password > ");
        $confirm = readline("Confirm the new password > ");

        if($pword !== $confirm) throw new Exception("Passwords did not match. Aborting.");
        $accounts = new Auth\UserAccount();
        $user = $accounts->get_user_by_uname_or_email($username);
        
        if($user === null) throw new Exception("That user doesn't exist.");

        $crud = new Auth\CRUDUser();
        // NOTE: Hashing is taken care of in the UserAccountValidation class
        $result = $crud->update_user($user['_id'],['pword' => $pword]);

        if($result->getModifiedCount() === 1) return "Password updated!";
        throw new Exception("An unknown error ocurred.");
    }

    function delete($username,$confirm = false){
        if(!app("Auth_user_accounts_enabled")) throw new Exception("User accounts are not enabled");
        $accounts = new Auth\UserAccount();
        $user = $accounts->get_user_by_uname_or_email($username);

        if($user === null) throw new Exception("That user account doesn't exist");

        if($confirm === "force") $confirm = true;

        if($confirm === false) {
            $name = "$user[fname] $user[lname]";
            if($name === " ") $name = "No first/last name specified";
            say($name,"b");
            say("$user[uname]\n","i");
            $confirm = confirm_message("Are you sure you want to delete this user?",false);
        }

        if($confirm !== true) return fmt("Aborting","e");
        $crud = new Auth\CRUDUser();
        $result = $crud->delete_user($user['_id']);
        return "Removed user account " . fmt((string)$user['_id'],"i");
    }

    function find($type, $value){
        if(!app("Auth_user_accounts_enabled")) throw new Exception("User accounts are not enabled");
        return fmt("This has not been implemented yet","e");
    }

    // function enable_accounts($bool){
    //     $bool = cli_to_bool($bool,false);

    // }
}