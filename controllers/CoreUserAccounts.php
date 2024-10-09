<?php

use Auth\SessionManager;
use Auth\UserCRUD;
use Auth\UserPersistance;
use Cobalt\Extensions\Extensions;
use Cobalt\Maps\GenericMap;
use Cobalt\Notifications\PushNotifications;
use Controllers\Crudable;
use Drivers\Database;
use MongoDB\Model\BSONDocument;

class CoreUserAccounts extends Crudable {
    /** @var UserCRUD */
    public Database $manager;

    public function get_manager(): Database {
        return new UserCRUD();
    }

    public function get_schema($data): GenericMap {
        return new UserPersistance();
    }

    public function edit($document): string {
        $user_permission_table = $GLOBALS['auth']->permissions->get_permission_table($document);

        $extension_tabs = [];
        Extensions::invoke('register_user_editor_tabs', $tabs);

        $push = new PushNotifications();

        try {
            $auth = new \Auth\AdditionalUserFields();
            $additional = array_merge($extension_tabs, $auth->__get_additional_user_tabs() ?? []);
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
                $panels .= "<section id='$id' class='drawer-list--item'>".view($values['view'])."</section>";
            }
            add_vars([
                'additional_button' => $buttons,
                'additional_panel' => $panels,
            ]);
        }

        return view("/authentication/user-management/individual-user.html", [
            'account_flags' => $this->manager->getUserFlags($document),
            'permission_table' => $user_permission_table,
            'notifications' => view("/authentication/user-management/push-notifications.html", [
                'doc' => $document,
                'endpoint' => '/api/v1/user/{{doc._id}}/push',
                'push_options' => $push->render_push_opt_in_form_values($document),
            ]),
            'sessions' => (new SessionManager())->session_manager_ui_by_user_id($document->_id)
        ]);
    }

    public function destroy(GenericMap|BSONDocument $document): array {
        return ['message' => "Are you sure you want to delete $document->fname $document->lname?"];
    }
    
    function update_permissions($user_id) {
        $array_of_permissions = $_POST;
        $validated = $GLOBALS['auth']->permissions->validate($user_id, $array_of_permissions);
        return $validated;
    }
    

}