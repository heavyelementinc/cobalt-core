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
    ];

    private NewProject $np;

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

    function get_deps() {
    }

}
