<?php

namespace Cobalt\Settings\Controllers;

use Cobalt\Controllers\Controller;

class Settings extends Controller {
    function update() {
        /** @var Settings $application */
        global $application;
        
        foreach($_POST as $setting => $value) {
            $result = $application->update_setting($setting, $value);
            update("[name=\"$result[0]\"]", ['value' => $result[1]]);
            update( "[for=\"$result[0]\"]", ['innerHTML' => $result[1]]);
        }
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

        return view("Cobalt/Settings/templates/settings/control-panel.php");
    }

    function app_settings() {
        return view("Cobalt/Settings/templates/settings/basic-settings.php");
    }
}