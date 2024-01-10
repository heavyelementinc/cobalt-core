<?php

use Drivers\DatabaseManagement;

class Database {
    public $help_documentation = [
        'export' => [
            'description' => "[filename] Export a database backup. Reads --export= flag (comma-delimited list)",
            'context_required' => true
        ],
        'import' => [
            'description' => "filename Import a database export"
        ]
    ];

    function export($filename = null) {
        $db = new DatabaseManagement();
        $db->export($filename, true, true, [], $GLOBALS['export_collections'] ?? null);
    }

    function import($filename) {
        $db = new DatabaseManagement();
        $db->import($filename, true);
    }
}
