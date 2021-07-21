<?php

/**
 * @todo Do not display help items that require environment context if in pre-env
 */
class Cron {

    public $help_documentation = [
        'list' => [
            'description' => "['all' | 'due'] List all scheduled crontask and delta to execution.",
            'context_required' => false,
        ],
        'exec' => [
            'description' => "Execute the Cron loop",
            'context_required' => false
        ]
    ];

    public function list($type = "due") {
        if (!in_array($type, ['due', 'all'])) $type = 'due';
        say("Showing " . fmt($type, 'i') . " tasks.");
        $cron = new \Cron\Run();
        // $tasks = 
    }

    public function exec() {
        $cron = new \Cron\Run();
        $cron->exec();
    }
}
