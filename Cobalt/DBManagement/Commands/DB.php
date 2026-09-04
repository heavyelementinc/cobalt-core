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
use Cobalt\Commands\Exceptions\CommandError;
use Cobalt\DBManagement\Import;
use Cobalt\Model\Model;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\StringType;
use Drivers\DatabaseManagement;
use Error;
use Exception;
use Override;
use Validation\Exceptions\ValidationIssue;

class DB extends CommandInterface {
    protected array $flags = [];
    #[Override]
    public function validCommands(): CommandList {
        $list = new CommandList();
        $list->add(new CommandItem($this, 'list',    'list'));
        $list->add(new CommandItem($this, 'export',  'export'));
        $list->add(new CommandItem($this, 'import',  'import'));
        $list->add(new CommandItem($this, 'migrate', 'migrate'));
        $list->add(new CommandItem($this, 'init',    'init'));
        return $list;
    }

    #[Override]
    public function handleFlags(array $flags, CommandItem $item, string $method, array $arguments): int {
        if(key_exists("include", $flags) && key_exists("exclude", $flags)) {
            throw new CommandError("Cannot accept both 'include' and 'exclude' flags.");
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
        $db = new DatabaseManagement(database: $this->flags['database']);
        $collections = $db->collections();
        $list = [];
        $nameMax = 0;
        $typeMax = 0;
        foreach($collections as $idx => $item) {
            $list[$idx] = [
                'name' => $item->getName(),
                'type' => $item->getType(),
                'count' => $db->getCollection($item->getName())->count([])
            ];
            $nameLength = strlen($list[$idx]['name']);
            $typeLength = strlen($list[$idx]['type']);
            $nameMax = ($nameLength > $nameMax) ? $nameLength : $nameMax;
            $typeMax = ($typeLength > $typeMax) ? $typeLength : $typeMax;
        }
        foreach($list as $idx => $item) {
            sprintf(" - %s %s %s\n", 
            fmt(str_pad($item['name'], $nameMax), 'i'),
            str_pad($item['type'], $typeMax), 
            fmt($item['count'], "i")
            );
        }
        return COBALT_COMMAND_SUCCESS;
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

    #[Description("(namespaced_model, [\"force\"]) Migrate all documents in a collection to a new model")]
    public function migrate(string $namespaced_model, string $skip = ""):int {
        $t = microtime(true);
        // Check if namespace exists
        try {
            /** @var Model $model */
            $model = new $namespaced_model;
        } catch (Error $e) {
            $this->failed_instantiation($e);
            return self::CONVERT_FAIL;
        } catch (Exception $e) {
            $this->failed_instantiation($e);
            return self::CONVERT_FAIL;
        }

        // Load and verify that the namespace is a Model
        if ($model instanceof Model === false) {
            // If it's *not* a model, fail gracefully
            say("$namespaced_model is not an instance of Model", 'e');
            return self::CONVERT_FAIL;
        }
        $limit = $model->count([]);
        $chars = strlen($limit);
        $progress = 0;
        $skipped = 0;
        $db = new DatabaseManagement();

        try {
            print("\nCollection \"" . $model->getCollectionName() . "\" contains $limit documents.\n");

            foreach ($db->convert($model, !$skip) as $result) {
                $this->convert_reporting($db, $result, $limit, $skipped, $progress, $chars);
            }
            print("\n");
        } catch (Error $e) {
            say($e->getMessage(), 'e');
            return self::CONVERT_FAIL;
        }
        $delta = microtime(true) - $t;
        print(" > Completed in " . fmt(round($delta, 2), "i") . " seconds\n");
        return self::CONVERT_SUCCESS;
    }

    private function failed_instantiation($e) {
        return say("Failed to instance model: " . $e->getMessage());
    }

    
    const CONVERT_SUCCESS = 0;
    const CONVERT_FAIL = 1;

    private function convert_reporting(DatabaseManagement $db, array $result, int $limit, int &$skipped, int &$progress, int $chars) {

        // Check if we should increment skip
        switch ($result['type']) {
            case $db::CONVERT_TYPE_SKIP:
                $skipped += $result['value'];
                break;
            case $db::CONVERT_TYPE_UPDATE:
                $progress += $result['value'];
                break;
            case $db::CONVERT_TYPE_DONE:
                return self::CONVERT_FAIL; // 1 means failure
            default:
                say("Something went wrong. Type was `$result[value]`");
                return self::CONVERT_FAIL; // 1 means failure
        }
        print("\r");
        $p = fmt(str_pad($progress, $chars, " ", STR_PAD_LEFT), "s");
        $s = fmt(str_pad($skipped,  $chars, " ", STR_PAD_LEFT), "w");
        print(" $p updated documents, $s skipped of " . fmt($limit, "i") . " documents updated");
        return self::CONVERT_SUCCESS; // 0 means success
    }

    #[Description("(namespaced_model) Initialize data in the database")]
    #[AcceptsFlags(
        "-F - Drops the collection without a prompt.",
        "-e - By default, this flag will try to replace `/` characters with `\`. Use this flag to prevent this behavior."
    )]
    function init(string $namespaced_model):int {
        $t = microtime(true);
        if(!$this->flags['e']) {
            $namespaced_model = str_replace("/",'\\', $namespaced_model);
        }
        // Check if namespace exists
        try {
            /** @var Model $model */
            $model = new $namespaced_model;
        } catch (Error $e) {
            $this->failed_instantiation($e);
            return self::CONVERT_FAIL; // 1 means failure

        } catch (Exception $e) {
            $this->failed_instantiation($e);
            return self::CONVERT_FAIL; // 1 means failure
        }

        if(!$this->flags['F']) {
            print("Do you want to ".fmt("drop the collection","e")." \"".fmt($model->getCollectionName(), "i")."\" before continuing? ");
            $dropBeforeInit = readline("(y)es, (n)o, (A)bort ");
        } else {
            $dropBeforeInit = 'y';
        }
        if($dropBeforeInit === "" || $dropBeforeInit === "a" || $dropBeforeInit === "A") {
            if($dropBeforeInit === "") say("You need to type either 'y' or 'n' to continue.",'i');
            say("No changes were made.");
            return self::CONVERT_FAIL; // 1 means failure
        }
        $dropBeforeInit = cli_to_bool($dropBeforeInit);
        $db = new DatabaseManagement();
        $totalCount = 0;
        $insertedCount = 0;
        $errCount = 0;
        /** @var array $item */
        foreach($db->initialize($model, $dropBeforeInit, $totalCount) as $item) {
            try {
                if($item instanceof $model !== true) {
                    $data = $item;
                    $item = new $model();
                    $item->bsonUnserialize($data);
                }
                $result = $model->insertOne($item);
                $insertedCount += $result->getInsertedCount();
            } catch(Exception|Error $e) {
                $errCount += 1;
            }
            printf("Inserted %s of %s documents%s\r", 
                fmt("$insertedCount", "s"),
                fmt("$totalCount", "i"),
                ($errCount == 0) ? "" : fmt(" $errCount errors", "e")
            );
        }
        print("\n");
        $delta = microtime(true) - $t;
        print(" > Completed in " . fmt(round($delta, 2), "i") . " seconds\n");
        return self::CONVERT_SUCCESS;
    }
}