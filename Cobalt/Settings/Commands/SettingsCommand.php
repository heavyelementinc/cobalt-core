<?php

namespace Cobalt\Settings\Commands;

use Cobalt\Commands\Attributes\Description;
use Cobalt\Commands\Classes\CommandInterface;
use Cobalt\Commands\Classes\CommandList;
use Override;
use Cobalt\Commands\Classes\CommandItem;
use Cobalt\Settings\Settings;
use Exception;

class SettingsCommand extends CommandInterface {
    #[Override]
    public function validCommands(): CommandList {
        $list = new CommandList();
        $list->add(new CommandItem($this, 'cache', 'cache'));
        return $list;
    }

    #[Override]
    public function handleFlags(array $flags, CommandItem $item, string $method, array $arguments): int {
        return COBALT_COMMAND_SUCCESS;
    }

    #[Description("[\"rebuilt\"] Clears the settings cache")]
    public function cache(string $order = "rebuild") {
        switch($order) {
            // case "rebuild":
            //     break;
            case "rebuild":
            default:
                return $this->rebuilt();
        }
    }

    private function rebuilt():int {
        $s = new Settings();
        $s->bootstrap(true);
        return COBALT_COMMAND_SUCCESS;
    }

}