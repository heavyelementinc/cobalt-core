<?php

use Auth\SessionManager;
use \Auth\UserSchema;
use MongoDB\BSON\ObjectId;
use Cobalt\Payments\PaymentGateway;
use Cobalt\Payments\PaymentGatewaySchema;
use CobaltEvents\EventManager;
use Contact\ContactManager;
use Cobalt\Extensions\Extensions;
use Cobalt\Notifications\PushNotifications;

class CoreAdmin {
    function index() {
        add_vars([
            'title' => "Admin Panel",
            'contact_manager' => (new ContactManager())->get_unread_count_for_user(session()),
            'user_accounts' => (new \Auth\UserCRUD())->count([]),
            'events' => (new EventManager())->getAdminWidget(),
            'plugin_count' => Extensions::get_active_count(),
            'cron_job' => (new \Cron\Run())->renderTaskStats(),
        ]);
        return view("/authentication/admin-dashboard/index.html");
    }

    // function list_all_users($page = 0) {
    //     $collection = \db_cursor('users');
    //     $list = "<flex-table><flex-row>
    //         <flex-header>Name</flex-header>
    //         <flex-header>Username</flex-header>
    //         <flex-header>Email</flex-header>
    //         <flex-header>Groups</flex-header>
    //         <flex-header>Verified</flex-header>
    //         <flex-header style='width:20px'></flex-header>
    //     </flex-row>";
    //     foreach ($collection->find([]) as $user) {
    //         $groups = str_replace("root", "<strong>root</strong>", implode(", ", (array)$user['groups']));
    //         $list .= view("/admin/users/user.html", [
    //             'user' => new UserSchema($user),
    //             'groups' => (($groups) ? $groups : "<span style='opacity:.6'>No groups</span>"),
    //         ]);
    //     }
    //     $list .= "</flex-table>";
    //     add_vars([
    //         'title' => "Manage users",
    //         "users" => $list
    //     ]);
    //     return view("/authentication/user-management/list-users.html");
    // }

    private function user_link($id, $target = "#basics") {
        $link = "<a href='/admin" . app("Auth_user_manager_individual_page") . "/" . (string)$id . "$target'>";
        return $link;
    }

    function individual_user_management_panel($id) {
        $ua = new \Auth\UserCRUD();
        $user = $ua->getUserById($id);
        if (!$user) throw new \Exceptions\HTTP\NotFound("That user doesn't exist.", ['template' => 'errors/404_invalid_user.html']);

        $table = $GLOBALS['auth']->permissions->get_permission_table($user);
        $push = new PushNotifications();
        add_vars([
            'title' => "$user->fname $user->lname",
            'user_account' => $user,
            'user_id' => (string)$user->_id,
            'permission_table' => $table,
            'account_flags' => $ua->getUserFlags($user),
            'notifications' => view("/authentication/user-management/push-notifications.html", [
                'doc' => $user,
                'endpoint' => '/api/v1/user/{{user_account._id}}/push',
                'push_options' => $push->render_push_opt_in_form_values($user),
            ]),
            'sessions' => (new SessionManager())->session_manager_ui_by_user_id($user->_id)
        ]);

        try {
            $auth = new \Auth\AdditionalUserFields();
            $additional = $auth->__get_additional_user_tabs();
            foreach($additional as $usr => $value) {
                $icon = $i['icon'] ?? "card-bulleted-outline";
                $additional[$usr]['name'] = "<i name='$icon'></i> " . $value['name'];
            }
        } catch (\Error $e) {
            $additional = "";
        }

        if ($additional) {
            $buttons = "";
            $panels = "";
            foreach($additional as $id => $values) {
                $buttons .= "<a href='#$id'>$values[name]</a>";
                $panels .= "<section id='$id' class='drawer-list--item'>".view($values['view'],['user_account' => $user])."</section>";
            }
            add_vars([
                'additional_button' => $buttons,
                'additional_panel' => $panels,
            ]);
        }

        return view("/authentication/user-management/individual-user.html",[]);
    }

    function create_user() {
        add_vars([
            'title' => "Create user"
        ]);
        return view("/authentication/user-management/create_new_user_basic.html");
    }

    function plugin_manager() {
        $content = $GLOBALS['plugin_manager']->get_plugin_list("/admin/plugins/");

        add_vars([
            'title' => "Plugin Manager",
            'main' => $content
        ]);
        return view("plugins/index.html");
    }

    function plugin_individual_manager($plugin_id) {
        $plugin = $GLOBALS['plugin_manager']->get_plugin_by_name($plugin_id);
        if ($plugin === null) throw new \Exceptions\HTTP\NotFound("That plugin does not exist.");
        add_vars([
            'title' => $plugin['name'],
            'plugin' => $plugin
        ]);
        return view("plugins/individual.html");
    }

    function settings_index() {
        add_vars([
            'title' => "Settings Panel",
            'presentation_settings' => get_route_group('presentation_settings', ['with_icon' => true]),
            'application_settings'  => get_route_group("application_settings",['with_icon' => true]),
            'advanced_settings'     => get_route_group('advanced_settings', ['with_icon' => true]),
            // 'access_panel'         => get_route_group("access_panel",['with_icon' => true]),
            // 'public_settings_panel'   => get_route_group("public_settings_panel",['with_icon' => true]),
        ]);

        return view("/admin/settings/control-panel.html");
    }

    function app_settings() {
        return view("/admin/settings/basic-settings.html");
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
        return view("/admin/cron/cron-task-index.html");
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

        return view("/admin/settings/payment-gateways.html");
    }
}
