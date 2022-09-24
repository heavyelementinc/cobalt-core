<?php

namespace Cobalt\Upgrade;

use CzProject\GitPhp\GitException;

class Manager {
    private $toUpgrade = "core";
    private $path = "-C " . __ENV_ROOT__;

    function __construct() {
        if(empty(`which git`)) throw new \Exception("FATAL ERROR: Git dependency not satisfied. Try installing Git and trying again.");
        try {
            $git = new \CzProject\GitPhp\Git;
        } catch (\Exception $e) {
            throw new \Exception("You must install composer dependencies before trying to upgrade Cobalt");
        }
    }

    public function get_collection_name() {
        return 'CobaltSettings';
    }

    function app($branch = null) {
        $this->set_app("app");
        if(!$branch) $branch = $this->get_branch();
        
        $this->run_upgrade();
    }

    function cobalt($branch = null) {
        $this->set_app("core");
        if(!$branch) $branch = $this->get_branch();
        $this->run_upgrade();
    }

    function all($core_branch = null, $app_branch = null) {
        $this->app();
        $this->cobalt();
    }

    private function run_upgrade($bypassChecks = false) {
        if(!is_dir($this->path . ".git")) throw new \Exception("No git repo detected.");
        $git = new \CzProject\GitPhp\Git;
        $git->open($this->path);
        if(!$git->hasChanges()) return "No changes for $this->toUpgrade";

        echo "There are upstream changes for $this->toUpgrade. Do you want to upgrade?";
        if(!in_array(readline('Answering "y" will overwrite any changes you\'ve made locally!'),['y','yes'])) return "ABORTING. No changes made.";
        
        try {
            return $git->pull();
        } catch (\Exception $e) {
            return $git->execute('reset', '--hard origin/' . $git->getCurrentBranchName());
        }
    }

    private function set_app($app) {
        if(!in_array($app,['core','app'])) throw new \Exception("Valid options are 'core' and 'app'");
        if($this->toUpgrade === "core") $this->path = "-C " . __ENV_ROOT__;
        else $this->path = "-C " . __APP_ROOT__;
    }

    private function get_branch() {
        // if(!$app) $app = $this->toUpgrade;
        $git = new \CzProject\GitPhp\Git;
        $git->open($this->path);
        return $git->getCurrentBranchName();
    }
}