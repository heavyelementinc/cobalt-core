<?php

class NewProject{

    var $app = [
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

    var $new_app = [];

    function __construct(){
        
    }

    function __collect_new_project_settings(){
        print("Let's set up your new Cobalt App!\nType `!quit` at any point to abort without making any changes.\n");
        
        /** Let's start with a for loop. This will allow us to repeat a step if
         * there was an error by decrementing $i;
         */
        for($i = 0; $i <= count($this->app) - 1; $i++){
            $key = array_keys($this->app)[$i];
            $val = trim(readline($this->app[$key]['prompt'] . " "));
            if($val === "!quit") return;
            try{
                if(key_exists('validate',$this->app[$key]) && method_exists($this,$this->app[$key]['validate'])) $val = $this->{$this->app[$key]['validate']}($val,$key);
                else if (key_exists('validate',$this->app[$key]) && is_callable($this->app[$key]['validate'])) $val = $this->app[$key]['validate']($val,$key);
                $this->new_app[$key] = $val;
            } catch(Exception $e){
                print($e->getMessage()."\n\n");
                $i--;
            }
        }

        if(!$this->__np_confirm_creation()) return "You chose to not create this project. Aborting!";
        // $this->__np_apache_config($new_app);
        
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
        $dir_name = __CLI_ROOT__ . "/../../../$validate";
        $dir_exists = file_exists($dir_name);
        if($dir_exists) throw new Exception("A file or directory with the name '$validate' already exists.\nPlease choose another.");
        return $validate;
    }
    
    function __np_apache_config(){
        
    }

    function __np_confirm_creation(){
        print("Does this look correct?");
        print(implode("\n  ",$this->new_app));
        $correct = confirm_message("Does this look correct?","Y");
        return $correct;
    }
}