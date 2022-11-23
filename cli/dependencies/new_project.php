<?php

class NewProject{

    var $app = [
        'root' => [
            'prompt' => "Project directory (name only, not path):",
            'confirm' => 'Directory     ',
            'validate' => '__np_validate_directory',
            'value' => null,
            'key' => 'settings.json',
        ],
        'domain_name' => [
            'prompt' => "Domain name:",
            'confirm' => 'Domain name   ',
            'validate' => '__np_validate_cannot_be_blank',
            'value' => null,
            'key' => 'settings.json',
        ],
        'app_name' => [
            'prompt' => "What's your app's name?",
            'confirm' => 'App name      ',
            'validate' => '__np_validate_cannot_be_blank',
            'value' => null,
            'key' => 'settings.json',
        ],
        'database' => [
            'prompt' => "Provide a unique name for your database:",
            'confirm' => 'Database name ',
            'validate' => "__np_validate_cannot_be_blank",
            'value' => null,
            'key' => 'config.php',
        ],
        'username' => [
            'prompt' => "Database username (leave blank if none)",
            'confirm' => 'DB Username   ',
            'validate' => "__np_validate_may_be_blank",
            'value' => null,
            'key' => 'config.php',
        ],
        'password' => [
            'prompt' => "Database password (leave blank if none)",
            'confirm' => 'DB Password   ',
            'validate' => "__np_validate_may_be_blank",
            'value' => null,
            'key' => 'config.php',
        ]
        // 'Auth_enable_logins' => [
        //     'prompt' => "Enable user accounts? (Y/n)",
        //     'validate' => '__np_validate_enable_logins'
        // ],
        // 'Auth_enable_root_group' => [
        //     'prompt' => 'Give admin user root permissions? (Y/n)',
        //     'validate' => '__np_validate_enable_logins'
        // ]
    ];

    var $exe = [
        "create" => [
            'execute' => '__np_create_directory',
        ],
        "ignored" => [
            'execute' => '__np_create_ignored',
        ],
        "config" => [
            'execute' => '__np_db_config_file',
        ],
        "settings" => [
            'execute' => '__np_write_app_settings',
        ],
        "apache" => [
            'execute' => '__np_apache_config'
        ]
        // "database" => [
        //     'execute' => '__np_database_do_nothing',
        // ]
    ];

    var $complete = "Your new project has been created!\n";

    var $new_app = [];

    function __construct(){
        $this->app_root = __CLI_ROOT__ . "/../../";
    }

    function __collect_new_project_settings($arguments = []){

        say("Let's set up your new Cobalt App!","b");
        say("Type `!quit` at any point to abort without making any changes.","i");
        
        /** Let's start with a for loop. This will allow us to repeat a step if
         * there was an error by decrementing $i;
         */
        for($i = 0; $i <= count($this->app) - 1; $i++){

            $key = array_keys($this->app)[$i];
            $val = (isset($arguments[$i])) ? trim($arguments[$i]) : trim(readline($this->app[$key]['prompt'] . " "));
            if($val === "!quit") return; // Quit this process without making changes
            try{
                // Check if we're supposed to validate, then validate
                if(key_exists('validate',$this->app[$key]) && method_exists($this,$this->app[$key]['validate'])) $val = $this->{$this->app[$key]['validate']}($val,$key);
                else if (key_exists('validate',$this->app[$key]) && is_callable($this->app[$key]['validate'])) $val = $this->app[$key]['validate']($val,$key);
                // Set the value in the new_app array
                $this->new_app[$this->app[$key]['key']][$key] = $val;
            } catch(Exception $e){
                // Catch Exceptions
                say($e->getMessage()."\n","e");
                $i--;
            }
        }

        // Wait for the user to confirm the new application settings.
        if(!$this->__np_confirm_creation()) return "You chose to not create this project. Aborting!";

        // Loop through the exe array and execute the methods
        foreach($this->exe as $name => $exe){
            if(!isset($exe['execute'])) continue;

            // Check if there's a shared key the with the new app array and hand
            // it to the method we're executing
            $setting = [];
            if(isset($this->new_app[$name])) $setting = $this->new_app[$name];
            if($this->{$exe['execute']}($setting)) print(fmt("ok!\n","i"));
            else {
                print(fmt("error!","e"));
                exit;
            }
        }
        
        print($this->complete);
        print("You can now copy " . fmt($this->apache_config_file,"b") . " to your Apache configuration directory.");
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
        if(strpos($validate,"/")) throw new Exception("Can't contain slashes");
        $dir_name = realpath($this->app_root) . "/$validate";
        $dir_exists = file_exists($dir_name);
        if($dir_exists) throw new Exception("A file or directory with the name '$validate' already exists.\nPlease choose another.");
        $this->new_project_dir = $dir_name;
        return $dir_name;
    }
    
