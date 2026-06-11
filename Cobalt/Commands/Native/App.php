<?php

namespace Cobalt\Commands\Native;

use Cobalt\Commands\Attributes\Description;
use Cobalt\Commands\Classes\CommandInterface;
use Cobalt\Commands\Classes\CommandItem;
use Cobalt\Commands\Classes\CommandList;
use Cobalt\Extensions\Extensions;
use Exception;
use MongoDB\Collection;
use Override;

class App extends CommandInterface {
    public function validCommands(): CommandList {
        $list = new CommandList();

        $list->add((new CommandItem($this, 'config', 'config'))
            ->setDescription("Get or set configuration details")
        );
        $list->add(new CommandItem($this, 'extensions', 'extensions'));
        return $list;
    }

    public function handleFlags(array $flags, CommandItem $item, string $method, array $arguments): int {
        return COBALT_COMMAND_SUCCESS;
    }
    
    #[Description("Get or set configuration details")]
    function config(string $file, mixed $value = null) {
        throw new Exception("Not implemented");
    }

    function get(string $setting) {
        throw new Exception("Not implemented");
    }

    #[Description("Disable all extensions")]
    function extensions(string $subcommand = "disable", string $arg = "all"):int {
        switch($subcommand) {
            default:
                return $this->disable_extensions($arg);
        }
    }

    private function disable_extensions($arg) {
        switch($arg) {
            case 'all':
                break;
            default:
                throw new Exception("Argument must specify 'all'");
        }
        $ext = new Extensions(true);
        // $results = $ext->find(['is_option' => ['$ne' => true]]);
        $ext->collection->drop();
        return COBALT_COMMAND_SUCCESS;
    }
}