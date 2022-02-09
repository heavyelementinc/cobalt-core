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

    public function list($type = "all") {
        if (!in_array($type, ['due', 'all'])) $type = 'all';
        say("Showing " . fmt($type, 'i') . " tasks.");
        $cron = new \Cron\Run();
        $tasks = $cron->get_tasks($type);

        $t = new \Render\CLITable();
        $t->head([
            'name' => ['title' => "Task Name"],
            'result' => ['title' => 'Last Run Result', 'max' => 50],
            'last_ran' => ['title' => "Last Run"],
            'microseconds' => ['title' => 'Last execution time', 'padding' => STR_PAD_BOTH],
        ]);
        foreach ($tasks as $task) {
            $task = $task;
            $stats = $cron->task_stats($task['name'], 'relative');
            $t->row([
                'name' => $task['name'],
                'result' => $stats['result'],
                'last_ran' => $stats['last_run'],
                'microseconds' => $stats['microseconds']
            ]);
        }
        $t->render();
    }

    public function exec() {
        $cron = new \Cron\Run();
        $cron->exec();
    }
}
