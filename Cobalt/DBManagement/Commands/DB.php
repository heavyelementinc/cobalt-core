<?php
namespace Cobalt\DBManagement\Commands;

use Cobalt\Auth\Users\Models\User;
use Cobalt\Commands\Attributes\AcceptsFlags;
use Cobalt\Commands\Attributes\CommandMethod;
use Cobalt\Commands\Attributes\Description;
use Cobalt\Commands\Attributes\Readline;
use Cobalt\Commands\Classes\CommandInterface;
use Cobalt\Commands\Classes\CommandItem;
use Cobalt\Commands\Classes\CommandList;
use Cobalt\DBManagement\Import;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\StringType;
use Drivers\DatabaseManagement;
use Exception;
use Override;
use Validation\Exceptions\ValidationIssue;

class DB extends CommandInterface {
    private array $flags = [];
    #[Override]
    public function validCommands(): CommandList {
        $list = new CommandList();
        $list->add(new CommandItem($this, 'list', 'list'));
        $list->add(new CommandItem($this, 'export', 'export'));
        $list->add(new CommandItem($this, 'import', 'import'));
        return $list;
    }

    #[Override]
    public function handleFlags(array $flags, CommandItem $item, string $method, array $arguments): int {
        if(key_exists("include", $flags) && key_exists("exclude", $flags)) {
            throw new Exception("Cannot accept both 'include' and 'exclude' flags.");
        }
        if(key_exists('f', $flags) && $flags['f']) $flags['force'] === true;
        $this->flags = [
            'database' => null,
            'include' => [],
            // 'exclude' => '',
            'force' => false,
            ...$flags
        ];
        if(key_exists("include", $flags)) $this->flags['include'] = explode(',',$flags['include']);
        return COBALT_COMMAND_SUCCESS;
    }

    #[Description( "[filename] Export a database backup.")]
    #[AcceptsFlags(
        "--database - Defaults to the application's database",
        "--include - A comma-delimited list of collections to import",
    )]
    public function list():int {
        throw new Exception("Not implemented");
    }

    #[Description( "[filename] Export a database backup.")]
    #[AcceptsFlags(
        "--database - Defaults to the application's database",
        "--include - A comma-delimited list of collections to import",
    )]
    public function export(?string $filename = null):int {
        $db = new DatabaseManagement(database: $this->flags['database']);
        $db->export($filename, false, true, true, []);
        return COBALT_COMMAND_SUCCESS;
    }

    #[Description("filename Import a database export")]
    #[AcceptsFlags(
        "--database - Defaults to the application's database",
        "--include - A comma-delimited list of collections to import",
    )]
    public function import(string $filename):int {
        $db = new Import(database: $this->flags['database']);
        $db->import($filename, true, true, $this->flags['include']);
        return COBALT_COMMAND_SUCCESS;
    }
}