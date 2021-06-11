<?php
class CoreAdmin extends \Controllers\Pages {
    function index() {
        add_vars(['title' => "Admin Panel"]);
        add_template("/authentication/admin-dashboard/index.html");
    }

    function manage_users() {
        $collection = \db_cursor('users');
        $list = "<flex-table>";
        foreach ($collection->find([]) as $user) {
            $link = "<a href='/admin" . app("Auth_user_manager_individual_page") . "/" . (string)$user["_id"] . "'>";
            $list .= "<flex-row><flex-cell><flex-cell>$link$user[fname] $user[lname]</a></flex-cell>";
            $list .= "<flex-cell>$link@$user[uname]</a></flex-cell>";
            $list .= "<flex-cell>$user[email]</flex-cell>";
            // $list .= "<flex-cell>" . json_encode($user["verified"]) . "</flex-cell>";
            $list .= "</flex-row>";
        }
        $list .= "</flex-table>";
        add_vars([
            'title' => "Manage users",
            "users" => $list
        ]);
        add_template("/authentication/user-management/list-users.html");
    }

    function user_manager($id) {
        $ua = new \Auth\UserCRUD();
        $user = (array)$ua->getUserById($id);
        if (!$user) throw new \Exceptions\HTTP\NotFound("That user doesn't exist.", ['template' => 'errors/404_invalid_user.html']);
        add_vars([
            'title' => "$user[fname] $user[lname]",
            'user_account' => $user,
            'user_id' => (string)$user['_id'],
            'permission_table' => $GLOBALS['auth']->permissions->get_permission_table($user),
        ]);
        add_template("/authentication/user-management/individual-user.html");
    }

    function create_user() {
        add_vars([
            'title' => "Create user"
        ]);
        add_template("/authentication/user-management/create_new_user_basic.html");
    }
}
