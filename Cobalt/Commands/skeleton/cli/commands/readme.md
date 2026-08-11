# Create new commands
Cobalt Engine allows you to create new commands that can be used to manage your site from the Cobalt CLI. To do so, simply create a directory and PHP file in the same directory as this document.

`./Somecommand/Somecommand.php`

> Please note that the above directory and the filename (as well as the class name) must start with a capital letter and be followed by *all lowercase letters*.

Now, in this file, create a class without a namespace that exactly matches the filename (minus the file extension).

Then create a `public` property called `$help_documentation`. This should be an array where each key is the name of a command. This key should then be created as a method within this class.

The value of the key should be an array. The following keys are allowed:

* `description` - A 'help' description that explains what this command does.
* `context_required` - A boolean value that determines if this command must be called from `/path/to/app/cobalt.sh` (for true) or from `/path/to/cobalt/core.sh` (for false)
* 

```php
<?php

class Somecommand {
    public $help_documentation = [
        'update' => [
            'description' => "Update listen counts",
            'context_required' => true
        ]
    ];
    
    public function help_documentation($values) {
        /* Your functionality here */
    }
}
```

> Note that one class can contain multiple command methods. Simply add another key and define the appropriate key values, then add the matching method to your class.

# Running your command
To issue your newly made command, `cd` into your `__APP_ROOT__`, `su` to your webserver's user (on Ubuntu, it's `www-data`), and then run the following command:

`./cobalt.sh`

This will return a list of available commands:

```
[ help ]
   all            List ALL supported commands and subcommands.
   <command>      List every subcommand of given command where <command> is the name of any valid command.
   flags          List known flags.

[ project ]
   init           Initializes a new project.
   rebuild        Schedule a rebuild of cached settings on next request.
   upgrade        ["all"*|"app"|"env"] - Pull from [specified] Git remotes.

[ somecommand ]
   update         Update listen counts
```

If you see the command and method you created, you're ready to run it!

`./cobalt.sh somecommand update`

# Additional paramters
Commands may also accept additional paramters:

`.cobalt.sh somecommand update additional paramter`

Paramters supplied to your command method are passed to the command as individual arguments to the method. This is done by separating arguments based on spaces after the command method.