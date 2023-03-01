<?php

use \Auth\UserSchema;
use MongoDB\BSON\ObjectId;
use Cobalt\Payments\PaymentGateway;
use Cobalt\Payments\PaymentGatewaySchema;
use CobaltEvents\EventManager;
use Contact\ContactManager;

class CoreAdmin {
    function index() {
        add_vars([
            'title' => "Admin Panel",
            'contact_manager' => (new ContactManager())->get_unread_count_for_user(session()),
            'user_accounts' => (new \Auth\UserCRUD())->count([]),
            'events' => (new EventManager())->getAdminWidget(),
            'plugin_count' => count($GLOBALS['ACTIVE_PLUGINS']),
            'cron_job' => (new \Cron\Run())->renderTaskStats(),
        ]);
        set_template("/authentication/admin-dashboard/index.html");
    }

    function list_all_users($page = 0) {
        $collection = \db_cursor('users');
        $list = "<flex-table><flex-row>
            <flex-header>Name</flex-header>
            <flex-header>Username</flex-header>
            <flex-header>Email</flex-header>
            <flex-header>Groups</flex-header>
            <flex-header>Verified</flex-header>
        </flex-row>";
        foreach ($collection->find([]) as $user) {
            $groups = str_replace("root", "<strong>root</strong>", implode(", ", (array)$user['groups']));
            $list .= view("/admin/users/user.html", [
                'user' => new UserSchema($user),
                'groups' => (($groups) ? $groups : "<span style='opacity:.6'>No groups</span>"),
            ]);
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
        $user = new UserSchema($ua->getUserById($id));
        if (!$user) throw new \Exceptions\HTTP\NotFound("That user doesn't exist.", ['template' => 'errors/404_invalid_user.html']);

        $table = $GLOBALS['auth']->permissions->get_permission_table($user);

        add_vars([
            'title' => "$user->fname $user->lname",
            'user_account' => $user,
            'user_id' => (string)$user->_id,
            'permission_table' => $table,
            'account_flags' => $ua->getUserFlags($user),
        ]);

        try {
            $auth = new \Auth\AdditionalUserFields();
            $additional = maybe_view($auth->__get_additional_user_tab());
        } catch (\Exception $e) {
            $additional = "";
        }

        if ($additional) {
            add_vars([
                'additional_button' => "<li><button for='additional-panel'>Other</button></li>",
                'additional_panel' => "<section id='additional-panel' class='drawer-list--item'>$additional</section>",
            ]);
        }

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
        set_template("plugins/index.html");
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

    function settings_index() {
        add_vars([
            'title' => "Settings Panel",
            'basic_settings' => get_route_group('admin_basic_panel', ['with_icon' => true]),
            'settings_panel' => get_route_group("settings_panel",['with_icon' => true]),
            'advanced_panel' => get_route_group("access_panel",['with_icon' => true]),
            'public_settings_panel' => get_route_group("public_settings_panel",['with_icon' => true]),
        ]);

        set_template("/admin/settings/control-panel.html");
    }

    function app_settings() {
        set_template("/admin/settings/basic-settings.html");
    }

    function cron_panel() {
        $cron = new \Cron\Run();

        $tasks = "";
        foreach($cron->get_tasks('all') as $task){
            $tasks .= (new \Cron\Task($task, new DateTime()))->getView($cron->task_stats($task->name));
        }
        add_vars([
            // 'widgets ' => $widgets,
            'tasks' => $tasks
        ]);
        set_template("/admin/cron/cron-task-index.html");
    }


    function payment_gateways() {
        $gateMan = new PaymentGateway();
        
        $stripe = $gateMan->get_gateway_data('stripe');
        if(!$stripe) $stripe = new PaymentGatewaySchema(['_id' => new ObjectId()]);

        $paypal = $gateMan->get_gateway_data('paypal');
        if(!$paypal) $paypal = new PaymentGatewaySchema(['_id' => new ObjectId()]);

        add_vars([
            'title' => "Payment Gateways",
            'stripe' => $stripe,
            'paypal' => $paypal,
        ]);

        set_template("/admin/settings/payment-gateways.html");
    }
}
