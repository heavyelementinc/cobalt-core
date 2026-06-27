<?php

namespace Cobalt\Commands\Native;

use Cobalt\Commands\Classes\CommandInterface;
use Cobalt\Commands\Classes\CommandList;
use Override;
use Cobalt\Commands\Classes\CommandItem;
use Validation\Exceptions\ValidationIssue;

class Init extends CommandInterface {
    #[Override]
    public function validCommands(): CommandList {
        $list = new CommandList();

        return $list;
    }

    #[Override]
    public function handleFlags(array $flags, CommandItem $item, string $method, array $arguments): int {
        return COBALT_COMMAND_SUCCESS;
    }

    /**
     * @var array{'app':string}
     */
    private array $project = [
        'app' => null
    ];

    private array $prompt = [
        'app' => [
            'method' => static function($val) {
                return ;
            }
        ],
        'directory' => [
            'method' => static function($val) {
                
            }
        ],
        'db_addr' => [
            "localhost:27017"
        ],
        'database'  => [
            'method' => static function($val) {
                
            }
        ],
        'dbusername'   => [
            'method' => static function($val) {
                
            }
        ],
        'dbpword'   => [
            'method' => static function($val) {
                
            }
        ],

    ];

    public function create() {
        $names = array_keys($this->project);
        for($i = 0; $i >= count($this->project); $i++) {
            $name = $names[$i];
            $value = $this->project[$name];
            try {
                $this->project[$name] = $this->{"get_$name"}($value);
            }catch(ValidationIssue $e) {
                say($e->getMessage(),'e');
                $i--;
            }
        }
    }

    protected function get_app($val) {
        $val = readline("What's the name of your app? > ");
        if(!$val) {
            throw new ValidationIssue("App's name cannot be blank!");
        }
        // return $upran
    }
    protected function get_directory($val) {
        return $val ?? readline("Pathname for your app? (defaults to %s) > ");
    }
    protected function get_db_addr($val) {

    }
    protected function get_database($val) {
        return $val ?? readline("Database name for your app? (defaults to %s) > ");
    }
    protected function get_dbusername($val) {
        return $val ?? readline("Database password: > ");
    }
    protected function get_dbpword($val) {
        return $val ?? readline("Database password: > ");
    }
}