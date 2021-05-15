<?php

/**
 * index.php - The Public Cobalt Entrypoint
 * 
 * Copyright 2021 - Heavy Element, Inc.
 * 
 * This file kicks off the entire bootstrapping process for Cobalt Engine. This
 * is the file that should be pointed to as the single entrypoint for all 
 * activity in a Cobalt app.
 * 
 * @license cobalt-core/license
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 */

$environment = "../../cobalt-core/env.php";
if (!file_exists($environment)) {
    $error = "Could not locate the environment";
    echo $error;
    throw new Exception($error);
}
require $environment;
