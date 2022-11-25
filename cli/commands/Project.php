<?php

require_once __CLI_ROOT__ . "/dependencies/new_project.php";

class Project {
    public $help_documentation = [
        'init' => [
            'description' => "Initializes a new project.",
            'context_required' => false
        ],
        'rebuild' => [
            'description' => "Schedule a rebuild of cached settings on next request.",
            'context_required' => true
        ],
        // 'upgrade' => [
        //     'description' => '["all"*|"app"|"env"] - Pull from [specified] Git remotes.',
        //     'context_required' => true
        // ]
    ];

    function init() {
        $this->np = new NewProject();
        $this->np->__collect_new_project_settings(func_get_args());
    }

    function rebuild() {
        $file = __APP_ROOT__ . "/ignored/config/settings.json";
        if (!file_exists($file)) {
            if (!mkdir(pathinfo($file, PATHINFO_DIRNAME), true)) throw new Exception("Unable to create APP_ROOT/ignored/config path");
        }
        touch($file);
        return "Next web request will regenerate settings cache.";
    }

    // /**
    //  * Upgrade the git repo to the latest version.
    //  * 
    //  * By doing this
    //  */

    // function upgrade($repo = "all", $force = "") {
    //     if (!exec("which git")) throw new Exception("You do not have git installed. Aborting.");

    //     $all_repos = ["env" => __ENV_ROOT__, "app" => __APP_ROOT__];
    //     $repos = $all_repos;

    //     if ($repo !== "all" && !key_exists($repo, $all_repos)) throw new Exception("Invalid option");
    //     else if ($repo !== "all") $repos = [$all_repos[$repo]];

    //     $git_pull = "git pull";
    //     if ($force === "force") $git_pull = "git reset --hard";

    //     $results = [];
    //     $errors = [];
    //     foreach ($repos as $name => $dir) {
    //         chdir($dir);
    //         $result = exec("git status | echo $?");
    //         if (!ctype_digit($result)) continue; // The DIR isn't a repo, skip it
    //         if ((int)$result < 0 || (int)$result > 128)  throw new Exception("Unexpected git repo status. Aborting");

    //         $results[$name] = exec("$git_pull | echo $?");
    //         if (!ctype_digit($results[$name])) $errors[$name] = $results[$name];
    //     }

    //     if (!empty($errors[$name])) throw new Exception(implode("\n", $errors) . "\n" . fmt("Errors occurred. Try cobalt project <app> force"));
    //     if (count($results) === 2) return "Projects updated";
    //     return "Environment updated.";
    // }

    function get_deps() {
    }
}
