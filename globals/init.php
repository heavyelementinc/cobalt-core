<?php

/**
 * init.php - The Cobalt Initial Configuration Routine
 * 
 * Copyright 2021 - Heavy Element, Inc
 * 
 * When invoked, this file will check the database to see if there is at least
 * one user set up.
 * 
 * If there are *no* users in the database, then it will either try to configure
 * a user from $init_file, OR it will add a route that is equivalent to the
 * current REQUEST_URI in order to capture all first-run setups.
 * 
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 */


/**
 * __cobalt_initialize accepts an array of values (either decoded with a uname,
 * pword, and email key) and will take appropriate action to either prompt the
 * user for more information if a key is missing or insert the first user
 * account into the database. 
 *
 * @param  array $values An array containing uname, pword, and email at a minimum
 * @return bool
 */
function __cobalt_initialize($values) {
    // We don't want to be passed anything but an array, so let's force an array.
    if (!is_array($values)) $values = [];

    // Check to see if we have at least one user account, if so, return.
    $collection = db_cursor('users');
    $result = $collection->count([]);
    if ($result !== 0) {
        __cobalt_initialize_set_init_file_complete();
        return;
    }

    // A list of fields that we require to be filled out to create a new user
    $required_fields = [
        "uname" => [
            "type" => "username",
            "label" => "Username"
        ],
        "pword" => [
            "type" => "password",
            "label" => "Password"
        ],
        "email" => [
            "type" => "email",
            "label" => "Email address"
        ],
    ];

    $ask_for = [];
    $included = [];

    // Loop through our required fields and generate inputs for those fields.
    foreach ($required_fields as $key => $field) {
        $input = "<fieldset><label>$field[label]</label><input type=\"$field[type]\" name=\"$key\" ";
        if (key_exists($key, $values)) {
            $input .= "value=\"$values[$key]\"";
            array_push($included, $key);
        }
        $input .= "></fieldset>";

        // Store the inputs we generated in an array
        array_push($ask_for, $input);
    }

    $result = false;
    try {
        // Check if the init file contains all the info we need to generate a user
        // account and, if so, generate it;
        if ($included === array_keys($required_fields)) $result = __cobalt_initialize_create_user($values);
        // Otherwise, prompt the user to create the account.
        else $result = __cobalt_initialize_routes($ask_for);
    } catch (Exception $e) {
        die($e->getMessage());
    }
    return $result;
}

/**
 * Prompt the user for missing information
 *
 * @param  mixed $prompts
 * @return void
 */
function __cobalt_initialize_routes($prompts) {
    // If we're in a web context, override it with the "init" context
    if ($GLOBALS['route_context'] === "web") $GLOBALS['route_context'] = "init";
    // Add the input fields we just generated to our variables
    add_vars(['prompts' => implode("", $prompts)]);
}

function __cobalt_initialize_create_user($root_user) {
    $root_user['groups'] = ["root"];

    // Let's create a new user
    $crud = new \Auth\UserCRUD();
    $result = $crud->createUser($root_user);
    $crud->updateOne(['_id' => $result['_id']], ['$set' => ['groups' => ['root']]]);
    // Redact the password field
    $root_user['pword'] = "###############";

    // Store an error message
    $err = "ERROR: Could not redact sensitive fields in config file! Please remove <code>__APP_ROOT__/ignored/init.json</code> from your app directory!";

    // If we fail to overwrite the contents of the init file, die with an error.
    if (!file_put_contents($GLOBALS['init_file'], json_encode([]))) {
        die($err);
    }

    __cobalt_initialize_set_init_file_complete();

    // Force a settings update on next load.
    touch(__APP_ROOT__ . "/private/config/settings.json");

    return true;
}

/**
 * __cobalt_initialize_set_init_file_complete
 *
 * @return void
 */
function __cobalt_initialize_set_init_file_complete() {
    $file = $GLOBALS['init_file'];
    // If the file doesn't exist, create it
    if (!file_exists($file)) touch($file);
    // If we fail to rename the init_file, die with an error.
    if (!rename($file, $file . ".set")) {
        die("Failed to initialize");
    }
}

__cobalt_initialize(get_json($init_file));
