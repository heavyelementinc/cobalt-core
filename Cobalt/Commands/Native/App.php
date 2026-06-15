<?php

namespace Cobalt\Commands\Native;

use Cache\Manager;
use Cobalt\Commands\Attributes\AcceptsFlags;
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
        $list->add(new CommandItem($this, 'cache', 'cache'));
        $list->add(new CommandItem($this, 'extensions', 'extensions'));
        return $list;
    }

    public function handleFlags(array $flags, CommandItem $item, string $method, array $arguments): int {
        $this->flags = $flags;
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

    const DIR = __APP_ROOT__ . "/cache/";
    const CUR = self::DIR . '/cache/' . __COBALT_VERSION . "/" . __APP_SETTINGS__['version'];

    #[Description("Prompt the cache to rebuild")]
    #[AcceptsFlags(
        // "--prune - Deletes all old compiled templates, JS, and CSS",
        "--all - Deletes all files in the current cache",
        "--views - Deletes all compiled views in the current cache",
        "--css - Deletes all CSS files in the current cache",
        "--js - Deletes all JS files in the current cache",
    )]
    function cache():int {
        // if(key_exists('prune', $this->flags)) $this->prune();
        if(key_exists('all', $this->flags)) {
            say('Deleting cache directory: '.self::DIR);
            unlink(self::DIR);
            say("Creating cache directory: ".self::DIR);
            mkdir(self::DIR);
            return COBALT_COMMAND_SUCCESS;
        }

        $count = 0;
        
        if(key_exists('css', $this->flags)) {
            $count += 1;
            say("Clearing CSS cache");
            unlink(self::CUR . "/css-precomp/");
        }
        
        if(key_exists('js', $this->flags)) {
            $count += 1;
            say("Clearing JS cache");
            unlink(self::CUR . "/js-precomp/");
        }

        if(key_exists('views', $this->flags)) {
            $count += 1;
            say("Clearing view cache");
            unlink(self::CUR . "/compiled/");
            unlink(self::CUR . "/template-precomp/");
        }
        if(!$count) {
            say('You must specify at least one of the following flags: --all, --js, --css, --views');
            return COBALT_COMMANT_UNKNOWN_ERR;
        }
        return COBALT_COMMAND_SUCCESS;
    }

    // private function prune() {
        
    //     return COBALT_COMMAND_SUCCESS;
    // }
}