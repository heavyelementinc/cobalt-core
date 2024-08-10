<?php

/**
 * @todo Do not display help items that require environment context if in pre-env
 */
class Help {

    public $help_documentation = [
        'all' => [
            'description' => "List ALL supported commands and subcommands.",
            'context_required' => false,
        ],
        '<command>' => [
            'description' => "List every subcommand of given command where <command> is the name of any valid command.",
            'context_required' => false
        ],
        'flags' => [
            'description' => "List known flags.",
            'context_required' => false
        ]
    ];

    public $known_flags = [];

    protected $help_items = [];
    protected $rendered = [];
    protected $do_all = true;

    protected $max_command_name_char_length = 0;
    protected $max_arg_list_char_length = 0;
    protected $max_flag_name_char_length = 0;

    function __construct($mode = false) {
        $this->help_mode = $mode;
    }

    function __call($name, $arguments) {
        $this->do_all = false;
        $this->build_class_table($name);
    }

    function all() {
        $this->do_all = false;
        $this->class_table_all();
    }

    function flags() {
        log_item("Building help table for flags");
        $this->known_flags['supported flags'] = $GLOBALS['flags'];
        foreach ($this->known_flags as $flag => $meta) {
            if (strlen($flag) > $this->max_flag_name_char_length) {
                $this->max_flag_name_char_length = strlen($flag) + 2;
            }
        }
        $this->display_help_table("known_flags", "flag");
    }

    private function build_class_table($class) {
        log_item("Building help table for \"$class\"");
        $command = strtolower($class);
        $capitalized = ucfirst($class);
        // $className = __CLI_ROOT__ . "/commands/$capitalized.php";
        $pathToClass = VALID_COMMANDS[$capitalized]['path'];
        // if (!file_exists($className)) {
        //     if (!defined("__APP_ROOT__")) {
        //         say("Unrecognized command", "e");
        //         exit;
        //     }
        //     $className = __APP_ROOT__ . "/cli/commands/$capitalized.php";
        //     if (!file_exists($className)) {
        //         say("Unrecognized command", "e");
        //         exit;
        //     }
        // }
        if(!$pathToClass) return;
        require_once $pathToClass;
        $c = new $capitalized("help");
        $this->help_items[$command] = $c->help_documentation;
        foreach ($this->help_items[$command] as $subcmd => $items) {
            // Establish the max char length of a subcommand so we can present a
            // nice table in the CLI
            if (strlen($subcmd) > $this->max_command_name_char_length) {
                $this->max_command_name_char_length = strlen($subcmd) + 2;
            }

            // Similar to the above but for argument list
            if (key_exists("args", $items) && strlen($items['args']) > $this->max_arg_list_char_length) {
                $this->max_arg_list_char_length = strlen($items['args']);
            }
        }
    }

    private function class_table_all() {
        foreach (VALID_COMMANDS as $commandName => $file) {
            $file = $file['path'];
            if ($file === "." || $file === "..") continue;
            $cmd = pathinfo($file, PATHINFO_FILENAME);
            $this->build_class_table($cmd, $file);
        }
    }

    private function display_help_table($index = "help_items", $max_length_name = "command") {
        $help = "";
        foreach ($this->{$index} as $command => $items) {
            $help .= "[ " . fmt($command, "b") . " ]\n";
            foreach ($items as $subcmd => $misc) {
                $max_length = "max_" . $max_length_name . "_name_char_length";
                $help .= "   " . fmt(str_pad($subcmd, $this->{$max_length}), "b");
                $help .= fmt($misc["description"], "i");
                $help .= "\n";
            }

            $help .= "\n";
        }

        print($help);
    }

    function __destruct() {
        $this->display_help_table();
    }
}
