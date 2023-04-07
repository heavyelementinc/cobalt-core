<?php

/**
 * index.php - The Public Cobalt Entrypoint
 * 
 * Copyright 2023 - Heavy Element, Inc.
 * 
 * This file kicks off the entire bootstrapping process for Cobalt Engine. This
 * is the file that should be pointed to as the single entrypoint for all 
 * activity in a Cobalt app.
 * 
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 */

// The default environment should be found in __APP_ROOT__/core/
$environment = __DIR__ . "/../core/env.php";
if (!file_exists($environment)) {
    // If the file does not exist, then we look for a sibling directory.
    $environment = __DIR__ . "/../../cobalt-core/env.php";
    if( !file_exists($environment) ) {
        // We can't find a Cobalt directory. Let's die.
        $error = "Could not locate the environment";
        die($error);
    }
}

// Start the bootstrapping process.
require $environment;
