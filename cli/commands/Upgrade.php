<?php

/**
 * @todo Do not display help items that require environment context if in pre-env
 */
class Upgrade{

    public $help_documentation = [
        'all' => [
            'description' => "Upgrades both Cobalt Engine and your application.",
            'context_required' => true,
        ],
        'core' => [
            'description' => "Upgrades only Cobalt Engine",
            'context_required' => false
        ],
        'app' => [
            'description' => "Upgrades only your application",
            'context_required' => true,
        ],
    ];
    
    function core($force = false) {
        if($force === "push") return $this->push(__ENV_ROOT__);
        return $this->upgrade(__ENV_ROOT__, $force);
    }

    function app($force = false) {
        if($force === "push") return $this->push(__APP_ROOT__);
        return $this->upgrade(__APP_ROOT__, $force);
    }

    private function upgrade($repo_path, $force = false) {
        $force = cli_to_bool($force);
        // Init our project
        $git = new CzProject\GitPhp\Git;
        $repo = $git->open($repo_path);

        // Get branch name
        $branch = $repo->getCurrentBranchName();

        // Check for updates. Tell user no changes are available.
        if($repo->hasChanges()) {
            if(!$force) return say("Your local repo has changes. You must specify 'true' as the first and only argument to overwrite these changes.");
            say("Local changes are being obliterated!", "i");
            return $repo->execute('reset', '--hard', $branch);
        }
        
        // Pull changes from repo
        $result = $repo->pull($branch,[]);
        return $result;
    }

    private function push($repo_path) {
        // if(app("debug")) return say("You seem to be running in production. Are you sure you want to push from production?",'i');
        // Init our project
        $git = new CzProject\GitPhp\Git;
        $repo = $git->open($repo_path);

        // Get branch name
        $branch = $repo->getCurrentBranchName();

        // adds all changes in repository
        $repo->addAllChanges();

        $repo->commit(readline("Commit >"));
        return $this->push($branch,[]);
    }

    function all($force = false) {
        $result = $this->core($force) . "/n";
        $result .= $this->app($force);
        return $result;
    }

}
