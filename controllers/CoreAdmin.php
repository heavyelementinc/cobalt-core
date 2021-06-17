<?php
class CoreAdmin extends \Controllers\Pages {
    function index() {
        add_vars(['title' => "Admin Panel"]);
        set_template("/authentication/admin-dashboard/index.html");
    }

    function list_all_users($page = 0) {
        $collection = \db_cursor('users');
        $list = "<flex-table><flex-row>
            <flex-header>Name</flex-header>
            <flex-header>Username</flex-header>
            <flex-header>Email</flex-header>
            <flex-header>Groups</flex-header>
        </flex-row>";
        foreach ($collection->find([]) as $user) {
            $list .= "<flex-row><flex-cell><flex-cell>" . $this->user_link($user['_id']) . "$user[fname] $user[lname]</a></flex-cell>";
            $list .= "<flex-cell>" . $this->user_link($user['_id']) . "@$user[uname]</a></flex-cell>";
            $list .= "<flex-cell>$user[email]</flex-cell>";
            $groups = str_replace("root", "<strong>root</strong>", implode(", ", (array)$user['groups']));
            $list .= "<flex-cell>" . $this->user_link($user['_id'], "#permissions") . (($groups) ? $groups : "<span style='opacity:.6'>No groups</span>") . "</a></flex-cell>";
            // $list .= "<flex-cell>" . json_encode($user["verified"]) . "</flex-cell>";
            $list .= "</flex-row>";
        }
        $list .= "</flex-table>";
        add_vars([
            'title' => "Manage users",
            "users" => $list
        ]);
        set_template("/authentication/user-management/list-users.html");
    }

    private function user_link($id, $target = "#basics") {
        $link = "<a href='/admin" . app("Auth_user_manager_individual_page") . "/" . (string)$id . "$target'>";
        return $link;
    }

    function individual_user_management_panel($id) {
        $ua = new \Auth\UserCRUD();
        $user = (array)$ua->getUserById($id);
        if (!$user) throw new \Exceptions\HTTP\NotFound("That user doesn't exist.", ['template' => 'errors/404_invalid_user.html']);

        add_vars([
            'title' => "$user[fname] $user[lname]",
            'user_account' => $user,
            'user_id' => (string)$user['_id'],
            'permission_table' => $GLOBALS['auth']->permissions->get_permission_table($user),
            'account_flags' => $ua->getUserFlags($user),
        ]);
        set_template("/authentication/user-management/individual-user.html");
    }

    function create_user() {
        add_vars([
            'title' => "Create user"
        ]);
        set_template("/authentication/user-management/create_new_user_basic.html");
    }

    function plugin_manager() {
        $content = $GLOBALS['plugin_manager']->get_plugin_list("/admin/plugins/");

        add_vars([
            'title' => "Plugin Manager",
            'main' => $content
        ]);
        set_template("parts/main.html");
    }

    function plugin_individual_manager($plugin_id) {
        $plugin = $GLOBALS['plugin_manager']->get_plugin_by_name($plugin_id);
        if ($plugin === null) throw new \Exceptions\HTTP\NotFound("That plugin does not exist.");
        add_vars([
            'title' => $plugin['name'],
            'plugin' => $plugin
        ]);
        set_template("plugins/individual.html");
    }
}
