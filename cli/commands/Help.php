<?php

class Help{

    public $help_documentation = [
        'all' => [
            'description' => "List ALL supported commands and subcommands."
        ],
        '<command>' => [
            'description' => "List every subcommand of given command where <command> is the name of any valid command."
        ]
    ];

    protected $help_items = [];
    protected $rendered = [];
    protected $do_all = true;

    protected $max_command_name_char_length = 0;
    protected $max_arg_list_char_length = 0;

    function __construct($mode = false){
        $this->help_mode = $mode;
    }

    function __call($name, $arguments){
        $this->do_all = false;
        $this->build_class_table($name);
    }

    function all(){
        $this->do_all = false;
        $this->class_table_all();
    }

    private function build_class_table($class){
        $command = strtolower($class);
        $capitalized = ucfirst($class);
        $className = __CLI_ROOT__ . "/commands/$capitalized.php";
        if(!file_exists($className)) {
            say("Unrecognized command","e");
            exit;
        }
        require_once $className;
        $c = new $capitalized("help");
        $this->help_items[$command] = $c->help_documentation;
        foreach($this->help_items[$command] as $subcmd => $items){
            // Establish the max char length of a subcommand so we can present a
            // nice table in the CLI
            if(strlen($subcmd) > $this->max_command_name_char_length) {
                $this->max_command_name_char_length = strlen($subcmd) + 2;
            }

            // Similar to the above but for argument list
            if(key_exists("args",$items) && strlen($items['args']) > $this->max_arg_list_char_length) {
                $this->max_arg_list_char_length = strlen($items['args']);
            }
        }
    }

    private function class_table_all(){
        $command_files = scandir(__CLI_ROOT__ . "/commands/");
        foreach($command_files as $file){
            if($file === "." || $file === "..") continue;
            $cmd = pathinfo($file,PATHINFO_FILENAME);
            $this->build_class_table($cmd);
        }
    }

    private function display_help_table(){
        $help = "";
        foreach($this->help_items as $command => $items){
            $help .= "[ ".fmt($command,"b")." ]\n";
            foreach($items as $subcmd => $misc){
                $help .= "   " . fmt(str_pad($subcmd,$this->max_command_name_char_length),"b");
                $help .= fmt($misc["description"],"i");
                $help .= "\n";
            }

            $help .= "\n";
        }

        print($help);
    }

    function __destruct(){
        $this->display_help_table();
    }

}