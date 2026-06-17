<?php

use Cobalt\Settings\CobaltSetting;
use Cobalt\Settings\Settings;
use Controllers\ClientFSManager;
use Controllers\Controller;
use Drivers\FSManager;
use Exceptions\HTTP\BadRequest;
use MongoDB\BSON\ObjectId;

class CoreSettingsPanel extends Controller {
    use ClientFSManager;
    private $requiresRoot = ['Cache &amp; Debug'];
    private $settings = null;

    function settings_index($subset = null) {
        // $this->settings = jsonc_decode(file_get_contents(__ENV_ROOT__ . "/config/setting_definitions.jsonc"));
        global $app;
        $app->bootstrap();
        $this->settings = $app->instances;

        $setting_groups = [];
        $setting_tables = [];

        /**
         * @var int $index
         * @var CobaltSetting $setting
         */
        foreach($this->settings as $index => $setting) {
            if(!isset($setting->meta)) continue;
            if(in_array($setting->meta['group'], $this->requiresRoot) && !is_root()) continue;
            if($setting->meta['group'] === "") $setting->meta['group'] = "Troublesome";
            if(!isset($setting->meta['subgroup']) || $setting->meta['subgroup'] === "") $setting->meta['subgroup'] = "General";
            $url = $this->url_name($setting->meta['group']);
            if(!key_exists($setting->meta['group'],$setting_groups)) $setting_groups[$setting->meta['group']] = "<a href='#$url'>".$setting->meta['group']."</a>";

            if(!key_exists($setting->meta['group'],$setting_tables)) $setting_tables[$setting->meta['group']] = ["<form-request method='PUT' action='/api/v1/settings/update/' autosave='autosave' id='$url'>"];

            // Instance subgroups
            if(!isset($setting_tables[$setting->meta['group']][$setting->meta['subgroup']])) $setting_tables[$setting->meta['group']][$setting->meta['subgroup']] = "<h2>" . $setting->meta['subgroup'] . "</h2><ul class='list-panel'>";

            // `view` overrides `type`
            if (isset($setting->meta['view'])) {
                $setting_tables[$setting->meta['group']][$setting->meta['subgroup']] .= $this->get_input_from_view($setting, $index);
            } else if(isset($setting->meta['type'])) {
                $setting_tables[$setting->meta['group']][$setting->meta['subgroup']] .= $this->get_setting_table_entry($setting, $index, $url);
            }
            
        }

        unset($setting_groups['']);
        unset($setting_tables['']);
        
        foreach($setting_tables as $heading => $column) {
            $setting_tables[$heading] = implode("</ul>", $column) . "</ul>";
        }

        add_vars([
            'title' => 'Settings',
            'headings' => implode("", $setting_groups),
            'settings' => implode("</form-request>", $setting_tables) . "</form-request>"
        ]);

        return set_template("Cobalt/Settings/templates/settings/basic-settings.php");
    }


    private function url_name($name) {
        return strtolower(str_replace(
            [' ','&amp;'],
            '-',
            $name));
    }

    


    private function get_input_from_view($setting, $name) {
        $template = $setting->meta['view'];
        return view($template, [
            'setting' => $setting,
            'name' => $name,
            'value' => $setting->get_value()
        ]);
    }

    // TODO: allow dot-notated settings to be modified
    public function update() {
        $name  = array_keys($_POST)[0];
        $value = $_POST[$name];
        return $GLOBALS['app']->update_setting($name, $value);
    }

    public function theme_update() {
        $this->update();
        $settings = new Settings(true);
        add_vars(['app' => $settings->get_settings()]);
        update('style#theme-variables', [
            'innerHTML' => view('/shared/css_v2/color-theme.css')
        ]);
    }

    public function reset_to_default($name) {
        $split = explode(",",$name);
        $updated = [];
        /** @var SettingsManager $GLOBALS['app'] */
        foreach($split as $name) {
            $updated[] = $GLOBALS['app']->reset_to_default($name);
        }
        return $updated;
    }

    public function fileManager() {
        $man = new FSManager();
        $page = $_GET['page'] ?? "1";
        if(!ctype_digit($page)) throw new BadRequest("'page' parameter must be a digit");
        $page -= 1;
        $limit = 50;

        $result = $man->find([], ['sort' => ['_id' => -1], 'skip' => $limit * $page, 'limit' => $limit]);

        $html = "";
        foreach($result as $data) {
            $html .= $man->fromData($data);
        }

        $lastPage = $page - 1;
        if($lastPage < 0) $lastPage = 0;
        $nextPage = $page + 1;

        add_vars(['html' => $html, 'lastPage' => $lastPage, 'nextPage' => $nextPage]);

        return view("/admin/fs-manager.html");
    }
    // private function get_object($settings, $index, $url) {
    //     $object = "";
    //     foreach(__APP_SETTINGS__[$index][0] as $name => $value) {
    //         $object .= "<label>$name<input name='$name' value='$value' placeholder='$name'></label>";
    //     }
    //     return "
    //     <li>
    //         <label>".$settings->meta['name']."</label>
    //         <input-object-array name='$index' value='".json_encode(__APP_SETTINGS__[$index])."'>
    //             <template></template>
    //         </input-object-array>
    //         <button onclick='reset_to_default(".json_encode($settings->defaultValue).")' disabled='disabled'>Reset</button>
    //     </li>
    //     ";
    // }
}
