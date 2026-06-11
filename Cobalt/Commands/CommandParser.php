<?php
namespace Cobalt\Commands;

use Cobalt\Commands\Native\Help;
use Cobalt\Commands\Traits\ErrorHandler;
use Exception;
use Override;

class CommandParser {
    // use ErrorHandler;
    const BUILT_IN_COMMANDS = __DIR__ . "/built_in_commands.php";
    const APP_COMMANDS = __APP_ROOT__ . "/config/commands.php";
    private array $commands = [];

    function __construct() {
        // $this->define_known_errors();
    }

    // #[Override]
    // public function define_known_errors(): array {
    //     return [
    //         ''
    //     ];
    // }

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

    const ERR_COMMAND_NOT_FOUND   = 1;
    const ERR_METHOD_NOT_FOUND    = 2;
    const ERR_MISSING_ARGUMENTS   = 3;
    const ERR_INVALID_RETURN_TYPE = 4;

    function exec(array $command, array $flags) {
        $name = array_shift($command);
        // If there's no name, default to "help"
        if(!$name) $name = "help";
        if(!key_exists($name, $this->commands)) {
            say("Command not found", "e");
            return self::ERR_COMMAND_NOT_FOUND;
        }

        $method = array_shift($command);
        if($name === "help") {
            if(!$method) $method = "list";
            else if($method !== "list") {
                array_unshift($command, $method);
                $method = "list";
            }
        }

        $cmd = $this->commands[$name];
        
        $commandMethods = $cmd->validCommands();
        $commandItem = $commandMethods->findByCommandName($method);
        if(!$commandItem) {
            say("Invalid command", "e");
            return self::ERR_METHOD_NOT_FOUND;
        }
        $commandItem->exec($command, $flags);
        
        return 0;
    }
}