<?php
require "cobalt_cli_new_project.php";
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

function __exit(){
    print("Goodbye\n");
    exit;
}

$GLOBALS['cobalt_cli_commands'] = [
    'help' => [
        'description' => 'Print this message',
        'callback' => '__help',
    ],
    'setup' => [
        'description' => 'Create a new project',
        'callback' => '__new_project'
    ],
    // 'useradd' => [
    //     'description' => 'Add a new user',
    //     'callback' => '__create_new_user',
    // ],
    'update' => [
        'description' => 'Update your current project',
        'callback' => '__update'
    ],
    'exit' => [
        'description' => 'Exit the current program',
        'callback' => '__exit'
    ]
];