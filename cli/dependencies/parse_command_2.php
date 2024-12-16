<?php
/**
 * @todo FINISH THIS
 */

// core.sh project init --something --something-else
$commands = $argv;
array_shift($commands); // Pop the core.sh off the front

$GLOBALS['CLI'] = new CobaltCLI($commands);
$GLOBALS['CLI']->exec();

class CobaltCLI{
    public $parsed = [
        'command' => null,
        'args'    => [],
        'flags'   => [],
        'globals' => [],
    ];

    function __construct($commands) {
        if(empty($commands)) kill("No command specified!");
        if($this->find_command(array_shift($commands), $commands)) continue;
        $this->subcommand = $commands[0];
        $this->remaining_commands = $commands;
        
    }

    function exec() {
        $commands = $this->remaining_commands;
        foreach($commands as $i => $arg) {
            if($this->find_args($arg, $commands)) continue;
            if($this->find_globals($arg,$commands)) continue;
            if($this->find_flags($arg, $commands)) continue;
        }
        $this->run();
    }

    function run() {
        $class = $this->parsed['command'];
        $this->instance = new $class();
        if(!method_exists($this->instance,"main")) kill("No entry point 'main' for command.");
        foreach($this->flags as $name => $value) {
            if(!is_numeric($name)) {
                if(method_exists($this->instance,$name)) $this->instance->{$name}(...$value);
                continue;
            }
            if(method_exists($this->instance, $value)) $this->instance->{$value}();
        }
        $this->instance->main(...$this->parsed['args']);
    }
    
    function find_command($candidate, $command) {
        $filename = __CLI_ROOT__ . "/commands/".strtolower($candidate).".php";
        if(file_exists($filename)) {
            require_once $filename;
            $this->parsed['command'] = "Cobalt\\CLI\\$candidate";
            return true;
        }
        return false;
    }
    
    function find_args($candidate, $command) {
        
    }
    
    function find_flags($candidate, $command) {
    
        return true;
    }
    
    function find_globals($candidate, $command) {
    
    }
    
    function parse_flag($candidate, $command) {
        
    }
    
    const VALID_CLI_GLOBALS = [
        
    ];
}