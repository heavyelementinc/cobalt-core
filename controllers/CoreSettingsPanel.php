<?php

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

        return set_template("/admin/settings/basic-settings.html");
    }


    private function url_name($name) {
        return strtolower(str_replace(
            [' ','&amp;'],
            '-',
            $name));
    }

    private function get_setting_table_entry($setting, $index, $url) {
        $template = false;
        $type = "input";
        $options = "";
        switch($setting->meta['type']) {
            case "input":
                $template = "/admin/settings/inputs/input.html";
                $type = "text";
                break;
            case "number": 
                $template = "/admin/settings/inputs/number.html";
                $type = "number";
                break;
            case "textarea":
                $template = "/admin/settings/inputs/textarea.html";
                $type = "text";
                break;
            case "password":
                $template = "/admin/settings/inputs/password.html";
                break;
            case "input-switch":
                $template = "/admin/settings/inputs/bool.html";
                break;
            case "input-array":
                $template = "/admin/settings/inputs/array.html";
                $options = "";
                $current = array_combine(__APP_SETTINGS__[$index], __APP_SETTINGS__[$index]);
                $opts = array_merge($current, $setting->validate['options']);
                foreach($opts as $key => $option) {
                    $selected = "";
                    if(in_array($key, $current)) $selected = " selected='selected'";
                    $options .= "<option value='$option'$selected>$option</option>";
                }
                break;
            case "radio-group":
                $template = "/admin/settings/inputs/radio-group.html";
                $options = "";
                foreach($setting->validate['options'] as $name => $display) {
                    $options .= "<label>
                        <span class='cobalt-radio-group--select-target'>$display</span>
                        <input type='radio' name='$index' value='$name'>
                    </label>";
                }
                break;
            case "select":
                $template = "/admin/settings/inputs/select.html";
                $options = "";
                foreach($setting->validate['options'] as $valid => $label) {
                    $checked = "";
                    if($valid === __APP_SETTINGS__[$index]) $checked = " selected='selected'";
                    $options .= "<option value='$valid'$checked>$label</option>\n";
                }
        }
        if($template) return view($template,[
            'name' => $setting->meta['name'],
            'setting' => $index,
            'value' => __APP_SETTINGS__[$index],
            'default' => $setting->defaultValue,
            'type' => $type,
            'disabled' => '',
            'options' => $options,
        ]);
        return "<li>Can't render \"$index\"</li>";
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

    public function updateLogo() {
        $name  = array_keys($_POST)[0];
        if(empty($_FILES)) throw new BadRequest("Must specify a file");
        
        $name = "logo";
        // $_FILES[$name]['name'][0] = "masthead.png";

        // First, let's find the current masthead
        $query = ['masthead' => true];

        // $cleanup = __APP_SETTINGS__[$name];

        // Delete the current masthead
        // if($cleanup) $this->delete($cleanup);

        // Now, let's get ready to insert our new logo
        $this->fs_filename_path = "/settings/masthead/";

        $content = mime_content_type($_FILES[$name]['tmp_name'][0]);

        // Upload it to the database
        if($content === "image/svg+xml") {
            $upload = [
                'media' => $this->clientUploadFile($name, 0, $query, $_FILES)
            ];
            $upload['thumb'] = $upload['media'];
        } else {
            $upload = $this->clientUploadImageThumbnail($name, 0, 300, null, $query, $_FILES);
        }
        
        $upload['media']['filename'] = "/res/fs".$upload['media']['filename'];
        $upload['thumb']['filename'] = "/res/fs".$upload['thumb']['filename'];

        // Set the value 
        $_POST[$name] = $upload;

        $value = $_POST[$name];
        return $GLOBALS['app']->update_setting($name, $value);
    }

    public function reset_to_default($name) {
        return $GLOBALS['app']->reset_to_default($name);
    }

    public function presentation() {
        add_vars([
            'title' => "Presentation",
        ]);

        return set_template("/admin/settings/presentation.html");
    }

    public function fileManager() {
        $man = new FSManager();
        $page = $_GET['page'] ?? "1";
        if(!ctype_digit($page)) throw new BadRequest("'page' parameter must be a digit");
        $page -= 1;
        $limit = 50;

        $result = $man->find([], ['sort' => ['_id' => 1], 'skip' => $limit * $page, 'limit' => $limit]);

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