    function __np_validate_may_be_blank($validate) {
        return $validate;
    }

    var $apache_config_file = "config/apache/cobalt.conf";
    function __np_apache_config(){
        print(" -> Generating Apache VirtualHost configuration... ");
        $file = $this->new_project_dir . "/$this->apache_config_file";

        $conf = file_get_contents($file);
        $repl = [
            '{{domain_name}}' => $this->new_app['domain_name'],
            '{{app_name}}' => $this->new_app['app_name'],
            '{{database}}' => $this->new_app['database'],
            '{{root}}' => $this->new_app['root'],
        ];

        $config = str_replace(array_keys($repl),array_values($repl),$conf);
        if(file_put_contents($file,$config)) return true;
        else return false;
    }

    function __np_confirm_creation(){
        say("\nNew app summary:","b");
        foreach($this->app as $name => $values){
            print("  - $values[confirm] " . $this->new_app[$values['key']][$name] . "\n");
        }
        print("\n\n");
        $correct = confirm_message("Does this look correct?","Y");
        return $correct;
    }

    function __np_create_directory(){
        $dir = $this->new_project_dir;
        print(" -> Copying new project files to ".fmt($dir,"b")."... ");
        recursive_copy(__CLI_ROOT__ . "/app",$dir);
        return true;
    }

    function __np_create_ignored(){
        print(" -> Creating ignored files... ");
        file_put_contents($this->new_project_dir . "/.gitignore",".vscode/\ncache/\nignored/");
        @mkdir($this->new_project_dir . "/ignored");
        touch($this->new_project_dir . "/ignored/settings.json");
        return true;
    }

    function __np_write_app_settings(){
        print(" -> Writing settings file... ");
        $settings = $this->new_app['settings.json'];
        unset($settings['root']);
        $conf = $this->new_project_dir . "/config/settings.json";
        if(file_put_contents($conf,json_encode($settings))) return true;
        else return false;
    }

    function __np_database_do_nothing(){
        print(" -> We're not doing anything with the database yet... ");
        return true;
    }

    function __np_db_config_file(){
        print(" -> Writing config.php... ");
        $conf = $this->new_app['config.php'];
        require_once __CLI_ROOT__ . "/../globals/global_functions.php";
        set_up_db_config_file($conf['database'],$conf['username'],$conf['password'], "localhost", "27017", "false", "", "false", $this->new_project_dir . "/config/config.php");
    }
}

function recursive_copy($source, $target) {
    // If the source isn't a directory, copy the file
    if (!is_dir($source)) {
        copy($source, $target);
        return;
    }

    // If it's a directory, copy its contents.
    @mkdir($target);
    $dir = dir($source);
    $navFolders = array('.', '..');
    
    // Copy the files
    while (false !== ( $fileEntry = $dir->read() )) {
        // If the file is in the array we skip
        if (in_array($fileEntry, $navFolders) ) {
            continue;
        }

        // Copy the files
        $src = "$source/$fileEntry";
        $trg = "$target/$fileEntry";
        recursive_copy($src, $trg);
    }
    $dir->close();
}
