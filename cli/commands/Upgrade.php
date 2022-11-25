<?php

/**
 * @todo Do not display help items that require environment context if in pre-env
 */
class Upgrade{

    public $help_documentation = [
        'all' => [
            'description' => "['push' | 'force'] Upgrades both Cobalt Engine and your application.",
            'context_required' => true,
        ],
        'core' => [
            'description' => "['push' | 'force'] Upgrades only Cobalt Engine",
            'context_required' => false
        ],
        'app' => [
            'description' => "['push' | 'force'] Upgrades only your application",
            'context_required' => true,
        ],
        'branch' => [
            'description' => "['core' | 'app', branch_name] Switch to a new branch. If no branch specified, a branch listing occurs.",
            'context_required' => true,
        ]
    ];
    
    function core($force = false) {
        if($force === "push") return $this->push(__ENV_ROOT__);
        return $this->upgrade(__ENV_ROOT__, $force === "force");
    }

    function app($force = false) {
        if($force === "push") return $this->push(__APP_ROOT__);
        return $this->upgrade(__APP_ROOT__, $force === "force");
    }

    private function upgrade($repo_path, $force = false) {
        // Init our project
        $git = new CzProject\GitPhp\Git;
        $repo = $git->open($repo_path);

        // Get branch name
        $branch = $repo->getCurrentBranchName();
        $app = (__ENV_ROOT__ === $repo_path) ? "core" : "app";

        say("Upgrading $app from remote: $branch", 'i');
        // Check for updates. Tell user no changes are available.
        if($repo->hasChanges()) {
            if(!$force) return say("Your local repo has changes. You must specify 'true' as the first and only argument to overwrite these changes.");
            say("Local changes are being obliterated!", "i");
            return $repo->execute('reset', '--hard', $branch);
        }
        
        // Pull changes from repo
        $result = $repo->pull('origin',[]);
        return fmt("Upgraded '$app' from remote: $branch",'i');
    }

    private function push($repo_path) {
        // if(app("debug")) return say("You seem to be running in production. Are you sure you want to push from production?",'i');
        // Init our project
        $git = new CzProject\GitPhp\Git;
        $repo = $git->open($repo_path);

        // Get branch name
        $branch = $repo->getCurrentBranchName();
        $app = (__ENV_ROOT__ === $repo_path) ? "core" : "app";
        if($repo->hasChanges() === false) return say("No changes to $app: $branch.");
        say("Pushing '$app' changes to remote: $branch.", 'i');

        // adds all changes in repository
        $repo->addAllChanges();
        $commit_message = readline("Message > ");
        if(isset($commit_message[0]) && $commit_message[0] === "!") return say("Aborting", 'e');
        $repo->commit($commit_message);
        say("Pushing changes... this may take some time...");
        $repo->push('origin',[]);
        say("Pushed changes to '$app' repo's origin: $branch",'i');
        return "Success";
    }

    function all($force = false) {
        $result = $this->core($force);
        $result .= $this->app($force);
        return "Completed process.";
    }

    function branch($app, $switch = false) {
        $repo_path = ($app === "core") ? __ENV_ROOT__ : __APP_ROOT__;
        $app = (__ENV_ROOT__ === $repo_path) ? "core" : "app";

        $git = new CzProject\GitPhp\Git;
        $repo = $git->open($repo_path);

        if($switch === false) return json_encode($repo->getBranches(), JSON_PRETTY_PRINT);
        
    }

}
