<?php
namespace Cobalt\Commands;

use Exception;

class CommandParser {

    const BUILT_IN_COMMANDS = __DIR__ . "/built_in_commands.php";
    const APP_COMMANDS = __APP_ROOT__ . "/config/commands.php";
    private array $commands = [];
    
    function __construct() {
        
    }

    function get_commands():array {
        return $this->commands;
    }

    function load_files() {
        $built_in_commands = include self::BUILT_IN_COMMANDS;
        if(!is_array($built_in_commands)) {
            throw new Exception("Failed to load commands");
        }
        $this->commands += $built_in_commands;

        if(!file_exists(self::APP_COMMANDS)) return;

        $app_commands = include self::APP_COMMANDS;
        if(!is_array($built_in_commands)) {
            throw new Exception("Failed to load commands");
        }

        $this->commands += $built_in_commands;
    }

    function exec(array $command, array $flags) {
        $name = array_shift($command);
        // If there's no name, default to "help"
        if(!$name) $name = "help";
        if(!key_exists($name, $this->commands)) {
            say("Command not found", "e");
        }

        $method = array_shift($command);
        if(!$method && $name === "help") $method = "list";

        
    }
}