<?php

/**
 * @todo Do not display help items that require environment context if in pre-env
 */
class Settings {

    // public $help_documentation = [
    //     'modified' => [
    //         'description' => "List all settings that have been modified by the user",
    //         'context_required' => true,
    //     ],
    //     'reset' => [
    //         'description' => "[name] Reset the specified setting to default",
    //         'context_required' => true
    //     ],
    //     'value' => [
    //         'description' => "[name] Get the current value of a setting",
    //         'context_required' => true
    //     ],
    //     'set' => [
    //         'description' => "[name] [value] Update a setting",
    //         'context_required' => true
    //     ],
    //     'push' => [
    //         'description' => "[name] [value] Push a value to an array (no duplicates)",
    //         'context_required' => true,
    //     ],
    //     'pull' => [
    //         'description' => "[name] [value] Pull a value from an array",
    //         'context_required' => true
    //     ]
    // ];

    public function modified() {
        
        $t = new \Render\CLITable();
        $t->head([
            'name' => [
                'title' => 'Name',
            ],
            'value' => [
                'title' => 'Value'
            ]
        ]);
        
        foreach ($GLOBALS['app']->__settings as $setting) {
            $t->row([
                'name' => $setting->meta['name'],
                'value' => $setting->value
            ]);
        }
        $t->render();
    }

    public function reset($name) {
        $GLOBALS['app']->reset_to_default($name);
        return "Reset $name to default value";
    }

    public function set($name) {
        $value = $this->value_parse();
        $GLOBALS['app']->update_setting($name, $value);
        return "Updated $name";
    }

    public function push($name, $value) {
        $value = $this->value_parse();
        return $GLOBALS['app']->push($name, $value);
    }

    public function pull($name, $value) {
        $value = $this->value_parse();
        return $GLOBALS['app']->pull($name, $value);
    }

    private function value_parse() {
        $value = readline("> ");
        if($value === "true") $value = true;
        if($value === "false") $value = false;
        if(ctype_digit($value)) $value = (int)$value;
        return $value;
    }
}
