<?php

/**
 * @todo Do not display help items that require environment context if in pre-env
 */
class Upgrade {

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
    
    private $toUpgrade = "core";
    private $path = "-C " . __ENV_ROOT__;

    function app($branch = null) {
        $this->toUpgrade = "app";
        if(!$branch) $branch = $this->get_branch();
        $this->run_upgrade();
    }

    function cobalt($branch = null) {
        $this->toUpgrade = "core";
        if(!$branch) $branch = $this->get_branch();
        $this->run_upgrade();
    }

    function all($core_branch = null, $app_branch = null) {
        $this->app();
        $this->cobalt();
    }

    private function run_upgrade($bypassChecks = false) {
        if(empty(`which git`)) throw new Exception("FATAL ERROR: Git dependency not satisfied. Try installing Git and trying again.");
        if(!is_dir($this->path . ".git")) throw new Exception("No git repo detected.");
        
        
        // if(!$bypassChecks) readline("There are $changeCount changes to Cobalt Engine. Are you sure you want to continue?");
    }

    private function set_app($app) {
        if(!in_array($app,['core','app'])) throw new Exception("Valid options are 'core' and 'app'");
        if($this->toUpgrade === "core") $this->path = "-C " . __ENV_ROOT__;
        else $this->path = "-C " . __APP_ROOT__;
    }

    private function get_branch() {
        // if(!$app) $app = $this->toUpgrade;
        return `git ` . $this->path . ` rev-parse --abbrev-ref HEAD`;
    }

}