<?php
namespace Handlers\Traits;

use Cobalt\Extensions\Extensions;
use Cobalt\Notifications\Classes\NotificationManager;
use Handlers\AdminHandler;

trait UserBar {

    public string $userbar_admin_panel   = "<a href=\"".__APP_SETTINGS__['cobalt_base_path']."/admin\">".__APP_SETTINGS__['app_short_name']."</a>";
    public string $userbar_new_post_link = "<a href=\"@route(\"\\\Cobalt\\\Pages\\\Controllers\\\Posts@__new_document\");\"><i name=\"post\"></i> Post</a>";
    public string $userbar_new_page_link = "<a href=\"@route(\"\\\Cobalt\\\Pages\\\Controllers\\\LandingPages@__new_document\");\"><i name=\"file-document\"></i></a>";


    function user_menu() {
        $masthead = "";

        if(__APP_SETTINGS__['Web_include_app_branding']) {
            $logo = app("logo.thumb");
            $meta = $logo['meta'];
            $masthead = "<a href='".to_base_url("/")."' title='Home'><img class='cobalt-masthead' src='".to_base_url($logo['filename'])."' width='$meta[width]' height='$meta[height]' alt=\"".htmlspecialchars(__APP_SETTINGS__['app_name'])." Homepage\"></a>";
        }
        $admin_masthead = str_replace("href=", "is='real' href=", $masthead);
        add_vars([
            'masthead' => (app("display_masthead")) ? $masthead : "",
            'admin_masthead' => $admin_masthead,
        ]);

        if (!session_exists()) return "";
        if (__APP_SETTINGS__["manifest_engine"] < 2) return "<!-- user_menu unsupported manifest engine -->";
        if ($this instanceof AdminHandler === false && !__APP_SETTINGS__["Auth_user_menu_enabled"]) return "<!-- user_menu disabled -->";
        $menu = "<label for='user-menu-bar-controller'></label><input id='user-menu-bar-controller' type='checkbox'><div id='user-menu-bar'><nav><ul class=\"userbar--navigation-options\">";
        
        $buttons = ['masthead' => $admin_masthead];
        $buttons += $this->userbar_start();
        $buttons['_main'] = $this->userbar_admin_panel;
        // $buttons['_post'] = (__APP_SETTINGS__['Posts_default_enabled']) ? $this->userbar_new_post_link : "";
        // $buttons['_page'] = (__APP_SETTINGS__['LandingPages_enabled']) ? $this->userbar_new_page_link : "";
        global $USER_BAR_DETAILS;
        $buttons += $USER_BAR_DETAILS;
        $this->userbar_before_extensions($buttons);
        Extensions::invoke("register_templates_dir", $buttons);
        $this->userbar_after_extensions($buttons);

        foreach($buttons as $type => $html) {
            $menu .= "<li id='userbar-$type' class=\"userbar--button-container\">$html</li>";
        }

        $settings = route("CoreAdmin@settings_index");
        global $USER_BAR_CUSTOMS;
        $customize = route("Customizations@index") . urlencode(implode(";",$USER_BAR_CUSTOMS));
        $panel = "";
        if(__APP_SETTINGS__['Notifications_system_enabled']) {
            $count = (new NotificationManager())->getUnreadNotificationCountForUser();
            $panel .= "<notify-button value=\"$count[unseen]\"></notify-button>";
        }
        $panel .= ($customize) ? "<a class='admin-panel--customize-link' href='$customize' rel='Customize Panel' title='Customize Panel'><i name='application-edit-outline'></i><span class='contextual contextual--hover'>Customize</span></a>" : "";
        $panel .= ($settings) ? "<a class='admin-panel--settings-link' href='$settings' rel='Settings Panel' title='Settings Panel'><i name='cog'></i><span class='contextual contextual--hover'>Settings</span></a>" : "";
        $usercontainer = view('/admin/users/session-panel.html',[]);
        $after_bar = $this->userbar_end();
        return $menu . <<<HTML
            </ul>
        </nav>
        <div class="userbar--user-container">
            $panel
            $usercontainer
        </div>
        $after_bar
        </div>
        HTML;
    }

    abstract function userbar_start():array;
    abstract function userbar_before_extensions(array &$buttons):void;
    abstract function userbar_after_extensions(array &$buttons):void;
    abstract function userbar_end():string;
}