<?php

use Cobalt\DBManagement\Import;
use Cobalt\Model\Model;
use Drivers\DatabaseManagement;

/**
 * The `database` command offers a CLI interface for importing and exporting the database.
 * @package cli_command
 */
class Db
{
    public $help_documentation = [
        'export' => [
            'description' => "[filename] Export a database backup. Reads --export= flag (comma-delimited list)",
            'context_required' => true
        ],
        'import' => [
            'description' => "filename Import a database export",
            'context_required' => true,
        ],
        'migrate' => [
            'description' => "(namespaced_model, [\"force\"]) Migrate all documents in a collection to a new model",
        ],
        'init' => [
            'description' => "(namespaced_model) Initialize data in the database"
        ]
    ];

    function export($filename = null)
    {
        $db = new DatabaseManagement();
        $db->export($filename, false, true, true, [], $GLOBALS['export_collections'] ?? null);
    }

    function import($filename)
    {
        $db = new Import();
        $db->import($filename, true);
    }


    function migrate(string $namespaced_model, string $skip = "") {
        $t = microtime(true);
        // Check if namespace exists
        try {
            /** @var Model $model */
            $model = new $namespaced_model;
        } catch (Error $e) {
            return $this->failed_instantiation($e);
        } catch (Exception $e) {
            return $this->failed_instantiation($e);
        }

        // Load and verify that the namespace is a Model
        if ($model instanceof Model === false) {
            // If it's *not* a model, fail gracefully
            return say("$namespaced_model is not an instance of Model", 'e');
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
            return say($e->getMessage(), 'e');
        }
        $delta = microtime(true) - $t;
        print(" > Completed in " . fmt(round($delta, 2), "i") . " seconds\n");
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

    function init(string $namespaced_model) {
        $t = microtime(true);
        // Check if namespace exists
        try {
            /** @var Model $model */
            $model = new $namespaced_model;
        } catch (Error $e) {
            return $this->failed_instantiation($e);
        } catch (Exception $e) {
            return $this->failed_instantiation($e);
        }

        $dropBeforeInit = cli_to_bool(readline("Do you want to drop the ".$model->getCollectionName()." collection before continuing? (y/N)"));
        $db = new DatabaseManagement();
        $insertedCount = 0;
        /** @var array $item */
        foreach($db->initialize($model, $dropBeforeInit) as $item) {
            $chars = strlen($item['totalDocuments']);
            $ic = $item['insertOneResult']->getInsertedCount();
            $insertedCount += $ic;
            print("\r");
            $p = fmt(str_pad($insertedCount, $chars, " ", STR_PAD_LEFT), "s");
            print(" $p " . fmt($item['totalDocuments'], "i") . " documents created");
        }
        print("\n");
        $delta = microtime(true) - $t;
        print(" > Completed in " . fmt(round($delta, 2), "i") . " seconds\n");
    }
}
