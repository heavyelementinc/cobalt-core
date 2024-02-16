<?php

namespace Cobalt\Upgrade;

use CzProject\GitPhp\GitException;
use Symfony\Component\HttpFoundation\UrlHelper;

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

    function update_app($branch = null) {
        $this->set_app("app");
        if(!$branch) $branch = $this->get_branch();
        
        $this->run_upgrade();
    }

    function update_cobalt($branch = null) {
        $this->set_app("core");
        if(!$branch) $branch = $this->get_branch();
        $this->run_upgrade();
    }

    function update_all($app_branch = null, $core_branch = null) {
        $this->update_app($app_branch);
        $this->update_cobalt($core_branch);
    }

    function push_cobalt($branch = null) {
        $this->set_app("core");
        
    }

    private function run_upgrade($bypassChecks = false) {
        // if(!is_dir($this->path . "/.git")) throw new \Exception("No git repo detected.");
        $git = new \CzProject\GitPhp\Git;
        $repo = $git->open($this->path);
        
        $url = $this->getAuthenticatedURL();
        
        if(!$repo->hasChanges()) return "No changes for $this->toUpgrade";
        return;
        echo "There are upstream changes for $this->toUpgrade. Do you want to upgrade?";
        if(!in_array(readline('Answering "y" will overwrite any changes you\'ve made locally!'),['y','yes'])) return "ABORTING. No changes made.";

        try {
            return $repo->pull();
        } catch (\Exception $e) {
            return $repo->execute('reset', '--hard origin/' . $repo->getCurrentBranchName());
        }
    }

    private function getAuthenticatedURL() {
        $exec = trim(`git config --get remote.origin.url`);
        $parse = parse_url($exec);
        $token = new ;
        return $parse['scheme'] . "://" .  . $parse['host'] . $parse['path'];
    }

    private function set_app($app) {
        if(!in_array($app,['core','app'])) throw new \Exception("Valid options are 'core' and 'app'");
        if($app === "core") $this->path = __ENV_ROOT__;
        else $this->path = __APP_ROOT__;
    }

    private function get_branch() {
        // if(!$app) $app = $this->toUpgrade;
        $git = new \CzProject\GitPhp\Git;
        $repo = $git->open($this->path);
        return $repo->getCurrentBranchName();
    }
}
