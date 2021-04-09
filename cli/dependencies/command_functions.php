<?php
require __CLI_ROOT__ . "/new_project.php";
function __help(){
    print("\n== COBALT HELP ==\n");
    foreach($GLOBALS['cobalt_cli_commands'] as $cmd => $items){
        print("$cmd\t\t$items[description]\n");
    }
    return "";
}

function __update(){
    return "Running update";
}


function __create_new_user(){
    print("Add a new user\n\n");
    $user = [];
    $user['uname'] = trim(readline("  Username > "));
    $user['pword'] = trim(readline("  Password > "));
    dbg($user);
    return "New user was not created";
}

function __new_project(){
    require_once __CLI_ROOT__ . "/new_project.php";
    $project = new NewProject();
    $project->__collect_new_project_settings();
    return "";
}

function __exit(){
    print("Goodbye\n");
    exit;
}

$GLOBALS['cobalt_cli_commands'] = [
    'help' => [
        'description' => 'Print this message',
        'callback' => '__help',
        'parse' => 0
    ],
    'init' => [
        'description' => 'Create a new project',
        'callback' => '__new_project',
        'parse' => 5
    ],
    // 'useradd' => [
    //     'description' => 'Add a new user',
    //     'callback' => '__create_new_user',
    // ],
    'update' => [
        'description' => 'Update your current project',
        'callback' => '__update',
        'parse' => 0
    ],
    'exit' => [
        'description' => 'Exit the current program',
        'callback' => '__exit',
        'parse' => 0
    ]
];