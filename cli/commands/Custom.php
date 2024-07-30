<?php

use Cobalt\Customization\CustomizationManager;
use Cobalt\Extensions\Extensions;
use MongoDB\BSON\ObjectId;

/**
 * The `database` command offers a CLI interface for importing and exporting the database.
 * @package cli_command
 */
class Custom {

    public $help_documentation = [
        'import' => [
            'description' => "Loads config/customizations.php and imports values that do not exist",
            'context_required' => true
        ],
        'reset' => [
            'description' => "Overrides all curretly set values with definitions in customizations file",
            'context_required' => true
        ]
    ];

    function import() {
        $manager = new CustomizationManager();
        $manager->import(false);
    }

    function reset() {
        $manager = new CustomizationManager();
        $manager->import(true);
    }
}