<?php

namespace Cobalt\Settings\Define;

use Closure;
use Cobalt\Manifests\Enums\ValidTypes;
use Cobalt\Settings\CobaltSetting;

class DefineSettings {
    private mixed $default;
    private string $key;
    public DefineMeta $meta;
    public DefineValidate $validate;

    function __construct(string $name) {
        $this->name = $name;
    }

    static function define(string $name):DefineSettings {
        return new static($name);
    }

    public function get_key():string {
        return $this->key;
    }

    public function get_default():mixed {
        return $this->default;
    }
    public function set_default(mixed $value):DefineSettings {
        $this->default = $value;
        return $this;
    }

    static function get_setting_table_entry(CobaltSetting $setting, $index, $url) {
        $template = false;
        $type = FieldTypes::input;
        $options = "";
        switch($setting->meta['type']) {
            case FieldTypes::input:
                $template = "/Cobalt/Settings/templates/settings/inputs/input.php";
                $type = "text";
                break;
            case FieldTypes::url:
                $template = "/Cobalt/Settings/templates/settings/inputs/input.php";
                $type = "url";
                break;
            case FieldTypes::number:
            // case "input-number":
                $template = "/Cobalt/Settings/templates/settings/inputs/number.php";
                $type = "number";
                break;
            case FieldTypes::textarea:
                $template = "/Cobalt/Settings/templates/settings/inputs/textarea.php";
                $type = "text";
                break;
            case FieldTypes::password:
                $template = "/Cobalt/Settings/templates/settings/inputs/password.php";
                break;
            // case "input-switch":
            // case "boolean":
            // case "bool":
            case FieldTypes::bool:
                $template = "/Cobalt/Settings/templates/settings/inputs/bool.php";
                break;
            // case "input-array":
            case FieldTypes::array:
                $template = "/Cobalt/Settings/templates/settings/inputs/array.php";
                $options = "";
                $current = array_combine(__APP_SETTINGS__[$index], __APP_SETTINGS__[$index]);
                $opts = array_merge($current, static::get_options($setting));
                foreach($opts as $key => $option) {
                    $selected = "";
                    if(in_array($key, $current)) $selected = " selected='selected'";
                    $options .= "<option value='$option'$selected>$option</option>";
                }
                break;
            // case "radio":
            case FieldTypes::radio:
                $template = "/Cobalt/Settings/templates/settings/inputs/radio-group.php";
                $options = "";
                foreach(static::get_options($setting) as $name => $display) {
                    $options .= "<label>
                        <span class='cobalt-radio-group--select-target'>$display</span>
                        <input type='radio' name='$index' value='$name'>
                    </label>";
                }
                break;
            // case "input-binary":
            case FieldTypes::binary:
                $template = "/Cobalt/Settings/templates/settings/inputs/input-binary.php";
                $options = "";
                $opts = static::get_options($setting);
                foreach($opts as $key => $option) {
                    $selected = "";
                    if($key & __APP_SETTINGS__[$index]) $selected = " selected='selected'";
                    $options .= "<option value='$key'$selected><span>$option</span></option>";
                }
                break;
            // case "select":
            case FieldTypes::select:
                $template = "/Cobalt/Settings/templates/settings/inputs/select.php";
                $options = "";
                foreach(static::get_options($setting) as $valid => $label) {
                    $checked = "";
                    if($valid === __APP_SETTINGS__[$index]) $checked = " selected='selected'";
                    $options .= "<option value='$valid'$checked>$label</option>\n";
                }
            case FieldTypes::date:
                $template = "/Cobalt/Settings/templates/settings/inputs/date.php";
                break;
        }
        if($template) return view($template,[
            'name' => $setting->meta['name'],
            'setting' => $index,
            'value' => __APP_SETTINGS__[$index],
            'default' => $setting->defaultValue,
            'small' => ($setting->meta['description']) ? "<small>".$setting->meta['description']."</small>" : "",
            'help' => ($setting->meta['help']) ? "<help-span value=\"".htmlentities($setting->meta['help'])."\"></help-span>" : "",
            'type' => $type,
            'disabled' => '',
            'options' => $options,
            'reset' => view("Cobalt/Settings/templates/settings/inputs/reset.php", ['setting' => $index, 'name' => $setting->meta['name'], 'value' => __APP_SETTINGS__[$index]]),
        ]);
        return "<li>Can't render \"$index\"</li>";
    }

    static function get_options(CobaltSetting $setting):array {
        $option = $setting->validate->options;
        if(is_array($option)) return $option;
        if(is_callable($option)) return $option($setting);
        return [];
    }

    private FieldTypes $field;
    private array $options = [];
    private string $confirm = "";
    private array $filters = [];
    
    public function get_field() {
        return $this->field;
    }
    public function set_field(FieldTypes $value):DefineSettings {
        $this->field = $value;
        return $this;
    }

    public function get_opts() {
        return $this->options;
    }
    public function set_opts(array $value):DefineSettings {
        $this->options = $value;
        return $this;
    }

    public function get_confirm() {
        return $this->confirm;
    }
    public function set_confirm(string $value):DefineSettings {
        $this->confirm = $value;
        return $this;
    }

    public function get_filter() {
        return $this->filters;
    }
    public function set_filter(array $value):DefineSettings {
        $this->filters = $value;
        return $this;
    }


    private ?string $group = null;
    private ?string $subgroup = null;
    private ?string $name = null;
    private ?string $description = null;
    private ?string $help = null;
    private ValidTypes $type;

    public function get_group() {
        return $this->group;
    }
    public function set_group(string $value):DefineSettings {
        $this->group = $value;
        return $this;
    }
    public function get_subgroup() {
        return $this->subgroup;
    }
    public function set_subgroup(string $value):DefineSettings {
        $this->subgroup = $value;
        return $this;
    }
    public function get_name() {
        return $this->name;
    }
    public function set_name(string $value):DefineSettings {
        $this->name = $value;
        return $this;
    }
    public function get_description() {
        return $this->description;
    }
    public function set_description(string $value):DefineSettings {
        $this->description = $value;
        return $this;
    }
    public function get_help() {
        return $this->help;
    }
    public function set_help(string $value):DefineSettings {
        $this->help = $value;
        return $this;
    }
    public function get_type() {
        return $this->type;
    }
    public function set_type(ValidTypes $value):DefineSettings {
        $this->type = $value;
        return $this;
    }
}