<?php

use Controllers\Controller;

class CoreSettingsPanel extends Controller {
    private $requiresRoot = ['Cache &amp; Debug'];
    
    function settings_index() {
        $this->settings = jsonc_decode(file_get_contents(__ENV_ROOT__ . "/config/setting_definitions.jsonc"));

        $setting_groups = [];
        $setting_tables = [];

        foreach($this->settings as $index => $setting) {
            if(!isset($setting->manage)) continue;
            if(in_array($setting->manage->group, $this->requiresRoot) && !is_root()) continue;
            $url = $this->url_name($setting->manage->group);
            if(!key_exists($setting->manage->group,$setting_groups)) $setting_groups[$setting->manage->group] = "<a href='#$url'>".$setting->manage->group."</a>";

            if(!key_exists($setting->manage->group,$setting_tables)) $setting_tables[$setting->manage->group] = "<div id='$url'><ul class='list-panel'>";

            $setting_tables[$setting->manage->group] .= $this->get_setting_table_entry($setting, $index, $url);
            
        }

        add_vars([
            'title' => 'Miscellaneous Settings',
            'headings' => implode(           "", $setting_groups),
            'settings' => implode("</ul></div>", $setting_tables) . "</ul></div>"
        ]);

        return set_template("/admin/settings/basic-settings.html");
    }

    private function url_name($name) {
        return strtolower(str_replace(
            [' ','&amp;'],
            '-',
            $name));
    }

    private function get_setting_table_entry($setting, $index, $url) {
        switch($setting->manage->type) {
            case "input":
                return $this->get_input($setting, $index, $url);
            break;
            case "input-switch":
                return $this->get_checkbox($setting, $index, $url);
                break;
            case "input-array":
                return $this->get_array($setting, $index, $url);
                break;
            // case "input-object":
                // return $this->get_object($setting, $index, $url);
        }
        return "<li>Can't render \"$index\"</li>";
    }

    private function get_input($setting, $index, $url) {
        if(gettype($setting->default) === "bool") return $this->get_checkbox($setting, $index, $url);
        return "
        <li>
            <label>".$setting->manage->name."</label>
            <input type='input' name='$index' value='".__APP_SETTINGS__[$index]."' disabled='disabled'>
            <button onclick='reset_to_default(".json_encode($setting->default).")' disabled='disabled'>Default</button>
        </li>
        ";
    }

    private function get_checkbox($setting, $index, $url) {
        return "
        <li>
            <label>".$setting->manage->name."</label>
            <input-switch name='$index' checked='".json_encode(__APP_SETTINGS__[$index])."' disabled='disabled'></input-switch>
            <button onclick='reset_to_default(".json_encode($setting->default).")' disabled='disabled'>Default</button>
        </li>";
    }

    private function get_array($settings, $index, $url) {
        $options = "";
        foreach(__APP_SETTINGS__[$index] as $option) {
            $options.= "<option value='$option' selected='selected'>$option</option>";
        }
        return "
        <li>
            <label>".$settings->manage->name."</label>
            <input-array name='$index' disabled='disabled'>$options</input-array>
            <button onclick='reset_to_default(".json_encode($settings->default).")' disabled='disabled'>Default</button>
        </li>
        ";
    }

    // private function get_object($settings, $index, $url) {
    //     $object = "";
    //     foreach(__APP_SETTINGS__[$index][0] as $name => $value) {
    //         $object .= "<label>$name<input name='$name' value='$value' placeholder='$name'></label>";
    //     }
    //     return "
    //     <li>
    //         <label>".$settings->manage->name."</label>
    //         <input-object-array name='$index' value='".json_encode(__APP_SETTINGS__[$index])."'>
    //             <template></template>
    //         </input-object-array>
    //         <button onclick='reset_to_default(".json_encode($settings->default).")' disabled='disabled'>Reset</button>
    //     </li>
    //     ";
    // }
}