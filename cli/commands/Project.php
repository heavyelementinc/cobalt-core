<?php

require_once __CLI_ROOT__ . "/dependencies/new_project.php";

class Project implements Command{
    public $help_documentation = [
        'init' => [
            'description' => "Initialized a new project."
        ]
    ];

    function init(){
        $this->np = new NewProject();
        $this->np->__collect_new_project_settings();
    }
}