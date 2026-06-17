<?php

namespace Cobalt\Settings\Controllers;

use Cobalt\Controllers\Controller;
use Cobalt\Model\Types\Traits\FileHandler;
use Cobalt\Settings\CobaltSetting;
use Cobalt\Settings\Settings as CobaltSettings;
use Controllers\ClientFSManager;
use Drivers\FileSystem;
use Exception;
use Exceptions\HTTP\BadRequest;

class Settings extends Controller {
    // use FileHandler;
    function update() {
        /** @var Settings $application */
        global $application;
        
        foreach($_POST as $setting => $value) {
            if($setting == "logo") {
                $this->handleLogoUpdate();
                continue;
            }
            $result = $application->update_setting($setting, $value);
            update("[name=\"$result[name]\"]", ['value' => $result['value']]);
            // update( "[for=\"$result[name]\"]", ['innerHTML' => $result[1]]);
        }
    }

    public function handleLogoUpdate() {
        throw new Exception("Not implemented");
        $files = normalize_uploaded_files($_FILES);
        // $upload['media']['filename'] 
        $upload['media']['filename'] = "/res/fs".$upload['media']['filename'];
        // $upload['thumb']['filename'] = "/res/fs".$upload['thumb']['filename'];

        // Set the value 
        $_POST[$name] = $upload;

        $value = $_POST[$name];
        return $GLOBALS['app']->update_setting($name, $value);
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
        $settings = new CobaltSettings(COBALT_BOOSTRAP_ALWAYS);
        $s = $settings->get_settings();
        $arr = [];
        /** @var CobaltSetting $details */
        foreach($settings->instances as $setting => $details) {
            if(!isset($details->meta['group'])) continue;
            $group = $details->meta['group'];
            $instance = $details->getTypedInstance();
            if(!$instance) continue;
            // This way 'General' settings always come first.
            $subgroup = "General";
            if(isset($details->meta['subgroup'])) $subgroup = $details->meta['subgroup'];
            if(!key_exists($group, $arr)) $arr[$group] = [];
            if(!key_exists($subgroup, $arr[$group])) $arr[$group]['General'] = '';
            $arr[$group][$subgroup] .= "<li>".$instance->getLabel() . $instance->field(). "</li>";
        }
        $nav = "";
        $list = "";
        foreach($arr as $group => $subgroups){
            $escapedGroup = url_fragment_sanitize(trim(strip_tags($group)));
            $nav .= "<a href=\"#$escapedGroup\">$group</a>";
            $list .= "<div id='$escapedGroup'>";
            foreach($subgroups as $subgroupName => $elements) {
                if(!$elements) continue;
                $list .= "<fieldset id='".url_fragment_sanitize($subgroupName)."'><legend>$subgroupName</legend><ul class='list-panel'>$elements</ul></fieldset>";
            }
            $list .= "</div>";
        }
        return view("Cobalt/Settings/templates/settings/basic-settings.php", [
            'nav' => $nav,
            'settings' => $list
        ]);
    }

    public function presentation() {
        add_vars([
            'title' => "Presentation",
        ]);

        return view("Cobalt/Settings/templates/settings/presentation.php");
    }
}