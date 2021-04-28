<?php

require_once __CLI_ROOT__ . "/dependencies/new_project.php";

class Project{
    public $help_documentation = [
        'init' => [
            'description' => "Initialized a new project.",
            'context_required' => false
        ]
    ];

    function init(){
        $this->np = new NewProject();
        $this->np->__collect_new_project_settings();
    }

    
}