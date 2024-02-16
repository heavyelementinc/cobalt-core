<?php

class Migration {

    const MIGRATIONS_DIRS = [
        __APP_ROOT__ . "/cli/migrations/",
        __ENV_ROOT__ . "/cli/migrations/",
    ];

    private $available_migrations = [];

    public $help_documentation = [
        'execute' => [
            'description' => "Executes a migration",
            'context_required' => true
        ],
        'list' => [
            'description' => "List migrations",
            'context_required' => true
        ]
    ];

    function execute($name = null) {
        if($name === null) {
            $this->list();
            $input = readline("Select migration to run by number:");
        } else {
            $this->get_index();
            foreach(self::MIGRATIONS_DIRS as $dir) {
                $input = array_search($dir . $name . ".php", $this->available_migrations);
                if($input !== false) break;
            }
            if($input === false) throw new \Exception("That migration does not exist");
        }
        if(filter_var($input, FILTER_VALIDATE_INT) === false) throw new \Exception("Invalid entry. Please enter a numerical value.");
        $number = (int)$input;
        if($number < 0 || $number >= count($this->available_migrations)) throw new \Exception("Outside of acceptable range");
        $filename = $this->available_migrations[$number];
        $migrationname = "\\".pathinfo($filename, PATHINFO_FILENAME);
        say("Executing `$migrationname` migration",'i');
        require $filename;
        $migration = new $migrationname();
        $migration->config();
        $migration->execute();
        $migration->printResults();
    }

    private function get_index() {
        $migrations = [];
        foreach(self::MIGRATIONS_DIRS as $dir) {
            if(!is_dir($dir)) continue;
            $scan_result = scandir($dir);
            foreach($scan_result as $file_handle) {
                if($file_handle[0] === ".") continue;
                $migrations[] = $dir . "$file_handle";
            }
        }
        $this->available_migrations = $migrations;
    }

    function list() {
        $this->get_index();
        $count = count($this->available_migrations);
        foreach($this->available_migrations as $index => $name) {
            print(fmt(str_pad($index, $count, " ", STR_PAD_LEFT), "i") . " " . pathinfo($name, PATHINFO_FILENAME) . "\n");
        }
    }

    private function run_migration($migration) {

    }
}
