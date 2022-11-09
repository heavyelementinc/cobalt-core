<?php

use Cobalt\Settings\Settings as CobaltSettings;

/**
 * @todo Do not display help items that require environment context if in pre-env
 */
class Settings {

    public $help_documentation = [
        'modified' => [
            'description' => "List all settings that have been modified by the user",
            'context_required' => true,
        ],
        'reset' => [
            'description' => "[name] Reset the specified setting to default",
            'context_required' => true
        ],
        'value' => [
            'description' => "[name] Get the current value of a setting",
            'context_required' => true
        ],
        'set' => [
            'description' => "[name] [value] Update a setting",
            'context_required' => true
        ],
        'push' => [
            'description' => "[name] [value] Push a value to an array (no duplicates)",
            'context_required' => true,
        ],
        'pull' => [
            'description' => "[name] [value] Pull a value from an array",
            'context_required' => true
        ]
    ];

    function __construct() {
        $this->settings = new CobaltSettings(true);
    }

    public function list() {
        
        $t = new \Render\CLITable();
        $t->head([
            'name' => [
                'title' => 'Name',
            ],
            'value' => [
                'title' => 'Value',
                'max' => 40
            ]
        ]);
        
        foreach ($this->settings->definitions as $name => $setting) {
            $t->row([
                'name' => $name,
                'value' => json_encode($this->settings->__settings->{$name})
            ]);
        }
        $t->render();
    }

    public function modified() {
        
        $t = new \Render\CLITable();
        $t->head([
            'name' => [
                'title' => 'Name',
            ],
            'value' => [
                'title' => 'Value',
                'max' => 40
            ]
        ]);
        $m_time = new stdClass;

        foreach ($this->settings->fetchModifiedSettings() as $name => $setting) {
            if($name == "Meta") {
                $m_time = $setting;
                continue;
            }
            $t->row([
                'name' => $name,
                'value' => json_encode($setting)
            ]);
        }
        $t->render();
        say("\nLast modified: " . date("Y-m-d h:i a", $m_time->max_m_time), "i");
    }

    public function value($name) {
        return json_encode($this->settings->__settings[$name]);
    }

    public function reset($name) {
        $this->settings->reset_to_default($name);
        return "Reset $name to default value";
    }

    public function set($name, $value = null) {
        $value = $this->value_parse($value);
        $this->settings->update_setting($name, $value);

        return "Updated \"$name\"";
    }

    public function push($name, $value = null) {
        $value = $this->value_parse($value);
        return json_encode($this->settings->push($name, $value));
    }

    public function pull($name, $value = null) {
        $value = $this->value_parse($value);
        return json_encode($this->settings->pull($name, $value));
    }

    private function value_parse($value = null) {
        if($value === null) $value = json_decode(readline("> "));
        if($value === "true") $value = true;
        if($value === "false") $value = false;
        if($value === "null") $value = null;
        if(ctype_digit($value)) $value = (int)$value;
        return $value;
    }
}
