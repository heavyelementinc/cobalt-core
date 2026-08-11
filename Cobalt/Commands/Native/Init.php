<?php

namespace Cobalt\Commands\Native;

use Cobalt\Commands\Attributes\AcceptsFlags;
use Cobalt\Commands\Attributes\Description;
use Cobalt\Commands\Classes\CommandInterface;
use Cobalt\Commands\Classes\CommandList;
use Override;
use Cobalt\Commands\Classes\CommandItem;
use Validation\Exceptions\ValidationIssue;

class Init extends CommandInterface {
    #[Override]
    public function validCommands(): CommandList {
        $list = new CommandList();
        $list->add(new CommandItem($this, 'app', 'app'));
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
        'app' => null,
        'directory' => null,
        'database' => null,
        'db_addr' => "localhost:27017",
        'dbusername' => null,
        'dbpword' => null,
    ];

    private array $prompt = [
        'app' => [
            'method' => static function(&$val, &$project) {
                $is_valid = ctype_alnum($val);
                if($is_valid) $project['directory'] = $val;
                return $is_valid;
            }
        ],
        'directory' => [
            'method' => static function(&$val, &$project) {
                
            }
        ],
        'db_addr' => [
            'method' => static function (&$val, &$project) {

            }
        ],
        'database'  => [
            'method' => static function(&$val, &$project) {
                
            }
        ],
        'dbusername'   => [
            'method' => static function(&$val, &$project) {
                
            }
        ],
        'dbpword'   => [
            'method' => static function(&$val, &$project) {
                
            }
        ],
    ];

    #[Description('Create a new Cobalt application')]
    #[AcceptsFlags('-f - Skip validation checks',
    '--app - Application name',
    '--directory - Application directory',
    '--db_addr - Database address (defaults to "localhost:27017")',
    '--database',
    '--dbusername',
    '--dbpword',
    )]
    public function app() {
        $names = array_keys($this->project);
        $args = func_get_args();
        for($i = 0; $i >= count($this->project); $i++) {
            $name = $names[$i];
            $value = $this->flags[$name] ?? $args[$i] ?? $this->project[$name];
            if(!$this->prompt[$name]['method']($value, $this->project)) {
                $i--;
                continue;
            }
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