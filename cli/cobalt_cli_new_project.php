<?php

function __new_project(){
    print("Let's set up your new Cobalt App!\nType `!quit` at any point to abort without making any changes.\n");
    $app = [
        'root' => [
            'prompt' => "Project directory (name only, not path)",
            'validate' => '__np_validate_directory',
            'value' => null,
            'execute' => '__np_create_directory',
        ],
        'app_name' => [
            'prompt' => "What's your app name?",
            'validate' => '__np_validate_cannot_be_blank',
            'value' => null,
            'execute' => '__np_write_app_settings',
        ],
        'database' => [
            'prompt' => "Provide a unique name for your database",
            'validate' => "__np_validate_cannot_be_blank",
            'value' => null,
        ],
        'Auth_enable_logins' => [
            'prompt' => "Enable user accounts? (Y/n)",
            'validate' => '__np_validate_enable_logins'
        ],
        'Auth_enable_root_group' => [
            'prompt' => 'Give admin user root permissions? (Y/n)',
            'validate' => '__np_validate_enable_logins'
        ]
    ];
    $new_app = [];
    for($i = 0; $i <= count($app) - 1; $i++){
        $key = array_keys($app)[$i];
        $val = trim(readline($app[$key]['prompt'] . " "));
        if($val === "!quit") return;
        try{
            if(key_exists('validate',$app[$key]) && is_callable($app[$key]['validate'])) $val = $app[$key]['validate']($val,$key);
        } catch(Exception $e){
            print($e->getMessage()."\n");
            $i--;
        }
        $new_app[$key] = $val;
    }
    // __np_apache_config($new_app);
    
}

function __np_validate_enable_logins($validate){
    return cli_to_bool($validate,true);
}

function __np_validate_cannot_be_blank($validate){
    if(!$validate) throw new Exception("Entry must not be blank");
    return $validate;
}

function __np_validate_directory($validate){
    if(!$validate) throw new Exception("Entry must not be blank");
    if(file_exists(__DIR__ . "/../../$validate")) throw new Exception("That project already exists!");
    return $validate;
}

function __np_apache_config($app){

}